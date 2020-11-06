<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

if ($trava_cliente_admin) {
	$admin_privilegios="call_center";
	$layout_menu = "callcenter";
}
else {
	$admin_privilegios="financeiro,gerencia,call_center";
	$layout_menu = "callcenter";
}
include 'autentica_admin.php';

include 'funcoes.php';
include "monitora.php";

$title = "RELATÓRIO DOS ATENDIMENTOS POR POSTO";

include 'cabecalho.php';

include "javascript_calendario.php";?>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});

	function habilitaPosto() {
		
		if (document.getElementById('ativo').checked) {
			document.getElementById('codigo_posto').disabled    = false;
			document.getElementById('descricao_posto').disabled = false;
		} else {
			document.getElementById('codigo_posto').disabled    = true;
			document.getElementById('descricao_posto').disabled = true;
		}

	}
</script>

<script language="JavaScript" src="js/qTip.js" type="text/JavaScript"></script>
<style type="text/css">
	.Titulo {
		text-align: center;
		font-family: Arial;
		font-size: 11px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #485989;
	}
	.Erro {
		text-align: center;
		font-family: Arial;
		font-size: 12px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #FF0000;
	}
	.Conteudo {
		text-align: left;
		font-family: Arial;
		font-size: 11px;
		font-weight: normal;
	}
	.Conteudo2 {
		text-align: center;
		font-family: Arial;
		font-size: 11px;
		font-weight: normal;
	}
	.Caixa{
		BORDER-RIGHT: #6699CC 1px solid;
		BORDER-TOP: #6699CC 1px solid;
		FONT: 8pt Arial ;
		BORDER-LEFT: #6699CC 1px solid;
		BORDER-BOTTOM: #6699CC 1px solid;
		BACKGROUND-COLOR: #FFFFFF
	}
	#tooltip{
		background: #5D92B1;
		border:2px solid #000;
		display:none;
		padding: 2px 4px;
		color: #FFFFFF;
		text-align: center;
		font-family: Arial;
		font-size: 11px;
		font-weight: normal;
		width: 250px;
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
	text-align:left;
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
</style>
<script language="JavaScript" type="text/javascript">
	window.onload = function(){
		tooltip.init();
	}
</script>

<? include "javascript_pesquisas.php" ?>
<script>
	function fnc_pesquisa_posto2 (campo, campo2, tipo) {
			
		if (tipo == "codigo" ) {
			var xcampo = campo;
		}
		if (tipo == "nome" ) {
			var xcampo = campo2;
		}
		if (xcampo.value != "") {
			var url = "";
			url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
			janela.codigo  = campo;
			janela.nome    = campo2;
			janela.focus();
		}
		else{
			alert("Informe toda ou parte da informação para realizar a pesquisa!");
		}
	}
</script>

<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type="text/javascript" src="js/jquery.bgiframe.min.js"></script>
<script type="text/javascript" src="js/jquery.autocomplete.js"></script>
<script type="text/javascript" src="js/jquery.dimensions.js"></script>
<script language="javascript">
	var hora = new Date();
	var engana = hora.getTime();
	$().ready(function() {
		$('#codigo_posto').autocomplete("autocomplete_posto_ajax.php?engana=" + engana,{
			minChars: 3,
			delay: 150,
			width: 450,
			scroll: true,
			scrollHeight: 200,
			matchContains: false,
			highlightItem: false,
			formatItem: function (row)   {return row[0]+"&nbsp;-&nbsp;"+row[1]},
			formatResult: function(row)  {return row[0];}
		});
		$('#codigo_posto').result(function(event, data, formatted) {
			$("#codigo_posto").val(data[0]);
			$("#descricao_posto").val(data[1]);
		});
		$('#descricao_posto').autocomplete("autocomplete_posto_ajax.php?engana=" + engana,{
			minChars: 3,
			delay: 150,
			width: 450,
			scroll: true,
			scrollHeight: 200,
			matchContains: false,
			highlightItem: false,
			formatItem: function (row)   {return row[0]+"&nbsp;-&nbsp;"+row[1]},
			formatResult: function(row)  {return row[0];}
		});
		$('#descricao_posto').result(function(event, data, formatted) {
			$("#codigo_posto").val(data[0]);
			$("#descricao_posto").val(data[1]);
		});
	})
</script>
<?

if($btn_acao=="Consultar"){
	flush();
	if (strlen($_GET['data_inicial']) > 0)
		$data_inicial = $_GET['data_inicial'];
	else
		$data_inicial = $_POST['data_inicial'];

	if (strlen($_GET['data_final']) > 0)
		$data_final   = $_GET['data_final'];
	else
		$data_final   = $_POST['data_final'];
	if(strlen($codigo_posto)>0){
		$sql = "SELECT posto
				FROM tbl_posto_fabrica
				WHERE codigo_posto = '$codigo_posto' 
				AND fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
		
		if (pg_num_rows($res)>0) {
			$posto = pg_result($res,0,'posto');
		}else {
			$msg_erro = 'Posto nao encontrado';  
		}
	}

	if (strlen($_GET['estado']) > 0)
		$estado = $_GET['estado'];
	else
		$estado = $_POST['estado'];

	if (strlen($_GET['linha']) > 0)
		$linha = $_GET['linha'];
	else
		$linha = $_POST['linha'];

	if (strlen($_GET['familia']) > 0)
		$familia = $_GET['familia'];
	else
		$familia = $_POST['familia'];
	
	//HD 247592
	$cliente_admin = $_GET["cliente_admin"];
	if (strlen($cliente_admin) == 0) {
		$cliente_admin = $_POST["cliente_admin"];
	}

	if (strlen($cliente_admin) > 0) {
		$cliente_admin = intval($cliente_admin);
		$sql = "
		SELECT
		cliente_admin

		FROM
		tbl_cliente_admin

		WHERE
		cliente_admin=$cliente_admin
		AND fabrica=$login_fabrica
		";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) == 0) {
			$msg_erro = "Cliente Admin não encontrado";
		}
	}
	//HD 247592: FIM

	if((strlen($data_inicial) > 0 AND $data_inicial!="dd/mm/aaaa") AND (strlen($data_final)>0 AND $data_final!="dd/mm/aaaa")){
		$ver_data = "Select case when '$data_inicial' < '$data_final' then true else false end";
		$res = @pg_exec($con,$ver_data);
		$resposta = pg_result($res,0,0);
		if ($resposta == 'f'){
			$msg_erro = "A DATA INICIAL NÃO PODE SER SUPERIOR A DATA FINAL";
		}
		if (strlen($msg_erro) == 0) {
			$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$msg_erro = pg_errormessage ($con) ;
			}
			if (strlen($msg_erro) == 0)
				$aux_data_inicial = @pg_result ($fnc,0,0);
		}
		if (strlen($erro) == 0) {
			if (strlen($msg_erro) == 0) {
				$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
					if (strlen ( pg_errormessage ($con) ) > 0) {
					$erro = pg_errormessage ($con) ;
				}
				if (strlen($msg_erro) == 0)
					$aux_data_final = @pg_result ($fnc,0,0);
			}
		}

		if(strlen($msg_erro)==0){
			if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -1 month')) {
				$msg_erro = 'O intervalo entre as datas não pode ser maior que 1 mês.';
			}
		 }
	}else{
		$msg_erro = "ENTRE COM O PERÍODO PARA FILTRAGEM";
	}
	if ($ativo == 't') {
		if((strlen($codigo_posto) == 0) OR (strlen($descricao_posto) == 0)){
			$msg_erro = "ENTRE COM O POSTO PARA FILTRAGEM";
		}
	}else{
		if (strlen($estado) == 0 && strlen($cliente_admin) == 0){
			//$msg_erro = "ENTRE COM O ESTADO PARA FILTRAGEM";
		}
	}
}

if(strlen($msg_erro)>0){
	echo "<table width='700' border='0' cellpadding='5' cellspacing='1' align='center'>";
		echo "<tr>";
			echo "<td class='msg_erro'>$msg_erro</td>";
		echo "</tr>";
	echo "</table>";
}
?>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'>
	
	<table width='700' class='formulario' border='0' cellpadding="0" cellspacing="0" align='center'>
		<tr class='titulo_tabela'>
			<td colspan="4">
				Parâmetros de Pesquisa
			</td>
		</tr>
		<tr>
			<td width="50">&nbsp;</td>
			<td>Data Inicial</td>
			<td>Data Final</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="50">&nbsp;</td>
			<td>
				<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value= "<?=$data_inicial?>" >
			</td>
			<td>
				<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<?=$data_final?>" >
			</td>
			<td>&nbsp;</td>
		</tr>	
		<tr>
			<td width="50">&nbsp;</td>
			<td>Código do Posto</td>
			<td>Descrição do Posto</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="50">&nbsp;</td>
				<!--<br />
					<div style="float:left; width:20px;padding-left:40px">
						<span style="margin-right: 20px">Ativo</span>
						<br />
						<input type="checkbox" name="ativo" id="ativo" value='t' onclick="habilitaPosto()" />
					</div>-->
			<td>
				<input class="frm" type="text" name="codigo_posto" id="codigo_posto" size="15" value="<? echo $codigo_posto ?>"  />&nbsp;
				<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo_posto')" >
			</td>
			
			<td>
				<input class="frm" type="text" name="descricao_posto" id="descricao_posto" size="32" value="<? echo $descricao_posto ?>"  />&nbsp;
				<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'posto_nome')" style="cursor:pointer;" >
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="50">&nbsp;</td>
			<td>Estado do Posto Autorizado</td>
			<td>Linha</td>
			<td>Família</td>
		</tr>
		<tr>
			<td width="50">&nbsp;</td>
			<td>
				<select name="estado" class='frm'>
					<option value=""   <? if (strlen($estado) == 0) echo " selected "; ?>>TODOS OS ESTADOS</option>
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
			<td>
				
				<?
					echo "<select name='linha' size='1' class='frm' style='width:95px'>";
					echo "<option value=''></option>";
					$sql = "SELECT linha, nome from tbl_linha where fabrica = $login_fabrica and ativo = true order by nome";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res)>0){
						for($i=0;pg_num_rows($res)>$i;$i++){
							$xlinha = pg_fetch_result($res,$i,linha);
							$xnome = pg_fetch_result($res,$i,nome); ?>
							<option value="<?echo $xlinha;?>" <? if ($xlinha == $linha) echo " selected "; ?>> <?echo $xnome;?></option><?
						}
					}
					echo "</SELECT>";
				?>
			</td>
			<td>
				
				<?
					echo "<select name='familia' size='1' class='frm' style='width:95px'>";
					echo "<option value=''></option>";
					$sql = "SELECT familia, descricao from tbl_familia where fabrica = $login_fabrica and ativo = true order by descricao";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res)>0){
						for($i=0;pg_num_rows($res)>$i;$i++){
							$xfamilia = pg_fetch_result($res,$i,familia);
							$xdescricao = pg_fetch_result($res,$i,descricao); ?>
							<option value="<?echo $xfamilia;?>" <? if ($xfamilia == $familia) echo " selected "; ?>> <?echo $xdescricao;?></option> <?
						}
					}
					echo "</SELECT>";
				?>
			</td>
				
		</tr>
		<!-- HD 247592: Acrescentar filtro para cliente admin -->
		<?
		//A variável $trava_cliente_admin é definida na pasta ../admin_cliente/relatorio_callcenter_atendimento.php, este programa dá um include no programa da pasta admin, definindo esta variável para trava
		if ((strlen($trava_cliente_admin) == 0) && ($login_fabrica == 30 || $login_fabrica == 52 || $login_fabrica == 85)) {
		?>
		<tr>
			<td width="50">&nbsp;</td>
			<td colspan="3">Cliente Admin</td>
		</tr>
		<tr>
			<td width="50">&nbsp;</td>
			<td colspan="3">
				<select name="cliente_admin" class='frm'>
					<option value="">TODOS</option>
					<?php
					$sql = "
					SELECT
					cliente_admin,
					nome

					FROM
					tbl_cliente_admin

					WHERE
					fabrica=$login_fabrica

					ORDER BY
					nome
					";
					$res = pg_query($con, $sql);

					$n = pg_num_rows($res);

					for($i = 0; $i < $n; $i++) {
						$_cliente_admin = pg_result($res, $i, cliente_admin);
						$_nome = pg_result($res, $i, nome);

						if ($cliente_admin == $_cliente_admin) {
							$selected = "selected";
						}
						else {
							$selected = "";
						}

						echo "<option value='$_cliente_admin' $selected>$_nome</option>";
					}
					?>
				</select>
			</td>
		</tr>
		<?
		}
		?>
		<!-- HD FIM: Acrescentar filtro para cliente admin -->
		<tr>
			<td colspan="4">
				<center>
					<br>
					<input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onclick="document.frm_relatorio.btn_acao.value='Consultar'; document.frm_relatorio.submit();"  alt="Preencha as opções e clique aqui para pesquisar">
					<input type='hidden' name='btn_acao' value='<?=$acao?>'>
				</center>
			</td>
		</tr>
	</table>
	<? if($btn_acao=="Consultar" AND strlen($msg_erro) == 0){
		
		if (strlen($estado) > 0){
			$cond1 = "AND tbl_posto_fabrica.contato_estado = '$estado'";
		}

		if (strlen($linha) > 0){
			$cond2 = "AND tbl_produto.linha   = $linha";
		}

		if (strlen($familia) > 0){
			$cond3 = "AND tbl_produto.familia  = $familia";
		}

		if (strlen($posto) > 0){
			$cond4 = "AND tbl_os.posto = $posto";
		}

		if ($data_inicial) {
			$data_inicial = formata_data($data_inicial);
		}
		 
		 if ($data_final) {
			$data_final = formata_data($data_final);
		}

		//HD 247592
		if (strlen($cliente_admin) > 0) {
			$cond6 = "AND tbl_os.cliente_admin=$cliente_admin";
		}
		
		//A variável $trava_cliente_admin é definida na pasta ../admin_cliente/relatorio_callcenter_atendimento.php, este programa dá um include no programa da pasta admin, definindo esta variável para trava
		if (strlen($trava_cliente_admin) > 0) {
			$cond6 = "AND tbl_os.cliente_admin=$trava_cliente_admin";
		}
		//HD 247592: FIM

		/* $sql = "SELECT DISTINCT
					tbl_os.os                                     AS os,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')    AS data_abertura,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY')  AS data_fechamento,
					tbl_os.revenda_nome                           AS revenda_nome,
					tbl_posto_fabrica.codigo_posto                AS posto_codigo,
					tbl_posto.nome                                AS posto_nome,
					tbl_os.revenda_cnpj                           AS revenda_cnpj,
					tbl_os.consumidor_nome                        AS consumidor_nome,
					tbl_os.revenda_nome                           AS revenda_nome,
					tbl_cidade.estado                             AS revenda_estado,
					tbl_os.consumidor_fone                        AS consumidor_fone,
					tbl_os.consumidor_cidade                      AS consumidor_cidade,
					tbl_os.consumidor_estado                      AS consumidor_estado,
					tbl_produto.descricao                         AS descricao_produto,
					tbl_defeito_constatado.descricao              AS defeito_constatado,
					tbl_defeito_constatado_grupo.descricao        AS defeito_constatado_grupo
				FROM tbl_os
					JOIN tbl_produto                        ON tbl_produto.produto                                      = tbl_os.produto
					JOIN tbl_defeito_constatado             ON tbl_defeito_constatado.defeito_constatado                = tbl_os.defeito_constatado
					LEFT JOIN tbl_defeito_reclamado         ON tbl_defeito_reclamado.defeito_reclamado                  = tbl_os.defeito_reclamado
					LEFT JOIN tbl_hd_chamado_extra          ON tbl_hd_chamado_extra.os                                  = tbl_os.os
					LEFT JOIN tbl_revenda                   ON tbl_revenda.cnpj                                         = tbl_os.revenda_cnpj
					LEFT JOIN tbl_cidade                    ON tbl_cidade.cidade                                        = tbl_revenda.cidade
					LEFT JOIN tbl_posto                     ON tbl_posto.posto                                          = tbl_os.posto
					LEFT JOIN tbl_posto_fabrica             ON tbl_posto_fabrica.posto                                  = tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica
					LEFT JOIN tbl_defeito_constatado_grupo  ON tbl_defeito_constatado_grupo.defeito_constatado_grupo    = tbl_os.defeito_constatado_grupo
					WHERE tbl_os.fabrica = $login_fabrica
					 $cond1 $cond2 $cond3 $cond4 $cond5
					AND tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final'";*/
		//$res = pg_exec($con,$sql);
		//echo nl2br($sql);
		
			$sql = "SELECT DISTINCT
				tbl_hd_chamado_item.hd_chamado,
				tbl_os.os AS os,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
				tbl_os.revenda_nome AS revenda_nome,
				tbl_posto_fabrica.codigo_posto AS posto_codigo,
				tbl_posto.nome AS posto_nome,
				tbl_os.revenda_cnpj AS revenda_cnpj,
				tbl_os.consumidor_nome AS consumidor_nome,
				tbl_os.revenda_nome AS revenda_nome,
				cidadeB.estado,
				cidadeA.estado AS revenda_estado,
				tbl_os.consumidor_fone AS consumidor_fone,
				tbl_os.consumidor_cidade AS consumidor_cidade,
				tbl_os.consumidor_estado AS consumidor_estado,
				tbl_produto.descricao AS descricao_produto,
				tbl_defeito_constatado.descricao AS defeito_constatado,
				tbl_defeito_constatado_grupo.descricao AS defeito_constatado_grupo
				FROM tbl_os
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
				JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado
				JOIN tbl_hd_chamado_item      ON tbl_hd_chamado_item.os = tbl_os.os
				JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado_extra.hd_chamado and tbl_hd_chamado_item.os is not null
				LEFT JOIN tbl_revenda ON tbl_revenda.cnpj = tbl_os.revenda_cnpj
				LEFT JOIN tbl_cidade cidadeA ON cidadeA.cidade = tbl_revenda.cidade
				LEFT JOIN tbl_cidade cidadeB ON cidadeB.cidade = tbl_hd_chamado_extra.cidade
				LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_os.posto
				LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto and tbl_posto_fabrica.fabrica = $login_fabrica
				LEFT JOIN tbl_defeito_constatado_grupo ON tbl_defeito_constatado_grupo.defeito_constatado_grupo = tbl_os.defeito_constatado_grupo
				WHERE tbl_hd_chamado_item.os is not null AND tbl_os.fabrica = $login_fabrica
					 $cond1 $cond2 $cond3 $cond4 $cond5 $cond6
				AND tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final'";

#		echo nl2br($sql);
#		exit;
		$res = pg_exec($con,$sql);
		if($login_fabrica == 52) {
			echo `rm /tmp/assist/relatorio-callcenter-atendimento-$login_fabrica.xls`;		

			$fp = fopen ("/tmp/assist/relatorio-callcenter-atendimento-$login_fabrica.html","w");
			$crlf   = "\r\n";
			$f_header = "<html>\n".
						"<head>\n".
						"	<title>RELATÓRIO DE CALLCENTER - $data_xls</title>\n".
						"	<meta name='Author' content='TELECONTROL NETWORKING LTDA'>\n".
						"</head>\n".
						"<body>\n";
			fputs ($fp,$f_header);
		}

		if (pg_numrows($res) > 0) {
			echo "<br>";
			$excel =  "<table border='0' cellpadding='2' cellspacing='1' class='tabela' align='center' width='850'>";
				$excel .= "<tr class='titulo_coluna' height='25' >";
					// 1 coluna (Nº da ORDEM DE SERVIÇO)
					$excel .= "<td width='80'>O.S.</td>";
					// 2 coluna (Data de Abertura)
					$excel .= "<td width='80'>Abertura</td>";
					// 3 coluna (Data de Fechamento)
					$excel .= "<td width='80'>Fechamento</td>";
					// 4 coluna (Revenda)
					$excel .= "<td width='80'>Cliente Fricon</td>";
					// 5 coluna (Estado da Revenda)
					$excel .= "<td width='80'>UF</td>";
					// 6 coluna (Posto)
					$excel .= "<td width='80'>Posto</td>";
					// 7 coluna (Consumidor)
					$excel .= "<td width='80'>Consumidor</td>";
					// 8 coluna (Cidade)
					$excel .= "<td width='80'>Cidade</td>";
					// 9 coluna (UF.) 
					$excel .= "<td width='80'>UF.</td>";
					//10 coluna (Fone)
					$excel .= "<td width='80'>Fone</td>";
					//11 coluna (Produto)
					$excel .= "<td width='80'>Produto</td>";
					//12 coluna (Grupo de Defeito)
					$excel .= "<td width='80'>Grupo de Defeito</td>";
					//13 coluna (Defeito Constatado)
					$excel .= "<td width='80'>Defeito Constatado</td>";
				$excel .= "</tr>";

				echo $excel;
				
				if($login_fabrica == 52) {
					fputs($fp,$excel);
				}
				
				$excel = "";
				for ($i=0; $i<pg_numrows($res); $i++){
					$os                 = trim(@pg_result($res,$i,os));
					$data_abertura      = trim(@pg_result($res,$i,data_abertura));
					$data_fechamento    = trim(@pg_result($res,$i,data_fechamento));
					$revenda_nome       = trim(@pg_result($res,$i,revenda_nome));
					$revenda_estado     = trim(@pg_result($res,$i,revenda_estado));
					$posto_codigo       = trim(@pg_result($res,$i,posto_codigo));
					$posto_nome         = trim(@pg_result($res,$i,posto_nome));
					$consumidor_nome    = trim(@pg_result($res,$i,consumidor_nome));
					$consumidor_cidade  = trim(@pg_result($res,$i,consumidor_cidade));
					$consumidor_uf      = trim(@pg_result($res,$i,consumidor_estado));
					$consumidor_fone    = trim(@pg_result($res,$i,consumidor_fone));
					$produto            = trim(@pg_result($res,$i,descricao_produto));
					$defeito_constatado  = trim(@pg_result($res,$i,defeito_constatado));
					$defeito_constatado_grupo = trim(@pg_result($res,$i,defeito_constatado_grupo));
					if($cor=="#F1F4FA")
						$cor = '#F7F5F0';
					else
						$cor = '#F1F4FA';
					$excel .= "<tr bgcolor='$cor'>";
						// 1 coluna (Nº da ORDEM DE SERVIÇO)
						$excel .= "<td width='80' nowrap>$os</td>";
						// 2 coluna (Data de Abertura)
						$excel .= "<td width='80' nowrap>$data_abertura</td>";
						// 3 coluna (Data de Fechamento)
						$excel .= "<td width='80' nowrap>$data_fechamento</td>";
						// 4 coluna (Atendente)
						$excel .= "<td width='80' nowrap>$revenda_nome</td>";
						// 5 coluna (Estado da Revenda)
						$excel .= "<td width='80' nowrap>$revenda_estado</td>";
						// 6 coluna (Posto)
						$excel .= "<td width='80' nowrap>$posto_codigo  - $posto_nome</td>";
						// 7 coluna (Consumidor)
						$excel .= "<td width='80' nowrap>$consumidor_nome</td>";
						// 8 coluna (Cidade)
						$excel .= "<td width='80' nowrap>$consumidor_cidade</td>";
						// 9 coluna (UF.) 
						$excel .= "<td width='80' nowrap>$consumidor_uf</td>";
						//10 coluna (Fone)
						$excel .= "<td width='80' nowrap>$consumidor_fone</td>";
						//11 coluna (Produto)
						$excel .= "<td width='80' nowrap>$produto</td>";
						//12 coluna (Grupo de Defeito)
						$excel .= "<td width='80' nowrap>$defeito_constatado_grupo</td>";
						//13 coluna (Defeito Constatado)
						$excel .= "<td width='80' nowrap>$defeito_constatado</td>";
					$excel .= "</tr>";
				}
				$excel .="<tr><td colspan='13' align='left'>Número de registros $i</td> </tr>";
			$excel .= "</table>";
			
			echo $excel;
			if($login_fabrica == 52) {
				fputs($fp,$excel);
				$data_xls = date("Y-m-d_H-i-s");
				if (strlen($trava_cliente_admin) > 0) {
					echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin_cliente/xls/relatorio-callcenter-atendimento-$login_fabrica-$data_xls.xls /tmp/assist/relatorio-callcenter-atendimento-$login_fabrica.html`;
				}
				else {
					echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio-callcenter-atendimento-$login_fabrica-$data_xls.xls /tmp/assist/relatorio-callcenter-atendimento-$login_fabrica.html`;
				}
				echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
				echo"<tr>";
				echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><BR>RELATÓRIO DE CALLCENTER<BR>Clique aqui para fazer o </font><a href='xls/relatorio-callcenter-atendimento-$login_fabrica-$data_xls.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
				echo "</tr>";
				echo "</table>";
			}
		}else{
			echo "<P style='font-size: 12px; text-align=center; '>Nenhum resultado encontrado</P>";
		}
	}
?>
</form>
<?include 'rodape.php';?>

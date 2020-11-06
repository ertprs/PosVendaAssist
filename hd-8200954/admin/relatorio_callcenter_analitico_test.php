<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include "monitora.php";

$layout_menu = "callcenter";
$title = "Relatório de Atendimento Analítico";

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
		$res = @pg_exec($con,$sql);
		if(pg_num_rows($res)<1){
			$msg_erro .= " Selecione o Posto! ";
		}else{
			$posto = pg_result($res,0,0);
			if(strlen($posto)==0){
				$msg_erro .= " Selecione o Posto! ";
			}else{
				$posto = $posto;
			}
		}
	}

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
	}else{
		$msg_erro = "ENTRE COM O PERÍODO PARA FILTRAGEM";
	}
	if ($ativo == 't'){
		if((strlen($codigo_posto) == 0) OR (strlen($descricao_posto) == 0)){
			$msg_erro = "ENTRE COM O POSTO PARA FILTRAGEM";
		}
	}
}

if(strlen($msg_erro)>0){
	echo "<table width='500' border='0' cellpadding='5' cellspacing='1' align='center'>";
		echo "<tr>";
			echo "<td class='Erro'>$msg_erro</td>";
		echo "</tr>";
	echo "</table>";
}
?>
<form name='frm_relatorio' METHOD='POST' ACTION='<?=$PHP_SELF?>' align='center'>
	<br>
	<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding="0" cellspacing="0" align='center'>
		<tr>
			<td width='100%' class='Titulo' background='imagens_admin/azul.gif' colspan="2">
				<span style="font-size:13px">Relatório de Atendimento Analítico</span>
			</td>
		</tr>
		<tr bgcolor="#D9E2EF">
				<td colspan="2">
				<br />
					<div style="float:left; width:20px;padding-left:40px">
						<span style="margin-right: 20px">Ativo</span>
						<br />
						<input type="checkbox" name="ativo" id="ativo" value='t' onclick="habilitaPosto()" />
					</div>
					<div style="float:left; width:140px;padding-left:30px">
						<span style="margin-right: 20px">Código do Posto</span>
						<br />
						<input class="Caixa" type="text" name="codigo_posto" id="codigo_posto" size="15" value="<? echo $codigo_posto ?>" title="Digite o código do posto" disabled="disabled" />&nbsp;
						<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo_posto')" title='Digite parte do código do posto e clique na lupa para encontrar todos os postos com parte da código'>
					</div>
					<div style="float:left; width:240px;padding-left:20px">
						<span style="margin-right: 20px">Descrição do Posto</span>
						<br />
						<input class="Caixa" type="text" name="descricao_posto" id="descricao_posto" size="32" value="<? echo $descricao_posto ?>" title="Digite a descrição do posto" disabled="disabled" />&nbsp;
						<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'posto_nome')" style="cursor:pointer;" title='Digite parte da descrição do posto e clique na lupa para encontrar todos os postos com parte da descrição'>
					</div>
				</td>
			</tr>
		<tr>
			<tr bgcolor="#D9E2EF">
				<td colspan="2">
				<br />
					<div style="float:left; width:150px;padding-left:90px">
						<span>Data Inicial</span>
						<br />
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='Caixa' value= "<?=$data_inicial?>" title="Preencha aqui a data inicial (pesquisa por data de abertura da Ordem de Serviço)">
					</div>
					<div style="float:left; width:150px;padding-left:50px">
						<span>Data Final</span>
						<br />
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='Caixa' value="<?=$data_final?>" title="Preencha aqui a data final (pesquisa por data de abertura da Ordem de Serviço)">
					</div>
						</td>
			<tr bgcolor="#D9E2EF">
				<td>
					<center>
						<br>
						<img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_relatorio.btn_acao.value='Consultar'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar">
						<input type='hidden' name='btn_acao' value='<?=$acao?>'>
					</center>
				</td>
			</tr>
	</table>
	<? if($btn_acao=="Consultar" AND strlen($msg_erro) == 0){
		$sql = "SELECT DISTINCT
					tbl_os.os                                     AS os,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')    AS data_abertura,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY')  AS data_fechamento,
					tbl_hd_chamado_extra.atendimento_callcenter   AS atendente,
					tbl_os.consumidor_nome                        AS consumidor_nome,
					tbl_os.consumidor_cpf                         AS consumidor_cpf,
					tbl_os.consumidor_fone                        AS consumidor_fone,
					tbl_os.consumidor_cidade                      AS consumidor_cidade,
					tbl_os.consumidor_estado                      AS consumidor_estado,
					tbl_produto.descricao                         AS descricao_produto,
					tbl_defeito_constatado.descricao              AS defeito_constatado,
					tbl_defeito_reclamado.descricao               AS defeito_reclamado
				FROM tbl_os
					JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado  = tbl_os.defeito_constatado
					JOIN tbl_defeito_reclamado  ON tbl_defeito_reclamado.defeito_reclamado    = tbl_os.defeito_reclamado
					JOIN tbl_produto            ON tbl_produto.produto                        = tbl_os.produto
					JOIN tbl_hd_chamado_extra   ON tbl_hd_chamado_extra.os                    = tbl_os.os
					JOIN tbl_hd_chamado         ON tbl_hd_chamado.hd_chamado                  = tbl_hd_chamado.hd_chamado
				WHERE tbl_os.fabrica = $login_fabrica ";
		if (strlen($posto) > 0) {
			$sql .= " AND tbl_os.posto = $posto ";
		}
		$sql .=	" AND tbl_os.data_abertura :: date BETWEEN '$data_inicial' AND '$data_final'";
		$res = pg_exec($con,$sql);
		if (pg_numrows($res) > 0) {
			echo "<br>";
			echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='850'>";
				echo "<tr class='Titulo' height='25' background='imagens_admin/azul.gif'>";
					// 1 coluna (Nº da ORDEM DE SERVIÇO)
					echo "<td width='80'>O.S.</td>";
					// 2 coluna (Data de Abertura)
					echo "<td width='80'>Abertura</td>";
					// 3 coluna (Data de Fechamento)
					echo "<td width='80'>Fechamento</td>";
					// 4 coluna (Atendente)
					echo "<td width='80'>Atendente</td>";
					// 5 coluna (Consumidor)
					echo "<td width='80'>Consumidor</td>";
					// 6 coluna (CPF)
					echo "<td width='80'>CPF</td>";
					// 7 coluna (Cidade)
					echo "<td width='80'>Cidade</td>";
					// 8 coluna (UF.) 
					echo "<td width='80'>UF.</td>";
					// 9 coluna (Fone)
					echo "<td width='80'>Fone</td>";
					//10 coluna (Produto)
					echo "<td width='80'>Produto</td>";
					//11 coluna (Defeito Reclamado)
					echo "<td width='80'>Defeito Reclamado</td>";
				echo "</tr>";
				for ($i = 0; $i < pg_numrows($res); $i++){
					$os                 = @trim(pg_result($res,$i,os));
					$data_abertura      = @trim(pg_result($res,$i,data_abertura));
					$data_fechamento    = @trim(pg_result($res,$i,data_fechamento));
					$atendente          = @trim(pg_result($res,$i,atendente));
					$consumidor_nome    = @trim(pg_result($res,$i,consuimidor_nome));
					$consumidor_cpf     = @trim(pg_result($res,$i,consumidor_cpf));
					$consumidor_cidade  = @trim(pg_result($res,$i,consumidor_cidade));
					$consumidor_uf      = @trim(pg_result($res,$i,consumidor_uf));
					$consumidor_fone    = @trim(pg_result($res,$i,consumidor_fone));
					$produto            = @trim(pg_result($res,$i,produto));
					$defeito_reclamado  = @trim(pg_result($res,$i,defeito_reclamado));
					$defeito_constatado = @trim(pg_result($res,$i,defeito_constatado));
					if($cor=="#F1F4FA")
						$cor = '#F7F5F0';
					else
						$cor = '#F1F4FA';
					echo "<tr class='Conteudo2'>";
						// 1 coluna (Nº da ORDEM DE SERVIÇO)
						echo "<td width='80' nowrap>$os</td>";
						// 2 coluna (Data de Abertura)
						echo "<td width='80' nowrap>$data_abertura</td>";
						// 3 coluna (Data de Fechamento)
						echo "<td width='80' nowrap>$data_fechamento</td>";
						// 4 coluna (Atendente)
						echo "<td width='80' nowrap>$atendente</td>";
						// 5 coluna (Consumidor)
						echo "<td width='80' nowrap>$consumidor_nome</td>";
						// 6 coluna (CPF)
						echo "<td width='80' nowrap>$consumidor_cpf</td>";
						// 7 coluna (Cidade)
						echo "<td width='80' nowrap>$consumidor_cidade</td>";
						// 8 coluna (UF.) 
						echo "<td width='80' nowrap>$consumidor_uf</td>";
						// 9 coluna (Fone)
						echo "<td width='80' nowrap>$consumidor_fone</td>";
						//10 coluna (Produto)
						echo "<td width='80' nowrap>$produto</td>";
						//11 coluna (Defeito Reclamado)
						echo "<td width='80' nowrap>$defeito_reclamado</td>";
					echo "</tr>";
				}
			echo "</table>";
		}else{
			echo "<P style='font-size: 12px; text-align=center; '>Nenhum resultado encontrado</P>";
		}
	}
?>
</form>
<br />
<?include 'rodape.php';?>

<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro,gerencia,call_center";
include 'autentica_admin.php';
include 'funcoes.php';
include "monitora.php";

$layout_menu = "callcenter";
$title = "Relatório de CALL CENTER (ATENDIMENTO)";

$meses = array(01 => "JANEIRO", "FEVEREIRO", "MARÇO", "ABRIL", "MAIO", "JUNHO", "JULHO", "AGOSTO", "SETEMBRO", "OUTUBRO", "NOVEMBRO", "DEZEMBRO");

$msg_erro = '';

include 'cabecalho.php';

include "javascript_calendario.php";?>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
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
	if(strlen($mes_inicial) == 0){
		$msg_erro = "ENTRE COM O MÊS INICIAL";
	}elseif (strlen($ano_inicial) == 0){
		$msg_erro = "ENTRE COM O ANO INICIAL";
	}elseif(strlen($mes_final) == 0){
		$msg_erro = "ENTRE COM O MÊS FINAL";
	}elseif(strlen($ano_final) == 0){
		$msg_erro = "ENTRE COM O ANO FINAL";
	}elseif((strlen($codigo_posto) == 0) OR (strlen($descricao_posto) == 0)){
		$msg_erro = "ENTRE COM O POSTO PARA FILTRAGEM";
	}
}

if(strlen($msg_erro)>0){
	echo "<table width='700' border='0' cellpadding='5' cellspacing='1' align='center'>";
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
				<span style="font-size:13px">Relatório de Atendimento por Posto </span>
			</td>
		</tr>
		<tr>
			<tr bgcolor="#D9E2EF">
				<td colspan="2">
					<br />
					<div style="float:left; width:100px;padding-left:55px">
						<span>Mês Inicial</span>
						<br />
						<select align ='center' name="mes_inicial" size="1" class="frm">
							<option value=''>
							</option>
								<?
									for ($i = 1 ; $i <= count($meses);$i++){
										echo "<option value='$i'";
										if ($_POST['mes_inicial'] == $i)
											echo " selected";
											echo ">" . $meses[$i] . "</option>";
									}
								?>
						</select>
					</div>
					<div style="float:left; width:100px;padding-left:10px">
						<span>Ano Inicial</span>
						<br />
						<select align ='center' name="ano_inicial" size="1" class="frm">
							<option value=''>
							</option>
								<?
									for ($i = date("Y") ; $i >= 2003 ; $i--) {
										echo "<option value='$i'";
										if ($_POST['ano_inicial'] == $i)
											echo " selected";
											echo ">$i</option>";
									}
								?>
						</select>
					</div>
					<div style="float:left; width:100px;padding-left:10px">
						<span>Mês Final</span>
						<br />
						<select align ='center' name="mes_final" size="1" class="frm">
							<option value=''>
							</option>
								<?
									for ($i = 1 ; $i <= count($meses);$i++){
										echo "<option value='$i'";
										if ($_POST['mes_final'] == $i) 
											echo " selected";
											echo ">" . $meses[$i] . "</option>";
									}
								?>
						</select>
					</div>
					<div style="float:left; width:100px;padding-left:10px">
						<span>Ano Final</span>
						<br />
						<select align ='center' name="ano_final" size="1" class="frm">
							<option value=''>
							</option>
								<?
									for ($i = date("Y") ; $i >= 2003 ; $i--) {
										echo "<option value='$i'";
										if ($_POST['ano_final'] == $i)
											echo " selected";
											echo ">$i</option>";
									}
								?>
						</select>
					</div>
				</td>
				<tr bgcolor="#D9E2EF">
					<td colspan="2">
						<br />
						<div style="float:left; width:130px;padding-left:55px">
							<span style="margin-right: 20px">Código do Posto</span>
							<br />
							<input class="Caixa" type="text" name="codigo_posto" id="codigo_posto" size="15" value="<? echo $codigo_posto ?>" title="Digite o código do posto">&nbsp;
							<input type="hidden" name="posto" value="<? echo $posto ?>">
							<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'codigo_posto')" title='Digite parte do código do posto e clique na lupa para encontrar todos os postos com parte da código'>
						</div>
						<div style="float:left; width:300px;padding-left:5px">
							<span style="margin-right: 20px">Descrição do Posto</span>
							<br />
							<input class="Caixa" type="text" name="descricao_posto" id="descricao_posto" size="40" value="<? echo $descricao_posto ?>" title="Digite a descrição do posto">&nbsp;
							<img src='imagens/lupa.png' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_relatorio.codigo_posto,document.frm_relatorio.posto_nome,'posto_nome')" style="cursor:pointer;" title='Digite parte da descrição do posto e clique na lupa para encontrar todos os postos com parte da descrição'>
						</div>
					</td>
				</tr>
				<tr bgcolor="#D9E2EF">
					<td>
						<center>
							<br>
							<img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_relatorio.btn_acao.value='Consultar'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar">
							<input type='hidden' name='btn_acao' value='<?=$acao?>'>
						</center>
					</td>
				</tr>
			</tr>
		</tr>
	</table>
	<? if($btn_acao=="Consultar" AND strlen($msg_erro) == 0){
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
		//   ***** CAPTURA DE DADOS *****   \\
		// armazena os dados selecionados nas veriáveis
		if($_POST['mes_inicial'])
			$mes_inicial = str_pad(trim($_POST['mes_inicial']), 2, '0', STR_PAD_LEFT);
		if($_POST['ano_inicial'])
			$ano_inicial = trim($_POST['ano_inicial']);
		if($_POST['mes_final'])
			$mes_final   = str_pad(trim($_POST['mes_final']), 2, '0', STR_PAD_LEFT);
		if($_POST['ano_final'])
			$ano_final   = trim($_POST['ano_final']);
		//   ***** VERIFICAÇÃO DO PERÍODO SELECIONADO *****   \\
		// Verifica se o mes/ano final é maior ou igual ao mes/ano inicial
		if (strtotime($ano_inicial.'-'.$mes_inicial.'-01') <= strtotime($ano_final.'-'.$mes_final.'-01')) {
			// Monta a data inicial 
			$data_inicial = $ano_inicial.'-'.$mes_inicial.'-'.'01';
			$dta_inicial  = $ano_inicial.'-'.$mes_inicial.'-'.'01';
			// Monta a data final (em duas variáveis)
			$data_final   = $ano_final.'-' .$mes_final.'-'.'01';
			$dta_final    = $ano_final.'-' .$mes_final.'-'.'01';
			// Verifica no banco se o perído selecionado não é maior que 12 mêses
			$sql_data = "Select '$data_inicial' ::date > '$data_final' :: date - interval '12 month' as data";
			$res_data = pg_exec($con,$sql_data);
			$vet = pg_result($res_data,0,data);
			$vet['data'] ;
			// Verifica se o período é maior que 12 mêses
			if ($vet == 't'){
				// Processo que determina a data inicial selecionada pelo usuário (YYYY-MM-DD HH:MM:SS)
				$dta_inicial     = substr($dta_inicial, 0,10 )." 00:00:00";
				// Processo que determina a data final selecionada mais um mês
				$sql_data_final  = "Select '$data_final'::date + interval '1 month' as data_lista";
				$res_data_final  = pg_exec($con,$sql_data_final);
				$vet_data_final  = pg_result($res_data_final,0,data_lista);
				// Processo que determina o último dia do mês selecionado
				$sql_data_final  = "Select '$vet_data_final'::date - interval '1 day' as data_lista";
				$res_data_final  = pg_exec($con,$sql_data_final);
				$vet_data_final  = pg_result($res_data_final,0,data_lista);
				$dta_final       = substr($vet_data_final, 0,10 )." 23:59:59";
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
						WHERE tbl_os.fabrica = $login_fabrica
							AND tbl_os.posto = $posto
							AND tbl_os.data_abertura :: date BETWEEN '$dta_inicial' AND '$dta_final'";
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
							//12 coluna (Defeito Constatado)
							echo "<td width='80'>Defeito Constatado</td>";
						echo "</tr>";
						for ($i=0; $i<pg_numrows($res); $i++){
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
							if($cor=="#F1F4FA"){
								$cor = '#F7F5F0';
							}else{
								$cor = '#F1F4FA';
							}
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
								//12 coluna (Defeito Constatado)
								//echo "<td width='80' nowrap>$defeito_constatado</td>";
							echo "</tr>";
						}
					echo "</table>";
				}else{
					echo "<P style='font-size: 12px; text-align=center; '>Nenhum resultado encontrado</P>";
				}
			}
		}
	}?>
</form>
<?include 'rodape.php';?>
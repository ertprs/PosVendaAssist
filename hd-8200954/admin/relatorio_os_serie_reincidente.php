<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="auditoria,gerencia";
include 'autentica_admin.php';
$layout_menu = "gerencia";
$title = "RELATÓRIO OS SÉRIE REINCIDENTE";
include 'cabecalho.php';
//echo "Olaaa \n\n";
$cachebypass=md5(time());

$codigo_posto			= $_POST['codigo_posto'];
$posto_nome				= $_POST['posto_nome'];

$periodo				= $_POST['periodo'];
$reincidente			= $_POST['reincidente'];
$estado					= $_POST['estado'];
$cidade					= $_POST['cidade'];

$produto_referencia		= $_POST['produto_referencia'];
$produto_descricao		= $_POST['produto_descricao'];

$linha					= $_POST['linha'];
$familia				= $_POST['familia'];

$btn_acao = $_POST['acao'];
if(strlen($btn_acao)>0){
	$data_inicial = $_POST['data_inicial_01'];
	$data_final   = $_POST['data_final_01'];
	//Início Validação de Datas
		if(!$data_inicial OR !$data_final)
			$erro = "Data Inválida";
		if(strlen($erro)==0){
			$dat = explode ("/", $data_inicial );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) $erro = "Data Inválida";
		}
		if(strlen($erro)==0){
			$dat = explode ("/", $data_final );//tira a barra
				$d = $dat[0];
				$m = $dat[1];
				$y = $dat[2];
				if(!checkdate($m,$d,$y)) $erro = "Data Inválida";
		}

		if(strlen($erro)==0) {
			$d_ini = explode ("/", $data_inicial);//tira a barra
			$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


			$d_fim = explode ("/", $data_final);//tira a barra
			$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

			if($nova_data_final < $nova_data_inicial){
				$erro = "Data Inválida";
			}

			$aux_data_inicial	= preg_replace('/(\d\d).(\d{2}).(\d{4})/','$3-$2-$1',utf8_decode($data_inicial));
			$aux_data_final		= preg_replace('/(\d\d).(\d{2}).(\d{4})/','$3-$2-$1',utf8_decode($data_final));

			if(strlen($erro) == 0){
				$sqlDC = "SELECT '$aux_data_inicial' ::date +  INTERVAL '3 Months' < '$aux_data_final' ::date AS verifica_data";
				$resDC = pg_query($con, $sqlDC);
				if(pg_num_rows($resDC)>0){
					$verifica_data = pg_fetch_result($resDC, 0, 0);
					if($verifica_data == 't'){
						$erro = "Data invalida. Período máximo para pesquisa é de 3 meses";
					}
				}
			}
			//Fim Validação de Datas
		}

		if(strlen($erro)==0) {
			$periodo = $_POST['periodo'];
			if(!$periodo){
				$erro = "Selecione o Período";
			}
		}

}

?>
<style type="text/css">
.Titulo{
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo{
	font-family: Arial;
	font-size: 11px;
	font-weight: normal;
}
.ConteudoBranco{
	font-family: Arial;
	font-size: 11px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 8px;
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
	color: #7092BE
}
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
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
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}
.formulario{
	background-color:#;
	font:11px Arial;
	text-align:left;
}

.formulario_resultado{
	background-color:#;
	font:11px Arial;
	text-align:left;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
#tabela{display:none;}
.sucesso{
	background-color:#008000;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

#relatorio tr td span{ cursor:pointer; }
#grid_list tr td span{ cursor:pointer; }

.tablesorter th{
	cursor: pointer;
}

table.tablesorter {
font-family: arial;
background-color: ;
margin: 10px auto;
font-size: 8pt;
text-align: left;
}

</style>

<?include "javascript_pesquisas.php"; ?>
<? include "javascript_calendario.php"; ?>

<link rel="stylesheet" href="../plugins/jquery/tablesorter/themes/telecontrol/style.css" type="text/css" media="all" />
<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<script src="../plugins/jquery/jquery.tablesorter.min.js" type="text/javascript"></script>

<script>
	$(document).ready(function(){
		$.tablesorter.defaults.widgets = ['zebra'];
		$("#relatorio").tablesorter();
	});
	function antecipaPedido(linha){
		var cache = new Date();
		cache = cache.getTime();
		var os = $('#selecao_'+linha).val();
//		if ($('#selecao_'+linha).attr('checked')){
			requisicaoHTTP('GET','relatorio_os_peca_sem_pedido_ajax.php?linha='+linha+'&os='+os+'&cachebypass='+cache, true , 'antecipa');
//		}else{
//			alert('Para antecipar marque a OS: ' + os);
//		}
	}

	function antecipa(campos){
		if (campos == 'ok'){
			alert('OS sera gerada antecipadamente');
			document.getElementById('btn_antecipar').disabled = true;
		}
	}
</script>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />

<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="javascript">
	function chamaAjax(linha,data_inicial,data_final,posto,produto,cache){
		if (document.getElementById('div_sinal_' + linha).innerHTML == '+'){
			requisicaoHTTP('GET','mostra_os_peca_sem_pedido_ajax.php?linha='+linha+'&data_inicial='+data_inicial+'&data_final='+data_final+'&posto='+posto+'&produto='+produto+'&cachebypass='+cache, true , 'div_detalhe_carrega');
		}else{
			document.getElementById('div_detalhe_' + linha).innerHTML = "";
			document.getElementById('div_sinal_' + linha).innerHTML = '+';
		}
	}
	function load(linha){
		document.getElementById('div_detalhe_' + linha).innerHTML = "<img src='a_imagens/ajax-loader.gif'>";
	}
	function div_detalhe_carrega (campos){
		campos_array = campos.split("|");
		linha = campos_array [0];
		document.getElementById('div_detalhe_' + linha).innerHTML = campos_array[1];
		document.getElementById('div_sinal_' + linha).innerHTML = '-';
	}

	$().ready(function() {
		$('.pesquisa_dados').click(function(){
			$("#div_carregando").html("<br>&nbsp;&nbsp;<img src='imagens/ajax-loader.gif'><br>&nbsp;&nbsp;CARREGANDO OS DADOS AGUARDE...");

			var acao = $("#verif_pesquisa").val();
			if(acao == ''){
				$("#verif_pesquisa").val('pesquisando');
			}else{
				alert("Aguarda ... Dados sendo carregado");
			return false;
			}
		});
	});

</script>

<script language='javascript' src='ajax.js'>
</script>

<script type="text/javascript" src="js/bibliotecaAJAX.js">
</script>


<form name="frm_pesquisa" method="POST" action="<?echo $PHP_SELF?>">

	<input type="hidden" id="verif_pesquisa">
	<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
		<? if(strlen($erro) > 0){ ?>
			<tr class="msg_erro"><td colspan="4"><? echo $erro; ?></td></tr>
		<? } ?>
		<tr class="titulo_tabela">
			<td colspan="4">
				Parâmetros de Pesquisa
			</td>
		</tr>
		<tr>
			<td width='20%'>
			</td>
			<td width='30%' align='left'>
				Data Inicial
			</td>
			<td width='30%' align='left'>
				Data Final
			</td>
			<td width='20%'>
			</td>
		</tr>
		<tr>
			<td width='20%'>
			</td>
			<td width='30%' align='left'>
				<input size="12" maxlength="10" TYPE="text" class='frm' NAME="data_inicial_01" id="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>">
			</td>
			<td width='30%' align='left'>
				<input size="12" maxlength="10" TYPE="text" class='frm'  NAME="data_final_01" id="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; ?>">
			</td>
			<td width='20%'>
			</td>
		</tr>
		<tr>
			<td width='20%'>
			</td>
			<td width='30%' align='left' >
				Código Posto
			</td>
			<td width='30%' align='left' >
				Nome Posto
			</td>
			<td width='20%'>
			</td>
		</tr>
		<tr>
			<td width='20%'>
			</td>
			<td width='30%' align='left'>
				<input type='text' name='codigo_posto' id='codigo_posto' size='15' value='<? echo $codigo_posto ?>' class='frm'>
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo')">
			</td>
			<td width='30%' align='left'>
				<input type='text' name='posto_nome' id='posto_nome' size='25' value='<? echo $posto_nome ?>' class='frm'>
				<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome')">
			</td>
			<td width='20%'>
			</td>
		</tr>
	 </table>

	 <table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
		<tr>
			<td width='20%'>
			</td>
			<td width='30%' align='left' >
				Período
			</td>
			<td width='30%' align='left' >
				Reincidência
			</td>
			<td width='20%'>
			</td>
		</tr>
		<tr>
			<td width='20%'>
			</td>
			<td width='30%' align='left'>
				<select name="periodo" id="periodo" class="frm">
					<option value="" <? if ($periodo == '') echo " SELECTED ";?>>Selecione uma opção</option>
					<option value="1" <? if ($periodo == '1') echo " SELECTED ";?>>Abertura</option>
					<option value="2" <? if ($periodo == '2') echo " SELECTED ";?>>Digitação</option>
					<option value="3" <? if ($periodo == '3') echo " SELECTED ";?>>Fabricação</option>
					<option value="4" <? if ($periodo == '4') echo " SELECTED ";?>>Fechamento</option>
					<option value="5" <? if ($periodo == '5') echo " SELECTED ";?>>Finalização</option>
				</select>
			</td>
			<td width='30%' align='left'>
				<input type="checkbox" name="reincidente" id="reincidente" value="t" <?php if($reincidente == 't') echo "CHECKED";?>>
			</td>
			<td width='20%'>
			</td>
		</tr>
		<tr><td colspan="4">&nbsp;</td></tr>
	</table>

	<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
		<tr>
			<td width='20%'>
			</td>
			<td width='30%' align='left' >
				Estado
			</td>
			<td width='30%' align='left' >
				Cidade
			</td>
			<td width='20%'>
			</td>
		</tr>
		<tr>
			<td width='20%'>
			</td>
			<td width='30%' align='left'>
				<select name="estado" id="estado" class="frm">
					<option value="" <?php if ($periodo == '') echo " SELECTED ";?>>Selecione uma opção</option>
					<option value="AC" <?php if ($periodo == 'AC') echo " SELECTED ";?>>Acre</option>
					<option value="AL" <?php if ($periodo == 'AL') echo " SELECTED ";?>>Alagoas</option>
					<option value="AM" <?php if ($periodo == 'AM') echo " SELECTED ";?>>Amazonas</option>
					<option value="AP" <?php if ($periodo == 'AP') echo " SELECTED ";?>>Amapá</option>
					<option value="BA" <?php if ($periodo == 'BA') echo " SELECTED ";?>>Bahia</option>
					<option value="CE" <?php if ($periodo == 'CE') echo " SELECTED ";?>>Ceará</option>
					<option value="DF" <?php if ($periodo == 'DF') echo " SELECTED ";?>>Distrito Federal</option>
					<option value="ES" <?php if ($periodo == 'ES') echo " SELECTED ";?>>Espírito Santo</option>
					<option value="GO" <?php if ($periodo == 'GO') echo " SELECTED ";?>>Goiás</option>
					<option value="MA" <?php if ($periodo == 'MA') echo " SELECTED ";?>>Maranhão</option>
					<option value="MG" <?php if ($periodo == 'MG') echo " SELECTED ";?>>Minas Gerais</option>
					<option value="MS" <?php if ($periodo == 'MS') echo " SELECTED ";?>>Mato Grosso do Sul</option>
					<option value="MT" <?php if ($periodo == 'MT') echo " SELECTED ";?>>Mato Grosso</option>
					<option value="PA" <?php if ($periodo == 'PA') echo " SELECTED ";?>>Pará</option>
					<option value="PB" <?php if ($periodo == 'PB') echo " SELECTED ";?>>Paraíba</option>
					<option value="PE" <?php if ($periodo == 'PE') echo " SELECTED ";?>>Pernambuco</option>
					<option value="PI" <?php if ($periodo == 'PI') echo " SELECTED ";?>>Piauí</option>
					<option value="PR" <?php if ($periodo == 'PR') echo " SELECTED ";?>>Paraná</option>
					<option value="RJ" <?php if ($periodo == 'RJ') echo " SELECTED ";?>>Rio de Janeiro</option>
					<option value="RN" <?php if ($periodo == 'RN') echo " SELECTED ";?>>Rio Grande do Norte</option>
					<option value="RO" <?php if ($periodo == 'RO') echo " SELECTED ";?>>Rondônia</option>
					<option value="RR" <?php if ($periodo == 'RR') echo " SELECTED ";?>>Roraima</option>
					<option value="RS" <?php if ($periodo == 'RS') echo " SELECTED ";?>>Rio Grande do Sul</option>
					<option value="SC" <?php if ($periodo == 'SC') echo " SELECTED ";?>>Santa Catarina</option>
					<option value="SE" <?php if ($periodo == 'SE') echo " SELECTED ";?>>Sergipe</option>
					<option value="SP" <?php if ($periodo == 'SP') echo " SELECTED ";?>>São Paulo</option>
					<option value="TO" <?php if ($periodo == 'TO') echo " SELECTED ";?>>Tocantins</option>
				</select>
			</td>
			<td width='30%' align='left'>
				<input type="text" name="cidade" id="cidade" value="<?php echo $cidade;?>" class="frm" size="30">
			</td>
			<td width='20%'>
			</td>
		</tr>
		<tr><td colspan="4">&nbsp;</td></tr>
	</table>


	<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
		<tr>
			<td width='20%'>
			</td>
			<td width='30%' align='left' >
				Ref. Produto
			</td>
			<td width='30%' align='left' >
				Descrição Produto
			</td>
			<td width='20%'>
			</td>
		</tr>
		<tr>
			<td width='20%'>
			</td>
			<td width='30%' align='left'>
				<input type='hidden' name='voltagem' value=''>
				<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" id='produto_referencia'>
				&nbsp;
				<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'referencia',document.frm_pesquisa.voltagem)">
			</td>
			<td width='40%' align='left'>
				<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>" id='produto_descricao'>
				&nbsp;
				<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia, document.frm_pesquisa.produto_descricao,'descricao',document.frm_pesquisa.voltagem)">
			</td>
			<td width='10%'>
			</td>
		</tr>
		<tr><td colspan="4">&nbsp;</td></tr>
	</table>





	<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
		<tr>
			<td width='20%'>
			</td>
			<td width='30%' align='left' >
				Linha
			</td>
			<td width='30%' align='left' >
				Familia
			</td>
			<td width='20%'>
			</td>
		</tr>
		<tr>
			<td width='20%'>
			</td>
			<td width='30%' align='left'>
				<input type='hidden' name='voltagem' value=''>
				<select class="frm" type="text" name="linha">
					<option value="">Selecione uma opção</option>
					<?php
						$sql = "SELECT linha, nome from tbl_linha where fabrica = $login_fabrica and ativo = true order by nome";
						$res = pg_query($con,$sql);
						if(pg_num_rows($res)>0){
							for($i=0;pg_num_rows($res)>$i;$i++){
								$xlinha = pg_fetch_result($res,$i,linha);
								$xnome = pg_fetch_result($res,$i,nome);
								?>
								<option value="<?echo $xlinha;?>" <?php if ($xlinha == $linha) echo " SELECTED ";?> ><?echo $xnome;?></option>
								<?
							}
						}
					?>
				</select>
			</td>
			<td width='40%' align='left'>
				<select class="frm" type="text" name="familia" id='familia'>
					<option value="">Selecione uma opção</option>
					<?php
						$sql = "SELECT familia, descricao from tbl_familia where fabrica = $login_fabrica and ativo = true order by descricao";
						$res = pg_query($con,$sql);
						if(pg_num_rows($res)>0){
							for($i=0;pg_num_rows($res)>$i;$i++){
								$xfamilia = pg_fetch_result($res,$i,familia);
								$xdescricao = pg_fetch_result($res,$i,descricao);
								?>
								<option value="<?echo $xfamilia;?>"  <?php if ($xfamilia == $familia) echo " SELECTED ";?> ><?echo $xdescricao;?></option>
								<?
							}
						}
					?>
				</select>
			</td>
			<td width='10%'>
			</td>
		</tr>
		<tr><td colspan="4">&nbsp;</td></tr>
	</table>


	<table width="700px" align="center" border="0" cellspacing="0" cellpadding="2" class="formulario">
		<tr><td colspan="4">&nbsp;</td></tr>
		<tr>
			<td colspan="4" align="center">
				<input type="submit" style="cursor:pointer;" name="acao" value="Pesquisar" id='pesquisa_dados' class='pesquisa_dados' alt="Preencha as opções e clique aqui para pesquisar">
			</td>
		</tr>
	</table>
</form>
<?php





if((strlen($btn_acao)>0) && (strlen($erro)==0)){

	$nome_programa	  = "relatorio_os_serie_reincidente";
	$arquivo_nome     = "$nome_programa-".$login_fabrica.".".$login_admin.".xls";
	$caminho_pasta	  = "xls/".$arquivo_nome;
	$caminho_arquivo  = dirname(__FILE__)."/".$caminho_pasta;

	fopen($caminho_pasta, "w+");
	$fp = fopen($caminho_pasta, "a");

	//echo "<script>coloca_inf_carregando('')</script>";

	?>
	<div id='div_carregando' name='div_carregando' class='div_carregando'></div>
	<?php

	$referencia		 = $_POST['referencia'];
	$descricao		 = $_POST['descricao'];

	$rl_data_inicial = $_POST['data_inicial_01'];
	$rl_data_final   = $_POST['data_final_01'];

	$codigo_posto			= $_POST['codigo_posto'];
	$periodo				= $_POST['periodo'];
	$reincidente			= $_POST['reincidente'];
	$estado					= $_POST['estado'];
	$cidade					= $_POST['cidade'];
	$produto_referencia		= $_POST['produto_referencia'];
	$linha					= $_POST['linha'];
	$familia				= $_POST['familia'];

	$rl_aux_data_inicial	= preg_replace('/(\d\d).(\d{2}).(\d{4})/','$3-$2-$1',utf8_decode($rl_data_inicial));
	$rl_aux_data_final		= preg_replace('/(\d\d).(\d{2}).(\d{4})/','$3-$2-$1',utf8_decode($rl_data_final));

	$and_posto ="";
	if(strlen($codigo_posto) > 0){
		$and_posto = "  JOIN tbl_posto_fabrica
						ON tbl_os.posto   = tbl_posto_fabrica.posto
						AND tbl_posto_fabrica.codigo_posto ='".$codigo_posto."'
						AND tbl_posto_fabrica.fabrica = 50";
	}

	$and_periodo = "";
	if(strlen($periodo) > 0){
		$and_data_do_filtro ="";
		if($periodo == '1'){
			$and_periodo = "AND tbl_os.data_abertura BETWEEN '$rl_aux_data_inicial' AND '$rl_aux_data_final'";
			$and_data_do_filtro = "Data de Abertura";
			$and_data_do_realtorio = "tbl_os.data_abertura AS data_pesquisa,";
		}
		if($periodo == '2'){
			$and_periodo = "AND tbl_os.data_digitacao BETWEEN '$rl_aux_data_inicial' AND '$rl_aux_data_final'";
			$and_data_do_filtro = "Data de Digitação";
			$and_data_do_realtorio = "tbl_os.data_digitacao AS data_pesquisa,";
		}
		if($periodo == '3'){
			$and_periodo_fabricacao = "JOIN tbl_numero_serie
										ON tbl_os.fabrica = tbl_numero_serie.fabrica
										AND tbl_os.produto = tbl_numero_serie.produto
										AND tbl_os.serie   = tbl_numero_serie.serie
										AND tbl_numero_serie.data_fabricacao BETWEEN '$rl_aux_data_inicial' AND '$rl_aux_data_final'";
			$and_data_do_realtorio = "tbl_numero_serie.data_fabricacao AS data_pesquisa,";
			$and_data_do_filtro = "Data da Fabricação";
		}
		if($periodo == '4'){
			$and_periodo = "AND tbl_os.data_fechamento BETWEEN '$rl_aux_data_inicial' AND '$rl_aux_data_final'";
			$and_data_do_filtro = "Data do Fachamento";
			$and_data_do_realtorio	= "tbl_os.data_fechamento AS data_pesquisa,";
		}
		if($periodo == '5'){
			$HI = "00:00:00";
			$HF = "23:59:59";
			$and_periodo = "AND tbl_os.finalizada BETWEEN '$rl_aux_data_inicial $HI' AND '$rl_aux_data_final $HF'";
			$and_data_do_filtro = "Data da Finalização";
			$and_data_do_realtorio = "tbl_os.finalizada AS data_pesquisa,";
		}
	}

	$and_reincidente ="";
	if($reincidente == 't'){
		$and_reincidente = "AND tbl_os.os_reincidente = 'TRUE'";
	}

	$and_estado ="";
	if(strlen($estado) > 0){
		$and_estado = "AND tbl_os.consumidor_estado = '$estado'";
	}

	$and_cidade ="";
	if(strlen($cidade) > 0){
		$and_cidade = "AND tbl_os.consumidor_cidade LIKE '%$cidade%'";
	}

	$and_produto_referencia ="";
	if(strlen($produto_referencia) > 0){
		$and_produto_referencia = "	JOIN tbl_produto
									ON tbl_os.produto = tbl_produto.produto
									AND tbl_produto.referencia = '$produto_referencia'";
	}

	$and_linha ="";
	if(strlen($linha) > 0){
		$and_linha = "	JOIN tbl_produto
						ON tbl_os.produto = tbl_produto.produto
						AND tbl_produto.linha ='$linha'";
		if(strlen($produto_referencia) > 0){
			$and_linha .= " AND tbl_os.produto = tbl_produto.produto
							AND tbl_produto.referencia = '$produto_referencia'";
			$and_produto_referencia = "";
		}
	}

	$and_familia ="";
	if(strlen($familia) > 0 && strlen($linha) <= 0){
		$and_familia = "JOIN tbl_produto
						ON tbl_os.produto = tbl_produto.produto
						AND tbl_produto.familia ='$familia'";
		if(strlen($produto_referencia) > 0){
			$and_familia .= " AND tbl_os.produto = tbl_produto.produto
							AND tbl_produto.referencia = '$produto_referencia'";
			$and_produto_referencia = "";
		}
	}

	if(strlen($familia) > 0 && strlen($linha) > 0){
		$and_linha = "	JOIN tbl_produto
						ON tbl_os.produto = tbl_produto.produto
						AND tbl_produto.linha ='$linha'
						AND tbl_produto.familia ='$familia' ";
		if(strlen($produto_referencia) > 0){
			$and_linha .= " AND tbl_os.produto = tbl_produto.produto
							AND tbl_produto.referencia = '$produto_referencia'";
			$and_produto_referencia = "";
		}
	}

	$sqlt = "SELECT TO_CHAR('$rl_aux_data_inicial'::date + INTERVAL '30 days','YYYY-MM-DD')";
	//echo nl2br($sqlt);
	$rest = pg_query($con,$sqlt);
	$aux_data_final = pg_fetch_result($rest,0,0);

	$sqlDA = "SELECT '$rl_aux_data_final' ::date <= '$aux_data_final' ::date AS verifica_data_excel";
	//echo nl2br($sqlDA);
	$resDA = pg_query($con, $sqlDA);
	if(pg_num_rows($resDA)>0){
		$verifica_data_excel = pg_fetch_result($resDA, 0, 0);
		if($verifica_data_excel == 't'){
			$data_verifica_excel = 't';
		}else{
			$data_verifica_excel = 'f';
		}
	}



		if($periodo != 3){
			$and_periodo_fabricacao = "
				   LEFT JOIN tbl_numero_serie
							ON tbl_os.fabrica = tbl_numero_serie.fabrica
							AND tbl_os.produto = tbl_numero_serie.produto
							AND tbl_os.serie   = tbl_numero_serie.serie";
		}


    $temp_tbl = "tmp_relatorio_os_serie_reincidente_os_$login_admin";

    if ($login_fabrica == 50) {
        $temp_tbl = "tmp1_relatorio_os_serie_reincidente_os_$login_admin";
    }

//------------------TEMPORARIA-----------------
$sqlos ="select distinct
		tbl_os.os as os,
		tbl_os.defeito_reclamado_descricao,
		tbl_os.defeito_constatado,
		tbl_defeito_constatado.descricao as descricao_defeito_constatado,
		tbl_solucao.descricao as descricao_solucao_os,
		tbl_os.solucao_os,
		tbl_os.serie,
		tbl_os.data_nf,
		tbl_os.posto,
		tbl_posto.nome as descricao_posto,
		tbl_os.consumidor_estado,
		tbl_os.consumidor_cidade,
		$and_data_do_realtorio
		tbl_numero_serie.data_fabricacao as data_fabricacao,
		tbl_os.os_reincidente,
		tbl_os.fabrica
		into temp $temp_tbl
		from tbl_os
		left join tbl_defeito_constatado
		on tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
		left join tbl_solucao
		on tbl_os.solucao_os = tbl_solucao.solucao
		join tbl_posto
		on tbl_os.posto = tbl_posto.posto
		$and_periodo_fabricacao
		$and_linha
		$and_familia
		$and_posto
		$and_produto_referencia
		where tbl_os.fabrica = $login_fabrica
		and tbl_os.excluida is not true
		and tbl_os.posto <> 6359
			$and_periodo
			$and_reincidente
			$and_estado
			$and_cidade
		order by tbl_os.os desc;

		create index tmp_relatorio_os_serie_reincidente_os_os on $temp_tbl(os);";

		$res_os = pg_query ($con,$sqlos);


        if ($login_fabrica == 50) {
            $qry_3_meses = pg_query(
                $con,
                "SELECT ('$rl_aux_data_inicial'::date - INTERVAL '3 months')::date AS reinc_limit"
            );
            $reinc_limit = pg_fetch_result($qry_3_meses, 0, 'reinc_limit');

            $sqlos ="
                SELECT os,
                  serie,
                  (
                    SELECT status_os 
                    FROM tbl_os_status
                    WHERE status_os = 67
                    AND os = tmp1_relatorio_os_serie_reincidente_os_$login_admin.os
                  ) AS status,
                  (
                    SELECT data_abertura - INTERVAL '3 months'
                    FROM tbl_os
                    WHERE os = tmp1_relatorio_os_serie_reincidente_os_$login_admin.os
                  ) AS data_limite
                INTO TEMP tmp_os_reincidente_x_$login_admin
                FROM tmp1_relatorio_os_serie_reincidente_os_$login_admin ;

                SELECT tbl_os.os AS os
                INTO TEMP tmp_os_reincidente_$login_admin
                FROM tbl_os
                JOIN tmp_os_reincidente_x_$login_admin
                  ON tmp_os_reincidente_x_$login_admin.serie = tbl_os.serie
                  AND tbl_os.data_abertura > tmp_os_reincidente_x_$login_admin.data_limite
                WHERE fabrica = $login_fabrica
                AND tbl_os.data_abertura BETWEEN '$reinc_limit' AND '$rl_aux_data_final'
                AND tbl_os.os NOT IN (
                  SELECT os FROM tmp_os_reincidente_x_$login_admin
                  WHERE status IS NOT NULL
                );

                select distinct
                    tbl_os.os as os,
                    tbl_os.defeito_reclamado_descricao,
                    tbl_os.defeito_constatado,
                    tbl_defeito_constatado.descricao as descricao_defeito_constatado,
                    tbl_solucao.descricao as descricao_solucao_os,
                    tbl_os.solucao_os,
                    tbl_os.serie,
                    tbl_os.data_nf,
                    tbl_os.posto,
                    tbl_posto.nome as descricao_posto,
                    tbl_os.consumidor_estado,
                    tbl_os.consumidor_cidade,
                    $and_data_do_realtorio
                    tbl_numero_serie.data_fabricacao as data_fabricacao,
                    tbl_os.os_reincidente,
                    tbl_os.fabrica
                    into temp tmp2_relatorio_os_serie_reincidente_os_$login_admin
                    from tbl_os
                    INNER JOIN tmp_os_reincidente_$login_admin ON tmp_os_reincidente_$login_admin.os = tbl_os.os
                    left join tbl_defeito_constatado
                    on tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
                    left join tbl_solucao
                    on tbl_os.solucao_os = tbl_solucao.solucao
                    join tbl_posto
                    on tbl_os.posto = tbl_posto.posto
                    $and_periodo_fabricacao
                    $and_linha
                    $and_familia
                    $and_produto_referencia
                    WHERE tbl_os.fabrica = $login_fabrica
                    order by tbl_os.os desc;

            create index tmp_relatorio_os_serie_reincidente_os_os1 on tmp2_relatorio_os_serie_reincidente_os_$login_admin(os);

            SELECT * INTO TEMP tmp_relatorio_os_serie_reincidente_os_$login_admin FROM (
                SELECT * FROM tmp1_relatorio_os_serie_reincidente_os_$login_admin
                UNION
                SELECT * FROM tmp2_relatorio_os_serie_reincidente_os_$login_admin
            ) x";
            $res_os = pg_query ($con,$sqlos);
        }


//echo nl2br($sqlos);
//echo "<br><br><br><br><br><br><br><br><br><br>";

$sql_peca ="SELECT
		tmp_relatorio_os_serie_reincidente_os_$login_admin.os as os,
		tbl_os_item.qtde AS qtde,
		tbl_os_item.os_item AS os_item,
		tbl_peca.peca AS peca,
		tbl_peca.descricao AS descricao,
		tbl_os_item.servico_realizado AS servico_realizado,
		tbl_os_produto.produto AS produto,
		tbl_servico_realizado.descricao AS descricao_servico_realizado,
		tmp_relatorio_os_serie_reincidente_os_$login_admin.fabrica AS fabrica
		into TEMP tmp_relatorio_os_serie_reincidente_peca_$login_admin
		FROM tmp_relatorio_os_serie_reincidente_os_$login_admin
		join tbl_os_produto using(os)
		JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto and tbl_os_item.fabrica_i=$login_fabrica
		JOIN tbl_peca using (peca)
		JOIN tbl_servico_realizado
		ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
			where tmp_relatorio_os_serie_reincidente_os_$login_admin.fabrica=$login_fabrica;

		CREATE INDEX tmp_relatorio_os_serie_reincidente_peca_os ON tmp_relatorio_os_serie_reincidente_peca_$login_admin(os);";
		//echo nl2br($sql_peca);
		//echo "<br><br><br><br><br><br><br><br><br><br>";
		$res_peca = pg_query ($con,$sql_peca);
//---------------------------------------------

$qtd_coluna ="0";
$busca_os_cont_peca = "select OS,COUNT(peca) AS peca from tmp_relatorio_os_serie_reincidente_peca_$login_admin GROUP BY OS ORDER BY peca desc limit 1";
$res_vr_qtd_col = pg_query($con,$busca_os_cont_peca);
if(pg_num_rows($res_vr_qtd_col) > 0) {
	$qtd_coluna	= utf8_encode(pg_fetch_result($res_vr_qtd_col,0,peca));
}
//echo $qtd_coluna;

//BUSCA RESULTADO
$busca_resultados_dados = "select
				distinct(tmp_os.os) AS os ,
				tmp_os.serie AS serie,
				tmp_os.data_nf AS data_nf,
				tmp_os.defeito_reclamado_descricao AS defeito_reclamado_descricao,
				tmp_os.defeito_constatado AS defeito_constatado,
				tmp_os.descricao_defeito_constatado AS descricao_defeito_constatado,
				tmp_os.solucao_os AS solucao_os,
				tmp_os.descricao_solucao_os AS descricao_solucao_os,
				tmp_os.posto AS posto,
				tmp_os.descricao_posto AS descricao_posto,
				tmp_os.consumidor_estado AS consumidor_estado,
				tmp_os.os_reincidente AS os_reincidente,
				TO_CHAR(tmp_os.data_pesquisa,'YYYY-MM-DD')  AS data_pesquisa,
				tmp_os.data_fabricacao AS data_fabricacao,
				(SELECT ARRAY(SELECT tmp_peca.peca|| '||' ||tmp_peca.descricao|| '||' ||tmp_peca.qtde|| '||' ||tmp_peca.servico_realizado|| '||' ||tmp_peca.descricao_servico_realizado|| '??'  FROM tmp_relatorio_os_serie_reincidente_peca_$login_admin tmp_peca
				WHERE tmp_os.os = tmp_peca.os)) AS pecas
				FROM  tmp_relatorio_os_serie_reincidente_os_$login_admin AS tmp_os
							order by tmp_os.os";
//echo nl2br($busca_resultados_dados);
//echo "<br><br><br><br><br><br><br><br><br><br>";exit;
$res_dados_os_2 = pg_query($con,$busca_resultados_dados);
if(pg_num_rows($res_dados_os_2) > 0) {



//MONTA TABELA
if($data_verifica_excel == 't'){
?>
<table>
	<tr>
		<td width="18" bgcolor="CC9900"></td><td align="left"><font size="1">OS Reincidente</font></td>
	</tr>
</table>
<?php
}
	fputs ($fp,'<table><tr><td width="18" bgcolor="CC9900"></td><td align="left"><font size="4" color="#1C1C1C"><b>OS Reincidente</b></font></td></tr><tr><td><br></td></tr></table>');


if($data_verifica_excel == 't') {
?>
<table class="formulario_resultado tablesorter" width="80%" align="center" cellspacing="1" id="relatorio">
		<thead>
			<tr class="titulo_coluna">
				<th nowrap>Número de Série</th>
				<th nowrap>Data da NF</th>
				<th nowrap>OS</th>
				<th nowrap><?php echo $and_data_do_filtro;?></th>
				<?php
					if($periodo != 3)
						echo "<th nowrap>Data da Fabricação</th>";
				 ?>
				<th nowrap>Defeito Reclamado</th>
				<th nowrap>Defeito Constatado</th>
				<th nowrap>Solução</th>
				<th nowrap>Posto</th>
				<th nowrap>Estado</th>
				<th nowrap>Cidade</th>
				<?php
}
				if($periodo != 3) {
					$mostra_data_fabricacao = "<th nowrap><font color='#FFFFFF'>Data da Fabricação</font></th>";
				}
				fputs($fp,"<table class='formulario_resultado tablesorter' width='80%' align='center' cellspacing='1' id='relatorio' bgcolor='#596D9B' BORDERCOLOR='#CFCFCF' border='1'>
				<thead>
					<tr class='titulo_coluna'>
						<th nowrap><font color='#FFFFFF'>Número de Série</font></th>
						<th nowrap><font color='#FFFFFF'>Data da NF</font></th>
						<th nowrap><font color='#FFFFFF'>OS</font></th>
						<th nowrap><font color='#FFFFFF'>{$and_data_do_filtro}</font></th>
						{$mostra_data_fabricacao}
						<th nowrap><font color='#FFFFFF'>Defeito Reclamado</font></th>
						<th nowrap><font color='#FFFFFF'>Defeito Constatado</font></th>
						<th nowrap><font color='#FFFFFF'>Solução</font></th>
						<th nowrap><font color='#FFFFFF'>Posto</font></th>
						<th nowrap><font color='#FFFFFF'>Estado</font></th>
						<th nowrap><font color='#FFFFFF'>Cidade</font></th>");

						for ($d=0; $d < $qtd_coluna; $d++) {
							//echo $d;
							if($data_verifica_excel == 't') {
						?>
							<th nowrap>Peça</th>
							<th nowrap>Quantidade</th>
							<th nowrap>Serviço Realizado</th>
						<?php
							}
							fputs ($fp,'<th nowrap><font color="#FFFFFF">Peça</font></th><th nowrap><font color="#FFFFFF">Quantidade</font></th><th nowrap><font color="#FFFFFF">Serviço Realizado</font></th>');
						}
						echo "<br>";
						?>
				</tr>
			</thead>
			<tbody>

<?php
fputs($fp,'</tr></thead><tbody>');
	for($p=0; $p<pg_numrows($res_dados_os_2); $p++) {
		$serie			     = "";
		$data_nf		     = "";
		$os			     = "";
		$defeito_constatado	     = "";
		$desc_defeito_constatado     = "";
		$solucao_os		     = "";
		$desc_solucao_os	     = "";
		$posto			     = "";
		$descricao_posto	     = "";
		$consumidor_estado	     = "";
		$os_reincidente		     = "";
		$data_fabricacao_pesquisa    = "";
		$peca			     = "";
		$serie			     = pg_fetch_result($res_dados_os_2,$p,serie);
		$data_nf		     = pg_fetch_result($res_dados_os_2,$p,data_nf);
		$os			     = pg_fetch_result($res_dados_os_2,$p,os);
		$defeito_reclamado_descricao = pg_fetch_result($res_dados_os_2,$p,defeito_reclamado_descricao);
		$defeito_constatado	     = pg_fetch_result($res_dados_os_2,$p,defeito_constatado);
		$desc_defeito_constatado     = pg_fetch_result($res_dados_os_2,$p,descricao_defeito_constatado);
		$solucao_os		     = pg_fetch_result($res_dados_os_2,$p,solucao_os);
		$desc_solucao_os	     = pg_fetch_result($res_dados_os_2,$p,descricao_solucao_os);
		$posto			     = pg_fetch_result($res_dados_os_2,$p,posto);
		$descricao_posto	     = pg_fetch_result($res_dados_os_2,$p,descricao_posto);
		$consumidor_estado	     = pg_fetch_result($res_dados_os_2,$p,consumidor_estado);
		$os_reincidente		     = pg_fetch_result($res_dados_os_2,$p,os_reincidente);
		$data_fabricacao_pesquisa    = @pg_fetch_result($res_dados_os_2,$p,data_pesquisa);
		$peca			     = @pg_fetch_result($res_dados_os_2,$p,pecas);

		$data_fabricacao        = utf8_encode(pg_fetch_result($res_dados_os_2,$p,data_fabricacao));
		$data_fabricacao	= preg_replace('/(\d{4}).(\d{2}).(\d{2})/','$3/$2/$1',utf8_decode($data_fabricacao));

		$rl_aux_data_nf	= preg_replace('/(\d{4}).(\d{2}).(\d{2})/','$3/$2/$1',utf8_decode($data_nf));

		$rl_aux_data_fabricacao_pesquisa	= preg_replace('/(\d{4}).(\d{2}).(\d{2})/','$3/$2/$1',utf8_decode($data_fabricacao_pesquisa));

		$background_os_reincidente ="";
		if($os_reincidente == 't'){
			$background_os_reincidente = "style='background-color: #C90';";
		}

		$cor_excel   = ($p % 2 == 0) ? "#F0F0F6" : "#FFFFFF";
		if($data_verifica_excel == 't') {
		?>
			<tr>
				<td nowrap <?php echo $background_os_reincidente;?>>&nbsp;<?php echo $serie;?></td>
				<td nowrap <?php echo $background_os_reincidente;?>>&nbsp;<?php echo $rl_aux_data_nf;?></td>
				<td nowrap <?php echo $background_os_reincidente;?>>&nbsp;<?php echo $os;?></td>
				<td nowrap <?php echo $background_os_reincidente;?>>&nbsp;<?php echo $rl_aux_data_fabricacao_pesquisa;?></td>
				<?php
					if($periodo != 3) {
						echo "<td nowrap {$background_os_reincidente}>{$data_fabricacao}</td>";
					}
				 ?>
				<td nowrap <?php echo $background_os_reincidente;?>>&nbsp;<?php echo $defeito_reclamado_descricao;?></td>
				<td nowrap <?php echo $background_os_reincidente;?>>&nbsp;<?php echo $defeito_constatado."-".$desc_defeito_constatado;?></td>
				<td nowrap <?php echo $background_os_reincidente;?>>&nbsp;<?php echo $solucao_os."-".$desc_solucao_os;?></td>
				<td nowrap <?php echo $background_os_reincidente;?>>&nbsp;<?php echo $posto."-".$descricao_posto;?></td>
				<td nowrap <?php echo $background_os_reincidente;?>>&nbsp;<?php echo $consumidor_estado;?></td>
				<td nowrap <?php echo $background_os_reincidente;?>>&nbsp;<?php echo $consumidor_cidade;?></td>
		<?php
		}

			if($periodo != 3) {
				$mostra_data_fabricacao = "<td nowrap {$background_os_reincidente} ><font size='11px' color='#3D3D3D'> &nbsp;{$data_fabricacao}</fonbt></td>";
			}

			fputs($fp,"<tr boder='1' bgcolor='{$cor_excel}'>
				<td nowrap {$background_os_reincidente} ><font size='11px' color='#3D3D3D'> &nbsp;{$serie}</fonbt></td>
				<td nowrap {$background_os_reincidente} ><font size='11px' color='#3D3D3D'> &nbsp;{$rl_aux_data_nf}</fonbt></td>
				<td nowrap {$background_os_reincidente} ><font size='11px' color='#3D3D3D'> &nbsp;{$os}</fonbt></td>
				<td nowrap {$background_os_reincidente} ><font size='11px' color='#3D3D3D'> &nbsp;{$rl_aux_data_fabricacao_pesquisa}</fonbt></td>
				{$mostra_data_fabricacao}
				<td nowrap {$background_os_reincidente} ><font size='11px' color='#3D3D3D'> &nbsp;{$defeito_reclamado_descricao}</fonbt></td>
				<td nowrap {$background_os_reincidente} ><font size='11px' color='#3D3D3D'> &nbsp;{$defeito_constatado} - {$desc_defeito_constatado}</fonbt></td>
				<td nowrap {$background_os_reincidente} ><font size='11px' color='#3D3D3D'> &nbsp;{$solucao_os} - {$desc_solucao_os}</fonbt></td>
				<td nowrap {$background_os_reincidente} ><font size='11px' color='#3D3D3D'> &nbsp;{$posto} - {$descricao_posto}</fonbt></td>
				<td nowrap {$background_os_reincidente} ><font size='11px' color='#3D3D3D'> &nbsp;{$consumidor_estado}</fonbt></td>
				<td nowrap {$background_os_reincidente} ><font size='11px' color='#3D3D3D'> &nbsp;{$consumidor_cidade}</fonbt></td>");



				if(count($peca) > 0) {
					$peca = str_replace('{','',$peca);
					$peca = str_replace('}','',$peca);
					$peca = str_replace('"','',$peca);

					$valores_peca = "";
					$valores_peca = explode("??,", $peca);
					$cont_registro=0;
					$valor = "";
					//print_r($valores_peca);
					//echo "<br>";
					foreach($valores_peca as $indice => $valor) {
						//echo "TESTE";

						$aux_valor_peca =  explode("||", $valor);

						$cod_peca			= "";
						$peca_descricao			= "";
						$qtde				= "";
						$servico_realizado		= "";
						$descricao_servico_realizado	= "";
						$cod_peca			= $aux_valor_peca[0];
						$peca_descricao			= $aux_valor_peca[1];
						$qtde				= $aux_valor_peca[2];
						$servico_realizado		= $aux_valor_peca[3];
						$descricao_servico_realizado	= $aux_valor_peca[4];

						$descricao_servico_realizado = str_replace('??','',$descricao_servico_realizado);
						//echo $cod_peca." == ".$peca_descricao." == ".$qtde." == ".$servico_realizado." == ".$descricao_servico_realizado."<br>";
						if($data_verifica_excel == 't'){
						?>
							<td nowrap <?php echo $background_os_reincidente;?>>&nbsp;<?php echo $cod_peca."-".$peca_descricao;?></td>
							<td nowrap <?php echo $background_os_reincidente;?>>&nbsp;<?php echo $qtde;?></td>
							<td nowrap <?php echo $background_os_reincidente;?>>&nbsp;<?php echo $servico_realizado."-".$descricao_servico_realizado;?></td>
						<?
						}
							fputs($fp,'<td nowrap '.$background_os_reincidente.'>&nbsp;<font  size="11px" color="#3D3D3D">'.$cod_peca.' - '.$peca_descricao.'</fonbt></td>
							<td nowrap '.$background_os_reincidente.'>&nbsp;<font size="11px"  color="#3D3D3D">'.$qtde.'</fonbt></td>
							<td nowrap '.$background_os_reincidente.'>&nbsp;<font size="11px" color="#3D3D3D">'.$servico_realizado.' - '.$descricao_servico_realizado.'</font></td>');


						//echo "PEÇA -".$cod_peca."=====QTD-".$qtde."=====SERV-".$servico_realizado."======INDICE -".$indice;
						$cont_registro++;
						//echo "<br>";
					}

				}

				//echo "<br>";
				//echo "OS =".$os." == CONTADOR =".$cont_registro." == QTD COLUNA =".$qtd_coluna."<br>";
				if($cont_registro < $qtd_coluna){
					for($f=$cont_registro; $f < $qtd_coluna; $f++) {
						//echo $f."<br>";
						if($data_verifica_excel == 't'){
						?>
							<td nowrap <?php echo $background_os_reincidente;?>>&nbsp;</td>
							<td nowrap <?php echo $background_os_reincidente;?>>&nbsp;</td>
							<td nowrap <?php echo $background_os_reincidente;?>>&nbsp;</td>
						<?php
						}
							fputs($fp,'<td nowrap '.$background_os_reincidente.'>&nbsp;</td>
							<td nowrap '.$background_os_reincidente.'>&nbsp;</td>
							<td nowrap '.$background_os_reincidente.'>&nbsp;</td>');
					}
				}

			}

			if(file_exists($caminho_arquivo)) {
				echo "<br>";
				echo "<table width='700px' border='0' cellspacing='2' cellpadding='2' align='center'>";
				echo "<tr>";
				echo "<td align='center'><button type='button' onclick=\"window.location='$caminho_pasta'\">Download em Excel</button></td>";
				echo "</tr>";
				echo "</table>";
			}

		}else{
			echo "<label style='font: 14px Arial;'><br>Nenhum resultado encontrado</label>";
		}

	if($data_verifica_excel == 't'){
	?>
	</tr>
	<?php
	fputs($fp,'</tr>');
	}

//}

//else{
//	echo "<label style='font: 14px Arial;'><br>Nenhum resultado encontrado</label>";
//}

if($data_verifica_excel == 't'){
?>
	</tbody>
</table>
<?php
}
fputs($fp,'</tbody></table>');

}
include "rodape.php" ;
?>

<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_admin.php";

include 'funcoes.php';

$title = "RELAÇÃO DE PEDIDO DE PEÇAS";
$layout_menu = 'callcenter';
include "cabecalho.php";

$admin_privilegios="call_center";
function valida_data($data){
	$data_separada=explode("/",$data);
	return  checkdate($data_separada[1],$data_separada[0],$data_separada[2]); 
} 

$erro = "";


if (strlen($_POST['btn_acao_pesquisa']) > 0)
	$btn_acao_pesquisa = $_POST['btn_acao_pesquisa'];

if ($btn_acao_pesquisa == 'continuar' OR isset($_GET['pagina'])){
	$query = "SELECT  tbl_faturamento.faturamento,
					to_char(tbl_faturamento.emissao, 'DD/MM/YYYY') AS emissao,
					to_char(tbl_faturamento.saida, 'DD/MM/YYYY') AS saida,
					to_char(tbl_faturamento.previsao_chegada, 'DD/MM/YYYY') AS previsao_chegada,
					to_char(tbl_faturamento.cancelada, 'DD/MM/YYYY') AS cancelada,
					trim(tbl_faturamento.nota_fiscal::text) AS nota_fiscal,
					trim(tbl_faturamento.total_nota::text) AS total_nota,
					tbl_faturamento.serie,
					tbl_faturamento.transp,
					tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto,
					tbl_condicao.descricao AS condicao
			FROM         tbl_faturamento
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_faturamento.posto AND tbl_posto_fabrica.fabrica=$login_fabrica
			JOIN    tbl_posto         ON tbl_posto.posto =  tbl_faturamento.posto
			LEFT JOIN    tbl_condicao       USING (condicao)
			WHERE   tbl_faturamento.distribuidor IS NULL AND tbl_faturamento.fabrica = $login_fabrica ";
	
	$caracteres = array(".", "-", "/"); //lista de caracteres a serem retidados da nota fiscal
	
	if (isset($_GET["pagina"])){
		$data_inicial = $aux_data_inicial	= trim($_GET["data_inicial_01"]);
		$data_final   = $aux_data_final	= trim($_GET["data_final_01"]);
		$codigo_posto					= trim($_GET['codigo_posto']);
		$nf							= trim($_GET['nf']);
		$nf							= str_replace($caracteres,"", $nf); //se tiver caracter esp., retira-os
	}else{
		$data_inicial = $aux_data_inicial	= trim($_POST["data_inicial_01"]);
		$data_final   = $aux_data_final	= trim($_POST["data_final_01"]);
		$codigo_posto					= trim($_POST['codigo_posto']);
		$nf							= trim($_POST['nf']);
		$nf							= str_replace($caracteres,"", $nf); //se tiver caracter esp., retira-os
	}

	if($data_inicial){
		$dat = explode ("/", $data_inicial);
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if( (!checkdate($m,$d,$y) && $data_inicial != "") ){
			$erro ="Data Inválida";
		}
	}
	
	if($data_final){
		$dat = explode ("/", $data_final);
		$d = $dat[0];
		$m = $dat[1];
		$y = $dat[2];
		if( (!checkdate($m,$d,$y) && $data_final != "") ){
			$erro = "Data Inválida.";
		}
	}


//Converte data para comparação
	$d_ini = explode ("/", $data_inicial);//tira a barra
	$nova_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...
	
	$d_fim = explode ("/", $data_final);//tira a barra
	$nova_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

	if($nova_data_inicial > $nova_data_final){
			$erro="Data Inválida";
		}
		
	// arruma as datas iniciais e finais para a pesquisa
	$aux_data_inicial	= ($aux_data_inicial{0}=='d')?"":$aux_data_inicial;
	$aux_data_final	= ($aux_data_final{0}=='d')?"":$aux_data_final;
	$aux_data_inicial	= (is_numeric($aux_data_inicial{0}))?$aux_data_inicial:"";
	$aux_data_final	= (is_numeric($aux_data_final{0}))?$aux_data_final:"";

	if (strlen($aux_data_inicial)>0 AND strlen($aux_data_final>0)) {
		if (valida_data($aux_data_inicial) AND valida_data($aux_data_final)){
			$aux_data_inicial	= formata_data($aux_data_inicial)." 00:00:00";
			$aux_data_final	= formata_data($aux_data_final)." 23:59:59";
			$query .= " AND tbl_faturamento.saida BETWEEN '$aux_data_inicial' AND '$aux_data_final' ";
		}
		
		
		//Nessa tela em especifico a data pode ser omitida
		//else 
		//	$erro = "Data inválida";
		//}
	}else {
		if (strlen($aux_data_inicial)>0 OR strlen($aux_data_final>0)) {
			if (strlen($aux_data_final>0))	$erro = "Data inválida";
			else						$erro = "Data inválida";
		}
	}

	if (strlen($codigo_posto)>0)
		$query .= " AND tbl_posto_fabrica.codigo_posto   = '$codigo_posto' ";
	
	if (strlen($nf) > 0) 
		$query .= " AND tbl_faturamento.nota_fiscal ILIKE '%$nf' ";
	
	$query .= "ORDER BY tbl_faturamento.emissao DESC, tbl_faturamento.nota_fiscal DESC";
	
	if (strlen($aux_data_inicial)==0 AND strlen($codigo_posto)==0 AND strlen($nf)==0){
		$erro ="Por favor, informe algum campo para a pesquisa.";
	}
	
	

// if ($ip=='201.71.54.144') echo "<br><br><hr>$query<hr>";


}
?>
<script language="JavaScript">

function date_onkeydown() {
  if (window.event.srcElement.readOnly) return;
  var key_code = window.event.keyCode;
  var oElement = window.event.srcElement;
  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
        var d = new Date();
        oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                         String(d.getDate()).padL(2, "0") + "/" +
                         d.getFullYear();
        window.event.returnValue = 0;
    }
    if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
        if ((key_code > 47 && key_code < 58) ||
          (key_code > 95 && key_code < 106)) {
            if (key_code > 95) key_code -= (95-47);
            oElement.value =
                oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
        }
        if (key_code == 8) {
            if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                oElement.value = "dd/mm/aaaa";
            oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                function ($0, $1, $2) {
                    var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                    if (idx >= 5) {
                        return $1 + "a" + $2;
                    } else if (idx >= 2) {
                        return $1 + "m" + $2;
                    } else {
                        return $1 + "d" + $2;
                    }
                } );
            window.event.returnValue = 0;
        }
    }
    if (key_code != 9) {
        event.returnValue = false;
    }
}


function SomenteNumero(e){
    var tecla=(window.event)?event.keyCode:e.which;
    if((tecla > 47 && tecla < 58)) 
		return true;
    else{
		if (tecla != 8) 
			return false;
		else 
			return true;
    }
}
</script>
<?
include "javascript_pesquisas.php"; 
?>

<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<?php include "../js/js_css.php";?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial_01').datepick({startDate:'01/01/2000'});
		$('#data_final_01').datepick({startDate:'01/01/2000'});
		$("#data_inicial_01").mask("99/99/9999");
		$("#data_final_01").mask("99/99/9999");
	});
</script>

<style>
.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
	}

.Titulo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-weight: bold;
	color:#ffffff;
	border: 1px solid;	
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
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
</style>

<!-- AQUI COMEï¿½ O SUB MENU - ï¿½EA DE CABECALHO DOS RELATï¿½IOS E DOS FORMULï¿½IOS -->

<p>
<form name='frm_pesquisa' action='<? echo $PHP_SELF; ?>' method='post'>
<table class='formulario' width='700px' cellspacing='0'  cellpadding='2' align='center'>
	<?
	if (strlen($erro)>0){ ?>
		<tr class='msg_erro'>
			<td colspan='4'>
				<? echo "<b>$erro</b>"; ?>
			</td>
		</tr>
	<?	
	}
	?>
	<tr >
		<td class="titulo_tabela" colspan="4" >Parâmetros de Pesquisa</td>
	</tr>
	<tr><td>&nbsp;</td></tr>
	<tr class="formulario" align='center'>
		<td class='formulario' align='right'>Data Inicial de Saída</td>
		<td align='left'>
			<input class='frm' size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" id="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
		</td>
		<td class="formulario" align='right'>Data Final de Saída</td>
		<td class='formulario' align='left'>
			<input size="12" class='frm' maxlength="10" TYPE="text" NAME="data_final_01" id="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final;?>">
		</td>
	</tr>
	<tr class="formulario"align='left'>
		<td align='right'>Posto</td>
		<td>
			<input type="text" name="codigo_posto" size="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_extrato.codigo_posto, document.frm_extrato.nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo cï¿½igo" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.nome, 'codigo')">
		</td>
		<td align='right'>Nome do Posto</td>
		<td>
			<input type="text" name="nome" size="30" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.nome, 'nome');" <? } ?> value="<?echo $nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo cï¿½igo" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.nome, 'nome')">
		</td>
	</tr>
	<tr class='formulario' align='Left'>
		<td align='right'>Número da NF&nbsp;</td>
		<td align='left'>
			<input type='text' name='nf' value='' class="frm" onkeypress="javascript: return SomenteNumero(event)">
		</td>
	</tr>

	<tr class='foormulario'>
		<td colspan='4'>
			<br />
			<input type='submit' value='Pesquisar' name='listar' onclick="javascript: if (document.frm_pesquisa.btn_acao_pesquisa.value == '' ) { document.frm_pesquisa.btn_acao_pesquisa.value='continuar' ; document.frm_pesquisa.submit() } else { alert ('Aguarde submissão') }" border='0' style='cursor: pointer'>
			<input type='hidden' name='btn_acao_pesquisa' value=''>
		</td>
	</tr>
	
</table>
</form>

<?

if (strlen($erro)==0 AND ($btn_acao_pesquisa == 'continuar' OR isset($_GET['pagina']))){
	
//	echo nl2br($sql);
	$sqlCount  = "SELECT count(*) FROM (";
	$sqlCount .= $query;
	$sqlCount .= ") AS count";
	
	// ##### PAGINACAO ##### //
	require "_class_paginacao.php";
	
	// definicoes de variaveis
	$max_links = 11;				// mï¿½imo de links ï¿½serem exibidos
	$max_res   = 30;				// mï¿½imo de resultados ï¿½serem exibidos por tela ou pagina
	$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
	$mult_pag->num_pesq_pag = $max_res; // define o nmero de pesquisas (detalhada ou nï¿½) por pï¿½ina

	
	$res = $mult_pag->executar($query, $sqlCount, $con, "otimizada", "pgsql");
	
	// ##### PAGINACAO ##### //

	if (@pg_numrows($res) > 0) {
		echo "<table width='700px' border='0' cellpadding='0' cellspacing='0' align='center' bgcolor='#ffffff'>";
		echo "<tr>";
		echo "<td><img height='1' width='20' src='imagens/spacer.gif'></td>";
		echo "<td valign='top' align='center'>";
		
		echo "<p>";
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$cor = "#F7F5F0";
			if ($i % 2 == 0) $cor = '#F1F4FA';
			
			$faturamento		= trim(pg_result($res,$i,faturamento));
			$emissao			= trim(pg_result($res,$i,emissao));
			$saida			= trim(pg_result($res,$i,saida));
			$previsao_chegada	= trim(pg_result($res,$i,previsao_chegada));
			$cancelada		= trim(pg_result($res,$i,cancelada));
			$nota_fiscal		= trim(pg_result($res,$i,nota_fiscal));
			$total_nota		= trim(pg_result($res,$i,total_nota));
			$serie			= trim(pg_result($res,$i,serie));
			$condicao		= trim(pg_result($res,$i,condicao));
			$codigo_posto		= trim(pg_result($res,$i,codigo_posto));
			$nome			= trim(pg_result($res,$i,nome));
			$transp			= strtoupper (trim(pg_result($res,$i,transp)));
			
			if ($i == 0) {
				echo "<table width='700px' border='0' cellspacing='2' cellpadding='0' align='center'>";
				echo "<tr height='20' class='titulo_coluna'>";
				echo "<td align='center'>Posto</td>";
				echo "<td align='center'>Nota Fiscal</td>";
				echo "<td align='center'>Série</td>";
				echo "<td align='center'>Transportadora</td>";
				echo "<td align='center'>Tipo</td>";
				echo "<td align='center'>Emissão</td>";
				echo "<td align='center'>Saída</td>";
				echo "<td align='center'>Total Nota</td>";
				echo "</tr>";
			}
			
			if ( strlen ($cancelada) > 0) $cor = '#FF6633';

			echo "<tr bgcolor='$cor' align='center'>";
			echo "<td ><font size='1' face='Geneva, Arial, Helvetica, san-serif' ><acronym title='$nome'>".substr($codigo_posto." - ".$nome,0,13)."</acronym></font></td>";
			echo "<td align='center'>" ;
			if (strlen ($cancelada) == 0) {
				echo "<a href='nf_detalhe_britania.php?faturamento=$faturamento&codigo_posto=$codigo_posto'>";
				echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif'>$nota_fiscal</font>";
				echo "</a>";
			}else{
				echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif'>$nota_fiscal (cancelada)</font>";
			}
			echo "</td>";

			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$serie</font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$transp</font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$condicao</font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$emissao</font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>$saida</font></td>";
			echo "<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif'>". number_format($total_nota,2,",",".") ."</font></td>";
			echo "</tr>";
		}
		echo "</table>";
		
		echo "</td>";
		echo "<td><img height='1' width='16' src='imagens/spacer.gif'></td>";
		
		echo "</tr>";
		echo "</table>";
		
		// ##### PAGINACAO ##### //
		// links da paginacao
		echo "<br>";
		
		echo "<div>";
		
		if($pagina < $max_links) { 
			$paginacao = pagina + 1;
		}else{
			$paginacao = pagina;
		}
		
		// paginacao com restricao de links da paginacao
		
		// pega todos os links e define que 'Prï¿½ima' e 'Anterior' serï¿½ exibidos como texto plano
		$todos_links		= $mult_pag->Construir_Links("strings", "sim");
		
		// funï¿½o que limita a quantidade de links no rodape
		$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);
		
		for ($n = 0; $n < count($links_limitados); $n++) {
			echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
		}
		
		echo "</div>";
		
		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ( $pagina * $max_res);
		$registros         = $mult_pag->Retorna_Resultado();
		
		$valor_pagina   = $pagina + 1;
		$numero_paginas = intval(($registros / $max_res) + 1);
		
		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;
		
		if ($registros > 0){
			echo "<br>";
			echo "<div>";
			echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
			echo "<font color='#cccccc' size='1'>";
			echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
			echo "</font>";
			echo "</div>";
		}
		// ##### PAGINACAO ##### //
	}else{
		echo "<p>";
		
		echo "<table width='600' border='0' cellpadding='2' cellspacing='2' align='center'>";
		echo "<tr>";
		
		echo "<td valign='top' align='center'>";
		echo "<h4>Não foram encontradas Notas Fiscais para esta consulta.</h4>";
		echo "</td>";
		
		echo "</tr>";
		echo "</table>";
	}
}
?>

<p>

<? include "rodape.php"; ?>

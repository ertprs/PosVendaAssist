<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';
include "funcoes.php";

$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q)>2){

		if ($tipo_busca=="posto"){
			$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

			$sql .= ($busca == "codigo") ? " AND tbl_posto_fabrica.codigo_posto = '$q' " : " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";

			$res = pg_query($con,$sql);
			if (pg_num_rows ($res) > 0) {
				for ($i=0; $i<pg_num_rows ($res); $i++ ){
					$cnpj = trim(pg_fetch_result($res,$i,cnpj));
					$nome = trim(pg_fetch_result($res,$i,nome));
					$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
					echo "$cnpj|$nome|$codigo_posto";
					echo "\n";
				}
			}
		}
	}
	exit;
}

if($_GET['ajax']=='consulta') {

	$data_inicial = $_GET['data_inicial'];
	$data_final = $_GET['data_final'];
	$codigo_posto = $_GET['codigo_posto'];

	if(empty($data_inicial) || empty($data_final)){
		$msg_erro = "Data Inválida";
    }
	
	if(strlen($msg_erro)==0){
		list($di, $mi, $yi) = explode("/", $data_inicial);
		if(!checkdate($mi,$di,$yi)){
			$msg_erro = "Data Inválida";
		}
	}
	if(strlen($msg_erro)==0){
		list($df, $mf, $yf) = explode("/", $data_final);
		if(!checkdate($mf,$df,$yf)){
			$msg_erro = "Data Inválida";
		}
	}

	if(strlen($msg_erro)==0){
		$aux_data_inicial = "$yi-$mi-$di";
		$aux_data_final = "$yf-$mf-$df";
	}
	
	
	if(strlen($msg_erro)==0){
		if(strtotime($aux_data_final) < strtotime($aux_data_inicial) 
		or strtotime($aux_data_inicial) > strtotime('today')){
			$msg_erro = "Data Inválida.";
		}
	}
	if (strlen($codigo_posto) > 0  && strlen($msg_erro) == 0) {
		$sql =	"SELECT tbl_posto.posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica USING (posto)
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
				AND   tbl_posto_fabrica.codigo_posto = '$codigo_posto';";
		$res = pg_query($con,$sql);
		if (pg_num_rows($res) == 1) {
			$posto        = trim(pg_fetch_result($res,0,posto));
		}else{
			$msg_erro = " Posto não encontrado. ";
		}
	}

	if(strlen($msg_erro) == 0) {
		$sql = " SELECT auditoria_online,
						tbl_posto.nome,
						tbl_posto_fabrica.codigo_posto,
						tbl_auditoria_online.visita_posto,
						to_char(tbl_auditoria_online.data_pesquisa,'DD/MM/YYYY') as data_pesquisa,
						to_char(tbl_auditoria_online.data_visita,'DD/MM/YYYY') as data_visita,
						tbl_admin.nome_completo
				FROM    tbl_auditoria_online
				JOIN tbl_posto USING(posto)
				JOIN tbl_admin USING(admin)
				JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE   tbl_auditoria_online.fabrica= $login_fabrica
				AND     data_digitacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";

		if(strlen($posto) > 0) {
			$sql .= " AND tbl_auditoria_online.posto = $posto ";
		}

		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){

			$resposta  .=  '<table align="center" width="700" cellspacing="1" class="tabela">';
			$resposta  .= "<thead>";
			$resposta  .= "<tr class='titulo_coluna'>";
			$resposta  .= "<Th><b>Posto</b></Th>";
			$resposta  .= "<th><b>Inspetor</b></th>";
			$resposta  .= "<th><b>Visita Posto</b></th>";
			$resposta  .= "<th><b>Data Visita</b></th>";
			$resposta  .= "<th><b>Data Pesquisa</b></th>";
			$resposta  .= "<th><b>Consultar</b></th>";
			$resposta  .= "</tr>";
			$resposta  .= "</thead>";
			$resposta  .= "<tbody>";
			$resultados = pg_fetch_all($res);

			foreach ($resultados as $resultado){

				$cor = ($cor == '#F7F5F0') ? '#F1F4FA' : '#F7F5F0';

				$visita_posto =($resultado['visita_posto'] =='t') ? "SIM" : "NÃO";

				$resposta  .=  "<tr bgcolor='$cor' >";
				$resposta  .=  "<td align='center'nowrap>".$resultado['codigo_posto']."-".$resultado['nome']."</td>";
				$resposta  .=  "<td align='center'nowrap>".$resultado['nome_completo']."</td>";
				$resposta  .=  "<td align='center'nowrap>".$visita_posto."</td>";
				$resposta  .=  "<td align='center'nowrap>".$resultado['data_visita']."</td>";
				$resposta  .=  "<td align='center'nowrap>".$resultado['data_pesquisa']."</td>";
				$resposta  .=  "<td align='center'nowrap>";
				$resposta  .=  "<input type='button' onclick=\"window.open('auditoria_online_detalhe.php?auditoria_online=".$resultado['auditoria_online']."');\" value='Ver'>";
				$resposta  .=  "</td>"; 
				$resposta  .=  "</TR>";
			}
			$resposta .="</tbody>";
			$resposta .= " </TABLE>";

		}else{
			$msg_erro = "Não foram Encontrados Resultados para esta Pesquisa";
		}
	}

	echo (strlen($msg_erro) > 0) ? "erro|$msg_erro" : "ok|$resposta";
	exit;
}

$title = "CONSULTA DE RELATÓRIO DE AUDITORIA ONLINE";
$layout_menu = "auditoria";
include 'cabecalho.php';
?>
<?php include "../js/js_css.php"; ?>
<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<script language="javascript" src="js/effects.explode.js"></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />

<script language="JavaScript">
	$(function(){
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	});
	$().ready(function() {

		function formatItem(row) {
			return row[2] + " - " + row[1];
		}

		/* Busca pelo Código */
		$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=codigo'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[2];}
		});

		$("#codigo_posto").result(function(event, data, formatted) {
			$("#posto_nome").val(data[1]) ;
		});

		/* Busca pelo Nome */
		$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?tipo_busca=posto&busca=nome'; ?>", {
			minChars: 3,
			delay: 150,
			width: 350,
			matchContains: true,
			formatItem: formatItem,
			formatResult: function(row) {return row[1];}
		});

		$("#posto_nome").result(function(event, data, formatted) {
			$("#codigo_posto").val(data[2]) ;
			//alert(data[2]);
		});

	})

	function Exibir () {
		var var1 = document.frm_consulta.data_inicial.value;
		var var2 = document.frm_consulta.data_final.value;
		var var3 = document.frm_consulta.codigo_posto.value;

		$.ajax({
			type: "GET",
			url: "<?=$PHP_SELF?>",
			data: 'data_inicial='+var1+'&ajax=consulta'+'&data_final='+var2+'&codigo_posto='+var3,
			beforeSend: function(){
				$('#dados').html("&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='js/loadingAnimation.gif'> ");
				$('#dados').show();
			},
			complete: function(http) {
				results = http.responseText.split('|');
				if(results[0] == 'ok') {
					$('#dados').html(results[1]);
					$('#consulta').addClass('botao');
					$('#consulta').show();
					$('#erro').html('').removeClass('msg_erro');
				}else if(results[1] == 'Não foram Encontrados Resultados para esta Pesquisa'){
					$('#erro').html('').removeClass('msg_erro');
					$('#dados').html(results[1]);
					$('#consulta').addClass('botao');
					$('#consulta').show();
				}else{
					$('#erro').html(results[1]).addClass('msg_erro');
					$('#dados').html('');
					$('#consulta').addClass('botao');
					$('#consulta').show();
				}
			}
		});
	}

function fnc_pesquisa_posto2 (campo, campo2, tipo) {

    if (tipo == "codigo" ) {
        var xcampo = campo;
    }

    if (tipo == "nome" ) {
        var xcampo = campo2;
    }

    if (xcampo.value != "") {
        var url = "";
        url="posto_pesquisa_2.php?campo="+xcampo.value+"&tipo="+tipo+"&os=t";
        janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
        janela.codigo  = campo;
        janela.nome    = campo2;

        if ("<? echo $pedir_sua_os; ?>" == "t") {
            janela.proximo = document.frm_consulta.sua_os;
        }else{
            janela.proximo = document.frm_consulta.data_abertura;
        }
        janela.focus();
    }

    else{
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
    }
}
</script>

<style type="text/css">
.pesquisa {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 15px;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
.border {
	border: 1px solid #ced7e7;
}
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}
.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}
.espaco{
	padding-left:100px;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 14px Arial;
    color: #FFFFFF;
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
.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.titulo_coluna {
    background-color: #596D9B;
    color: #FFFFFF;
    font: bold 11px "Arial";
    text-align: center;
}

</style>

<div align="center">
	<div id='erro' style="width:700px"></div>
</div>
<form name="frm_consulta" method="post" action="<? echo $PHP_SELF ?>">
<table class='formulario' width='700' border='0' align='center'>
	<caption class='titulo_tabela' colspan='100%' align='center'>Parâmetros de Pesquisa</caption>
	<tbody>
		<tr>
			<td class="espaco">
				Data Inicial<br>
				<input type="text" name="data_inicial" id="data_inicial" size="15" maxlength="10" value="" class="frm">
			</td>
			<td>
				Data Final<br/>
				<input type="text" name="data_final" id="data_final" size="15" maxlength="10" value=""  class="frm">
			</td>
		</tr>	
		
		<tr class="subtitulo" align="center">
			<td colspan="2">
				Informações do Posto
			</td>
		</tr>
		
		<tr>
			<td class="espaco">
				Código Posto<br/>
				<input type='text' name='codigo_posto' id='codigo_posto' size='20' value='<? echo $codigo_posto ?>' class="frm">
				<img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código"  onclick="fnc_pesquisa_posto2(document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo')">
			</td>
			<td >Nome Posto<br>
			 <input type='text' name='posto_nome' id='posto_nome' size='50' value='<? echo $posto_nome ?>' class="frm">
			 <img src="../imagens/lupa.png" border="0" style="cursor:pointer" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="fnc_pesquisa_posto2(document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome')">
			</td>
		</tr>
	</tbody>
	<tfoot>
		<tr>
			<td colspan='2' align='center'>&nbsp;</td>
		</tr>
		<tr>
			<td colspan='2' align='center'>
				<input type='button' onclick="javascript:Exibir();" style="cursor:pointer " value='Pesquisar' id='consulta'>
			</td>
		</tr>
		<tr>
			<td colspan='2' align='center'>&nbsp;</td>
		</tr>
	</tfoot>
</table>
</form>
<div align="center">
	<div id='erro' style="width:700px"></div>
</div>
<p>
<div id='dados'></div>
<p>

<? include "rodape.php" ?>
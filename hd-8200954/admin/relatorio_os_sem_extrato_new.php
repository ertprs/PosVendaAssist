<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="financeiro";
include 'autentica_admin.php';

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_query($con,$sql);
		if (pg_num_rows ($res) > 0) {
			for ($i=0; $i<pg_num_rows ($res); $i++ ){
				$cnpj			= trim(pg_fetch_result($res,$i,cnpj));
				$nome			= trim(pg_fetch_result($res,$i,nome));
				$codigo_posto	= trim(pg_fetch_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}

if($_GET['ajax']=='sim') {

	if (strlen($_GET["mes"]) == 0)$erro .= "Favor informar o mês para pesquisa<br>";
	if (strlen($_GET["ano"]) == 0)$erro .= "Favor informar o ano para pesquisa<br>";


	$codigo_posto = trim($_GET["codigo_posto"]);
	$cond_1 = " 1=1 ";
	if(strlen($codigo_posto) > 0){
		$cond_1 = " tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
	}

	$estado = $_GET["estado"];
	$cond_2 = " 1=1 ";
	if(strlen($estado) > 0){
		$cond_2 = " tbl_posto_fabrica.contato_estado = '$estado' ";
	}

	if (strlen($erro) > 0) {
		$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg .= $erro;


	}else $listar = "ok";
	if ($listar == "ok") {
		$data_inicial = date("Y-m-01", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t", mktime(0, 0, 0, $mes, 1, $ano));

		$sql = "SELECT tbl_os.sua_os       ,
					tbl_os.os              ,
					tbl_os.mao_de_obra     ,
					tbl_os.consumidor_nome ,
					to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
					tbl_produto.referencia ,
					tbl_produto.descricao  ,
					tbl_os.posto           ,
					tbl_posto.nome         ,
					tbl_posto_fabrica.codigo_posto,
					tbl_os_extra.mao_de_obra_desconto
					FROM tbl_os
					JOIN tbl_produto USING(produto)
					JOIN tbl_os_extra USING(os)
					JOIN tbl_posto_fabrica USING(posto,fabrica)
					JOIN tbl_posto USING(posto)
					WHERE tbl_os.fabrica = $login_fabrica
					AND tbl_os_extra.extrato IS NULL
					AND NOT(tbl_os.data_fechamento IS NULL)
					AND NOT(tbl_os.finalizada IS NULL)
					AND tbl_os.excluida IS NOT TRUE
					AND tbl_os.data_fechamento BETWEEN '$data_inicial' AND '$data_final'
					AND $cond_1 
					AND $cond_2
					order by tbl_posto.nome,tbl_os.data_fechamento,tbl_os.sua_os";
					//order by tbl_posto_fabrica.codigo_posto,tbl_os.data_fechamento,tbl_os.sua_os";
		$res = pg_query ($con,$sql);

		if (pg_num_rows($res) > 0) {
			$total = 0;
			//$resposta  .= "<b>Resultado de pesquisa</b>";
			$resposta  .=  "<br><br>";
			$resposta  .=  "<table border='0' cellpadding='2' cellspacing='0' class='tabela'  align='center' width='1000px'>";

			for ($i=0; $i<pg_num_rows($res); $i++){
				$linha_posto = $posto;
				$os              = trim(pg_fetch_result($res,$i,'os'))             ;
				$sua_os          = trim(pg_fetch_result($res,$i,'sua_os'))         ;
				$referencia      = trim(pg_fetch_result($res,$i,'referencia'))     ;
				$descricao       = trim(pg_fetch_result($res,$i,'descricao'))      ;
				$consumidor_nome = trim(pg_fetch_result($res,$i,'consumidor_nome'));
				$data_fechamento = trim(pg_fetch_result($res,$i,'data_fechamento'));
				$mao_de_obra     = trim(pg_fetch_result($res,$i,'mao_de_obra'))    ;
				$posto           = trim(pg_fetch_result($res,$i,'posto'))    ;
				$nome            = trim(pg_fetch_result($res,$i,'nome'))    ;
				$codigo_posto    = trim(pg_fetch_result($res,$i,'codigo_posto'))    ;
				$mao_de_obra_desconto    = trim(pg_fetch_result($res,$i,'mao_de_obra_desconto'))    ;

				if (!empty($mao_de_obra_desconto) and $mao_de_obra > 0){
					$mao_de_obra = $mao_de_obra - $mao_de_obra_desconto;
				}
				
				$posto_anterior = ($i == 0) ?$posto: $posto_anterior;
			
				$cor = ($i % 2 )? '#F7F5F0':'#F1F4FA';

				$resposta  .= ($posto_anterior != $posto) ?  "<TR class='titulo_coluna'><TD align='left' nowrap colspan=4>Total Posto</TD><TD align='center' nowrap>".number_format($total_posto,2,",",".")."</TD></TR>":"";
				$resposta  .= ($posto_anterior != $posto) ?  "<TR style='border: none; background: none;'><TD colspan='5' style='border: none; background: none;'>&nbsp;</TD></TR>":"";
				
				$total_posto = ($posto_anterior != $posto) ? $mao_de_obra : $total_posto;
				$resposta  .= ($linha_posto <> $posto) ? "<TR class='subtitulo'><td colspan='100%' align='left'>$codigo_posto - $nome</td></tr>" : "";
				
				if($linha_posto <> $posto){
					$resposta  .=  "<TR class='titulo_coluna'>";
			            $resposta  .=  "<TD>OS</TD>";
			            $resposta  .=  "<TD>Consumidor</TD>";
                        $resposta  .=  "<TD>Produto</TD>";
			            $resposta  .=  "<TD>Data Fechamento</TD>";
			            $resposta  .=  "<TD>Valor M.O.</TD>";
			        $resposta  .=  "</TR>";
				}
				
				$resposta  .=  "<TR bgcolor='$cor'>";
					$resposta  .=  "<TD align='center'><a href='os_press.php?os=$os' target='_blank'>&nbsp;$sua_os</a></TD>";
					$resposta  .=  "<TD align='left'>&nbsp;$consumidor_nome</TD>";
					$resposta  .=  "<TD align='left'>&nbsp;$referencia - $descricao</TD>";
					$resposta  .=  "<TD align='center'>&nbsp;$data_fechamento</TD>";
					$resposta  .=  "<TD align='center'>&nbsp;".number_format($mao_de_obra,2,",",".")."</TD>";
				$resposta  .=  "</TR>";
				
				
				$total_posto += ($posto_anterior == $posto ) ? $mao_de_obra : 0 ;
				$total += $mao_de_obra;
				$posto_anterior = $posto;
			}
			
			if($cond_1 == " 1=1 "){
				$resposta  .=  "<TR class='titulo_coluna'><TD align='left' nowrap colspan=4>Total Posto</TD><TD align='center' nowrap>".number_format($total_posto,2,",",".")."</TD></TR>";
				$resposta  .=  "<TR style='border: none; background: none;'><TD colspan='5' style='border: none; background: none;'>&nbsp;</TD></TR>";
			}
			
			$resposta  .=  "<TR class='titulo_coluna'>";
				$resposta  .=  "<TD align='center' nowrap colspan=4>Total</TD>";
				$resposta  .=  "<TD align='center' nowrap>".number_format($total,2,",",".")."</TD>";
			$resposta  .=  "</TR>";
			
			$resposta .= " </TABLE>";

			$resposta .=  "<br>";
			$resposta .=  "<hr width='1000px'>";
			$resposta .=  "<br>";

		}else{
			$resposta .=  "<br>";
			$resposta .= "Não foram encontrados resultados para esta pesquisa";
		}
		$listar = "";

	}
	if (strlen($erro) > 0) {
		echo "no|".$msg;
	}else{
//		$resposta .=  "$sql";
		echo "ok|".$resposta;
	}
	exit;

	flush();

}

$layout_menu = "financeiro";
$title = "RELATÓRIO DE PREVISÃO DE MÃO DE OBRA";

include "cabecalho.php";

?>
<style>
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 10px;
	font-weight: normal;
}


.Caixa{
	BORDER-RIGHT: #6699CC 1px solid;
	BORDER-TOP: #6699CC 1px solid;
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid;
	BORDER-BOTTOM: #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid;
	BORDER-TOP: #990000 1px solid;
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid;
	BORDER-BOTTOM: #990000 1px solid;
	BACKGROUND-COLOR: #FF0000;
}
.Carregando{
	TEXT-ALIGN: center;
	BORDER-RIGHT: #aaa 1px solid;
	BORDER-TOP: #aaa 1px solid;
	FONT: 10pt Arial ;
	COLOR: #000000;
	BORDER-LEFT: #aaa 1px solid;
	BORDER-BOTTOM: #aaa 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
	margin-left:20px;
	margin-right:20px;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial" !important; 
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial" !important; 
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #63769F;
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
    width: 700px;
    margin: 0 auto;
}

.espaco{
    padding-left: 110px;
}

.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
</style>



<script language='javascript' src='../ajax.js'></script>
<script language='javascript' src='js/jquey-1.4.2.js'></script>
<script language='javascript'>

function Exibir (componente,componente_erro,componente_carregando,fabrica) {
	var var1 = $('#mes').val();
	var var2 = $('#ano').val();
	var var3 = $('#codigo_posto').val();
	var var4 = $('#estado').val();

	$.ajax({
		type:'GET',
		url:'<?=$PHP_SELF?>?ajax=sim',
		data:'mes='+var1+'&ano='+var2+'&codigo_posto='+var3+'&estado='+var4,
		beforeSend:function(){
			$('#consulta').hide('');
			$('#dados').html("&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='js/loadingAnimation.gif'> ");
			$('#dados').show('slow');
		},
		complete:function(resposta) {
			$('#consulta').show('');
			resposta = resposta.responseText.split('|');
			if (resposta[0]=='ok')	{
					$('#dados').html(resposta[1]);
			}else{
				$('#erro').html(resposta[1]);
			}
				
		}
	});

}


</script>

<? include "javascript_pesquisas.php" ?>

<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007 ?>


<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}

	function formatResult(row) {
		return row[0];
	}

	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[0]) ;
	});

});
</script>


<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<div id='erro' style='position: absolute; top: 150px; left: 80px;visibility:hidden;opacity:.85;' class='Erro'></div>
<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando'></div>
<table width='700px' border='0' cellpadding='4' cellspacing='0' align='center' class='formulario'>
	<tr>
		<td class='titulo_tabela' colspan='2'>Parâmetros de Pesquisa</td>
	</tr>
	<tr>
	    <td class='espaco'>
	        Mês<br>
	        <?php
				$mes_extenso = array('01' => "janeiro", '02' => "fevereiro", '03' => "março", 
				'04' => "abril", '05' => "maio", '06' => "junho", '07' => "julho", 
				'08' => "agosto", '09' => "setembro", '10' => "outubro", '11' => "novembro", 
				'12' => "dezembro");
			?>
			<select name="mes" class="frm" id="mes" style='width: 110px;'><?php
				foreach ($mes_extenso as $k => $v) {
					echo '<option value="'.$k.'"'.($mes == $k ? ' selected="selected"' : '').'>
					'.ucwords($v)."</option>\n";
				}?>
			</select>
	    </td>
	     <td>
	        Ano<br>
	        <select name="ano" class="frm" id="ano" style='width: 110px;'><?php
				for($i = date("Y"); $i > 2008; $i--){
					echo "<option value='$i'";
					if ($ano == $i) echo " selected";
						echo ">$i</option>";
				}?>
			</select>
	     </td>
	</tr>
	<tr>
	     <td class='espaco'>
	        Cód Posto<br>
	        <input type="text" name="codigo_posto" id="codigo_posto" style='width: 110px;'  value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
	     </td>
	     <td>
	        Nome do Posto<br>
            <input type="text" name="posto_nome" id="posto_nome" style='width: 220px;' value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
	     </td>
	     
	</tr>
	<tr>
	    <td colspan='2' class='espaco'>
	        Estado<br>
			<?php
			    $array_estado = array("AC"=>"AC - Acre","AL"=>"AL - Alagoas","AM"=>"AM - Amazonas",
					"AP"=>"AP - Amapá", "BA"=>"BA - Bahia", "CE"=>"CE - Ceará","DF"=>"DF - Distrito Federal",
					"ES"=>"ES - Espírito Santo", "GO"=>"GO - Goiás","MA"=>"MA - Maranhão","MG"=>"MG - Minas Gerais",
					"MS"=>"MS - Mato Grosso do Sul","MT"=>"MT - Mato Grosso", "PA"=>"PA - Pará","PB"=>"PB - Paraíba",
					"PE"=>"PE - Pernambuco","PI"=>"PI - Piauí","PR"=>"PR - Paraná","RJ"=>"RJ - Rio de Janeiro",
					"RN"=>"RN - Rio Grande do Norte","RO"=>"RO - Rondônia","RR"=>"RR - Roraima",
					"RS"=>"RS - Rio Grande do Sul", "SC"=>"SC - Santa Catarina","SE"=>"SE - Sergipe",
					"SP"=>"SP - São Paulo","TO"=>"TO - Tocantins");
			?>
			<select name="estado" class="frm" id="estado" style='width: 220px;'>
				<option value = ""></option>
				<?php
					foreach ($array_estado as $k => $v) {
						echo '<option value="'.$k.'"'.($estado == $k ? ' selected="selected"' : '').'>'.$v."</option>\n";
					}?>
			</select>
	    </td>
	</tr>
	<tr>
	    <td colspan='2' style='text-align: center; padding: 10px 0;'>
	        <input type='button' onclick="javascript:Exibir('dados','erro','carregando','<?=$login_fabrica?>');" style="cursor:pointer " value='Consultar' id='consulta'>
	    </td>
    </tr>
</table>
</FORM>


<?

echo "<div id='dados'></div>";

?>

<p>

<? include "rodape.php" ?>

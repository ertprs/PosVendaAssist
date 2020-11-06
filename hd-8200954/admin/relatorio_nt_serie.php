<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="gerencia";
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

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj			= trim(pg_result($res,$i,cnpj));
				$nome			= trim(pg_result($res,$i,nome));
				$codigo_posto	= trim(pg_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}



if($_GET['ajax']=='sim') {
	
	
	
		if(strlen($_GET["data_inicial_01"])==0 and strlen($_GET["data_final_01"])==0 and strlen($_GET['codigo_posto'])==0 ) {
		$erro = " Por favor, informar parametros para pesquisa.";
	}

	if (strlen($erro) == 0) {
		#$data_inicial   = trim($_GET["data_inicial_01"]); HD 79035
		if(strlen($_POST["data_inicial_01"])>0) $data_inicial = trim($_POST["data_inicial_01"]);
		else                                    $data_inicial = trim($_GET["data_inicial_01"]);

		$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");

		if (strlen ( pg_errormessage ($con) ) > 0) {
			$erro = pg_errormessage ($con) ;
		}

		if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
	}

	$codigo_posto = trim($_GET["codigo_posto"]);
	if(strlen($codigo_posto) > 0){
		$cond_1 = " AND tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
	}

	if (strlen($erro) == 0) {

		if (strlen($erro) == 0) {
			#$data_final   = trim($_GET["data_final_01"]);
			if(strlen($_POST["data_final_01"])>0) $data_final = trim($_POST["data_final_01"]);
			else                                  $data_final = trim($_GET["data_final_01"]);

			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");

			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}

			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		}
	}

	if(strlen($aux_data_inicial) > 0 and strlen($aux_data_final) > 0) {
		$cond_2 = " AND tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59' ";
	}
	


	if (strlen($erro) > 0) {
		$data_inicial = trim($_GET["data_inicial_01"]);
		$data_final   = trim($_GET["data_final_01"]);
		$familia      = trim($_GET["familia"]);
		$msg  = "<b>Foi(foram) detectado(s) o(s) seguinte(s) erro(s): </b><br>";
		$msg .= $erro;


	}else $listar = "ok";
	if ($listar == "ok") {
		$sql = "SELECT tbl_posto_fabrica.codigo_posto   ,
				tbl_posto.nome                          ,
				tbl_os.os                               ,
				tbl_os.sua_os                           ,
				tbl_os.serie                            ,
				tbl_produto.referencia                  ,
				tbl_produto.descricao                   ,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
				to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento,
				count(os_item) as qtde_item
			FROM tbl_os
			JOIN tbl_produto USING (produto)
			JOIN tbl_posto         ON tbl_os.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_os_produto USING (os)
			LEFT JOIN tbl_os_item    USING (os_produto)
			JOIN tbl_os_extra      ON tbl_os.os = tbl_os_extra.os
			JOIN tbl_extrato       ON tbl_os_extra.extrato = tbl_extrato.extrato
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_produto.familia=$familia 
			$cond_1
			$cond_2
			GROUP BY	tbl_posto_fabrica.codigo_posto   ,
						tbl_posto.nome                          ,
						tbl_os.os                               ,
						tbl_os.sua_os                           ,
						tbl_os.serie                            ,
						tbl_produto.referencia                  ,
						tbl_produto.descricao                   ,
						tbl_os.data_abertura                    ,
						tbl_os.data_fechamento                  ,
						tbl_produto.familia
			ORDER BY tbl_posto_fabrica.codigo_posto,tbl_os.data_abertura";
		//echo nl2br($sql);
		$res = pg_exec ($con,$sql);
			


		if (pg_numrows($res) > 0) {
			$total = 0;

			$resposta  .= "<div align='left' style='position: relative; left: 25'>";
			$resposta  .= "<table border='0' cellspacing='0' cellpadding='0' align='center'>";
			$resposta  .= "<tr height='18'>";
			$resposta  .= "<td width='18' bgcolor='#D7FFE1'>&nbsp;</td>";
			$resposta  .= "<td align='left'><font size='1'><b>&nbsp; OS sem pe�a</b></font></td>";
			$resposta  .= "</tr>";
			$resposta  .= "<tr height='3'><td colspan='2'></td></tr>";
			$resposta  .= "</table>";
			$resposta  .= "</div>";
			$resposta  .=  "<br><br>";
			$resposta  .=  "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' >";
			$resposta  .= "<thead>";
			$resposta  .= "<TR class='Titulo' background='imagens_admin/azul.gif' height='25'>";
			$resposta  .= "<Th><b>OS</b></Th>";
			$resposta  .= "<th><b>Data Abertura</b></th>";
			$resposta  .= "<th><b>Data Fechamento</b></th>";
			$resposta  .= "<th><b>Posto</b></th>";
			$resposta  .= "<th><b>Produto</b></th>";
			$resposta  .= "<th><b>S�rie</b></th>";
			$resposta  .= "</TR>";
			$resposta  .= "</thead>";
			$resposta  .= "<tbody>";
			for ($i=0; $i<pg_numrows($res); $i++){
				$codigo_posto    = trim(pg_result($res,$i,codigo_posto)) ;
				$nome            = trim(pg_result($res,$i,nome))         ;
				$os              = trim(pg_result($res,$i,os))           ;
				$sua_os          = trim(pg_result($res,$i,sua_os))       ;
				$serie           = trim(pg_result($res,$i,serie))        ;
				$referencia      = trim(pg_result($res,$i,referencia))   ;
				$descricao       = trim(pg_result($res,$i,descricao))    ;
				$data_abertura   = trim(pg_result($res,$i,data_abertura));
				$data_fechamento = trim(pg_result($res,$i,data_fechamento));
				$qtde_item       = trim(pg_result($res,$i,qtde_item));

				$cor="";
				if($i%2==0)$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';

				if($qtde_item == 0) $cor='#D7FFE1';

				$resposta  .=  "<TR bgcolor='$cor'class='Conteudo'>";
				$resposta  .=  "<TD align='center'nowrap><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></TD>";
				$resposta  .=  "<TD align='center' >$data_abertura</TD>";
				$resposta  .=  "<TD align='center' >$data_fechamento</TD>";
				$resposta  .=  "<TD align='center'>$codigo_posto - $nome</TD>";
				$resposta  .=  "<TD>$referencia $descricao</TD>";
				$resposta  .=  "<TD align='center'>$serie</TD>";
				$resposta  .=  "</TR>";
			}
			$resposta .="</tbody>";
			$resposta .= " </TABLE>";

			flush();
			$data = date ("d/m/Y H:i:s");

			$arquivo_nome     = "relatorio-nt-serie-$login_fabrica.xls";
			$path             = "/www/assist/www/admin/xls/";
			$path_tmp         = "/tmp/";

			$arquivo_completo     = $path.$arquivo_nome;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

			echo `rm $arquivo_completo_tmp `;
			echo `rm $arquivo_completo `;

			$fp = fopen ($arquivo_completo_tmp,"w");

			fputs ($fp,"<html>");
			fputs ($fp,"<head>");
			fputs ($fp,"<title>Relat�rio de N�mero de S�rie da Linha NT - $data");
			fputs ($fp,"</title>");
			fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs ($fp,"</head>");
			fputs ($fp,"<body>");
			fputs ($fp,"<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc'  align='center' >");
			fputs ($fp,"<thead>");
			fputs ($fp,"<TR class='Titulo' background='imagens_admin/azul.gif' height='25'>");
			fputs ($fp,"<Th><b>OS</b></Th>");
			fputs ($fp,"<th><b>Data Abertura</b></th>");
			fputs ($fp,"<th><b>Data Fechamento</b></th>");
			fputs ($fp,"<th><b>Posto</b></th>");
			fputs ($fp,"<th><b>Produto</b></th>");
			fputs ($fp,"<th><b>S�rie</b></th>");
			fputs ($fp,"</TR>");
			fputs ($fp,"</thead>");
			fputs ($fp,"<tbody>");
			for ($i=0; $i<pg_numrows($res); $i++){
				$codigo_posto    = trim(pg_result($res,$i,codigo_posto)) ;
				$nome            = trim(pg_result($res,$i,nome))         ;
				$os              = trim(pg_result($res,$i,os))           ;
				$sua_os          = trim(pg_result($res,$i,sua_os))       ;
				$serie           = trim(pg_result($res,$i,serie))        ;
				$referencia      = trim(pg_result($res,$i,referencia))   ;
				$descricao       = trim(pg_result($res,$i,descricao))    ;
				$data_abertura   = trim(pg_result($res,$i,data_abertura));
				$data_fechamento = trim(pg_result($res,$i,data_fechamento));

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';
				fputs ($fp,"<TR bgcolor='$cor'class='Conteudo'>");
				fputs ($fp,"<TD align='center'nowrap>$sua_os</TD>");
				fputs ($fp,"<TD align='center' >$data_abertura</TD>");
				fputs ($fp,"<TD align='center' >$data_fechamento</TD>");
				fputs ($fp,"<TD align='center'>$codigo_posto - $nome</TD>");
				fputs ($fp,"<TD>$referencia $descricao</TD>");
				fputs ($fp,"<TD align='center'>$serie</TD>");
				fputs ($fp,"</TR>");

			}
			fputs ($fp,"</tbody>");
			fputs ($fp, " </TABLE>");


			echo ` cp $arquivo_completo_tmp $path `;
			$data = date("Y-m-d").".".date("H-i-s");

			echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
			$resposta .= "<br>";
			$resposta .="<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			$resposta .="<tr>";
			$resposta .= "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/$arquivo_nome' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Voc� pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
			$resposta .= "</tr>";
			$resposta .= "</table>";
		}else{
			$resposta .=  "<br>";
			$resposta .= "<b>Nenhum resultado encontrado</b>";
		}
		$listar = "";

	}
	if (strlen($erro) > 0) {
		echo $msg;
	}else{
		echo $resposta;
	}
	exit;

	flush();

}

$layout_menu = "gerencia";
$title = "RELAT�RIO DE S�RIE DA LINHA NT";

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
</style>


<script language="javascript" src="js/jquery-1.3.1.js"></script>
<link rel="stylesheet" type="text/css" href="js/datePicker.v1.css" title="default" media="screen" />
<script type="text/javascript" src="js/datePicker.v1.js"></script>
<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
<script language='javascript' src='../ajax.js'></script>
<script language='javascript'>

function Exibir (componente,fabrica) {
	var var1 = document.frm_relatorio.data_inicial.value;
	var var2 = document.frm_relatorio.data_final.value;
	var var3 = document.frm_relatorio.codigo_posto.value;
	var var4 = document.frm_relatorio.familia.value;
	var com = document.getElementById(componente);
	
	$.ajax({
		type: "GET",
		url: "<?=$PHP_SELF?>",
		data: 'data_inicial_01='+var1+'&data_final_01='+var2+'&codigo_posto='+var3+'&familia='+var4+'&ajax=sim&tempo='+ new Date().getTime(),
		beforeSend: function(){
			$('#botao').slideUp('slow');
			$('#dados').html("&nbsp;&nbsp;Carregando...&nbsp;&nbsp;<br><img src='js/loadingAnimation.gif'> ");
			$('#dados').show('slow');
		},
		complete: function(http) {
			results = http.responseText;
			$(com).html(results);
			$('#botao').addClass('botao');
			$('#botao').show('slow');
		}
	});
}


</script>

<? include "javascript_pesquisas.php" ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>


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

	/* Busca pelo C�digo */
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
		//alert(data[2]);
	});

});
</script>


<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<div id='erro' style='position: absolute; top: 150px; left: 80px;visibility:hidden;opacity:.85;' class='Erro'></div>
<div id='carregando' style='position: absolute;visibility:hidden;opacity:.90;' class='Carregando'></div>
<table width='600' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center' >
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'><?=$title?></td>
	</tr>

	<tr>
		<td bgcolor='#DBE5F5' valign='bottom'>

			<table width='100%' border='0' cellspacing='1' cellpadding='2' >

				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
					<td align='right'><font size='2'>Data Inicial</td>
					<td align='left'>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
					</td>
					<td align='right'><font size='2'>Data Final</td>
					<td align='left'>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" >
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				<tr>
					<td width="10">&nbsp;</td>
					<td align='right' nowrap><font size='2'>C�digo Posto</td>
					<td align='left'>
						<input type="text" name="codigo_posto" id="codigo_posto" size="12"  value="<? echo $codigo_posto ?>" class="Caixa">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo c�digo" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
					</td>
					<td align='right' nowrap><font size='2'>Nome do Posto</td>
					<td align='left'>
						<input type="text" name="posto_nome" id="posto_nome" size="30"  value="<?echo $posto_nome?>" class="Caixa">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo c�digo" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
					</td>
				</tr>
				<tr>
					<td width="10">&nbsp;</td>
					<td align='right' nowrap><font size='2'>Fam�lia</td>
					<td align='left' colspan='3'>
						<select name="familia">
							<option></option>
							<?
							$sqlFamilia = "SELECT  familia, descricao
								FROM  tbl_familia
								WHERE fabrica = $login_fabrica
								AND   descricao ilike '% NT%'
								AND   ativo
								order by tbl_familia.descricao";
								#$teste = $sqlTP;
							$resFamilia = pg_exec($con, $sqlFamilia);

							if(pg_numrows($resFamilia)>0){
								for($i=0; $i<pg_numrows($resFamilia); $i++){
									$xfamilia    = pg_result($resFamilia,$i,familia);
									$xdescricao = pg_result($resFamilia,$i,descricao);
									echo "<option value='$xfamilia'";
									if($xfamilia==$familia) echo "selected";
									echo ">$xdescricao</option>";
								}
							}
							?>
						</select>
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				</table><br>
			<input type='button' onclick="javascript:Exibir('dados','<?=$login_fabrica?>');" style="cursor:pointer " value='Consultar' id='botao'>
		</td>
	</tr>
</table>
</FORM>


<?

echo "<div id='dados'></div>";


?>
<p>



<? include "rodape.php" ?>

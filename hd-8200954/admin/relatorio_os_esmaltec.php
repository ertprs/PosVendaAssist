<?php
# HD 47172 - Relatório OS com KM solicitada

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="auditoria";
include "autentica_admin.php";

include "funcoes.php";

# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];

	if (strlen($q)>2){
		$sql = "SELECT tbl_posto.nome, tbl_posto_fabrica.codigo_posto
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
				$nome         = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

$layout_menu = "Gerencia";
$title = "RELATÓRIO DE OS";
include "cabecalho.php";

$xpagina = $_GET["pagina"];
if (strlen($xpagina) > 0){
	$btn_acao = $_GET["btn_acao"];
}else{
	$btn_acao = trim($_POST["btn_acao"]);
	$porpost = 0;
}


if($btn_acao == 'Pesquisar'){
	if (isset($porpost)){
		$data_inicial = $_POST["data_inicial"];
		$data_final   = $_POST["data_final"];
		$posto_codigo = trim($_POST["posto_codigo"]);
		$posto_nome   = trim($_POST["posto_nome"]);
		$os           = trim($_POST["os"]);
		$estado       = trim($_POST["estado"]);
	}else{
		$data_inicial = $_GET["data_inicial"];
		$data_final   = $_GET["data_final"];
		$posto_codigo = $_GET["posto_codigo"];
		$posto_nome   = $_GET["posto_nome"];
		$os           = $_GET["os"];
		$estado       = $_GET["estado"];
	}

	#--- Validações - início ---#
	if (strlen($data_inicial) > 0) {
		$sdata_inicial = formata_data ($data_inicial);
		
	}else{
		$msg_erro = "É necessário informar a Data Inicial para realizar a pesquisa!";
	}

	if (strlen($data_final) > 0) {
		$sdata_final = formata_data ($data_final);
	}else{
		$msg_erro = "É necessário informar a Data Final para realizar a pesquisa!";
	}

	if ((strlen($data_inicial) == 0) AND (strlen($data_final) == 0)){
		$msg_erro = "Data Inválida.<br/>";
	}
	if($data_inicial){
		$dat = explode ("/", $data_inicial );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}
	if($data_final){
		$dat = explode ("/", $data_final );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";
	}
	if(strlen($msg_erro)==0){
		
		if($sdata_final < $sdata_inicial){
			$msg_erro = "Data Inválida.";
		}

	}
	#--- Validações - fim ---#

	
}


?>

<?php include "javascript_calendario.php"; ?>

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
		return row[1] + " - " + row[0];
	}

	function formatResult(row) {
		return row[1];
	}

	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[0]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[1]) ;
	});

});

function SomenteNumero(e){
    var tecla=(window.event)?event.keyCode:e.which;
    if((tecla > 47 && tecla < 58)) return true;
    else{
    if (tecla != 8) return false;
    else return true;
    }
}
</script>

<style type="text/css">

</style>

<FORM NAME="frm_pesquisa" METHOD="POST" ACTION="<?echo $PHP_SELF?>">

<TABLE width="700" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>
<? if(strlen($msg_erro) > 0){ ?>
	<tr bgcolor='#ff0000' style='font:bold 16px Arial; color:#ffffff'>
		<td colspan='5' align='center'><? echo $msg_erro; ?></td>
	</tr>
<? } ?>
<tr bgcolor='#596D9B' style='font:bold 14px Arial; color:#ffffff'><td colspan='5' align='center'>Parâmetros de Pesquisa</td></tr>

<TBODY style='font-size:10px;'>
	<TR>
		<td width='120'>&nbsp;</td>
		<TD width='130'>
			Número da OS<br>
		
			<INPUT TYPE="TEXT" NAME="os" ID="os" SIZE="20" MAXLENGTH="20" VALUE="<? echo $os ?>" CLASS="frm" onkeypress='return SomenteNumero(event)'>
		</TD>
		<TD colspan='2' width='274'>
			<table>
				<tr>
					<td width='117'>
						Data Inicial (*)<br>		
						<INPUT TYPE="TEXT" NAME="data_inicial" ID="data_inicial" SIZE="11" MAXLENGTH="10" VALUE="<? echo $data_inicial ?>" class="frm">
					</td>
					<td width='117'>
						Data Final (*)<br>		
						<INPUT TYPE="TEXT" NAME="data_final" ID="data_final" SIZE="11" MAXLENGTH="10" VALUE="<? echo $data_final ?>" CLASS="frm">
					</td>
				</tr>
			</table>
		</TD>
		<td width='50'>&nbsp;</td>
	</TR>

	<TR >
		<td width='50' colspan='2'>&nbsp;</td>
		<TD COLSPAN='2' >(*) Data de Fechamento da OS.
		</TD>
		<td width='50'>&nbsp;</td>
	</TR>
	<TR>
		<td width='50'>&nbsp;</td>
		<TD>Código Posto<BR/>
		<INPUT TYPE="TEXT" NAME="posto_codigo" ID="posto_codigo" SIZE="15" VALUE="<? echo $posto_codigo ?>" CLASS="frm">
		</TD>
		<TD>Nome do Posto<BR/>
		<INPUT TYPE="TEXT" NAME="posto_nome" ID="posto_nome" SIZE="40" VALUE="<? echo $posto_nome ?>" CLASS="frm">
		</TD>
		<td width='50' colspan='2'>&nbsp;</td>
	</TR>
		<TR>
		<td width='50''>&nbsp;</td>
		<TD colspan='2' align='left'>Estado<BR/>
				<select name="estado" id='estado' style='width:120px; font-size:9px'>
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
			<td width='50' colspan='2'>&nbsp;</td>
		</tr>
</TBODY>

<TR>
	<TD COLSPAN="5" ALIGN="CENTER">
		<BR/>
		<INPUT TYPE="HIDDEN" NAME="btn_acao" VALUE="">
		<input type='image' SRC="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" STYLE="cursor:pointer " ALT='Clique AQUI para pesquisar'>
	</TD>
</TR>
</TABLE>
</FORM>
<BR/>

<?php
if($btn_acao == 'Pesquisar'){
	if (strlen($posto_codigo) >0){
		$sqlPosto = "SELECT tbl_posto.posto
						FROM tbl_posto
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
						WHERE tbl_posto_fabrica.fabrica = $login_fabrica
						AND tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
		$resPosto = pg_exec($con,$sqlPosto);

		if (pg_numrows($resPosto) > 0){
			$scodigo_posto = trim(pg_result($resPosto,0,posto));
			$cond_posto = "AND tbl_os.posto = $scodigo_posto";
		}
	}

	if (strlen($os) > 0){
		$cond_os = "AND	tbl_os.os = $os";
	}
	if (strlen($estado) > 0){
		$cond_estado = "AND	tbl_posto_fabrica.contato_estado='$estado'";
	}

	if (strlen($msg_erro) == 0){
		$sql = "SELECT tbl_posto_fabrica.codigo_posto                              ,
						tbl_posto.nome                                             ,
						tbl_os.os                                                  ,
						tbl_os.qtde_km                                             ,
						tbl_os.defeito_constatado                                  ,
						tbl_defeito_constatado.descricao                           ,
						tbl_defeito_constatado.codigo                              ,
						tbl_produto.descricao as prod_descricao                    ,
						tbl_produto.familia                                        ,
						tbl_familia.descricao as fam_descricao                     ,
						tbl_posto_fabrica.contato_cidade                           ,
						tbl_posto_fabrica.contato_estado                           ,
						tbl_os.consumidor_cidade                                   ,
						tbl_os.consumidor_estado                                   ,
						tbl_os.revenda_nome                                        ,
						to_char(tbl_os.data_digitacao,'DD/MM/YYYY') AS data_digitacao,
						to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
						to_char(tbl_os.finalizada,'DD/MM/YYYY') AS data_finalizada ,
						to_char(tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento
					FROM tbl_os
					JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
					JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
					JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado = tbl_defeito_constatado.defeito_constatado
					JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
					JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia
					WHERE tbl_os.fabrica = $login_fabrica
					AND tbl_os.finalizada BETWEEN '$sdata_inicial 00:00:00' AND
					'$sdata_final 23:59:59' AND tbl_os.qtde_km IS NOT NULL
					AND tbl_os.finalizada IS NOT NULL
					$cond_posto
					$cond_os
					$cond_estado
					ORDER BY tbl_posto.nome";

		##### PAGINAÇÃO - INÍCIO #####
		$sqlCount  = "SELECT count(*) FROM (";
		$sqlCount .= $sql;
		$sqlCount .= ") AS count";

		require "_class_paginacao.php";

		// definicoes de variaveis
		$max_links = 11;				// máximo de links à serem exibidos
		$max_res   = 100;				// máximo de resultados à serem exibidos por tela ou pagina
		$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

		##### PAGINAÇÃO - FIM #####

		$resultados = pg_numrows($res);
		if($login_fabrica==19 or $login_fabrica==30 or $login_fabrica==46 or $login_fabrica==50) {
			$res_xls = pg_exec($con,$sql);
		}

		if (pg_numrows ($res) > 0) {
			echo "<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
			echo "<tr>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>CÓDIGO POSTO</strong></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>POSTO NOME</strong></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>OS</strong></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>DATA DIGITAÇÃO</strong></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>DATA ABERTURA</strong></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>DATA FINALIZADA</strong></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>DATA FECHAMENTO</strong></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>GRUPO</strong></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>PRODUTO</strong></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>Cidade Origem</strong></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>Estado Origem</strong></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>Cidade Destino</strong></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>Estado Destino</strong></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>KM</strong></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>DEFEITO CONSTATADO</strong></font></td>";
			if($login_fabrica == 30){
				echo "<td bgcolor='#485989'><font color='#FFFFFF'><strong>Nome Revenda</strong></font></td>";
			}
			echo "</tr>";
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$rcodigo_posto      = trim(pg_result($res,$i,codigo_posto));
				$rnome              = trim(pg_result($res,$i,nome));
				$ros                = trim(pg_result($res,$i,os));
				$qtde_km            = trim(pg_result($res,$i,qtde_km));
				$defeito_constatado = trim(pg_result($res,$i,defeito_constatado));
				$descricao          = trim(pg_result($res,$i,descricao));
				$codigo             = trim(pg_result($res,$i,codigo));
				$prod_descricao     = trim(pg_result($res,$i,prod_descricao));
				$familia            = trim(pg_result($res,$i,familia));
				$fam_descricao      = trim(pg_result($res,$i,fam_descricao));
				$contato_cidade     = trim(pg_result($res,$i,contato_cidade));
				$contato_estado     = trim(pg_result($res,$i,contato_estado));
				$consumidor_cidade  = trim(pg_result($res,$i,consumidor_cidade));
				$consumidor_estado  = trim(pg_result($res,$i,consumidor_estado));
				$nome_revenda       = trim(pg_result($res,$i,revenda_nome));
				$data_digitacao     = trim(pg_result($res,$i,data_digitacao));
				$data_abertura      = trim(pg_result($res,$i,data_abertura));
				$data_finalizada    = trim(pg_result($res,$i,data_finalizada));
				$data_fechamento    = trim(pg_result($res,$i,data_fechamento));


				$qtde_km = number_format($qtde_km,3,",","");
				// HD 50708
				if($familia ==723 or $familia == 1098) $grupo ="Fogão";
				elseif($familia==1100 or $familia==933 or $familia== 934 or $familia == 1099 or $familia ==1202 or $familia ==932) $grupo = "Refrigeração";
				elseif($familia == 1083) $grupo ="Lavadora";
				else $grupo = $fam_descricao;

				$cores++;
				$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';

				echo "<tr bgcolor='$cor'>";
				echo "<td style='font-size: 9px; font-family: verdana;'>";
				echo "$rcodigo_posto</td>";
				echo "<td style='font-size: 9px; font-family: verdana; padding-left:5px; text-align:left;'>";
				echo "$rnome</td>";
				echo "<td style='font-size: 9px; font-family: verdana'>";
				echo "$ros</td>";
				echo "<td style='font-size: 9px; font-family: verdana'>";
				echo "$data_digitacao</td>";
				echo "<td style='font-size: 9px; font-family: verdana'>";
				echo "$data_abertura</td>";
				echo "<td style='font-size: 9px; font-family: verdana'>";
				echo "$data_finalizada</td>";
				echo "<td style='font-size: 9px; font-family: verdana'>";
				echo "$data_fechamento</td>";
				echo "<td style='font-size: 9px; font-family: verdana'>";
				echo "$grupo</td>";
				echo "<td style='font-size: 9px; font-family: verdana'>";
				echo "$prod_descricao</td>";
				echo "<td style='font-size: 9px; font-family: verdana'>";
				echo "$contato_cidade</td>";
				echo "<td style='font-size: 9px; font-family: verdana'>";
				echo "$contato_estado</td>";
				echo "<td style='font-size: 9px; font-family: verdana'>";
				echo "$consumidor_cidade</td>";
				echo "<td style='font-size: 9px; font-family: verdana'>";
				echo "$consumidor_estado</td>";
				echo "<td style='font-size: 9px; font-family: verdana; padding-right:5px; text-align:right;'>";
				echo "$qtde_km</td>";
				echo "<td style='font-size: 9px; font-family: verdana; padding-left:5px; text-align:left;'>";
				echo "$codigo - $descricao</td>";
				if($login_fabrica == 30) {
					echo "<td style='font-size: 9px; font-family: verdana'>";
					echo "$nome_revenda</td>";
				}
				echo "</tr>";
			}
			echo "</table>";

			// ##### PAGINACAO ##### //
			// links da paginacao
			echo "<br/>";

			echo "<div>";

			if($pagina < $max_links) {
				$paginacao = pagina + 1;
			}else{
				$paginacao = pagina;
			}

			// paginacao com restricao de links da paginacao

			// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
			$todos_links		= $mult_pag->Construir_Links("strings", "sim");

			// função que limita a quantidade de links no rodape
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
				echo "<br/>";
				echo "<div>";
				echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
				echo "<font color='#cccccc' size='1'>";
				echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
				echo "</font>";
				echo "</div>";
			}
			##### PAGINAÇÃO - FIM #####

			if(pg_numrows($res_xls) > 0 and $pagina == 0) { // HD 52493

				flush();
				$data = date ("d/m/Y H:i:s");

				$arquivo_nome     = "relatorio-km-os-$login_fabrica.xls";
				$path             = "/www/assist/www/admin/xls/";
				$path_tmp         = "/tmp/";

				$arquivo_completo     = $path.$arquivo_nome;
				$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

				echo `rm $arquivo_completo_tmp `;
				echo `rm $arquivo_completo `;

				$fp = fopen ($arquivo_completo_tmp,"w");

				fputs ($fp,"<html>");
				fputs ($fp,"<head>");
				fputs ($fp,"<title>Relatório de KM - $data");
				fputs ($fp,"</title>");
				fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
				fputs ($fp,"</head>");
				fputs ($fp,"<body>");
				fputs ($fp, "<table width='98%' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana); font-size: 11px' bgcolor='#FFFFFF'>");
				fputs ($fp, "<tr>");
				fputs ($fp, "<td bgcolor='#485989'><font color='#FFFFFF'><strong>CÓDIGO POSTO</strong></font></td>");
				fputs ($fp, "<td bgcolor='#485989'><font color='#FFFFFF'><strong>POSTO NOME</strong></font></td>");
				fputs ($fp, "<td bgcolor='#485989'><font color='#FFFFFF'><strong>OS</strong></font></td>");
				fputs ($fp, "<td bgcolor='#485989'><font color='#FFFFFF'><strong>DATA ABERTURA</strong></font></td>");
				fputs ($fp, "<td bgcolor='#485989'><font color='#FFFFFF'><strong>DATA FINALIZADA</strong></font></td>");
				fputs ($fp, "<td bgcolor='#485989'><font color='#FFFFFF'><strong>DATA FECHAMENTO</strong></font></td>");
				fputs ($fp, "<td bgcolor='#485989'><font color='#FFFFFF'><strong>GRUPO</strong></font></td>");
				fputs ($fp, "<td bgcolor='#485989'><font color='#FFFFFF'><strong>PRODUTO</strong></font></td>");
				fputs ($fp, "<td bgcolor='#485989'><font color='#FFFFFF'><strong>Cidade Origem</strong></font></td>");
				fputs ($fp, "<td bgcolor='#485989'><font color='#FFFFFF'><strong>Estado Origem</strong></font></td>");
				fputs ($fp, "<td bgcolor='#485989'><font color='#FFFFFF'><strong>Cidade Destino</strong></font></td>");
				fputs ($fp, "<td bgcolor='#485989'><font color='#FFFFFF'><strong>Estado Destino</strong></font></td>");
				fputs ($fp, "<td bgcolor='#485989'><font color='#FFFFFF'><strong>KM</strong></font></td>");
				fputs ($fp, "<td bgcolor='#485989'><font color='#FFFFFF'><strong>DEFEITO CONSTATADO</strong></font></td>");
				if($login_fabrica == 30) {
					fputs ($fp, "<td bgcolor='#485989'><font color='#FFFFFF'><strong>Nome Revenda</strong></font></td>");
				}
				fputs ($fp, "</tr>");
				for ($i=0; $i<pg_numrows ($res_xls); $i++ ){
					$rcodigo_posto      = trim(pg_result($res_xls,$i,codigo_posto));
					$rnome              = trim(pg_result($res_xls,$i,nome));
					$ros                = trim(pg_result($res_xls,$i,os));
					$qtde_km            = trim(pg_result($res_xls,$i,qtde_km));
					$defeito_constatado = trim(pg_result($res_xls,$i,defeito_constatado));
					$descricao          = trim(pg_result($res_xls,$i,descricao));
					$codigo             = trim(pg_result($res_xls,$i,codigo));
					$prod_descricao     = trim(pg_result($res_xls,$i,prod_descricao));
					$familia            = trim(pg_result($res_xls,$i,familia));
					$fam_descricao      = trim(pg_result($res_xls,$i,fam_descricao));
					$contato_cidade     = trim(pg_result($res_xls,$i,contato_cidade));
					$contato_estado     = trim(pg_result($res_xls,$i,contato_estado));
					$consumidor_cidade  = trim(pg_result($res_xls,$i,consumidor_cidade));
					$consumidor_estado  = trim(pg_result($res_xls,$i,consumidor_estado));
					$data_abertura      = trim(pg_result($res_xls,$i,data_abertura));
					$nome_revenda       = trim(pg_result($res_xls,$i,revenda_nome));
					$data_finalizada    = trim(pg_result($res_xls,$i,data_finalizada));
					$data_fechamento    = trim(pg_result($res_xls,$i,data_fechamento));


					$qtde_km = number_format($qtde_km,3,",","");
					// HD 50708
					if($familia ==723 or $familia == 1098) $grupo ="Fogão";
					elseif($familia==1100 or $familia==933 or $familia== 934 or $familia == 1099 or $familia ==1202 or $familia ==932) $grupo = "Refrigeração";
					elseif($familia == 1083) $grupo ="Lavadora";
					else $grupo = $fam_descricao;

					$cores++;
					$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';

					fputs ($fp, "<tr bgcolor='$cor'>");
					fputs ($fp, "<td style='font-size: 9px; font-family: verdana);'>");
					fputs ($fp, "$rcodigo_posto</td>");
					fputs ($fp, "<td style='font-size: 9px; font-family: verdana); padding-left:5px; text-align:left);'>");
					fputs ($fp, "$rnome</td>");
					fputs ($fp, "<td style='font-size: 9px; font-family: verdana'>");
					fputs ($fp, "$ros</td>");
					fputs ($fp, "<td align='center' style='font-size: 9px; font-family: verdana'>");
					fputs ($fp, "$data_abertura</td>");
					fputs ($fp, "<td align='center' style='font-size: 9px; font-family: verdana'>");
					fputs ($fp, "$data_finalizada</td>");
					fputs ($fp, "<td align='center' style='font-size: 9px; font-family: verdana'>");
					fputs ($fp, "$data_fechamento</td>");
					fputs ($fp, "<td style='font-size: 9px; font-family: verdana'>");
					fputs ($fp, "$grupo</td>");
					fputs ($fp, "<td style='font-size: 9px; font-family: verdana'>");
					fputs ($fp, "$prod_descricao</td>");
					fputs ($fp, "<td style='font-size: 9px; font-family: verdana'>");
					fputs ($fp, "$contato_cidade</td>");
					fputs ($fp, "<td align='center' style='font-size: 9px; font-family: verdana'>");
					fputs ($fp, "$contato_estado</td>");
					fputs ($fp, "<td style='font-size: 9px; font-family: verdana'>");
					fputs ($fp, "$consumidor_cidade</td>");
					fputs ($fp, "<td align='center' style='font-size: 9px; font-family: verdana'>");
					fputs ($fp, "$consumidor_estado</td>");
					fputs ($fp, "<td style='font-size: 9px; font-family: verdana); padding-right:5px; text-align:right);'>");
					fputs ($fp, "$qtde_km</td>");
					fputs ($fp, "<td style='font-size: 9px; font-family: verdana); padding-left:5px; text-align:left);'>");
					fputs ($fp, "$codigo - $descricao</td>");
					if($login_fabrica == 30) {
						fputs ($fp, "<td style='font-size: 9px; font-family: verdana'>");
						fputs ($fp, "$nome_revenda</td>");
					}
					fputs ($fp, "</tr>");
				}
				fputs ($fp, "</table>");

				echo ` cp $arquivo_completo_tmp $path `;
				$data = date("Y-m-d").".".date("H-i-s");

				echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
				echo "<br>";
				echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
				echo"<tr>";
				echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/relatorio-km-os-$login_fabrica.xls'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
				echo "</tr>";
				echo "</table>";
			}
		}else{
			echo "<center>Nenhum resultado encontrado.</center>";
		}
	}
}

include "rodape.php"
?>
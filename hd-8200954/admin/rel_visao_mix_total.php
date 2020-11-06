<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia,call_center";
include "autentica_admin.php";
include "monitora.php";
include "funcoes.php";

$erro = "";

if (strlen($_POST["botao"]) > 0) $botao = strtoupper($_POST["botao"]);

if ($botao == "BUSCAR") {
	$x_data_inicial = trim($_POST["data_inicial"]);
	$x_data_final   = trim($_POST["data_final"]);
    $linha          = $_POST["linha"];
	$marca          = $_POST["marca"];
	$familia        = $_POST["familia"];
	$estado         = $_POST["estado"];
	$x_posto_codigo = trim($_POST["posto_codigo"]);
	$x_posto_nome   = trim($_POST["posto_nome"]);
	$ordem          = $_POST["ordem"];
	$carvao         = $_POST["carvao"];
	$pecas          = $_POST["pecas"];
	if($login_fabrica==1){
		$tipo_data = $_POST["tipo_data"];
	}
	if (strlen($x_data_inicial) == 0) $erro = " Data inválida ";
	if (strlen($x_data_final) == 0)   $erro = " Data inválida ";

	

	//Início Validação de Datas
	if(strlen($erro)==0){
		$dat = explode ("/", $x_data_inicial );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $erro = "Data Inválida";
	}
	if(strlen($erro)==0){
		$dat = explode ("/", $x_data_final );//tira a barra
			$d = $dat[0];
			$m = $dat[1];
			$y = $dat[2];
			if(!checkdate($m,$d,$y)) $erro = "Data Inválida";
	}
	if(strlen($erro)==0){
		$d_ini = explode ("/", $x_data_inicial);//tira a barra
		$x_data_inicial = "$d_ini[2]-$d_ini[1]-$d_ini[0]";//separa as datas $d[2] = ano $d[1] = mes etc...


		$d_fim = explode ("/", $x_data_final);//tira a barra
		$x_data_final = "$d_fim[2]-$d_fim[1]-$d_fim[0]";//separa as datas $d[2] = ano $d[1] = mes etc...

		if($x_data_final < $x_data_inicial){
			$erro = "Data Inválida.";
		}
		if (strtotime($x_data_inicial.'+31 days') < strtotime($x_data_final) ) {
    		$erro .= "O intervalo entre as datas não pode ser maior que 30 dias <br />";
   		}
		//Fim Validação de Datas
	}

	if (strlen($x_posto_codigo) > 0 OR strlen($x_posto_nome) > 0) {
		$sql =	"SELECT tbl_posto.posto                ,
						tbl_posto_fabrica.codigo_posto ,
						tbl_posto.nome
				FROM tbl_posto
				JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica";
		if (strlen($x_posto_codigo) > 0) $sql .= " AND tbl_posto_fabrica.codigo_posto = '$x_posto_codigo'";
		if (strlen($x_posto_nome) > 0) $sql .= " AND tbl_posto.nome = '$x_posto_nome';";
//if ($ip=='201.42.111.192') echo $sql;
		$res = pg_exec($con,$sql);

		if (pg_numrows($res) == 1) {
			$posto        = pg_result($res,0,posto);
			$posto_codigo = pg_result($res,0,codigo_posto);
			$posto_nome   = pg_result($res,0,nome);
		}else{
			$erro = " Posto digitado não foi encontrado.<br> ";
			$posto_codigo = $x_posto_codigo;
			$posto_nome   = $x_posto_nome;
		}

	}
}

$layout_menu = "auditoria";
$title = "VISÃO GERAL POR PRODUTO";

include "cabecalho.php";
?>

<style type="text/css">
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
</style>

<script language="JavaScript">
function fnc_pesquisa_posto(campo, campo2, tipo) {
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
		alert("Informe toda ou parte da informação para realizar a pesquisa");
	}
}
</script>

<!--
<script language="JavaScript" src="js/cal2.js"></script>
<script language="JavaScript" src="js/cal_conf2.js"></script>
-->

<? include "javascript_calendario_new.php"; //adicionado por Fabio 27-09-2007
include "../js/js_css.php";
?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datepick({startDate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");
	});
</script>

<br>



<form method="POST" action="<?echo $PHP_SELF?>" name="frm_os_aprovada">
<input type="hidden" name="botao">
<table width="700" border="0" cellpadding="2" cellspacing="0" align="center" class="formulario">
	<? if (strlen($erro) > 0) { ?>
		<tr class="msg_erro">
			<td colspan="4"><?echo $erro?></td>
		</tr>

	<? } ?>
	<tr class="titulo_tabela">
		<td colspan="4" height="20">Parâmetros de Pesquisa</td>
	</tr>
	<tr  align='left'>
		<td width="80">&nbsp;</td>
		<td width="180">
			Data Início<br>
			<input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm">
		</td>
		<td width="200">
			Data Final<br>
			<input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm">
		</td>
		<td width="80">&nbsp;</td>
	</tr>
	<?php if($login_fabrica == 1){ ?>
		<tr  align='left'>
			<td >&nbsp;</td>
			<td ><input type="radio" name="tipo_data" value="osEnviadaFinanceiro" style="margin-left:0px;"  <? if ( ($tipo_data == "osEnviadaFinanceiro") OR (strlen($tipo_data) ) == 0) echo "checked"; ?> class="frm"> OSs Enviadas para o Financeiro</td>
			<td ><input type="radio" name="tipo_data" value="osAberta" 		style="margin-left:0px;"		<? if ($tipo_data == "osAberta")  echo "checked"; ?> class="frm"> OSs Abertas</td>
		</tr>
		
	<?php } ?>
	<tr >
		<td colspan="4"></td>
	</tr>
	<tr >
		<td width="80">&nbsp;</td>
		<td colspan="2" align="left">
			Linha <br>
			<?
			$sql =	"SELECT linha, nome
					FROM tbl_linha
					WHERE fabrica = $login_fabrica
					ORDER BY nome;";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {
				echo "<select name='linha' size='1' class='frm'>";
				echo "<option value=''></option>";
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_linha      = trim(pg_result($res,$x,linha));
					$aux_linha_nome = trim(pg_result($res,$x,nome));
					echo "<option value='$aux_linha'";
					if ($linha == $aux_linha) echo " selected";
					echo ">$aux_linha_nome</option>";
				}
				echo "</select>";
			}
			?>
		</td>
		<td width="80">&nbsp;</td>
	</tr>
<?
if($login_fabrica == 1){
?>
    <tr>
        <td colspan="4"></td>
    </tr>
    <tr>
        <td width="80"></td>
        <td colspan="2" style="text-align:left;">
            Marca <br />
            <select name="marca" class="frm">
                <option value=''>Todas</option>
<?
    $sqlMarca = "
        SELECT  marca,
                nome
        FROM    tbl_marca
        WHERE   fabrica = $login_fabrica;
    ";
    $resMarca = pg_query($con,$sqlMarca);
    $marcas = pg_fetch_all($resMarca);

    foreach($marcas as $chave => $valor){
?>
                <option value="<?=$valor['marca']?>" <?=($valor['marca'] == $_GET['marca']) ? "selected='selected'" : "" ?>><?=$valor['nome']?></option>
<?
    }
?>
            </select>
        </td>
        <td width="80"></td>
    </tr>
<?
}
?>
	<tr >
		<td colspan="4"></td>
	</tr>
	<tr >
		<td width="80">&nbsp;</td>
		<td colspan="2" align="left">
			Família <br>
			<?
			$sql =	"SELECT familia, descricao
					FROM tbl_familia
					WHERE fabrica = $login_fabrica
					ORDER BY descricao;";
			$res = pg_exec($con,$sql);
			if (pg_numrows($res) > 0) {
				echo "<select name='familia' size='1' class='frm'>";
				echo "<option value=''></option>";
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_familia           = trim(pg_result($res,$x,familia));
					$aux_familia_descricao = trim(pg_result($res,$x,descricao));
					echo "<option value='$aux_familia'";
					if ($familia == $aux_familia) echo " selected";
					echo ">$aux_familia_descricao</option>";
				}
				echo "</select>";
			}
			?>
		</td>
		<td width="80">&nbsp;</td>
	</tr>
	<tr >
		<td colspan="4"></td>
	</tr>
	<tr >
		<td width="80">&nbsp;</td>
		<td colspan="2" align="left">Agrupar por Estado <br>
			<select name="estado" size="1" class="frm">
				<option value="" <? if (strlen($estado) == 0) echo "selected"; ?>>UF</option>
				<option value="AC" <? if ($estado == "AC") echo "selected"; ?>>AC - Acre</option>
				<option value="AL" <? if ($estado == "AL") echo "selected"; ?>>AL - Alagoas</option>
				<option value="AM" <? if ($estado == "AM") echo "selected"; ?>>AM - Amazonas</option>
				<option value="AP" <? if ($estado == "AP") echo "selected"; ?>>AP - Amapá</option>
				<option value="BA" <? if ($estado == "BA") echo "selected"; ?>>BA - Bahia</option>
				<option value="CE" <? if ($estado == "CE") echo "selected"; ?>>CE - Ceará</option>
				<option value="DF" <? if ($estado == "DF") echo "selected"; ?>>DF - Distrito Federal</option>
				<option value="ES" <? if ($estado == "ES") echo "selected"; ?>>ES - Espírito Santo</option>
				<option value="GO" <? if ($estado == "GO") echo "selected"; ?>>GO - Goiás</option>
				<option value="MA" <? if ($estado == "MA") echo "selected"; ?>>MA - Maranhão</option>
				<option value="MG" <? if ($estado == "MG") echo "selected"; ?>>MG - Minas Gerais</option>
				<option value="MS" <? if ($estado == "MS") echo "selected"; ?>>MS - Mato Grosso do Sul</option>
				<option value="MT" <? if ($estado == "MT") echo "selected"; ?>>MT - Mato Grosso</option>
				<option value="PA" <? if ($estado == "PA") echo "selected"; ?>>PA - Pará</option>
				<option value="PB" <? if ($estado == "PB") echo "selected"; ?>>PB - Paraíba</option>
				<option value="PE" <? if ($estado == "PE") echo "selected"; ?>>PE - Pernambuco</option>
				<option value="PI" <? if ($estado == "PI") echo "selected"; ?>>PI - Piauí</option>
				<option value="PR" <? if ($estado == "PR") echo "selected"; ?>>PR - Paraná</option>
				<option value="RJ" <? if ($estado == "RJ") echo "selected"; ?>>RJ - Rio de Janeiro</option>
				<option value="RN" <? if ($estado == "RN") echo "selected"; ?>>RN - Rio Grande do Norte</option>
				<option value="RO" <? if ($estado == "RO") echo "selected"; ?>>RO - Rondônia</option>
				<option value="RR" <? if ($estado == "RR") echo "selected"; ?>>RR - Roraima</option>
				<option value="RS" <? if ($estado == "RS") echo "selected"; ?>>RS - Rio Grande do Sul</option>
				<option value="SC" <? if ($estado == "SC") echo "selected"; ?>>SC - Santa Catarina</option>
				<option value="SE" <? if ($estado == "SE") echo "selected"; ?>>SE - Sergipe</option>
				<option value="SP" <? if ($estado == "SP") echo "selected"; ?>>SP - São Paulo</option>
				<option value="TO" <? if ($estado == "TO") echo "selected"; ?>>TO - Tocantins</option>
			</select>
		</td>
		<td width="80">&nbsp;</td>
	</tr>
	<tr >
		<td colspan="4"></td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td align="left">Código do Posto<br>
			<input type="text" name="posto_codigo" size="10" value="<?echo $posto_codigo?>" class="frm">
			<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_os_aprovada.posto_codigo,document.frm_os_aprovada.posto_nome,'codigo')" alt="Clique aqui para pesquisar os postos pelo Código" style="cursor: pointer;">
		</td>
		<td align="left">Razão Social do Posto<br>
			<input type="text" name="posto_nome" size="25" value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript: fnc_pesquisa_posto (document.frm_os_aprovada.posto_codigo,document.frm_os_aprovada.posto_nome,'nome')" alt="Clique aqui para pesquisar os postos pela Razão Social" style="cursor: pointer;">
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr >
		<td colspan="4"></td>
	</tr>
	<tr >
		<td>&nbsp;</td>
		<td colspan="2" align="center">
			<fieldset>
				<legend>Ordernar por</legend>

				<table width="100%">
					<tr >
						<td>&nbsp;</td>
						<td align='left'> &nbsp; <input type="radio" name="ordem" value="referencia" <? if ($ordem == "referencia" OR strlen($ordem) == 0) echo "checked"; ?> class="frm"> Referência</td>
						<td align='left'> &nbsp; <input type="radio" name="ordem" value="geral"      <? if ($ordem == "geral") echo "checked"; ?> class="frm"> Total</td>
						<td>&nbsp;</td>
					</tr>
					<tr >
						<td>&nbsp;</td>
						<td align='left'> &nbsp; <input type="radio" name="carvao" value="com" <? if ($carvao == "com" OR strlen($carvao) == 0) echo "checked"; ?> class="frm"> Com Carvão e Escova</td>
						<td align='left'> &nbsp; <input type="radio" name="carvao" value="sem" <?php if ($carvao == "sem") echo "checked"; ?> class="frm"> Sem Carvão e Escova</td>
						<td>&nbsp;</td>
					</tr>
					<tr >
						<td>&nbsp;</td>
						<td align='left'> &nbsp; <input type="radio" name="pecas" value="com" <? if ($pecas == "com" OR strlen($pecas) == 0) echo "checked"; ?> class="frm"> Com Itens na OS</td>
						<td align='left'> &nbsp; <input type="radio" name="pecas" value="sem" <?php if ($pecas == "sem") echo "checked"; ?> class="frm"> Sem Itens na OS</td>
						<td>&nbsp;</td>
					</tr>
				</table>
			</fieldset>
		</td>
		<td>&nbsp;</td>
	</tr>
	
	<tr >
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr >
		<td colspan="4" align="center"><input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onClick="javascript: if (document.frm_os_aprovada.botao.value == '' ) { document.frm_os_aprovada.botao.value='BUSCAR'; document.frm_os_aprovada.submit(); }else{ alert('Aguarde submissão'); }" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

</form>
<br>

<?
if (strlen($erro) == 0 && strlen($botao) > 0) {
	$sql = "SELECT  tbl_os.produto                                         ,
			COUNT(*)                                AS qtde        ,
			SUM (tbl_os.mao_de_obra + tbl_os.pecas) AS geral       ,
			SUM (tbl_os.mao_de_obra)                AS mao_de_obra ,
			SUM (tbl_os.pecas)                      AS pecas
		INTO TEMP tmp_rvmt1_$login_admin
		FROM tbl_os
		WHERE tbl_os.fabrica = $login_fabrica AND tbl_os.excluida IS NOT TRUE 
		  AND tbl_os.os IN (
			SELECT tbl_os.os
			FROM tbl_os
			JOIN tbl_produto  ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
			JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os AND tbl_os_extra.i_fabrica = $login_fabrica
			JOIN tbl_extrato  ON tbl_extrato.extrato = tbl_os_extra.extrato AND tbl_extrato.fabrica = $login_fabrica
			JOIN tbl_posto          ON  tbl_posto.posto           = tbl_os.posto
			JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_os.fabrica = $login_fabrica AND tbl_os.excluida IS NOT TRUE ";
	
    if (strlen($linha) > 0) $sql .= " AND tbl_produto.linha = $linha";
	if (strlen($marca) > 0) $sql .= " AND tbl_produto.marca = $marca";
	if (strlen($familia) > 0) $sql .= " AND tbl_produto.familia = $familia";
	if (strlen($posto) > 0) $sql .= " AND tbl_posto.posto = $posto ";
	if (strlen($estado) > 0) $sql .= "AND tbl_posto.estado = '$estado' ";
	if($login_fabrica ==1 && $tipo_data =="osAberta"){
		$sql .= "	AND   tbl_os.data_digitacao BETWEEN '$x_data_inicial' AND '$x_data_final' ";
	}else{
		$sql .= "	AND   tbl_extrato.aprovado BETWEEN '$x_data_inicial' AND '$x_data_final' " ;
	}
	$sql .= " 
		)
		GROUP BY tbl_os.produto;

		CREATE INDEX tmp_rvmt1_produto_$login_admin ON tmp_rvmt1_$login_admin(produto);";

	$sql .= "SELECT  tbl_os.produto         ,
			tbl_peca.referencia    ,
			tbl_peca.descricao     ,
			tbl_os_item.qtde       ,
			tbl_os_item.custo_peca
		INTO TEMP tmp_rvmt2_$login_admin
		FROM    tbl_os_item
		JOIN    tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = $login_fabrica
		JOIN    tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		JOIN    tbl_os         ON tbl_os.os = tbl_os_produto.os
		WHERE tbl_os.fabrica = $login_fabrica AND tbl_os.excluida IS NOT TRUE 
		  AND tbl_os.os IN (
			SELECT tbl_os.os FROM tbl_os 
			WHERE tbl_os.fabrica = $login_fabrica AND tbl_os.excluida IS NOT TRUE
			  AND tbl_os.produto IN (
				SELECT produto FROM tbl_produto WHERE tbl_produto.fabrica_i = $login_fabrica AND linha = 200
			)
			AND   tbl_os.os IN (
				SELECT os FROM tbl_os_extra JOIN tbl_extrato USING (extrato) WHERE tbl_extrato.fabrica = $login_fabrica AND tbl_extrato.aprovado BETWEEN '2006-10-28' AND '2007-01-28'
			)
		)";
				/*
					SELECT tbl_os.os
					FROM tbl_os
					JOIN tbl_produto  USING (produto)
					JOIN tbl_os_extra USING (os)
					JOIN tbl_extrato  USING (extrato)
					JOIN tbl_posto          ON  tbl_posto.posto           = tbl_os.posto
					JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
					WHERE tbl_os.fabrica = $login_fabrica";
	
	if (strlen($linha) > 0) $sql .= " AND tbl_produto.linha = $linha ";
	if (strlen($familia) > 0) $sql .= " AND tbl_produto.familia = $familia";
	if (strlen($posto) > 0) $sql .= " AND tbl_posto.posto = $posto ";
	if (strlen($estado) > 0) $sql .= "AND tbl_posto.estado = '$estado' ";
	
	$sql .= "		AND   tbl_extrato.aprovado BETWEEN $x_data_inicial AND $x_data_final
				) ";
*/
	if ($carvao == "sem") {
		$sql .="
		AND tbl_peca.descricao NOT ILIKE 'escova%'
		AND tbl_peca.descricao NOT ILIKE 'carvao%'
		AND tbl_peca.descricao NOT ILIKE 'carvão%'
		AND tbl_peca.descricao NOT ILIKE 'CARVÃO%' 
		AND tbl_peca.peca      NOT IN (518669, 518573, 518574, 514782, 514783, 521182, 521183, 521184) ";
	}
	$sql .= ";CREATE INDEX tmp_rvmt2_produto_$login_admin ON tmp_rvmt2_$login_admin(produto);";


	$sql .=	"SELECT tbl_produto.produto                                                                     ,
			tbl_produto.referencia                                            AS produto_referencia ,
			tbl_produto.descricao                                             AS produto_descricao  ,
			tbl_produto.voltagem                                              AS produto_voltagem   ,
			CASE WHEN os.qtde         IS NULL THEN 0 ELSE os.qtde         END AS total_qtde         ,
			CASE WHEN os.geral        IS NULL THEN 0 ELSE os.geral        END AS total_geral        ,
			CASE WHEN sum(os.mao_de_obra)  IS NULL THEN 0 ELSE sum(os.mao_de_obra)  END AS total_mo           ,
			CASE WHEN sum(os.pecas)   IS NULL THEN 0 ELSE sum(os.pecas)   END AS total_peca         ,
			CASE WHEN sum(peca.qtde)  IS NULL THEN 0 ELSE sum(peca.qtde)  END AS peca_qtde          ,
			CASE WHEN sum(peca.custo_peca) IS NULL THEN 0 ELSE sum(peca.custo_peca) END AS peca_custo         ,
			peca.referencia                                                   AS peca_referencia    ,
			peca.descricao                                                    AS peca_descricao
		FROM tbl_produto
		JOIN tbl_linha USING (linha)
		LEFT JOIN tmp_rvmt1_$login_admin os   ON os.produto   = tbl_produto.produto
		LEFT JOIN tmp_rvmt2_$login_admin peca ON peca.produto = os.produto
		WHERE tbl_linha.fabrica = $login_fabrica ";
	
	if ($pecas == "com")     $sql .= " AND peca.referencia NOTNULL ";
	elseif ($pecas == "sem") $sql .= " AND peca.referencia ISNULL ";
	
	if ($ordem == "referencia") {
		$sql .= " GROUP BY    tbl_produto.produto   ,
					tbl_produto.referencia,
					tbl_produto.descricao ,
					tbl_produto.voltagem  ,
					total_qtde            ,
					total_geral           ,
					peca_referencia       ,
					peca.descricao
			 ORDER BY    tbl_produto.produto   ,
				     tbl_produto.referencia,
				     peca_qtde             ,
				     peca.referencia;";
	}elseif ($ordem == "geral") {
		$sql .= "      GROUP BY tbl_produto.produto      ,
					tbl_produto.referencia,
					tbl_produto.descricao ,
					tbl_produto.voltagem  ,
					total_qtde            ,
					total_geral           ,
					peca_referencia       ,
					peca.descricao
				ORDER BY    tbl_produto.produto    DESC,
					    tbl_produto.referencia DESC,
					    peca_qtde              DESC;";
	}

	$res = pg_exec($con,$sql);
	
//if (getenv("REMOTE_ADDR") == "201.0.9.216") { echo nl2br($sql)."<br><BR><BR>".pg_numrows($res)."<br><br>"; exit; }
	
	if (pg_numrows($res) > 0) {
		echo "<table border='1' cellpadding='2' cellspacing='1' class='tabela' align='center' WIDTH='700'>";
		$i = 0;
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$produto            = trim(pg_result($res,$x,produto));
			$produto_referencia = trim(pg_result($res,$x,produto_referencia));
			$produto_descricao  = trim(pg_result($res,$x,produto_descricao));
			$produto_voltagem   = trim(pg_result($res,$x,produto_voltagem));
			$total_qtde         = trim(pg_result($res,$x,total_qtde));
			$total_geral        = trim(pg_result($res,$x,total_geral));
			$total_mo           = trim(pg_result($res,$x,total_mo));
			$total_peca         = trim(pg_result($res,$x,total_peca));
			$peca_referencia    = trim(pg_result($res,$x,peca_referencia));
			$peca_descricao     = trim(pg_result($res,$x,peca_descricao));
			$peca_qtde          = trim(pg_result($res,$x,peca_qtde));
			$peca_custo         = trim(pg_result($res,$x,peca_custo));
			
			if ($produto != $produto_anterior) {
				$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
				
				echo "<tr class='subtitulo' height='15'>";
				echo "<td>Produto</TD>";
				echo "<td>Nome</TD>";
				echo "<td>Ocorrência</TD>";
				echo "<td>Total</TD>";
				echo "</tr>";
				echo "<tr  height='15' bgcolor='$cor'>";
				echo "<td align='left'>" . $produto_referencia . "</td>";
				echo "<td align='left'>" . $produto_descricao . " " . $produto_voltagem . "</td>";
				echo "<td>" . $total_qtde . "</td>";
				echo "<td align='right'>" . number_format($total_geral,2,",",".") . "</td>";
				echo "</tr>";
				$i++;
			}
			
			if (strlen($peca_referencia) > 0 && strlen($peca_descricao) > 0) {
				if ($produto != $produto_anterior) {
					echo "<tr class='titulo_coluna' height='15' >";
					echo "<td>Peça</TD>";
					echo "<td>Nome</TD>";
					echo "<td colspan='2'>Qtde</TD>";
					echo "</tr>";
				}
				echo "<tr  height='15' bgcolor='$cor'>";
				echo "<td align='left'>" . $peca_referencia . "</td>";
				echo "<td align='left'>" . $peca_descricao . "</td>";
				echo "<td colspan='2'>" . $peca_qtde . "</td>";
				echo "</tr>";
			}
			
			$produto_anterior = $produto;
		}
		echo "</table>\n";
	}
	else{
		echo "<center>Não Foram Encotrados Resultados para esta Pesquisa</center>";
	}
}


include "rodape.php";
?>

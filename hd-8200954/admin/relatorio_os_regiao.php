<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = 'auditoria';
$layout_menu = 'auditoria';

// HD 2425769 - Recarrega só, não precisa do paralelo... comentando.

include 'autentica_admin.php';

include_once 'funcoes.php';

// if ($gera_automatico != 'automatico'){
// }
// include 'gera_relatorio_pararelo_include.php';

$erro = '';

if (strlen($_POST['btn_acao']) > 0) $btn_acao = strtoupper($_POST['btn_acao']);

if ($btn_acao == 'BUSCAR') {
	$btn_acao2 = $btn_acao;
	$parametros = array(
		'data_inicial','data_final','qtde_os',
		'posto_codigo','posto_nome','regiao',
		'produto_referencia','marca','linha','produto_descricao','produto_voltagem',
		'situacao','ordem','arq_xls'
	);

	if (count($_POST)) {
		foreach($parametros as $postData)
			$$postData = ($_POST[$postData]);

	} else if (count($_GET)) {
		foreach($parametros as $getData)
			$$getData = ($_POST[$getData]);
	}

	if ($data_inicial != 'dd/mm/aaaa' && $data_final != 'dd/mm/aaaa') {

		//Início Validação de Datas
		$nova_data_inicial = dateFormat($data_inicial, 'dmy', 'y-m-d');
		$nova_data_final   = dateFormat($data_final, 'dmy', 'y-m-d');

		if ($nova_data_final < $nova_data_inicial) {
			$erro = "Data Inválida.";
		}

		if (strlen($erro) == 0) {
			if (strtotime($nova_data_inicial.'+3 months') < strtotime($nova_data_final) ) {
				$erro = 'O intervalo entre as datas não pode ser maior que 3 meses';
			}
		} //Fim Validação de Datas

	} else {
		$erro = ' Data Inválida ';
	}

	if (strlen($erro) == 0) {
		if (strlen($posto_codigo) > 0 || strlen($posto_nome) > 0) {
			$sql =	"SELECT tbl_posto.posto                ,
							tbl_posto.nome                 ,
							tbl_posto_fabrica.codigo_posto
					FROM tbl_posto
					JOIN tbl_posto_fabrica USING (posto)
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica";
			if (strlen($posto_codigo) > 0)
				$sql .= " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
			if (strlen($posto_nome) > 0)
				$sql .= " AND tbl_posto.nome ILIKE '%$posto_nome%'";
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) == 1) {
				$posto        = trim(pg_fetch_result($res,0,posto));
				$posto_codigo = trim(pg_fetch_result($res,0,codigo_posto));
				$posto_nome   = trim(pg_fetch_result($res,0,nome));
			}else{
				$erro = ' Posto não encontrado. ';
			}
		}
	}

	if (strlen($erro) == 0) {
		if (strlen($produto_referencia) > 0 || strlen($produto_descricao) > 0 || strlen($produto_voltagem) > 0) {
			$sql =	"SELECT tbl_produto.produto    ,
							tbl_produto.referencia ,
							tbl_produto.descricao  ,
							tbl_produto.voltagem
					FROM tbl_produto
					JOIN tbl_linha USING (linha)
					WHERE tbl_linha.fabrica = $login_fabrica";
			if (strlen($produto_referencia) > 0) {
				$produto_pesquisa = str_replace (".","",$produto_referencia);
				$produto_pesquisa = str_replace ("-","",$produto_pesquisa);
				$produto_pesquisa = str_replace ("/","",$produto_pesquisa);
				$produto_pesquisa = str_replace (" ","",$produto_pesquisa);
				$sql .= " AND tbl_produto.referencia_pesquisa = '$produto_pesquisa'";
			}
			if (strlen($produto_voltagem) > 0)
				$sql .= " AND tbl_produto.voltagem ILIKE '%$produto_voltagem%'";
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) == 1) {
				$produto            = trim(pg_fetch_result($res,0,'produto'));
				$produto_referencia = trim(pg_fetch_result($res,0,'referencia'));
				$produto_descricao  = trim(pg_fetch_result($res,0,'descricao'));
				$produto_voltagem   = trim(pg_fetch_result($res,0,'voltagem'));
			}else{
				$erro = ' Produto não encontrado. ';
			}
		}
	}

	if (!$erro and strlen($linha) == 0 && strlen($regiao) == 0 && strlen($posto) == 0 && strlen($produto) == 0)
		$erro = ' Selecione mais parâmetros para fazer a pesquisa ';
}

$title = ($login_fabrica == 6) ? 'RELATÓRIO DE OS POR ESTADO' : 'RELATÓRIO DE OS POR REGIÃO';

include 'cabecalho.php';
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

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}
</style>

<?
	include 'javascript_pesquisas.php';
	include 'javascript_calendario_new.php';
	include '../js/js_css.php';
?>

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
		alert("Preencha toda ou parte da informação para realizar a pesquisa");
	}
}

$(function(){
	$("#data_inicial").datepick({startDate:"01/01/2000"});
	$("#data_final").datepick({startDate:"01/01/2000"});
	$("#data_inicial").mask("99/99/9999");
	$("#data_final").mask("99/99/9999");
});
</script>

<!--
<script src="js/cal2.js"></script>
<script src="js/cal_conf2.js"></script>
-->
<br>

<?

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0) $btn_acao = trim($_GET["btn_acao"]);

// if (strlen($btn_acao) > 0 && strlen($erro) == 0) {
// 	include "gera_relatorio_pararelo.php";
// }

// HD 2425769 - Recarrega só, não precisa do paralelo... comentando.
// if ($gera_automatico != 'automatico' and strlen($erro)==0){
// 	include "gera_relatorio_pararelo_verifica.php";
// }

?>

<form method="POST" action="<?echo $PHP_SELF?>" name="frm_relatorio">
	<input type="hidden" name="btn_acao">
	<table width="700" border="0" cellpadding="2" cellspacing="0" align="center" class="formulario">

	<? if (strlen($erro) > 0) { ?>

		<tr class="msg_erro">
			<td colspan="5"><?echo $erro?></td>
		</tr>

	<? } ?>
		<tr class="titulo_tabela">
			<td colspan="5">Parâmetros de Pesquisa</td>
		</tr>
		<tr>
			<td width="70">&nbsp;</td>
			<td>
				Data Inicial<br>
				<input type="text" name="data_inicial" id="data_inicial" size="13" maxlength="10" value="<?=$data_inicial?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">

			</td>
			<td colspan="2">
				Data Final<br>
				<input type="text" name="data_final" id="data_final" size="13" maxlength="10" value="<?=$data_final?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">

			</td>
			<td width="70">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="5"></td>
		</tr>
		<tr>
			<td width="70">&nbsp;</td>
			<td <?=($login_fabrica != 1) ? "colspan='5'": ""?>>
				Linha <br>
			<?
			$sql =	"SELECT linha, nome
					FROM tbl_linha
					WHERE fabrica = $login_fabrica
					ORDER BY nome;";
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) > 0) {
				echo "<select name='linha' size='1' class='frm'>";
				echo "<option value=''></option>";
				for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
					$aux_linha      = trim(pg_fetch_result($res,$x,'linha'));
					$aux_linha_nome = trim(pg_fetch_result($res,$x,'nome'));
					echo "<option value='$aux_linha'";
					if ($linha == $aux_linha) echo ' selected';
					echo ">$aux_linha_nome</option>";
				}
				echo "</select>";
			}
			?>
		</td>
<?
if ($login_fabrica == 1) {
?>
			<td colspan="4">
				Marca<br />
				<select name="marca" class="frm">
					<option value=''>&nbsp;</option>
<?
	$sqlMarca = "
		SELECT marca, nome
		  FROM tbl_marca
		 WHERE fabrica = $login_fabrica;
	";
	$resMarca = pg_query($con,$sqlMarca);
	$marcas   = pg_fetch_all($resMarca);

	foreach($marcas as $chave => $valor){
?>
					<option value="<?=$valor['marca']?>" <?=($valor['marca'] == $marca) ? "selected='selected'" : "" ?>><?=$valor['nome']?></option>
<?  } ?>
				</select>
			</td>
<?  } ?>
		</tr>
		<tr>
			<td colspan="5"></td>
		</tr>
		<tr>
			<td width="70">&nbsp;</td>
			<td colspan="5">

			<?
			echo ($login_fabrica <> 6) ? "Região <br>" : "Estado <br>" ;

			if ($login_fabrica <> 6){

				$sql =	"SELECT DISTINCT regiao
						FROM tbl_estado
						WHERE regiao NOTNULL;";

			}else{

				$sql =	"SELECT DISTINCT estado
						FROM tbl_ibge
						WHERE estado NOTNULL order by estado ";

			}
			$res = pg_query($con,$sql);
			if (pg_num_rows($res) > 0) {
				echo "<select name='regiao' size='1' class='frm'>";
				echo "<option value=''></option>";
				for ($x = 0 ; $x < pg_num_rows($res) ; $x++){
					$aux = trim(pg_fetch_result($res,$x,0));
					echo "<option value='$aux'";
					if ($regiao == $aux) echo " selected";
					echo ">$aux</option>";
				}
				echo "</select>";
			}
			?>
			</td>
		</tr>
		<tr>
			<td colspan="5"></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td nowrap>
				Código do Posto<br>
				<input type="text" name="posto_codigo" size="8" value="<?echo $posto_codigo?>" class="frm">
				<img src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.forms[0].posto_codigo, document.forms[0].posto_nome,'codigo');">
			</td>
			<td nowrap colspan="2">
				Nome do Posto<br>
				<input type="text" name="posto_nome" size="15" value="<?echo $posto_nome?>" class="frm">
				<img src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.forms[0].posto_codigo, document.forms[0].posto_nome,'nome');">
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="5"></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td nowrap>
				Referência do Produto<br>
				<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>">
				<img border="0" src="imagens/lupa.png" align="absmiddle" style="cursor: hand;" onclick="javascript: fnc_pesquisa_produto (document.forms[0].produto_referencia, document.forms[0].produto_descricao, 'referencia', document.forms[0].produto_voltagem);">
			</td>
			<td nowrap>
				Descrição do Produto<br>
				<input class="frm" type="text" name="produto_descricao" size="30" value="<? echo $produto_descricao ?>">
				<img border="0" src="imagens/lupa.png" align="absmiddle" style="cursor: hand;" onclick="javascript: fnc_pesquisa_produto (document.forms[0].produto_referencia, document.forms[0].produto_descricao, 'descricao', document.forms[0].produto_voltagem);">
			</td>
			<td>
				Voltagem<br>
				<input class="frm" type="text" name="produto_voltagem" size="5" value="<? echo $produto_voltagem ?>">
			</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td colspan="5"></td>
		</tr>

		<tr>
			<td width="70">&nbsp;</td>
			<td>
				<fieldset>
					<legend>Situação da OS:</legend>
					<table width="100%">
						<tr>
							<td colspan="3" align="left">
								<input type="radio" name="situacao" value="ABERTA" <? if (strlen($situacao) == 0 || $situacao == "ABERTA") echo "checked"; ?>> OS Aberta &nbsp;&nbsp;
								<input type="radio" name="situacao" value="FECHADA"<? if ($situacao == "FECHADA") echo "checked"; ?>> OS Fechada
							</td>
						</tr>
					</table>
				</fieldset>
			</td>
			<td colspan='2'>
				<fieldset>
					<legend>Ordenar Por</legend>
					<table width="100%">
						<tr>
							<td colspan="3" align="left">
								<input type="radio" name="ordem" value="OS" <? if (strlen($ordem) == 0 || $ordem == "OS") echo "checked"; ?>> Ordem de Serviço &nbsp;&nbsp;
								<input type="radio" name="ordem" value="ABERTURA"<? if ($ordem == "ABERTURA") echo "checked"; ?>> Data de Abertura
							</td>
						</tr>
					</table>
				</fieldset>
			</td>
		</tr>
		<tr>
			<td colspan="5"></td>
		</tr>
		<tr>
			<td colspan="5"></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td colspan="3" align="left">
				<input type="checkbox" name="qtde_os" value="qtde_os" <? if ($qtde_os == "qtde_os") echo "checked"; ?>> Postos com mais de 5 OS.<br>
			</td>
			<td >&nbsp;</td>
		</tr>
		<tr>
			<td colspan="5">&nbsp;</td>
		</tr>
		<tr>
			<td >&nbsp;</td>
			<td colspan="3" align="left">
				<input type="checkbox" name="arq_xls" value="arq_xls" <? if ($arq_xls == "arq_xls") echo "checked"; ?>> Apenas o arquivo XLS.<br>
			</td>
			<td >&nbsp;</td>
		</tr>
		<tr>
			<td colspan="5">&nbsp;</td>
		</tr>
		<tr>
			<td colspan="5" align="center">
				<input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;"
					  value="&nbsp;" onClick="javascript: document.frm_relatorio.btn_acao.value='BUSCAR'; document.frm_relatorio.submit();"
					   	alt="Preencha as opções e clique aqui para pesquisar">
			</td>
		</tr>
	</table>
	<iframe style="visibility: hidden; position: absolute;" id="FrameRelatorio"></iframe>
</form>
<?

if (strlen($erro) == 0 && strlen($btn_acao2) > 0) {

	if(strlen($qtde_os) > 0){
		$sql =  "SELECT COUNT(tbl_os.posto) AS total_os_posto, tbl_os.posto
						INTO TEMP TABLE tmp_os_regiao
				FROM tbl_os
				JOIN tbl_posto          ON  tbl_posto.posto           = tbl_os.posto
				JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_produto        ON  tbl_produto.produto       = tbl_os.produto";

		if (strlen($regiao) > 0){

				$sql .=	" JOIN tbl_estado ON tbl_estado.estado = tbl_posto.estado";

		}


		$sql .=	" WHERE tbl_os.fabrica = $login_fabrica";

			if (strlen($data_inicial) > 0 && strlen($data_final) > 0) {
				$sql .= " AND tbl_os.data_abertura BETWEEN '$nova_data_inicial 00:00:00' AND '$nova_data_final 23:59:59'";
			}

			if (strlen($linha) > 0) {
				$sql .= " AND tbl_produto.linha = $linha";
			}
			if (strlen($marca) > 0) {
				$sql .= " AND tbl_produto.marca = $marca";
			}

			if (strlen($regiao) > 0) {
				if ($login_fabrica == 6){
					$sql .= " AND tbl_posto.estado = '$regiao'";
				}else{
					$sql .= " AND tbl_estado.regiao = '$regiao'";
				}
			}

			if (strlen($posto) > 0) {
				$sql .= " AND tbl_os.posto = $posto";
			}

			if (strlen($produto) > 0) {
				$sql .= " AND tbl_produto.produto = $produto";
			}

			if ($situacao == "ABERTA") {
				$sql .= " AND tbl_os.data_fechamento IS NULL";
			}

			if ($situacao == "FECHADA") {
				$sql .= " AND tbl_os.data_fechamento IS NOT NULL";
			}

			$sql .= " AND tbl_os.excluida IS FALSE GROUP BY tbl_os.posto; ";

// 		echo nl2br($sql);
//		echo "<br><br>";
		#exit;
		$res = pg_query($con,$sql);
	}

	$sql =  "SELECT tbl_os.os                                                         ,
					tbl_os.sua_os                                                     ,
					LPAD(tbl_os.sua_os,10,'0')                   AS os_ordem          ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY')   AS abertura          ,
					tbl_os.data_abertura                         AS abertura_ordem    ,
					TO_CHAR(tbl_os.data_fechamento,'DD/MM/YYYY') AS fechamento        ,
					TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf           ,
					tbl_os.data_nf                               AS data_compra       ,
					tbl_os.serie                                                      ,";
	if ($login_fabrica == 6){
		$sql .= "   tbl_os_extra.extrato,";
	}

	$sql .= "
					tbl_os.nota_fiscal                                                ,
					tbl_os.consumidor_revenda                                         ,
					tbl_os.consumidor_nome                                            ,
					tbl_os.revenda_nome                                               ,
					tbl_os.revenda_cnpj                                               ,
					tbl_os.consumidor_email                                           ,
					tbl_os.motivo_atraso                                              ,
					tbl_posto_fabrica.posto                                           ,
					tbl_posto_fabrica.codigo_posto              AS posto_codigo       ,
					tbl_posto.nome                              AS posto_nome         ,
					tbl_posto.estado                            AS posto_estado       ,
					tbl_posto.nome                              AS posto_ordem        ,
					tbl_produto.referencia                      AS produto_referencia ,
					tbl_produto.descricao                       AS produto_descricao  ,
					tbl_produto.voltagem                        AS produto_voltagem
			FROM tbl_os
			JOIN tbl_posto          ON  tbl_posto.posto           = tbl_os.posto
			JOIN tbl_posto_fabrica  ON  tbl_posto_fabrica.posto   = tbl_posto.posto
									AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_produto        ON  tbl_produto.produto       = tbl_os.produto AND tbl_produto.fabrica_i = tbl_os.fabrica";
	if(strlen($qtde_os) > 0)
		$sql .= " JOIN tmp_os_regiao ON tbl_os.posto = tmp_os_regiao.posto ";

	if (strlen($regiao) > 0){

			$sql .=	" JOIN tbl_estado ON tbl_estado.estado = tbl_posto.estado";

	}

	if ($login_fabrica == 6){
		$sql .= " LEFT JOIN tbl_os_extra on tbl_os.os = tbl_os_extra.os";
	}

	$sql .=	" WHERE tbl_os.fabrica = $login_fabrica";

	if (strlen($data_inicial) > 0 && strlen($data_final) > 0) {
		$sql .= " AND tbl_os.data_abertura BETWEEN '$nova_data_inicial 00:00:00' AND '$nova_data_final 23:59:59'";
	}

	if (strlen($linha) > 0) {
		$sql .= " AND tbl_produto.linha = $linha";
	}

	if (strlen($marca) > 0) {
		$sql .= " AND tbl_produto.marca = $marca";
	}

	if (strlen($regiao) > 0) {
		if ($login_fabrica == 6) {
			$sql .= " AND tbl_posto.estado = '$regiao'";
		} else {
			$sql .= " AND tbl_estado.regiao = '$regiao'";
		}


	}

	if (strlen($posto) > 0) {
		$sql .= " AND tbl_os.posto = $posto";
	}

	if (strlen($produto) > 0) {
		$sql .= " AND tbl_produto.produto = $produto";
	}

	if ($situacao == "ABERTA") {
		$sql .= " AND tbl_os.data_fechamento IS NULL";
	}

	if ($situacao == "FECHADA") {
		$sql .= " AND tbl_os.data_fechamento IS NOT NULL";
	}

	if(strlen($qtde_os) > 0){
		$sql .= " AND tmp_os_regiao.total_os_posto > 5 ";
	}

	$sql .= " AND tbl_os.excluida IS FALSE ORDER BY posto_ordem ASC";

	if ($ordem == "OS")
		$sql .= ", os_ordem DESC;";

	if ($ordem == "ABERTURA")
		$sql .= ", abertura_ordem DESC;";

	// die (nl2br($sql));

	$res = pg_query($con,$sql);
	$totRows = pg_num_rows($res);

	if ($totRows > 0) {

		if($login_fabrica == 1 or $login_fabrica == 6){
			$data = date ("d/m/Y H:i:s");

			$arquivo_nome = "relatorio-os-por-regiao-$login_admin.xls";
			#$path        = "/mnt/home/gabriel/public_html/posvenda/admin/xls/";
			$path         = "/www/assist/www/admin/xls/";
			$path_tmp     = "/tmp/";

			$arquivo_completo     = $path.$arquivo_nome;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

			echo `rm $arquivo_completo_tmp `;
			echo `rm $arquivo_completo `;

			$fp = fopen ($arquivo_completo_tmp,"w");

			fputs ($fp,"<html>");
			fputs ($fp,"<head>");

			if ($login_fabrica == 6) {
				fputs ($fp,"<title>RELATORIO OS POR ESTADO - $data");
			}else{
				fputs ($fp,"<title>RELATORIO OS POR REGIÃO - $data");
			}

			fputs ($fp,"</title>");
			fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs ($fp,"</head>");
			fputs ($fp,"<body>");

			fputs ($fp,"<table width='100%' align='left' border='1' cellpadding='2' cellspacing='2'>");
			fputs ($fp,"<tr bgcolor='#AFC4FF' >");
			if ($login_fabrica <> 6){

				fputs ($fp,"<td align='center'><b>POSTO</b></td>");
				fputs ($fp,"<td align='center'><b>OS</b></td>");
				fputs ($fp,"<td align='center'><b>AB</b></td>");
				fputs ($fp,"<td align='center'><b>FC</b></td>");
				fputs ($fp,"<td align='center'><b>NF</b></td>");
				fputs ($fp,"<td align='center'><b>DATA NF</b></td>");
				fputs ($fp,"<td align='center'><b>REFERÊNCIA</b></td>");
				fputs ($fp,"<td align='center'><b>DESCRIÇÃO</b></td>");
				fputs ($fp,"<td align='center'><b>CLIENTE</b></td>");
				fputs ($fp,"<td align='center'><b>E-MAIL</b></td>");

			} else {

				fputs ($fp,"<td align='center'><b>POSTO</b></td>");
				fputs ($fp,"<td align='center'><b>UF</b></td>");
				fputs ($fp,"<td align='center'><b>OS</b></td>");
				fputs ($fp,"<td align='center'><b>Data de Abertura</b></td>");
				fputs ($fp,"<td align='center'><b>Data de Compra</b></td>");
				fputs ($fp,"<td align='center'><b>Diferença de dias entre Data de Abertura e Data de Compra</b></td>");
				fputs ($fp,"<td align='center'><b>Produto</b></td>");
				fputs ($fp,"<td align='center'><b>Prod. Série</b></td>");
				fputs ($fp,"<td align='center'><b>NF</b></td>");
				fputs ($fp,"<td align='center'><b>Revenda</b></td>");
				fputs ($fp,"<td align='center'><b>Extrato</b></td>");
			}

			fputs ($fp,"</tr>");


			for ($x = 0; $x < $totRows; $x++) {
				$os                 = trim(pg_fetch_result($res,$x,"os"));
				$sua_os             = trim(pg_fetch_result($res,$x,"sua_os"));
				$abertura           = trim(pg_fetch_result($res,$x,"abertura"));
				$fechamento         = trim(pg_fetch_result($res,$x,"fechamento"));
				$serie              = trim(pg_fetch_result($res,$x,"serie"));
				$consumidor_revenda = trim(pg_fetch_result($res,$x,"consumidor_revenda"));
				$consumidor_nome    = trim(pg_fetch_result($res,$x,"consumidor_nome"));
				$revenda_nome       = trim(pg_fetch_result($res,$x,"revenda_nome"));
				$consumidor_email   = trim(pg_fetch_result($res,$x,"consumidor_email"));
				$motivo_atraso      = trim(pg_fetch_result($res,$x,"motivo_atraso"));
				$posto              = trim(pg_fetch_result($res,$x,"posto"));
				$posto_codigo       = trim(pg_fetch_result($res,$x,"posto_codigo"));
				$posto_nome         = trim(pg_fetch_result($res,$x,"posto_nome"));
				$produto_referencia = trim(pg_fetch_result($res,$x,"produto_referencia"));
				$produto_descricao  = trim(pg_fetch_result($res,$x,"produto_descricao"));
				$produto_voltagem   = trim(pg_fetch_result($res,$x,"produto_voltagem"));
				$data_nf            = trim(pg_fetch_result($res,$x,"data_nf"));
				$data_compra        = trim(pg_fetch_result($res,$x,"data_compra"));
				$abertura_ordem     = trim(pg_fetch_result($res,$x,"abertura_ordem"));
				$revenda_cnpj       = trim(pg_fetch_result($res,$x,"revenda_cnpj"));
				$nf       			= trim(pg_fetch_result($res,$x,"nota_fiscal"));
				$posto_estado       = trim(pg_fetch_result($res,$x,"posto_estado"));

				$extrato            = ($login_fabrica == 6) ? trim(pg_fetch_result($res,$x,"extrato")) : "" ;


				if ($x % 2 == 0) {
					$cor   = "#F1F4FA";

				}else{
					$cor   = "#C4C4C4";

				}

				fputs ($fp,"<tr bgcolor='$cor'>");

				if ($login_fabrica == 6) {

					fputs ( $fp,"<td align='left' nowrap>&nbsp;" . $posto_codigo . " - " . $posto_nome . "&nbsp;</td>" );
					fputs ($fp,"<td align='left' nowrap>&nbsp;" . $posto_estado . "&nbsp;</td>");
					fputs ($fp,"<td align='left' nowrap>&nbsp;" . $sua_os . "&nbsp;</td>");
					fputs ($fp,"<td align='left' nowrap>&nbsp;" . $abertura . "&nbsp;</td>");
					fputs ($fp,"<td align='left' nowrap>&nbsp;" . $data_nf . "&nbsp;</td>");

					$d1 = explode('-', $data_compra);
					$d2 = explode('-', $abertura_ordem);

					$d2 = mktime(0, 0, 0, $d2[1], $d2[2], $d2[0]);
					$d1 = mktime(0, 0, 0, $d1[1], $d1[2], $d1[0]);

					$dif_dias = ($d2 - $d1)/86400;

					fputs ($fp,"<td align='left' nowrap>&nbsp;" . ceil($dif_dias)."&nbsp;</td>");

					fputs ($fp,"<td align='left' nowrap>&nbsp;" . $produto_referencia . " - " . $produto_descricao . "&nbsp;</td>");
					fputs ($fp,"<td align='left' nowrap>&nbsp;" . $serie . "&nbsp;</td>");
					fputs ($fp,"<td align='left' nowrap>&nbsp;" . $nf . "&nbsp;</td>");
					fputs ($fp,"<td align='left' nowrap>&nbsp;" . $revenda_cnpj . " - " . $revenda_nome . "&nbsp;</td>");
					fputs ($fp,"<td align='left' nowrap>&nbsp;" . $extrato . "&nbsp;</td>");

				}else{

					fputs ($fp,"<td align='left' nowrap>&nbsp;" . $posto_codigo. " - " .$posto_nome. "&nbsp;</td>");
					fputs ($fp,"<td align='left' nowrap>&nbsp;" . $posto_codigo . $sua_os . "&nbsp;</td>");
					fputs ($fp,"<td align='left' nowrap>&nbsp;" . $abertura . "&nbsp;</td>");
					fputs ($fp,"<td align='center' nowrap>&nbsp;" . $fechamento . "&nbsp;</td>");
					fputs ($fp,"<td align='center' nowrap>&nbsp;" . $nf . "&nbsp;</td>");
					fputs ($fp,"<td align='center' nowrap>&nbsp;" . $data_nf . "&nbsp;</td>");
					fputs ($fp,"<td align='center' nowrap>&nbsp;" . $produto_referencia . "&nbsp;</td>");
					fputs ($fp,"<td align='center' nowrap>&nbsp;" . $produto_descricao . "&nbsp;</td>");
					if($consumidor_revenda <> 'R'){
						fputs ($fp,"<td align='center' nowrap>&nbsp;". $consumidor_nome ."&nbsp;</td>");
					}else{
						fputs ($fp,"<td align='center' nowrap>&nbsp;". $revenda_nome ."&nbsp;</td>");
					}
					fputs ($fp,"<td align='center' nowrap>&nbsp;". $consumidor_email ."&nbsp;</td>");

				}
				fputs ($fp,"</tr>");

			}
			fputs ($fp,"</table>");
			fputs ($fp,"</body>");
			fputs ($fp,"</html>");
			fclose ($fp);
			echo ` cp $arquivo_completo_tmp $path `;

			$data = date("Y-m-d.H-i-s");

			echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
			#echo ` cd $path; rm $arquivo_nome.zip; zip $arquivo_nome.zip $arquivo_nome > NULL`;

			echo "<br /><div align='center' class='PesquisaTabela'>A pesquisa retornou <strong>$totRows</strong> resultados.</div>";

			echo"<p style='font:normal normal 1em Verdana, Arial, sans-serif;color:black;text-align:center;margin:auto'>Clique aqui para fazer o <a href='xls/relatorio-os-por-regiao-$login_admin.xls' style='font-family:Arial, Verdana, Times, Sans;font-size:13px;color:#0000FF'>download do arquivo em EXCEL</a>.<br><span style='font-size:0.84em'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</span></p>";

			if($arq_xls == 'arq_xls'){
				include "rodape.php";
				exit;
			}

		}
		flush();
		echo "<br><br><table border='0' cellspacing='0' cellpadding='0' align='center'>";
		echo "<tr height='18'>";
		echo "<td width='18' bgcolor='#FFCC66'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; OSs sem lancamento de itens há mais de 5 dias, efetue o lançamento</b></font></td>";
		echo "</tr>";
		echo "<tr height='3'><td colspan='2'></td></tr>";
		echo "<tr height='18'>";
		echo "<td width='18' bgcolor='#FF0000'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; OSs que excederam o prazo limite de 30 dias para fechamento, informar \"Motivo\"</b></font></td>";
		echo "</tr>";
		echo "<tr height='3'><td colspan='2'></td></tr>";
		echo "<tr height='18'>";
		echo "<td width='18' bgcolor='#91C8FF'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; OSs abertas há mais de 25 dias sem data de fechamento</b></font></td>";
		echo "</tr>";
		echo "</table>";

		echo "<br>";

		echo "<table border='1' cellpadding='2' cellspacing='1' align='center' class='tabela'>";

		$sqlX = "SELECT TO_CHAR(CURRENT_DATE,'YYYY-MM-DD')";
		$resX = pg_query($con,$sqlX);
		$aux_atual = pg_fetch_result($resX,0,0);

		$sqlX = "SELECT TO_CHAR(CURRENT_DATE + INTERVAL '5 days','YYYY-MM-DD')";
		$resX = pg_query($con,$sqlX);
		$data_hj_mais_5 = pg_fetch_result($resX,0,0);

		for ($x = 0; $x < $totRows; $x++) {
			$os                 = trim(pg_fetch_result($res,$x,'os'));
			$sua_os             = trim(pg_fetch_result($res,$x,'sua_os'));
			$abertura           = trim(pg_fetch_result($res,$x,'abertura'));
			$fechamento         = trim(pg_fetch_result($res,$x,'fechamento'));
			$serie              = trim(pg_fetch_result($res,$x,'serie'));
			$consumidor_revenda = trim(pg_fetch_result($res,$x,'consumidor_revenda'));
			$consumidor_nome    = trim(pg_fetch_result($res,$x,'consumidor_nome'));
			$revenda_nome       = trim(pg_fetch_result($res,$x,'revenda_nome'));
			$consumidor_email   = trim(pg_fetch_result($res,$x,'consumidor_email'));
			$motivo_atraso      = trim(pg_fetch_result($res,$x,'motivo_atraso'));
			$posto              = trim(pg_fetch_result($res,$x,'posto'));
			$posto_codigo       = trim(pg_fetch_result($res,$x,'posto_codigo'));
			$posto_nome         = trim(pg_fetch_result($res,$x,'posto_nome'));
			$posto_estado       = trim(pg_fetch_result($res,$x,'posto_estado'));
			$produto_referencia = trim(pg_fetch_result($res,$x,'produto_referencia'));
			$produto_descricao  = trim(pg_fetch_result($res,$x,'produto_descricao'));
			$produto_voltagem   = trim(pg_fetch_result($res,$x,'produto_voltagem'));
			$data_nf            = trim(pg_fetch_result($res,$x,'data_nf'));
			$data_compra        = trim(pg_fetch_result($res,$x,'data_compra'));
			$abertura_ordem     = trim(pg_fetch_result($res,$x,'abertura_ordem'));
			$revenda_cnpj       = trim(pg_fetch_result($res,$x,'revenda_cnpj'));
			$nf       			= trim(pg_fetch_result($res,$x,'nota_fiscal'));
			$extrato            = ($login_fabrica == 6) ? trim(pg_fetch_result($res,$x,'extrato')) : '' ;

			if (empty($extrato) && $login_fabrica == 6){
				$extrato = "&nbsp;";
			}

			if ($posto_anterior != $posto) {
				echo "<tr class='titulo_tabela' height='15'>";
				if($login_fabrica == 1){
					echo "<td nowrap align='left' colspan='100%' style='font-size:14px;'>Posto: $posto_codigo - $posto_nome</td>";
				}else{
					if ($login_fabrica == 6){
						$colspan_td = '10';
					}else{
						$colspan_td = '100%';
					}
					echo "<td nowrap align='left' colspan='$colspan_td' style='font-size:14px;'>Posto: $posto_codigo - $posto_nome</td>";
				}
				echo "</tr>";
				echo "<tr class='titulo_coluna' height='15'>";
				if ($login_fabrica <> 6){
					echo '<td>OS</td>';
					echo '<td>AB</td>';
					echo '<td>FC</td>';
					echo '<td>NF</td>';
					echo '<td>DATA NF</td>';
					echo '<td>Produto</td>';
					if($login_fabrica <> 1){
						echo '<td>Motivo</td>';
					}
					if($login_fabrica == 1){
						echo '<td>Cliente</td>';
						echo '<td>E-mail</td>';
					}
				}else{
					echo '<td>UF Posto</td>';
					echo '<td>OS</td>';
					echo '<td>Data Abertura</td>';
					echo '<td>Data Compra</td>';
					echo '<td>Dif. de dias entre Data Compra e Data Abertura</td>';
					echo '<td>Produto</td>';
					echo '<td>Prod. Série</td>';
					echo '<td>NF</td>';
					echo '<td>Revenda</td>';
					echo '<td>Extrato</td>';
				}
				echo ($login_fabrica <> 6) ? "<td colspan='2'>Ações</td>" : "";
				echo "</tr>";
			}

			if ($x % 2 == 0) {
				$cor   = "#F1F4FA";
				$botao = "azul";
			}else{
				$cor   = "#F7F5F0";
				$botao = "amarelo";
			}

			// CONDIÇÕES PARA BLACK & DECKER - INÍCIO
			// Verifica se não possui itens com 5 dias de lançamento
			if ($login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '5 days','YYYY-MM-DD')";
				$resX = pg_query($con,$sqlX);
				$aux_consulta = pg_fetch_result($resX,0,0);

				$sql = "SELECT COUNT(tbl_os_item.*) AS total_item
						FROM tbl_os_item
						JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						JOIN tbl_os         ON tbl_os.os                 = tbl_os_produto.os
						WHERE tbl_os.os = $os
						AND   tbl_os.data_abertura::date >= '$aux_consulta'";
				$resItem = pg_query($con,$sql);

				$itens = pg_fetch_result($resItem,0,total_item);

				if ($itens == 0 && $aux_consulta > $data_hj_mais_5) $cor = "#FFCC66";

				$mostra_motivo = 2;
			}

			// Verifica se está sem fechamento há 20 dias ou mais da data de abertura
			if (strlen($fechamento) == 0 && $mostra_motivo == 2 && $login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '20 days','YYYY-MM-DD')";
				$resX = pg_query($con,$sqlX);
				$aux_consulta = pg_fetch_result($resX,0,0);

				if ($consumidor_revenda != "R") {
					if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
						$mostra_motivo = 1;
						$cor = "#91C8FF";
					}
				}
			}

			// Se estiver acima dos 30 dias, não exibirá os botões
			if (strlen($fechamento) == 0 && $login_fabrica == 1) {
				$aux_abertura = fnc_formata_data_pg($abertura);

				$sqlX = "SELECT TO_CHAR($aux_abertura::date + INTERVAL '30 days','YYYY-MM-DD')";
				$resX = pg_query($con,$sqlX);
				$aux_consulta = pg_fetch_result($resX,0,0);

				$sqlX = "SELECT TO_CHAR(current_date,'YYYY-MM-DD')";
				$resX = pg_query($con,$sqlX);
				$aux_atual = pg_fetch_result($resX,0,0);

				if ($consumidor_revenda != "R"){
					if ($aux_consulta < $aux_atual && strlen($fechamento) == 0) {
						$mostra_motivo = 1;
						$cor = "#FF0000";
					}
				}
			}
			// CONDIÇÕES PARA BLACK & DECKER - FIM

			echo "<tr class='Conteudo' height='15' bgcolor='$cor'>";
			if ($login_fabrica == 6){
				?>
				<td nowrap>
					<?php echo $posto_estado ?>
				</td>
				<td nowrap>
					<a href="os_press.php?os=<?=$os?>" target="_blank" >
						<?php echo $sua_os ?>
					</a>
				</td>
				<td nowrap>
					<?php echo $abertura; ?>
				</td>
				<td nowrap>
					<?php echo $data_nf; ?>
				</td>
				<td nowrap>
					<?php
					$d1 = explode('-', $data_compra);
					$d2 = explode('-', $abertura_ordem);

					$d2 = mktime(0, 0, 0, $d2[1], $d2[2], $d2[0]);
					$d1 = mktime(0, 0, 0, $d1[1], $d1[2], $d1[0]);

					$dif_dias = ($d2 - $d1)/86400;
					echo ceil($dif_dias);
					?>
				</td>
				<td nowrap>
					<?php echo $produto_referencia . " - " . $produto_descricao; ?>
				</td>
				<td nowrap>
					<?php echo $serie ?>
				</td>
				<td nowrap>
					<?php echo $nf ?>
				</td>
				<td nowrap>
					<?php echo $revenda_cnpj . " - " . $revenda_nome ?>
				</td>
				<td nowrap>
					<?php echo $extrato ?>
				</td>

				<?php
			}else{

				echo "<td nowrap>" . $posto_codigo . $sua_os . "</td>";
				echo "<td nowrap>" . $abertura . "</td>";
				echo "<td nowrap>" . $fechamento . "</td>";
				echo "<td nowrap>" . $nf . "</td>";
				echo "<td nowrap>" . $data_nf . "</td>";
				$produto = $produto_referencia . " - " . $produto_descricao;
				echo "<td nowrap align='left'>" . $produto . "</td>";
				if($login_fabrica <> 1){
					echo "<td nowrap align='left'>" . $motivo_atraso . "</td>";
				}
				if($login_fabrica == 1){
					echo "<td nowrap align='left'>"; if($consumidor_revenda <> 'R') echo $consumidor_nome; else echo $revenda_nome; echo "</td>";
					echo "<td nowrap>$consumidor_email</td>";
				}

				echo "<td width='60' align='center'>";
				echo "<a href='os_cadastro.php?os=$os' target='_blank'>Alterar</a>";
				echo "</td>\n";

				echo "<td width='60' align='center'>";
				echo "<a href='os_press.php?os=$os' target='_blank'>Consultar</a>";
				echo "</td>\n";

			}
			echo '</tr>';
			$posto_anterior = $posto;
		}
		echo "</table>\n";
	}

	else{
		echo '<center>Nenhum Resultado Encontrado</center>';
	}
}

include 'rodape.php';

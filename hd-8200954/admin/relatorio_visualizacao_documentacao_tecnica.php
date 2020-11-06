<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

$admin_privilegios="info_tecnica";
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

if(strlen($_POST['btn_acao'])>0) $btn_acao = $_POST['btn_acao'];
else                             $btn_acao = $_GET['btn_acao'];

if($btn_acao=="PESQUISAR") {
	if(strlen($_POST["data_inicial"])>0) $data_inicial = trim($_POST["data_inicial"]);
	else                                 $data_inicial = trim($_GET["data_inicial"]);

	if(strlen($_POST["data_final"])>0) $data_final = trim($_POST["data_final"]);
	else                               $data_final = trim($_GET["data_final"]);

	 if(empty($data_inicial) OR empty($data_final)){
        $erro = "Data Inválida";
    }
    
    if(strlen($erro)==0){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(!checkdate($mi,$di,$yi)) 
            $erro = "Data Inválida";
    }
    if(strlen($erro)==0){
        list($df, $mf, $yf) = explode("/", $data_final);
        if(!checkdate($mf,$df,$yf)) 
            $erro = "Data Inválida";
    }

    if(strlen($erro)==0){
        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";
    }
    if(strlen($msg_erro)==0){
        if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
            $erro = "Data Inválida.";
        }
    }



	if(strlen($erro)==0){
		if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -3 month')) {
			$erro = 'O intervalo entre as datas não pode ser maior que 3 meses.';
		}
	 }
	
	if (strlen($erro) == 0) {
		$cond_1 = " 1=1 ";
		if(strlen($aux_data_inicial)>0 AND strlen($aux_data_final)>0){
			$cond_1 = " tbl_comunicado_posto_blackedecker.data_confirmacao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59'";
		}

		$codigo_posto = $_POST["codigo_posto"];
		$cond_2 = " 1=1 ";
		if(strlen($codigo_posto) > 0){
			$cond_2 = " tbl_posto_fabrica.codigo_posto = '$codigo_posto' ";
		}else{
			#$erro .= "Favor informar o posto para pesquisa<br>";
		}

		$tipo = $_POST["tipo"];
		$cond_3 = " 1=1 ";
		if(strlen($tipo)>0){
			if($tipo=="vista_explodida"){
				$cond_3 = " tbl_comunicado.tipo = 'Vista Explodida' ";
			}

			if($tipo=="manual_servico"){
				$cond_3 = " tbl_comunicado.tipo = 'Manual de Serviço' ";
			}

			if($tipo=="comunicado"){
				$cond_3 = " tbl_comunicado.tipo = 'Comunicado' ";
			}

			if($tipo=="todos"){
				$cond_3 = " tbl_comunicado.tipo IN('Vista Explodida', 'Manual de Serviço', 'Comunicado') ";
			}
		}else{
			$cond_3 = " tbl_comunicado.tipo IN('Vista Explodida', 'Manual de Serviço', 'Comunicado') ";
		}
	}

	if (strlen($erro) > 0) {
		$data_inicial    = trim($_POST["data_inicial"]);
		$data_final      = trim($_POST["data_final"]);
		$codigo_posto    = trim($_POST["codigo_posto"]);
		$posto_nome      = trim($_POST["posto_nome"]);
		$tipo            = trim($_POST["tipo"]);

		$msg .= $erro;
		
	}
}

$layout_menu = "tecnica";
$title = "RELATÓRIO DE VISUALIZAÇÃO DE DOCUMENTAÇÃO TÉCNICA";

include "cabecalho.php";

?>
<style>
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

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
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

table.tabela{
	empty-cells:show;
	border-spacing: 1px;
}

</style>
<!--[if lt IE 8]>
<style>
table.tabela {
border-collapse: collapse;
empty-cells:show;
border-spacing: 2px;
}

</style>
<![endif]-->

<? include "javascript_calendario.php"; ?>

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


<form name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<table width='700' class='formulario'  border='0' cellpadding='5' cellspacing='1' align='center'>
	<?php if(strlen($msg)>0){ ?>
		<tr class="msg_erro">
			<td><?php echo $msg; ?> </td>
		</tr>
	<?php } ?>
	<tr>
		<td class='titulo_tabela'>Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td valign='bottom' align="center">
			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='formulario'>
				<tr>
					<td width="10">&nbsp;</td>
					<td style='padding:0 0 0 130px;' width='130'>Data Inicial <br />
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
					</td>
					<td>Data Final <br />
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" >
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				
				<tr>
					<td width="10">&nbsp;</td>
					<td nowrap style='padding:0 0 0 130px;'>Código Posto <br />
						<input type="text" name="codigo_posto" id="codigo_posto" size="12"  value="<? echo $codigo_posto ?>" class="frm">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
					</td>
					<td nowrap>Nome do Posto <br />
						<input type="text" name="posto_nome" id="posto_nome" size="30"  value="<?echo $posto_nome?>" class="frm">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
					</td>
					<td width="10">&nbsp;</td>
				</tr>
				
				<tr>
					<td width="10">&nbsp;</td>
					<td align='left' nowrap colspan="4" style='padding:0 0 0 130px;'>
						<fieldset style='width:400px;'>
							<legend>Tipo</legend>
							<table>
								<tr>
									<td align='left' nowrap>
										<INPUT TYPE="radio" NAME="tipo" VALUE="vista_explodida" <? if($tipo=="vista_explodida") echo "checked"; ?>>
										Vista Explodida
									</td>
									<td align='left' nowrap>
										<INPUT TYPE="radio" NAME="tipo" VALUE="manual_servico" <? if($tipo=="manual_servico") echo "checked"; ?>>
										Manual de Serviço
									</td>
									<td align='left' nowrap>
										<INPUT TYPE="radio" NAME="tipo" VALUE="comunicado" <? if($tipo=="comunicado") echo "checked"; ?>>
										Comunicado
									</td>
									<td align='left' nowrap>
										<INPUT TYPE="radio" NAME="tipo" VALUE="todos" <? if($tipo=="" or $tipo=="todos") echo "checked"; ?>>
										Todos
									</td>
								</tr>
							</table>
						</fieldset>
						
					</td>
					<td width="10">&nbsp;</td>
				</tr>

			</table>
			<br>
			<INPUT TYPE="hidden" NAME="btn_acao">
			<input type="button" value="Pesquisar" border="0" onClick="document.frm_relatorio.btn_acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar">
		</td>
	</tr>
</table>
</form>

<?
if($btn_acao=="PESQUISAR" AND strlen($msg)==0){
	$sql = "SELECT  tbl_posto_fabrica.codigo_posto               ,
					tbl_posto.nome                               ,
					tbl_comunicado.tipo                          ,
					tbl_comunicado.descricao                     ,
					tbl_produto.referencia  AS referencia_produto,
					tbl_produto.descricao   AS descricao_produto ,
					tbl_comunicado_posto_blackedecker.data_confirmacao
			FROM tbl_comunicado_posto_blackedecker
			JOIN tbl_comunicado ON tbl_comunicado.comunicado = tbl_comunicado_posto_blackedecker.comunicado
			JOIN tbl_posto ON tbl_posto.posto = tbl_comunicado_posto_blackedecker.posto
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_comunicado_posto_blackedecker.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto
			WHERE $cond_1
			AND   $cond_2
			AND   $cond_3
			AND tbl_comunicado.fabrica = $login_fabrica
			AND tbl_comunicado.ativo IS TRUE
			ORDER BY tbl_posto.nome, tbl_comunicado.tipo, tbl_comunicado_posto_blackedecker.data_confirmacao DESC";
			#echo nl2br($sql); exit;
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
		$data = date("Y-m-d").".".date("H-i-s");

		$arquivo_nome     = "relatorio-visualizacao-documentacao-tecnica-$data.xls";
		$path             = "/www/assist/www/admin/xls/";
		$path_tmp         = "/tmp/";

		$arquivo_completo     = $path.$arquivo_nome;

		$fp = fopen ($arquivo_completo,"w");

		echo "<BR>";
		$conteudo = "<TABLE width='100%' border='0' cellpadding='2' cellspacing='1' class='tabela' align='center'>";
		$conteudo .=  "<TR class='titulo_coluna'>";
			$conteudo .=  "<TD>Código Posto</TD>";
			$conteudo .=  "<TD>Nome Posto</TD>";
			$conteudo .=  "<TD>Tipo</TD>";
			$conteudo .=  "<TD>Descrição do Documento</TD>";
			$conteudo .=  "<TD>Referência Produto</TD>";
			$conteudo .=  "<TD>Descrição Produto</TD>";
		$conteudo .=  "</TR>";
		for($i=0; $i<pg_numrows($res); $i++){
			$codigo_posto       = pg_result($res,$i,codigo_posto);
			$nome_posto         = pg_result($res,$i,nome);
			$tipo               = pg_result($res,$i,tipo);
			$descricao          = pg_result($res,$i,descricao);
			$referencia_produto = pg_result($res,$i,referencia_produto);
			$descricao_produto  = pg_result($res,$i,descricao_produto);
			
			$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

			$conteudo .=  "<TR bgcolor='$cor'>";
				$conteudo .=  "<TD align='center' nowrap>$codigo_posto</TD>";
				$conteudo .=  "<TD align='left' nowrap>$nome_posto</TD>";
				$conteudo .=  "<TD align='center' nowrap>$tipo</TD>";
				$conteudo .=  "<TD align='left' nowrap>$descricao</TD>";
				$conteudo .=  "<TD align='center' nowrap>$referencia_produto</TD>";
				$conteudo .=  "<TD align='left' nowrap>$descricao_produto</TD>";
			$conteudo .=  "</TR>";
		}
		$conteudo .=  "</TABLE>";

		fputs ($fp,$conteudo);
		fclose ($fp);
		flush();

		echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<tr>";
		echo "<td align='center'><input type='button' value='Download em Excel' onclick=\"window.location='xls/$arquivo_nome'\"></td>";
		echo "</tr>";
		echo "</table>";
		
		echo "<br>";
		echo $conteudo;
		echo "<br>";
	}else{
		echo "<P>Nenhum resultado encontrado!</P>";
	}
}
?>

<? include "rodape.php" ?>

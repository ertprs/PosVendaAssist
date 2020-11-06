<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';

$layout_menu = "tecnica";

$title = "PESQUISA DE COMUNICADOS";

$tipo_busca = $_GET["tipo_busca"];
$btn_acao   = $_POST["btn_acao"];

if ($btn_acao=="Consultar" or strlen($busca)>0){
	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$codigo_posto       =$_POST['codigo_posto'];
	$codigo_posto       = trim(strtoupper($_POST['codigo_posto']));
	$posto_nome         = trim(strtoupper($_POST['posto_nome']));
	$produto_referencia = trim(strtoupper($_POST['produto_referencia']));
	$tipo               = $_POST['psq_tipo'];
	$descricao          = $_POST['psq_descricao'];

	if(!$data_inicial OR !$data_final){
        $msg_erro = "Data Inválida";
    }

	if(strlen($msg_erro)==0){
		$xdata_inicial = dateFormat($data_inicial, 'dmy');
        $xdata_final   = dateFormat($data_final, 'dmy');

		if (!$xdata_inicial or !$xdata_final or $xdata_final < $xdata_inicial) {
            $msg_erro = "Data Inválida.";
        }
	}
      


	if ($tipo_busca=="posto"){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";

		if ($busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	exit;
	}
	if ($tipo_busca=="produto"){
		$sql = "SELECT tbl_produto.produto,
						tbl_produto.referencia,
						tbl_produto.descricao
				FROM tbl_produto
				JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
				WHERE tbl_linha.fabrica = $login_fabrica ";

		if ($busca == "codigo"){
			$sql .= " AND UPPER(tbl_produto.descricao) like UPPER('%$q%') ";
		}else{
			$sql .= " AND tbl_produto.referencia like '%$q%' ";
		}

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$produto    = trim(pg_result($res,$i,produto));
				$referencia = trim(pg_result($res,$i,referencia));
				$descricao  = trim(pg_result($res,$i,descricao));
				echo "$produto|$descricao|$referencia";
				echo "\n";
			}
		}
	exit;
	}
}

include "cabecalho.php";

?>
<? include "javascript_pesquisas.php"; ?>
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script>


<script language="JavaScript">
//Pesquisa pelo AutoComplete AJAX

$(document).ready(function() {
	$("#relatorio").tablesorter({
		sortMultiSortKey: 'altKey'
	});
		} );
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


	/* Busca por Produto */
	$("#produto_descricao").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#produto_descricao").result(function(event, data, formatted) {
		$("#produto_referencia").val(data[2]) ;
	});

	/* Busca pelo Nome */
	$("#produto_referencia").autocomplete("<?echo $PHP_SELF.'?tipo_busca=produto&busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#produto_referencia").result(function(event, data, formatted) {
		$("#produto_descricao").val(data[1]) ;
		//alert(data[2]);
	});

});
</script>


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

.sub_os{
	font-size:10px;
	color:#676767;
}
</style>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<script language='javascript' src='../ajax.js'></script>



<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<table width='700' class='formulario' border='0' cellpadding='5' cellspacing='1' align='center'>
	<?

		if(strlen($msg_erro)>0){
			echo "<tr class='msg_erro'>";
				echo "<td>";
					echo "$msg_erro";
				echo "</td>";
			echo "</tr>";
			
		}
	?>
	<tr>
		<td class='titulo_tabela'>Parâmetros de Pesquisa</td>
	</tr>
	<tr>
		<td valign='bottom'>
			<table width='100%' border='0' cellspacing='1' cellpadding='2' class='formulario'>
				<tr>
					<td width='120'>&nbsp;</td>
					<td nowrap>Data Inicial</td>
					<td nowrap>Data Final</td>
					<td width='10'>&nbsp;</td>
				</tr>
				<tr align='left'>
					<td width='10'>&nbsp;</td>
					<td align='left' nowrap>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class='frm'>
					</td>
					<td align='left'>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class='frm'>

					</td>
					<td width='10'>&nbsp;</td>
				</tr>
				<tr align='left'>
					<td width='10'>&nbsp;</td>
					<td nowrap>Tipo</td>
					<td nowrap>Descrição/Título</td>
					<td width='10'>&nbsp;</td>
				</tr>
				<tr align='left'>
					<td width='10'>&nbsp;</td>
					<td>
					<? $ArrayComunicados = array('Com. Unico Posto','Comunicado','Informativo','Foto',
										'Vista Explodida','Esquema Elétrico','Manual de Serviço','Lançamentos',
										'Procedimentos','Promocao'
									);
						echo array2select('psq_tipo', null, $ArrayComunicados, $psq_tipo, " class='frm'");
					?>
					</td>
					<td><input type='text' name='psq_descricao' size='40' value='<? echo $psq_descricao; ?>' class='frm'></td>
					<td width='10'>&nbsp;</td>
				</tr>
				<tr align='left'>
					<td width='10'>&nbsp;</td>
					<td>Referência</td>
					<td>Descrição do Produto</td>
					<td width='10'>&nbsp;</td>
				</tr>
				<tr align='left'>
					<td width='10'>&nbsp;</td>
					<td>
					<input class="frm" type="text" name="produto_referencia" size="15" id="produto_referencia" maxlength="20" value="<? echo $produto_referencia ?>" class='frm'>
					&nbsp;
					<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'referencia')">
					</td>
					<td>
					<input class="frm" type="text" name="produto_descricao" id="produto_descricao" size="40" value="<? echo $produto_descricao ?>"  class='frm'>
					&nbsp;
					<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'descricao')">
					</td>
					<td width='10'>&nbsp;</td>
						<tr align='left'>
					<td width='10'>&nbsp;</td>
							<td>Cód. Posto</td>
							<td>Nome do Posto</td>
					<td width='10'>&nbsp;</td>
				</tr>
				<tr align='left'>
					<td width='10'>&nbsp;</td>
					<td>
						<input type="text" name="codigo_posto" size="15" id="codigo_posto" value="<? echo $codigo_posto ?>" class="frm">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'codigo')">
					</td>
					<td>
						<input type="text" name="posto_nome" size="40" id="posto_nome" value="<?echo $posto_nome?>" class="frm">
						<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto, document.frm_relatorio.posto_nome, 'nome')">
					</td>
				</tr>
				<tr>
					<td align='center' colspan='4' style="padding:20px 0 10px 0;" nowrap><input type='submit' style="cursor:pointer" name='btn_acao' value='Consultar'></td>
				</tr>
			</table>
		</tr>
</table>
</FORM>

<?
if($btn_acao=="Consultar"){


if (strlen ($produto_referencia) > 0) {
	$sqlX = "SELECT produto FROM tbl_produto JOIN tbl_linha USING (linha) WHERE tbl_linha.fabrica = $login_fabrica AND tbl_produto.referencia = '$produto_referencia'";
	$resX = pg_exec ($con,$sqlX);
	$produto = pg_result ($resX,0,0);
}

if (strlen($codigo_posto) > 0 && strlen($posto_nome) > 0) {
	$sql =	"SELECT tbl_posto.posto
			FROM tbl_posto
			JOIN tbl_posto_fabrica USING (posto)
			WHERE tbl_posto_fabrica.fabrica = $login_fabrica
			AND   tbl_posto_fabrica.codigo_posto = '$codigo_posto';";
	$res = @pg_exec($con,$sql);
	$msg_erro=@pg_errormessage($con);
	if (pg_numrows($res) == 1) {
		$posto        = trim(pg_result($res,0,posto));
	}else{
		$msg_erro .= " Posto não encontrado. ";
	}
}

$largunta_tabela = "90%";

$cond_1 =" 1=1 ";
$cond_2 =" 1=1 ";
$cond_3 =" 1=1 ";
$cond_4 =" 1=1 ";
$cond_5 =" 1=1 ";

if(strlen($data_inicial) > 0 AND strlen($data_final) > 0) $cond_1 =" tbl_comunicado.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59' ";
if(strlen($produto) > 0)                                  $cond_2 =" tbl_comunicado.produto = $produto ";
if(strlen($posto) > 0)                                    $cond_3 =" tbl_comunicado.posto = $posto ";
if(strlen($tipo) > 0)                                     $cond_4 =" tbl_comunicado.tipo      = '$tipo' ";
if(strlen($descricao) > 0)                                $cond_5 =" tbl_comunicado.descricao ILIKE '%$descricao%' ";
$lista_comunicados = implode($ArrayComunicados,"','");

if(strlen($msg_erro)==0){
	$sql = "SELECT  tbl_comunicado.comunicado                            ,
					tbl_comunicado.descricao                             ,
					to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data    ,
					tbl_comunicado.tipo                                  ,
					tbl_produto.descricao AS produto_descricao           ,
					tbl_comunicado.ativo                                 ,
					tbl_posto.nome    AS nome_fantasia                   ,
					tbl_posto_fabrica.codigo_posto
			FROM    tbl_comunicado
			LEFT JOIN tbl_produto            ON tbl_comunicado.produto = tbl_produto.produto
			LEFT JOIN tbl_linha              ON tbl_linha.linha = tbl_produto.linha
			LEFT JOIN tbl_comunicado_produto ON tbl_comunicado.comunicado = tbl_comunicado_produto.comunicado
			LEFT JOIN tbl_posto              ON tbl_posto.posto = tbl_comunicado.posto
			LEFT JOIN tbl_posto_fabrica      ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE  tbl_comunicado.fabrica = $login_fabrica
			AND    tbl_comunicado.tipo in ('$lista_comunicados')
			AND $cond_1
			AND $cond_2
			AND $cond_3
			AND $cond_4
			AND $cond_5
			ORDER BY tbl_comunicado.data ";

	$res = pg_exec($con,$sql);
	if (pg_numrows($res)>0) {
		echo "</table>";
		echo "<br><br>";
		echo "<table width='$largunta_tabela' border='0' cellspacing='1' align='center' id='relatorio' name='relatorio' class='tablesorter tabela'>";
		echo "<thead>";
		echo "<TR class='titulo_coluna'>\n";
		echo "<th>Tipo</th>";
		echo "<th>Descrição</th>";
		echo "<th>Produto</th>";
		echo "<th>Data</th>";
		echo "<th>Posto</th>";
		echo "<th>Status</th>";
		echo "</TR >\n";
		echo "</thead>\n";
		echo "<tbody>\n";
			for($y=0;pg_numrows($res)>$y;$y++){
				$codigo_posto      = pg_result($res,$y,codigo_posto);
				$posto_nome        = strtoupper(pg_result($res,$y,nome_fantasia));
				$descricao         = trim(pg_result ($res,$y,descricao));
				$comunicado        = trim(pg_result ($res,$y,comunicado));
				$produto_descricao = trim(pg_result ($res,$y,produto_descricao));
				$data              = trim(pg_result ($res,$y,data));
				$tipo              = trim(pg_result ($res,$y,tipo));
				$ativo             = trim(pg_result ($res,$y,ativo));
				if(strlen($codigo_posto)> 0) {
					$posto_completo    = "$codigo_posto - $posto_nome";
				}

			if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#F7F5F0';}

			echo "<TR bgcolor='$cor'>\n";
				echo "<TD align='center' nowrap>$tipo</TD>\n";
				echo "<TD align='center' nowrap><a href='comunicado_produto.php?comunicado=$comunicado' target='_blank'>$descricao</a></TD>\n";
				echo "<TD align='center' nowrap>$produto_descricao</TD>\n";
				echo "<TD align='center' nowrap>$data</TD>\n";
				echo "<TD align='left' nowrap>$posto_completo</TD>\n";
				echo "<td align='left'>";
				if ($ativo != 't') {
					echo  "Inativo";
				} else {
					echo "<b>Ativo</b>";
				}
				echo "</td>";
			echo "</TR >\n";
			}
		echo "</tbody>";
		echo "</table>";
	}else{
		echo "<P>Nenhum resultado encontrado</P>";
	}
}
}
?>

<p>

<? include "rodape.php" ?>

<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

include "autentica_admin.php";
$admin_privilegios="gerencia";

$sql = "SELECT posto_fabrica
		FROM tbl_fabrica
		WHERE fabrica = $login_fabrica ";
$res2 = pg_query ($con,$sql);
$posto_da_fabrica = pg_fetch_result ($res2,0,0);


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
				$cnpj = trim(pg_fetch_result($res,$i,cnpj));
				$nome = trim(pg_fetch_result($res,$i,nome));
				$codigo_posto = trim(pg_fetch_result($res,$i,codigo_posto));
				echo "$cnpj|$nome|$codigo_posto";
				echo "\n";
			}
		}
	}
	exit;
}

function converte_data($date){
	$date = explode("-", preg_replace('/\//', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}


$msg_erro = "";

if (strlen($_POST["btnacao"]) > 0) $btnacao = trim(strtolower($_POST["btnacao"]));
if (strlen($_GET["btnacao"])  > 0) $btnacao = trim(strtolower($_GET["btnacao"]));

if (strlen($_POST["posto"]) > 0) $posto = $_POST["posto"];
if (strlen($_GET["posto"])  > 0) $posto = $_GET["posto"];

if (strlen($_POST["nf"]) > 0) $nf = $_POST["nf"];
if (strlen($_GET["nf"])  > 0) $nf = $_GET["nf"];

if (strlen($_POST["data_inicial"]) > 0) $data_inicial = $_POST["data_inicial"];
if (strlen($_GET["data_inicial"])  > 0) $data_inicial = $_GET["data_inicial"];

if (strlen($_POST["data_final"]) > 0) $data_final = $_POST["data_final"];
if (strlen($_GET["data_final"])  > 0) $data_final = $_GET["data_final"];

$posto_nome   = $_POST['posto_nome'];
if (strlen($_GET['posto_nome']) > 0) $posto_nome = $_GET['posto_nome'];

$posto_codigo = $_POST['posto_codigo'];
if (strlen($_GET['posto_codigo']) > 0) $posto_codigo = $_GET['posto_codigo'];

$extrato = $_REQUEST['extrato'];

$os= $_REQUEST['os'];

if ((strlen($data_inicial)==0 AND strlen($data_final)>0) OR (strlen($data_inicial)>0 AND strlen($data_final)==0)){
	$msg_erro .= "Preencha as duas datas";
}


$layout_menu = "gerencia";
$title = "Relatório OS Scrap";


include "cabecalho.php";

?>
<? include "javascript_calendario_new.php";?>


<style type="text/css">

.Tabela{
	border:1px solid #596D9B;
	background-color:#596D9B;
	}
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10PX	;
	font-weight: bold;
	border: 1px solid;
	background-color: #D9E2EF
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #FFFFFF
}
.quadro{
	border: 1px solid #596D9B;
	width:450px;
	height:50px;
	padding:10px;

}
.botao {
		border-top: 1px solid #333;
	        border-left: 1px solid #333;
	        border-bottom: 1px solid #333;
	        border-right: 1px solid #333;
	        font-size: 13px;
	        margin-bottom: 10px;
	        color: #0E0659;
		font-weight: bolder;
}
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
.inpu{
	border:1px solid #666;
	font-size:12px;
}
.butt{
	border:1px solid #666;
	background-color:#ccc;
	font-size:12px;
}

.mensagem {
    width: 600px;
    margin: 0 auto;
    margin-top: 20px;
    margin-bottom: 20px;
    text-align: center;
    padding: 10px 5px;
    font-size: 10pt;
}

.msg-info {
    border: 1px solid #596D9B;
    background-color: #E6EEF7;
}
</style>

<? include "javascript_pesquisas.php"; ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#datai').datePicker({startDate:'01/01/2000'});
		$('#dataf').datePicker({startDate:'01/01/2000'});
		$('#datai').maskedinput("99/99/9999");
		$('#dataf').maskedinput("99/99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatResult(row) {
		return row[0];
	}

	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
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
		$("#posto_codigo").val(data[2]) ;
		//alert(data[2]);
	});

	/**
	 * Inver seleção dos checkbox de envio de e-mail
	 * HD 107532
	 *
	 * @author Augusto Pascutti <augusto.pascutti@telecontrol.com.br>
	 */
	$('#inverter_chk_email').change(function() {
		var chk_status = $(this).attr('checked');
		$('.checkable').attr('checked',chk_status);
	});
	// fim HD 107532
});
</script>
<?

if (strlen($msg_erro) > 0) {
	echo "<table width='600' align='center' border='0' cellspacing='1' cellpadding='1' class='error'>\n";
	echo "<tr>";
	echo "<td>$msg_erro</td>";
	echo "</tr>";
	echo "</table>\n";
}

if(strlen($msg)>0){
	echo "<center><b style='font-size:12px;border:1px solid #999;padding:10px;background-color:#dfdfdf'>$msg</b></center><br>";
}

echo "<FORM METHOD='GET' NAME='frm_extrato' ACTION=\"$PHP_SELF\">";
?>

<input type='hidden' name='btnacao'>
<table class='Tabela' width='650' cellspacing='0'  cellpadding='0' bgcolor='#596D9B' align='center'>

	<tr >
		<td class="Titulo" height='20'>Relatório OS Scrap</td>
	</tr>
	<tr>
		<td bgcolor='#F3F8FE'>
			<TABLE width='100%' align='center' border='0' cellspacing='0' cellpadding='2'>
					<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
						<td align='right'><br>Código Posto&nbsp;</td>
						<td><br><input type="text" name="posto_codigo" id="posto_codigo" size="16" value="<? echo $posto_codigo ?>" class="frm">
							<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_extrato.posto_codigo, document.frm_extrato.posto_nome, 'codigo')"></td>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
						<td align='right'>Nome do Posto&nbsp;</td>
						<td>
							<input type="text" name="posto_nome" id="posto_nome" size="30"  value="<?echo $posto_nome?>" class="frm">
							<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_extrato.posto_codigo, document.frm_extrato.posto_nome, 'nome')">
						</td>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
						<td align='right'>Nota de Devolução &nbsp;</td>
						<td><input type="text" name="nota_devolucao" size="10"  value="<?echo $nota_devolucao?>" class="frm">
						</td>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
						<td align='right'>Extrato&nbsp;</td>
						<td><input type="text" name="extrato" size="10"  value="<?echo $extrato?>" class="frm">
						</td>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
						<td align='right'>OS&nbsp;</td>
						<td><input type="text" name="os" size="10"  value="<?echo $os?>" class="frm">
						</td>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
						<td align='right'>Data do Envio &nbsp;</td>
						<td><input type="text" name="data_inicial" id="datai" size="12"  value="<?echo $data_inicial?>" class="frm"> &nbsp;&nbsp;&nbsp;&nbsp;até
						</td>
					</tr>
					<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
						<td align='right'>&nbsp;</td>
						<td><input type="text" name="data_final" id="dataf" size="12"  value="<?echo $data_final?>" class="frm">
						</td>
					</tr>
					<tr>
						<td colspan='2' bgcolor="#D9E2EF" align='center'><img src="imagens_admin/btn_filtrar.gif" onclick="javascript: document.frm_extrato.btnacao.value='filtrar' ; document.frm_extrato.submit() " ALT="Filtrar" border='0' style="cursor:pointer;"><br>
						</td>
					</tr>
			</TABLE>
		</td>
	</tr>
	<tr><td bgcolor='#D9E2EF'>
		</td>
	</tr>

</table>
</form>
<br>

<?
	if(isset($btnacao) and empty($msg_erro) ) {
		if (strlen($posto_codigo)>0){
			$sql_adicional = " AND tbl_posto_fabrica.codigo_posto = '$posto_codigo' ";
		}

		if (strlen($data_inicial)>0 AND strlen($data_final)>0){
			$tmp_data_inicial = converte_data($data_inicial);
			$tmp_data_final   = converte_data($data_final);
			$sql_adicional_2  = " AND tbl_faturamento.emissao BETWEEN '$tmp_data_inicial' AND '$tmp_data_final' ";
		}

		if (strlen($nota_devolucao)>0){
			$sql_adicional_3 = " AND tbl_faturamento.nota_fiscal like '%$nota_devolucao' ";
		}

		if (strlen($os)>0){
			$sql_adicional_4 = " AND tbl_os.sua_os LIKE '$os' ";
		}

		if (strlen($extrato)>0){
			$sql_adicional_5 = " AND tbl_faturamento_item.extrato_devolucao::text LIKE '$extrato' ";
		}

		$sql = " SELECT tbl_faturamento_item.extrato_devolucao,
						tbl_faturamento.nota_fiscal
				INTO TEMP tmp_scrap_os_$login_admin
				FROM tbl_faturamento_item
				JOIN tbl_faturamento USING(faturamento)
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_faturamento.posto
				WHERE tbl_faturamento.distribuidor IS NOT NULL
				AND tbl_faturamento.posto = $posto_da_fabrica
				AND tbl_faturamento.fabrica  in ($login_fabrica)
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				AND tbl_faturamento.conferencia IS NOT NULL
				$sql_adicional
				$sql_adicional_2
				$sql_adicional_3
				$sql_adicional_5
				;

				CREATE INDEX tmp_scrap_os_extrato_$login_admin ON tmp_scrap_os_$login_admin(extrato_devolucao);
				SELECT
					    os,
						sua_os,
						referencia,
						descricao,
						nome,
						codigo_posto,
						extrato_devolucao
				FROM (
				(SELECT  DISTINCT tbl_os.os,
						tbl_os.sua_os,
						tbl_produto.referencia,
						tbl_produto.descricao,
						tbl_posto.nome,
						tbl_posto_fabrica.codigo_posto,
						tmp_scrap_os_$login_admin.extrato_devolucao
				FROM tmp_scrap_os_$login_admin
				JOIN tbl_faturamento_item USING(extrato_devolucao)
				JOIN tbl_os USING(os)
				JOIN tbl_posto         ON tbl_os.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				JOIN tbl_produto USING(produto)
				JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				AND tbl_os_extra.scrap
				$sql_adicional_4
				)
				UNION
				(SELECT  DISTINCT tbl_os.os,
						tbl_os.sua_os,
						tbl_produto.referencia,
						tbl_produto.descricao,
						tbl_posto.nome,
						tbl_posto_fabrica.codigo_posto,
						0 AS extrato_devolucao
				FROM tbl_os
				JOIN tbl_posto         ON tbl_os.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				JOIN tbl_produto USING(produto)
				JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				AND tbl_os_extra.scrap
				AND tbl_os_extra.data_scrap between '$tmp_data_inicial' AND '$tmp_data_final'
				$sql_adicional_4)
				) AS A
				WHERE 1=1 order by extrato_devolucao
				";
		$res = pg_query($con,$sql);
		if(pg_num_rows($res) > 0){
			$lista .=  "<center><table border='0' cellpadding='4' cellspacing='0'  width='600px'>";

			$lista .=  "<tr class='Titulo' height='20'>";
			$lista .=  "<td>POSTO</td>";
			$lista .=  "<td>EXTRATO</td>";
			$lista .=  "<td>NOTA<br>FISCAL</td>";
			$lista .=  "<td>OS</td>";
			$lista .=  "<td>PRODUTO</td>";
			$lista .=  "</tr>";
			$extrato_devolucao = "";
			for($i =0;$i<pg_num_rows($res);$i++) {
				$os                = pg_fetch_result($res,$i,os);
				$sua_os            = pg_fetch_result($res,$i,sua_os);
				$extrato_devolucao = pg_fetch_result($res,$i,extrato_devolucao);
				$referencia        = pg_fetch_result($res,$i,referencia);
				$descricao         = pg_fetch_result($res,$i,descricao);
				$nome              = pg_fetch_result($res,$i,nome);
				$codigo_posto      = pg_fetch_result($res,$i,codigo_posto);


				$cor = ($i%2==0) ? '#E9E9E9' : '#ffffff';

				$lista .= "<tr class='Conteudo' height='20' bgcolor='$cor' align='left'  >";
				$lista .= "<td><acronym title='$codigo_posto - $nome'>$codigo_posto</acronym></td>";
				$lista .= "<td>";
				if($extrato_devolucao<> 0) {
					$lista.="<a href='extrato_consulta_os.php?extrato=$extrato_devolucao' target='_blank'>$extrato_devolucao</a>";
				}
				$lista.="</td>";
				$lista .= "<td>";
				if($extrato_anterior != $extrato_devolucao) {
					$nota_fiscal = "";
					$sqln = " SELECT DISTINCT nota_fiscal
								FROM
								tmp_scrap_os_$login_admin
								WHERE extrato_devolucao = $extrato_devolucao";
					$resn = pg_query($con,$sqln);

					for($j =0;$j<pg_num_rows($resn);$j++) {
						$nota_fiscal .=pg_fetch_result($resn,$j,nota_fiscal)."<br/>";
						$lista .=pg_fetch_result($resn,$j,nota_fiscal)."<br/>";
					}
				}else{
					$lista.=$nota_fiscal;
				}
				$lista .="</td>";
				$lista .= "<td><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
				$lista .= "<td>$referencia-$descricao</td>";
				$lista .= "</tr>";
				$extrato_anterior = $extrato_devolucao;
			}
			$lista .= "</table></center>";
			echo $lista;
		}

	}
 include "rodape.php"; ?>

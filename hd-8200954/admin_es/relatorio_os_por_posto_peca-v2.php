<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';

include 'funcoes.php';

$msg_erro = "";

$layout_menu = "auditoria";
$title = "REPORTE DE OS DIGITADAS";

include "cabecalho.php";

include "javascript_pesquisas.php";

?>

<script language="JavaScript" src="js/cal2.js"></script>
<script language="JavaScript" src="js/cal_conf2.js"></script>

<script language="JavaScript">
// ========= Fun��o PESQUISA DE POSTO POR C�DIGO OU NOME ========= //
function fnc_pesquisa_posto2 (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo + "&proximo=";
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}

</script>

<style type="text/css">
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}
.topo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
</style>

<p>

<?
$btn_acao = strtolower($_POST['btn_acao']);

$posto_codigo = trim($_POST["posto_codigo"]);
$posto_nome   = trim($_POST["posto_nome"]);
$ano          = trim($_POST["ano"]);
$mes          = trim($_POST["mes"]);

if (strlen($posto_codigo) == 0 AND strlen($posto_nome) == 0 AND strlen($ano) == 0 AND strlen($mes) == 0 AND strlen($btn_acao) > 0)
	$msg_erro = " Llene al minus un de los campos. ";

if (strlen($msg_erro) > 0) { ?>
<table width='600' align='center' border='0' cellspacing='2' cellpadding='2'>
	<tr class='error'>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<? } ?>

<form name='frm_os_posto' action='<? echo $PHP_SELF ?>' method="POST">
<input type="hidden" name='btn_acao' value="">

<table width='600' align='center' border='0' cellspacing='2' cellpadding='2'>
<tr class='topo'>
	<td colspan='2'>Llenar los campos para efectuar la consulta</td>
</tr>
<tr class='menu_top'>
	<td>C�digo del servicio</td>
	<td>Nombre del servicio</td>
</tr>
<tr>
	<td>
		<input class="frm" type="text" name="posto_codigo" size="13" value="<? echo $posto_codigo ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'codigo')" <? } ?>>&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'codigo')">
	</td>
	<td>
		<input class="frm" type="text" name="posto_nome" size="50" value="<? echo $posto_nome ?>" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'nome')" <? } ?>>&nbsp;<img src='imagens_admin/btn_lupa.gif' style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_posto2 (document.frm_os_posto.posto_codigo,document.frm_os_posto.posto_nome,'nome')" style="cursor:pointer;">
	</td>
</tr>
<tr class='menu_top'>
	<td>A�o</td>
	<td>Mes</td>
</tr>
<tr>
	<td>
		<input class="frm" type="text" name="ano" size="13" maxlength="4" value="<? echo $ano ?>">
	</td>
	<td>
		<?
			$meses = array (1 => "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");
		?>
		<select name="mes" class="frm">
			<option value=''></option>
			<?
			for ($i = 1 ; $i <= count($meses) ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">".$meses[$i]."</option>\n";
		}
			?>
		</select>
	</td>
</tr>
</table>

<br>

<center>
<img src='imagens_admin/btn_confirmar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_os_posto.btn_acao.value == '' ) { document.frm_os_posto.btn_acao.value='continuar' ; document.frm_os_posto.submit() } else { alert ('Aguarde submiss�o') }" ALT="Confirmar" border='0'>
</center>

</form>

<br>

<?

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0){

	$posto_codigo = trim($_POST["posto_codigo"]);
	$posto_nome   = trim($_POST["posto_nome"]);
	$ano          = trim($_POST["ano"]);
	$mes          = trim($_POST["mes"]);

	if (strlen($mes) > 0 OR strlen($ano) > 0){
		if (strlen($mes) > 0) {
			if (strlen($mes) == 1) $mes = "0".$mes;
			$data_inicial = "2005-$mes-01 00:00:00";
			$data_final   = "2005-$mes-".date("t", mktime(0, 0, 0, $mes, 1, 2005))." 23:59:59";
		}
		if (strlen($ano) > 0) {
			$data_inicial = "$ano-01-01 00:00:00";
			$data_final   = "$ano-12-".date("t", mktime(0, 0, 0, 12, 1, 2005))." 23:59:59";
		}
		if (strlen($mes) > 0 AND strlen($ano) > 0) {
			$data_inicial = "$ano-$mes-01 00:00:00";
			$data_final   = "$ano-$mes-".date("t", mktime(0, 0, 0, $mes, 1, 2005))." 23:59:59";
		}
	}

	if (strlen($posto_codigo) > 0){
		$sqlPosto =	"SELECT posto
					FROM tbl_posto_fabrica
					WHERE codigo_posto = '$posto_codigo'
					AND fabrica = $login_fabrica";
		$res = pg_exec($con,$sqlPosto);
		if (pg_numrows($res) == 1){
			$posto = pg_result($res,0,0);
		}
	}
			//TAKASHI COLOCOU CAMPO DIGITACAO DO ITEM
	$sql =	"SELECT tbl_os.sua_os                                                       ,
					tbl_os.consumidor_nome                                              ,
					tbl_os.consumidor_fone                                              ,
					tbl_os.serie                                                        ,
					tbl_os.tipo_atendimento                                             ,
					to_char (tbl_os.data_digitacao,'DD/MM/YYYY')  AS data_digitacao     ,
					to_char (tbl_os.data_abertura,'DD/MM/YYYY')   AS data_abertura      ,
					to_char (tbl_os.data_fechamento,'DD/MM/YYYY') AS data_fechamento    ,
					to_char (tbl_os.finalizada,'DD/MM/YYYY')      AS data_finalizada    ,
					to_char (tbl_os.data_nf,'DD/MM/YYYY')         AS data_nf            ,
					data_abertura::date - data_nf::date           AS dias_uso           ,
					tbl_produto.produto                                                 ,
					tbl_produto.referencia                        AS produto_referencia ,
					tbl_produto.descricao                         AS produto_descricao  ,
					tbl_peca.peca                                                       ,
					tbl_peca.referencia                           AS peca_referencia    ,
					tbl_peca.descricao                            AS peca_descricao     ,
					tbl_servico_realizado.descricao               AS servico            ,
					TO_CHAR (tbl_os_item.digitacao_item,'DD/MM') AS data_digitacao_item ,
					tbl_os_item.adicional_peca_estoque                                  ,
					tbl_os_item.qtde                                                    ,
					tbl_posto_fabrica.codigo_posto                                      ,
					tbl_posto.nome AS nome_posto                                        
			FROM tbl_os
			JOIN      tbl_produto       ON  tbl_os.produto            = tbl_produto.produto
			JOIN      tbl_posto         ON  tbl_os.posto              = tbl_posto.posto
			JOIN      tbl_posto_fabrica ON  tbl_posto.posto           = tbl_posto_fabrica.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
			LEFT JOIN tbl_os_produto    ON  tbl_os.os                 = tbl_os_produto.os
			LEFT JOIN tbl_os_item       ON  tbl_os_produto.os_produto = tbl_os_item.os_produto
			LEFT JOIN tbl_peca          ON  tbl_os_item.peca          = tbl_peca.peca
			LEFT JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado =
			tbl_servico_realizado.servico_realizado
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_posto.pais = '$login_pais'";

	if (strlen($data_inicial) > 0 AND strlen($data_final) > 0) $sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final' ";

	if (strlen($posto) > 0) $sql .= " AND tbl_os.posto = $posto ";
	if (strlen($uf) > 0)    $sql .= " AND tbl_posto.estado = '$uf' ";

	$sql .= " ORDER BY tbl_os.sua_os;";
if($ip == '201.0.9.216') echo nl2br($sql);
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='700'>";
		echo "<tr class='menu_top'>";
		echo "<td nowrap>OS</td>";
		echo "<td nowrap>USU�RIO</td>";
		echo "<td nowrap>TEL�FONO</td>";
		echo "<td nowrap>N� S�RIE</td>";
		echo "<td nowrap>DIGITACI�N</td>";
		echo "<td nowrap>ABERTURA</td>";
		echo "<td nowrap>CIERRE</td>";
		echo "<td nowrap>FINALIZADA</td>";
		echo "<td nowrap>FECHA FACTURA</td>";
		echo "<td nowrap>D�AS EN USO</td>";
		echo "<td nowrap>HERRAMIENTA REFER�NCIA</td>";
		echo "<td nowrap>HERRAMIENTA DESCRIPCI�N</td>";
		if ($login_pais == 'CO'){
			echo "<td nowrap>PIEZA ESTOQUE</td>";
		}
		echo "<td nowrap>PIEZA REFER�NCIA</td>";
		echo "<td nowrap>CANTIDAD</td>";
		echo "<td nowrap>PIEZA DESCRIPCI�N</td>";
		echo "<td nowrap>FECHA ITEM</TD>";
		echo "<td nowrap>C�DIGO DEL SERVICIO</td>";
		echo "<td nowrap>NOMBRE DEL SERVICIO</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$sua_os             = pg_result($res,$i,sua_os);
			$consumidor_nome    = pg_result($res,$i,consumidor_nome);
			$consumidor_fone    = pg_result($res,$i,consumidor_fone);
			$tipo_atendimento   = pg_result($res,$i,tipo_atendimento);
			$serie              = pg_result($res,$i,serie);
			$data_digitacao     = pg_result($res,$i,data_digitacao);
			$data_abertura      = pg_result($res,$i,data_abertura);
			$data_fechamento    = pg_result($res,$i,data_fechamento);
			$data_finalizada    = pg_result($res,$i,data_finalizada);
			$data_nf            = pg_result($res,$i,data_nf);
			$dias_uso           = pg_result($res,$i,dias_uso);
			$produto            = pg_result($res,$i,produto);
			$produto_referencia = pg_result($res,$i,produto_referencia);
			$produto_descricao  = pg_result($res,$i,produto_descricao);
			$peca               = pg_result($res,$i,peca);
			$peca_referencia    = pg_result($res,$i,peca_referencia);
			$qtde               = pg_result($res,$i,qtde);
			$peca_descricao     = pg_result($res,$i,peca_descricao);
			$servico            = pg_result($res,$i,servico);
			$codigo_posto       = pg_result($res,$i,codigo_posto);
			$nome_posto         = pg_result($res,$i,nome_posto);
			$adicional_peca_estoque= pg_result($res,$i,adicional_peca_estoque);
			
						//TAKASHI COLOCOU CAMPO DIGITACAO DO ITEM

			//--=== Tradu��o para outras linguas ============================= Raphael HD:1212
			$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = 'ES'";
		
			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$produto_descricao  = trim(@pg_result($res_idioma,0,descricao));
			}

			$sql_idioma = "SELECT * FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma) = 'ES'";
		
			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$peca_descricao  = trim(@pg_result($res_idioma,0,descricao));
			}
			//--=== Tradu��o para outras linguas ================================================

			$data_digitacao_item= pg_result($res,$i,data_digitacao_item);
			if ($i % 2 == 0) $cor = '#F1F4FA';
			else             $cor = '#F7F5F0';
			
			//IGOR - HD 2777 Acrescentar no relat�rio OS de troca em garant�a
			//Identificar quando for em garantia
			if($tipo_atendimento == 13){
				$peca_descricao = "CAMBIO DE GARANT�A";
			}

			if ($login_fabrica == 1) $sua_os = $codigo_posto.$sua_os;

			echo "<tr class='table_line' bgcolor='$cor'>";
			echo "<td nowrap align='center'>$sua_os</td>";
			if ($ant_consumidor_nome == $consumidor_nome) echo "<td>&nbsp;</td>";
			else echo "<td nowrap align='left'>$consumidor_nome</td>";
			if ($ant_consumidor_fone == $consumidor_fone) echo "<td>&nbsp;</td>";
			else echo "<td nowrap align='center'>$consumidor_fone</td>";
			if ($ant_serie == $serie) echo "<td>&nbsp;</td>";
			else echo "<td nowrap align='center'>$serie</td>";
			if ($ant_data_digitacao == $data_digitacao) echo "<td>&nbsp;</td>";
			else echo "<td nowrap align='center'>$data_digitacao</td>";
			if ($ant_data_abertura == $data_abertura) echo "<td>&nbsp;</td>";
			else echo "<td nowrap align='center'>$data_abertura</td>";
			if ($ant_data_fechamento == $data_fechamento) echo "<td>&nbsp;</td>";
			else echo "<td nowrap align='center'>$data_fechamento</td>";
			if ($ant_data_finalizada == $data_finalizada) echo "<td>&nbsp;</td>";
			else echo "<td nowrap align='center'>$data_finalizada</td>";
			if ($ant_data_nf == $data_nf) echo "<td>&nbsp;</td>";
			else echo "<td nowrap align='center'>$data_nf</td>";
			if ($ant_dias_uso == $dias_uso) echo "<td>&nbsp;</td>";
			else echo "<td nowrap align='center'>$dias_uso</td>";
			if ($ant_produto_referencia == $produto_referencia) echo "<td>&nbsp;</td>";
			else echo "<td nowrap align='center'>$produto_referencia</td>";
			if ($ant_produto_descricao == $produto_descricao) echo "<td>&nbsp;</td>";
			else echo "<td nowrap align='left'>$produto_descricao</td>";
			if ($login_pais == "CO"){
				if($adicional_peca_estoque == 't'){
					echo "<td nowrap align='center'>Si</td>";
				}else{
					echo "<td nowrap align='center'>No</td>";
				}
			}		
			echo "<td nowrap align='center'>$peca_referencia</td>";
			echo "<td nowrap align='center'>$qtde</td>";
			echo "<td nowrap align='left'>$peca_descricao</td>";
			echo "<td nowrap align='center'>$data_digitacao_item</td>";
			echo "<td nowrap align='center'>$codigo_posto</td>";
			echo "<td nowrap align='left'>$nome_posto</td>";
			echo "</tr>";
/*
			$ant_consumidor_nome    = $consumidor_nome;
			$ant_consumidor_fone    = $consumidor_fone;
			$ant_serie              = $serie;
			$ant_data_digitacao     = $data_digitacao;
			$ant_data_abertura      = $data_abertura;
			$ant_data_fechamento    = $data_fechamento;
			$ant_data_nf            = $data_nf;
			$ant_dias_uso           = $dias_uso;
			$ant_produto_referencia = $produto_referencia;
			$ant_produto_descricao  = $produto_descricao;
*/
		}

		echo "</table>";
		flush();
		echo "<br>";
	}
}

echo "<br>";

include "rodape.php";
?>

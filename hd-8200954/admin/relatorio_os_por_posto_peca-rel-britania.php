<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';

include 'funcoes.php';

$msg_erro = "";

$layout_menu = "auditoria";
$title = "Relatório de OSs digitadas";

include "cabecalho.php";

include "javascript_pesquisas.php";

?>

<script language="JavaScript" src="js/cal2.js"></script>
<script language="JavaScript" src="js/cal_conf2.js"></script>

<script language="JavaScript">
// ========= Função PESQUISA DE POSTO POR CÓDIGO OU NOME ========= //
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

function fnc_pesquisa_produto (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}


	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=18, left=0");
		janela.referencia	= campo;
		janela.descricao	= campo2;
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
	$msg_erro = " Preencha pelo menos um dos campos. ";

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
	<td colspan='2'>Preencha os campos p/ efetuar a pesquisa</td>
</tr>
<tr class='menu_top'>
	<td>Código do Posto</td>
	<td>Nome do Posto</td>
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
	<td>Ano</td>
	<td>Mês</td>
</tr>
<tr>
	<td>
		<input class="frm" type="text" name="ano" size="13" maxlength="4" value="<? echo $ano ?>">
	</td>
	<td>
		<?
			$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
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
<?if($login_fabrica == 20 ){
// MLG 2009-08-04 HD 136625
    $sql = 'SELECT pais,nome FROM tbl_pais';
    $res = pg_query($con,$sql);
    $p_tot = pg_num_rows($res);
    for ($i; $i<$p_tot; $i++) {
        list($p_code,$p_nome) = pg_fetch_row($res, $i);
    	$sel_paises .= "\t\t\t\t<option value='$p_code'";
        $sel_paises .= ($pais==$p_code)?" selected":"";
        $sel_paises .= ">$p_nome</option>\n";
    }
?>
<tr class='menu_top' bgcolor="#D9E2EF">
	<td>País</td>
	<td>Tipo Atendimento</td>
</tr>
	<td>
			<select name='pais' size='1' class='frm'>
			 <option></option>
            <?echo $sel_paises;?>
			</select>
	</td>
	<td>
			<select name="tipo_atendimento" size="1" class="frm">
			<option <? if (strlen ($tipo_atendimento) == 0) echo " selected " ?> ></option>
			<?
/*			$sql = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica ORDER BY tipo_atendimento";
			$res = pg_exec ($con,$sql) ;

			for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
				echo "<option ";
				if ($tipo_atendimento == pg_result ($res,$i,tipo_atendimento) ) echo " selected ";
				echo " value='" . pg_result ($res,$i,tipo_atendimento) . "'" ;
				echo " > ";
				echo pg_result ($res,$i,codigo) . " - " . pg_result ($res,$i,descricao) ;
				echo "</option>\n";
			}
*/			?>
		</select>
	</td>
</tr>

<?}?>
<tr class='menu_top' bgcolor="#D9E2EF">
	<td>Referencia</td>
	<td>Descrição</td>
</tr>
<tr>
	<td><input type="text" name="produto_referencia" value="<? echo $produto_referencia ?>" size="15" maxlength="20" class='frm'>&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os_posto.produto_referencia,document.frm_os_posto.produto_descricao,'referencia')" alt='Clique aqui para pesquisar pela referência do produto' style='cursor:pointer;'></td>
	<td><input type="text" name="produto_descricao" value="<? echo $produto_descricao ?>" size="45" maxlength="50" class='frm'>&nbsp;<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_os_posto.produto_referencia,document.frm_os_posto.produto_descricao,'descricao')" alt='Clique aqui para pesquisar pela descrição do produto' style='cursor:pointer;'></td>
</tr>
</table>


<br>

<center>
<img src='imagens_admin/btn_confirmar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_os_posto.btn_acao.value == '' ) { document.frm_os_posto.btn_acao.value='continuar' ; document.frm_os_posto.submit() } else { alert ('Aguarde submissão') }" ALT="Confirmar" border='0'>
</center>

</form>

<br>

<?
$btn_acao = 5;
if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0){

	$posto_codigo     = trim($_POST["posto_codigo"]);
	$posto_nome       = trim($_POST["posto_nome"]);
	$ano              = trim($_POST["ano"]);
	$mes              = trim($_POST["mes"]);
	$produto_ref      = trim($_POST['produto_referencia']);
	$tipo_atendimento = trim($_POST['tipo_atendimento']);
	$pais             = trim($_POST['pais']);

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


	if (strlen($data_inicial) > 0 AND strlen($data_final) > 0) $sql .= " AND tbl_os.data_digitacao BETWEEN '$data_inicial' AND '$data_final' ";

	if (strlen($posto) > 0)             $sql .= " AND tbl_os.posto = $posto ";
	if (strlen($uf) > 0)                $sql .= " AND tbl_posto.estado = '$uf' ";
	if (strlen($produto_ref) > 0)       $sql .= " AND tbl_produto.referencia = '$produto_ref' " ;
	if (strlen($pais) > 0)              $sql .= " AND tbl_posto.pais = '$pais' " ;
	if (strlen($tipo_atendimento) > 0)  $sql .= " AND tbl_os.tipo_atendimento = '$tipo_atendimento' " ;
	$sql .= " ORDER BY tbl_os.sua_os;";

	$sql =	"SELECT *
			from tmp_britania_2007_05
			limit 5000;";

if($ip == '189.18.99.251') echo nl2br($sql);

	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='700'>";
		echo "<tr class='menu_top'>";
		echo "<td nowrap>OS</td>";
		if($login_fabrica==20)echo "<td nowrap>Tipo Atendimento</td>";
		echo "<td nowrap>CONSUMIDOR</td>";
		echo "<td nowrap>TELEFONE</td>";
		echo "<td nowrap>Nº SÉRIE</td>";
		echo "<td nowrap>DIGITAÇÃO</td>";
		echo "<td nowrap>ABERTURA</td>";
		echo "<td nowrap>FECHAMENTO</td>";
		echo "<td nowrap>FINALIZADA</td>";
		echo "<td nowrap>DATA NF</td>";
		echo "<td nowrap>DIAS EM USO</td>";
		echo "<td nowrap>PRODUTO REFERÊNCIA</td>";
		echo "<td nowrap>PRODUTO DESCRIÇÃO</td>";
		echo "<td nowrap>PEÇA REFERÊNCIA</td>";
		echo "<td nowrap>PEÇA DESCRIÇÃO</td>";
		//TAKASHI COLOCOU CAMPO DIGITACAO DO ITEM
		echo "<td nowrap>DATA ITEM</TD>";
		echo "<td nowrap>DEFEITO CONSTATADO</td>";
		echo "<td nowrap>SERVIÇO REALIZADO</td>";
		echo "<td nowrap>CÓDIGO POSTO</td>";
		echo "<td nowrap>RAZÃO SOCIAL</td>";
		if($login_fabrica == 20) echo "<td nowrap>PAÍS</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$sua_os             = pg_result($res,$i,sua_os);
			$consumidor_nome    = pg_result($res,$i,consumidor_nome);
			$consumidor_fone    = pg_result($res,$i,consumidor_fone);
			$serie              = pg_result($res,$i,serie);
			$data_digitacao     = pg_result($res,$i,data_digitacao);
			$data_abertura      = pg_result($res,$i,data_abertura);
			$data_fechamento    = pg_result($res,$i,data_fechamento);
			$data_finalizada    = pg_result($res,$i,data_finalizada);
			$data_nf            = pg_result($res,$i,data_nf);
			$dias_uso           = pg_result($res,$i,dias_uso);
			$produto_referencia = pg_result($res,$i,produto_referencia);
			$produto_descricao  = pg_result($res,$i,produto_descricao);
			$peca_referencia    = pg_result($res,$i,peca_referencia);
			$peca_descricao     = pg_result($res,$i,peca_descricao);
			$servico            = pg_result($res,$i,servico);
			$codigo_posto       = pg_result($res,$i,codigo_posto);
			$nome_posto         = pg_result($res,$i,nome_posto);
			$defeito_constatado	= pg_result($res,$i,defeito_constatado);
						//TAKASHI COLOCOU CAMPO DIGITACAO DO ITEM
			$data_digitacao_item= pg_result($res,$i,data_digitacao_item);

			$posto_pais         = pg_result($res,$i,posto_pais);
			$ta_codigo          = pg_result($res,$i,ta_codigo);
			$ta_descricao       = pg_result($res,$i,ta_descricao);

			if ($i % 2 == 0) $cor = '#F1F4FA';
			else             $cor = '#F7F5F0';

			if ($login_fabrica == 1) $sua_os = $codigo_posto.$sua_os;

			echo "<tr class='table_line' bgcolor='$cor'>";
			echo "<td nowrap align='center'>$sua_os</td>";
			if($login_fabrica == 20) echo "<td nowrap align='left'>$ta_codigo - $ta_descricao</td>";
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
			echo "<td nowrap align='center'>$peca_referencia</td>";
			echo "<td nowrap align='left'>$peca_descricao</td>";
			//TAKASHI COLOCOU CAMPO DIGITACAO DO ITEM
			echo "<td nowrap align='center'>$data_digitacao_item</td>";
			echo "<td nowrap align='left'>$defeito_constatado</td>";
			echo "<td nowrap align='left'>$servico</td>";
			echo "<td nowrap align='center'>$codigo_posto</td>";
			echo "<td nowrap align='left'>$nome_posto</td>";
			if($login_fabrica == 20) echo "<td nowrap align='left'>$posto_pais</td>";
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

<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

$btn_acao = trim (strtolower ($_POST['btn_acao']));

$msg_erro = "";

if ($btn_acao == "gravar") {
}

$layout_menu = "callcenter";
$title = "Relação de Call-center";

include "cabecalho.php";
$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
?>

<?

if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if (strlen($q)>2){
		if ($tipo_busca=="cliente_admin"){
			$y = trim (strtoupper ($q));
			$condicao = explode(';',$y);
			$palavras = explode(' ',$condicao[0]);
			$cidade = $condicao[1];
			$count = count($palavras);
			$sql_and = "";
			for($i=0 ; $i < $count ; $i++){
				if(strlen(trim($palavras[$i]))>0){
					$cnpj_pesquisa = trim($palavras[$i]);
					$cnpj_pesquisa = str_replace (' ','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('-','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('\'','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('.','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('/','',$cnpj_pesquisa);
					$cnpj_pesquisa = str_replace ('\\','',$cnpj_pesquisa);
					$sql_and .= " AND (tbl_cliente_admin.nome ILIKE '%".trim($palavras[$i])."%'
								 	  OR  tbl_cliente_admin.cnpj ILIKE '%$cnpj_pesquisa%' OR tbl_cliente_admin.cidade ILIKE '%".trim($palavras[$i])."%')";
					if (strlen($cidade)>0) {
						$sql_and .= " AND tbl_cliente_admin.cidade ILIKE '%".trim($cidade)."%'";
					}
				}
			}

			$sql = "SELECT      tbl_cliente_admin.cliente_admin,
								tbl_cliente_admin.nome,
								tbl_cliente_admin.codigo,
								tbl_cliente_admin.cnpj,
								tbl_cliente_admin.cidade
					FROM        tbl_cliente_admin
					WHERE       tbl_cliente_admin.fabrica = $login_fabrica
					$sql_and limit 30";

			$res = pg_exec($con,$sql);
			if (pg_numrows ($res) > 0) {
				for ($i=0; $i<pg_numrows ($res); $i++ ){
					$cliente_admin      = trim(pg_result($res,$i,cliente_admin));
					$nome               = trim(pg_result($res,$i,nome));
					$codigo             = trim(pg_result($res,$i,codigo));
					$cnpj               = trim(pg_result($res,$i,cnpj));
					$cidade             = trim(pg_result($res,$i,cidade));

					echo "$cliente_admin|$cnpj|$codigo|$nome|$cidade";
					echo "\n";
				}
			}
		}
	}
exit;
}

?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

}

</style>

<? include "javascript_pesquisas.php" ?>


<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<!--
<script language="javascript" src="js/cal2">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">

	
function fnc_pesquisa_cliente_admin(campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "cliente_admin_pesquisa.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.codigo_cliente_admin  = campo;
		janela.nome    = campo2;
		janela.focus();
	}
}

	
	$(function(){
		$('.mask_date').datePicker({startDate:'01/01/2000'}).maskedinput("99/99/9999");
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<br>

<FORM name="frm_pesquisa" METHOD="GET" ACTION="<?php echo ( strpos($_SERVER['PHP_SELF'],'test') === false ) ? 'callcenter_backup.php' : 'callcenter_backup_test.php' ?>">
<TABLE width="400" align="center" border="0" cellspacing="0" cellpadding="2">
<TR>
	<TD colspan="5" class="menu_top"><div align="center"><b>Pesquisa por Intervalo entre Datas</b></div></TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan=4><INPUT TYPE="checkbox" NAME="chk_opt1" value="1">&nbsp; Atendimentos lançados hoje</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan=4><INPUT TYPE="checkbox" NAME="chk_opt2" value="1">&nbsp; Atendimentos lançados ontem</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan=4><INPUT TYPE="checkbox" NAME="chk_opt3" value="1">&nbsp; Atendimentos lançados nesta semana</TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan=4><INPUT TYPE="checkbox" NAME="chk_opt4" value="1">&nbsp; Atendimentos lançados neste mês</TD>
</TR>
<? if ($login_fabrica == 52) {?>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" colspan=4><INPUT TYPE="checkbox" NAME="chk_opt18" value="1">&nbsp; Pré-OSs</TD>
</TR>
<?}?>
<TR>
	<TD colspan="5" class="table_line" style="text-align: left;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onClick="document.frm_pesquisa.submit();" style="cursor:pointer " alt="Preencha as opções e clique aqui para pesquisar"></TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line">&nbsp;</td>
	<TD class="table_line">Data Inicial</TD>
	<TD class="table_line" align='left'>Data Final</TD>
	<TD class="table_line" align='left' >&nbsp;</TD>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
		<td class="table_line">
			<input type="text" name="data_inicial" id="data_inicial" size="8" maxlength="10" class='Caixa' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
			
			<!--
			<img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataInicial')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">
			-->
		</td>
		<td class="table_line">
			<input type="text" name="data_final" id="data_final" size="10" maxlength="10" class='Caixa' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
			
			<!-- <img border="0" src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript:showCal('DataFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário"> -->
		</td>
		<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<? if ($login_fabrica == 52) { ?>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="350" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt19" value="1"> Cliente Admin</TD>
	<TD width="180" class="table_line">Código do Cliente Admin</TD>
	<TD width="180" class="table_line">Nome do Cliente Admin</TD>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" align="left" nowrap><INPUT TYPE="text" NAME="codigo_cliente_admin" SIZE="8"> <IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.codigo_cliente_admin,3); fnc_pesquisa_cliente_admin (document.frm_pesquisa.codigo_cliente_admin,document.frm_pesquisa.cliente_nome_admin,'codigo')"></TD>

	<TD width="151" class="table_line" style="text-align: left;" nowrap><INPUT TYPE="text" NAME="cliente_nome_admin" size="15"> <IMG src="imagens/lupa.png" style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.cliente_nome_admin,3); fnc_pesquisa_cliente_admin (document.frm_pesquisa.codigo_cliente_admin,document.frm_pesquisa.cliente_nome_admin,'nome')"></TD>
	<TD width="19" class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>

<? } ?>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="350" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt6" value="1"> Posto</TD>
	<TD width="180" class="table_line">Código do Posto</TD>
	<TD width="180" class="table_line">Nome do Posto</TD>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" align="left" nowrap><INPUT TYPE="text" NAME="codigo_posto" SIZE="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')" <? } ?>><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.codigo_posto,3); fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')"></TD>
	<TD width="151" class="table_line" style="text-align: left;" nowrap><INPUT TYPE="text" NAME="nome_posto" size="15" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'nome')" <? } ?>> <IMG src="imagens/lupa.png" style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.nome_posto,3); fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'nome')"></TD>
	<TD width="19" class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD rowspan="2" width="350" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt7" value="1">Aparelho</TD>
	<TD width="100" class="table_line">Referência</TD>
	<TD width="180" class="table_line">Descrição</TD>
	<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" align="left"><INPUT TYPE="text" NAME="produto_referencia" SIZE="8" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'referencia')" <? } ?>><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.produto_referencia,3); fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'referencia')"></TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="produto_nome" size="15" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'descricao')" <? } ?>><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.produto_nome,3); fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'descricao')"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD colspan="2" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt15" value="1"> Número do Atendimento</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="callcenter" size="17"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD colspan="2" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt8" value="1"> Número de série</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="numero_serie" size="17"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD colspan="2" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt14" value="1"> Número da nota fiscal</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="nota_fiscal" size="17" maxlength='10'></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD colspan="2" class="table_line"><INPUT TYPE="checkbox" NAME="chk_opt9" value="1"> Nome do Consumidor</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="nome_consumidor" size="17"><!-- IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pelo nome do consumidor." onclick="javascript: fnc_pesquisa_consumidor (document.frm_pesquisa.nome_consumidor,'nome')"--></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	<TD class="table_line" colspan=2><INPUT TYPE="checkbox" NAME="chk_opt10" value="1"> CPF/CNPJ do Consumidor</TD>
	<TD class="table_line" align="left"><INPUT TYPE="text" NAME="cpf_consumidor" size="17"><!-- IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar um consumidor pelo seu CPF" onclick="javascript: fnc_tamanho_minimo(document.frm_pesquisa.codigo_posto,3); fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')" --></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>

<!--
<TR>
	<TD class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD class="table_line" colspan=2><INPUT TYPE="checkbox" NAME="chk_opt11" value="1"> Cidade</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="cidade" size="17"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD class="table_line" colspan=2><INPUT TYPE="checkbox" NAME="chk_opt12" value="1"> UF</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="uf" size="2" maxlength="2"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
-->
<TR>
	<TD class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD class="table_line" colspan=2><INPUT TYPE="checkbox" NAME="chk_opt13" value="1"> Número da OS</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="numero_os" size="17"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD class="table_line" colspan=2><INPUT TYPE="checkbox" NAME="chk_opt16" value="1"> Telefone do Consumidor</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="fone" size="17"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD class="table_line" colspan=2><INPUT TYPE="checkbox" NAME="chk_opt17" value="1"> CEP do Consumidor</TD>
	<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="cep" size="17"></TD>
	<TD class="table_line" style="text-align: center;">&nbsp;</TD>
</TR>

<?php 
$aExibirFiltroAtendente = array(5,59,50,30,11,52); // fabricas que podem ver o filtro de atendente
$aExibirFiltroAtendente = array_flip($aExibirFiltroAtendente);
if ( isset($aExibirFiltroAtendente[$login_fabrica]) ){
	# HD 58801
	echo "<tr>";
	echo "<td class='table_line' style='text-align: left;'>&nbsp;</td>";
	echo "<td class='table_line' colspan=2><input type='checkbox' name='por_atendente' value='1'> Atendente</td>";
	echo "<td class='table_line'>";
	echo "<select name='atendente' class='input'>";
	echo "<option value=''></option>";
	$sqlAdm = "SELECT admin, login, nome_completo
			FROM tbl_admin
			WHERE fabrica = $login_fabrica
			AND ativo is true
			AND (privilegios like '%call_center%' or privilegios like '*') 
			ORDER BY nome_completo, login";
	$resAdm = pg_exec($con,$sqlAdm);
	if ( is_resource($resAdm) && pg_numrows($resAdm) > 0){
		$nome_completo_limit = 20;
		while ( $row_atendente = pg_fetch_assoc($resAdm) ) {
			$nome_completo = $nome = ( empty($row_atendente['nome_completo']) ) ? $row_atendente['login'] : $row_atendente['nome_completo'];
			if (strlen($nome) >= $nome_completo_limit) {
				$nome = substr($nome, 0, $nome_completo_limit-3).'...';
			}
			?>
			<option value="<?php echo $row_atendente['admin']; ?>"><?php echo $nome; ?></option>
			<?php
		}
	}
	echo "</select>";
	echo "</td>";
	echo "<TD class='table_line' style='text-align: center;'>&nbsp;</TD>";
	echo "</tr>";
}
?>

<?php if ($login_fabrica == 5): // HD 59746 (augusto) ?>
<tr>
	<td class="table_line"> &nbsp; </td>
	<td class="table_line" colspan="2">
		<input type="checkbox" id="providencia_chk" name="providencia_chk" value="1" />
		<label for="providencia_chk">Providência</label>
	</td>
	<td class="table_line">
		<?php 
			$sql = "SELECT hd_situacao, descricao
					FROM tbl_hd_situacao
					WHERE fabrica = %s
					ORDER BY descricao";
			$sql       = sprintf($sql,pg_escape_string($login_fabrica));
			$res       = pg_exec($con,$sql);
			$rows      = (int) pg_numrows($res);
			$situacoes = array();
			if ( $rows > 0 ) {
				while ($row = pg_fetch_assoc($res)) {
					$situacoes[$row['hd_situacao']] = $row['descricao'];
				}
			}
		?>
		<select name="providencia" id="providencia" style="width: 140px;">
			<option value=""></option>
			<?php foreach($situacoes as $id=>$descr): ?>
				<option value="<?php echo $id; ?>"><?php echo utf8_decode($descr); ?></option>
			<?php endforeach; ?>
		</select>
	</td>
	<td class="table_line"> &nbsp; </td>
</tr>
<tr>
	<td class="table_line"> &nbsp; </td>
	<td class="table_line" colspan="2">
		<input type="checkbox" id="providencia_data_chk" name="providencia_data_chk" value="1" />
		<label for="providencia_data_chk">Data da Providência</label>
	</td>
	<td class="table_line">
		<input type="text" name="providencia_data" id="providencia_data" class="mask_date" size="10" maxlength="10" />
	</td>
	<td class="table_line"> &nbsp; </td>
</tr>
<tr>
	<td class="table_line"> &nbsp; </td>
	<td class="table_line" colspan="2">
		<input type="checkbox" id="regiao_chk" name="regiao_chk" value="1" />
		<label for="regiao_chk">Região</label>
	</td>
	<td class="table_line">
		<select name="regiao" id="regiao" style="width: 140px;">
			<option value=""></option>
			<option value="SUL">Sul</option>
			<option value="SP">São Paulo - Capital</option>
			<option value="SP-interior">São Paulo - Interior</option>
			<option value="RJ">Rio de Janeiro</option>
			<option value="MG">Minas Gerais</option>
			<option value="PE">Pernambuco</option>
			<option value="BA">Bahia</option>
			<option value="BR-NEES">Nordeste + E.S.</option>
			<option value="BR-NCO">Norte + C.O.</option>
		</select>
	</td>
	<td class="table_line"> &nbsp; </td>
</tr>
<?php endif; ?>

<TR>
	<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
</TR>
<TR>
	<TD colspan="5" class="table_line"><b>Condição do Atendimento</b></TD>
</TR>
<TR>
	<TD class="table_line" style="text-align: left;">&nbsp;</TD>
	<TD  class="table_line"><input type="radio" name="situacao" value="TODOS"  checked>Todos</TD>
	<TD  class="table_line"><input type="radio" name="situacao" value="PENDENTES" >Pendentes</TD>
	<?php if ($login_fabrica == 11): // HD 133146 (augusto) ?>
			<TD  class="table_line"><input type="radio" name="situacao" value="ANALISE" >Em análise</TD>
	<?php endif; ?>
	<?php if ($login_fabrica == 5): // HD 59746 (augusto) ?>
			<TD  class="table_line"><input type="radio" name="situacao" value="PENDENTES" >Em andamento</TD>
	<?php endif; ?>
	<TD  class="table_line"><input type="radio" name="situacao" value="SOLUCIONADOS" >Solucionados</TD>
	<?php if ($login_fabrica != 5): ?>
		<TD  class="table_line" style="text-align: left;">&nbsp;</TD>
	<?php endif; ?>
</TR>




<TR>
	<TD colspan="5" class="table_line" style="text-align: left;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onClick="document.frm_pesquisa.submit();" style="cursor:pointer " alt="Preencha as opções e clique aqui para pesquisar"></TD>
</TR>
</TABLE>
</FORM>
<BR>

<? include "rodape.php" ?>
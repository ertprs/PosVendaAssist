<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

#Para a rotina automatica - Fabio - HD 11750
$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include "autentica_admin.php";
}

//include "gera_relatorio_pararelo_include.php";
//--------------

include 'includes/funcoes.php';

$admin_privilegios="gerencia";

$title = "RELATÓRIO GARANTIAS";
$layout_menu = "gerencia";

include "cabecalho.php";

if (filter_input(INPUT_POST,'btn_acao')) {
    $data_inicial   = filter_input(INPUT_POST,'data_inicial_01');
    $data_final     = filter_input(INPUT_POST,'data_final_01');
    $codigo_posto   = filter_input(INPUT_POST,'codigo_posto');
    $pais           = filter_input(INPUT_POST,'pais');
    $origem         = filter_input(INPUT_POST,'origem',FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);
    $linha          = filter_input(INPUT_POST,'linha');
    $familia        = filter_input(INPUT_POST,'familia');
    $tabela_preco   = filter_input(INPUT_POST,'tabela_preco',FILTER_UNSAFE_RAW,FILTER_REQUIRE_ARRAY);

	if(strlen($pais)==0) $msg_erro = "Escolha o País";

    if(count($origem) == 0 && empty($msg_erro)) $msg_erro = "Escolha a Origem";

    if(count($tabela_preco) == 0  && empty($msg_erro)) $msg_erro = "Escolha a Tabela de Preço";

	if (strlen($data_inicial) == 0 or strlen($data_final) == 0 or $data_inicial > $data_final) {
		$msg_erro = "Data Inválida<br>";
	}

	if (strlen($msg_erro) == 0) {

		$dat = explode ("/", $data_inicial );
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];
            if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";

		if(strlen($msg_erro) == 0)
			$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");

		if (strlen ( pg_errormessage ($con) ) > 0) {
			$msg_erro = pg_errormessage ($con) ;
		}

		if (strlen($msg_erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
	}

	if (strlen($msg_erro) == 0) {

		$dat = explode ("/", $data_final );
            $d = $dat[0];
            $m = $dat[1];
            $y = $dat[2];
            if(!checkdate($m,$d,$y)) $msg_erro = "Data Inválida";

		if(strlen($msg_erro) == 0)
			$fnc = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");

		if (strlen ( pg_errormessage ($con) ) > 0) {
			$msg_erro = pg_errormessage ($con) ;
		}

		if (strlen($msg_erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
	}

}

// MLG 2009-08-04 HD 136625
    $sql = 'SELECT pais,nome FROM tbl_pais';
    $res = pg_query($con,$sql);
    $p_tot = pg_num_rows($res);
    for ($i; $i<$p_tot; $i++) {
        list($p_code,$p_nome) = pg_fetch_row($res, $i);
        $a_paises[$p_code] = $p_nome;
    	$sel_paises .= "\t\t\t\t<option value='$p_code'";
        $sel_paises .= ($pais==$p_code)?" selected":"";
        $sel_paises .= ">$p_nome</option>\n";
    }
?>
<style type="text/css">
.topo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}
	.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
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
table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.conteudo10 {
	color: #000000;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.conteudo20 {
	background-color: #D9E2EF;
	color: #000000;
	font:12px Tahoma, Verdana, Arial, Helvetica, Sans-Serif
}

.box{width:200px;}

</style>
<?
include "javascript_calendario_new.php";

include "../js/js_css.php";

?>
<link rel="stylesheet" href="css/multiple-select.css" />
<script src="js/jquery.multiple.select.js"></script>

<script type="text/javascript">
function fnc_pesquisa_posto (campo, campo2, tipo) {
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
    else
        alert("Preencha toda ou parte da informação para realizar a pesquisa!");
}

$(function()
{
    $('#data_inicial_01').datepick({startDate:'01/01/2000'});
    $('#data_final_01').datepick({startDate:'01/01/2000'});
    $("#data_inicial_01").mask("99/99/9999");
    $("#data_final_01").mask("99/99/9999");

    $("#origem").multipleSelect({
        width: '250px',
        selectAllText: "TODOS",
        allSelected:"Todas origens selecionadas",
        countSelected: "# de % selecionadas"
    });

    $("#tblpreco").multipleSelect({
        width: '500px',
        selectAllText: "TODOS",
        allSelected: "Todas tabelas selecionadas",
        countSelected: "# de % selecionadas"
    });

});
</script>

<?
//gera relatorio
if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	//include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	//include "gera_relatorio_pararelo_verifica.php";
}

if (strlen($msg_erro) > 0){
?>
<table width="700" border="0" cellpadding="0" cellspacing="1" align='center'>
<tr>
	<td align="center" class='msg_erro'>
		<? echo $msg_erro ?>
	</td>
</tr>
</table>

<?
}
?>
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">
<input type="hidden" name='btn_acao' value="">

	<TABLE width="700" align="center" border="0" class='formulario espaco' cellspacing="1" cellpadding="0">
		<TR>
			<TD colspan="3" class="titulo_tabela" align='center' >Parâmetros de Pesquisa</TD>
		</TR>
		<TR>
			<td width="15%">&nbsp;</td>
			<TD width="250px"> Data Inicial<BR>
			<INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" id="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" class='frm'></TD>
			<TD>Data Final<BR>
			<INPUT size="12" class="frm" maxlength="10" TYPE="text" NAME="data_final_01" id="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; ?>"></TD>
		</TR>
		<tr>
			<td>&nbsp;</td>
			<td>Cód. Posto<BR>
			<input type="text" name="codigo_posto" size="8"  value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo')">
			</td>
			<td>Nome Posto<BR>
			<input type="text" name="posto_nome" size="30" value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome')">
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>País<BR>
    			<select name='pais' size='1' class='frm box'>
        			 <option></option>
                    <?echo $sel_paises;?>
    			</select>
			</td>
			<td>Origem<BR>
				<select name="origem[]" id="origem" class="frm box" multiple="multiple">
					<option value='Nac' <?=(in_array('Nac',$origem)) ? " SELECTED " : ""?>>Nacional</option>
					<option value='Imp' <?=(in_array('Imp',$origem)) ? " SELECTED " : ""?>>Importado</option>
					<option value='USA' <?=(in_array('USA',$origem)) ? " SELECTED " : ""?>>Importado USA</option>
					<option value='Asi' <?=(in_array('Asi',$origem)) ? " SELECTED " : ""?>>Importado Asia</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td>Linha<BR>
			<?
			##### INÍCIO LINHA #####
			$sql = "SELECT  *
					FROM    tbl_linha
					WHERE   tbl_linha.fabrica = $login_fabrica
					ORDER BY tbl_linha.nome;";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {
				echo "<select class='frm box' name='linha'>\n";
				echo "<option value=''>ESCOLHA</option>\n";

				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_linha = trim(pg_result($res,$x,linha));
					$aux_nome  = trim(pg_result($res,$x,nome));

					echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>\n";
				}
				echo "</select>\n";
			}
			##### FIM LINHA #####
			?>
			</td>
			<td>Família<BR>
			<?
			##### INÍCIO FAMÍLIA #####
			$sql = "SELECT  *
					FROM    tbl_familia
					WHERE   tbl_familia.fabrica = $login_fabrica
					ORDER BY tbl_familia.descricao;";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {
				echo "<select class='frm box' name='familia'>\n";
				echo "<option value=''>ESCOLHA</option>\n";

				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_familia = trim(pg_result($res,$x,familia));
					$aux_descricao  = trim(pg_result($res,$x,descricao));

					echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
				}
				echo "</select>\n";
			}
			##### FIM FAMÍLIA #####
			?>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<TD colspan='2' >Tabela Preço<BR>
				<select class='frm' style='width: 200px;' name='tabela_preco[]' multiple="multiple" id="tblpreco">
<?php
					$sql_preco = "select
									tabela,
									descricao
								from tbl_tabela where
								fabrica = $login_fabrica";
					$resp = pg_exec ($con,$sql_preco);

					for ($w = 0 ; $w < pg_numrows($resp) ; $w++){
						$aux_tabela = trim(pg_result($resp,$w,tabela));
						$aux_nome  = trim(pg_result($resp,$w,descricao));

						echo "<option value='$aux_tabela'"; if (in_array($aux_tabela,$tabela_preco)) echo " SELECTED "; echo ">$aux_nome</option>\n";
					}
?>
				</SELECT>
			</TD>
		</tr>
		<tr>
			<td colspan='3' align='center'>
				<input type="button" style="cursor:pointer;margin:5px 0 5px;" onclick="javascript: if (document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='continuar' ; document.frm_pesquisa.submit() } else { alert ('Aguarde submissão') }" value="Pesquisar" />

			</td>
		</tr>
	</TABLE>

<!-- 	<BR>
	<center>
	<img src='imagens_admin/btn_pesquisar_400.gif' style="cursor:pointer" onclick="javascript: if (document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='continuar' ; document.frm_pesquisa.submit() } else { alert ('Aguarde submissão') }" ALT="Confirmar" border='0'>
	</center>
 -->	<br>

</FORM>

<?
/*###############################################################################################*/

	if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {

		if (strlen($codigo_posto) > 0) {
			$sql = "SELECT  posto
				FROM    tbl_posto_fabrica
				JOIN    tbl_posto USING(posto)
				WHERE fabrica = $login_fabrica
				AND codigo_posto = '$codigo_posto'";
			//echo "sql: $sql";

			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {
				$posto = trim(pg_result($res,0,posto));
			}
		}

		if(strlen($posto )>0){
			$cond1 = " AND tbl_posto.posto = $posto";
		}else{
			$cond1 = " AND 1=1";
		}

		if (is_array($origem)) {
// 		print_r($origem);
// 		echo implode("','",$origem);exit;
			$cond2 = "AND tbl_produto.origem IN ('".implode("','",$origem)."')";
		}

		if(strlen($linha)>0){
			$cond3 = "AND tbl_produto.linha = '$linha'";
		}else{
			$cond3 = "AND 1=1";
		}

		if(strlen($familia)>0){
			$cond4 = "AND tbl_produto.familia = '$familia'";
		}else{
			$cond4 = "AND 1=1";
		}

		$sql = "SELECT tbl_extrato.extrato,
					tbl_extrato.posto
					INTO TEMP tmp_extrato_posto_$login_admin
					FROM tbl_extrato
					WHERE tbl_extrato.fabrica = $login_fabrica
					AND tbl_extrato.data_geracao BETWEEN '$aux_data_inicial 00:00:00' AND '$aux_data_final 23:59:59';

					CREATE INDEX tmp_extrato_posto_posto_$login_admin ON tmp_extrato_posto_$login_admin(posto);

					CREATE INDEX tmp_extrato_posto_extrato_$login_admin ON tmp_extrato_posto_$login_admin(extrato);

					SELECT extrato , pais
					INTO TEMP tmp_extrato_$login_admin
					from tbl_posto
					JOIN tmp_extrato_posto_$login_admin USING(posto)
					WHERE tbl_posto.pais = UPPER('$pais')
					$cond1;

					CREATE INDEX tmp_extrato_extrato_$login_admin ON tmp_extrato_$login_admin(extrato);

					SELECT tbl_os_extra.os ,tmp_extrato_$login_admin.pais
					INTO TEMP tmp_valor_os_$login_admin
					FROM tbl_os_extra
					JOIN tmp_extrato_$login_admin USING(extrato)
					WHERE tbl_os_extra.i_fabrica = $login_fabrica;

					CREATE INDEX tmp_valor_os_OS_$login_admin ON tmp_valor_os_$login_admin(os);

					SELECT tbl_os.os,
					tbl_os.mao_de_obra,
					tbl_produto.origem,
					tbl_produto.linha,
					tbl_produto.familia,
					tmp_valor_os_$login_admin.pais
					INTO TEMP tmp_os_valor_$login_admin
					FROM tbl_os
					JOIN tmp_valor_os_$login_admin ON tmp_valor_os_$login_admin.os = tbl_os.os
					JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto AND tbl_produto.fabrica_i = $login_fabrica
					WHERE tbl_os.fabrica = $login_fabrica
					$cond2
					$cond3
					$cond4
					AND tbl_os.excluida IS NOT TRUE ;

					CREATE INDEX tmp_os_valor_ ON tmp_os_valor_$login_admin(os);

					select os,
					peca,
					qtde
					into temp table tmp_os_item_pecas_$login_admin
					from tbl_os_produto
					join tbl_os_item using(os_produto)
					where tbl_os_produto.os in (select os from tmp_os_valor_$login_admin);

					CREATE INDEX tmp_os_item_valor_pecas_os_$login_admin ON tmp_os_item_pecas_$login_admin(os);

					CREATE INDEX tmp_os_item_valor_pecas_peca_$login_admin ON tmp_os_item_pecas_$login_admin(peca);

					SELECT os,
						sum(preco * qtde) as pecas
					INTO TEMP tmp_os_valor_pecas_$login_admin
					FROM tmp_os_item_pecas_$login_admin
					JOIN tbl_tabela_item ON tbl_tabela_item.peca = tmp_os_item_pecas_$login_admin.peca AND tbl_tabela_item.tabela IN (".implode(',',$tabela_preco).")
					GROUP BY OS;

					SELECT sum(tmp_os_valor_$login_admin.mao_de_obra) as total_mo,
						sum(tmp_os_valor_pecas_$login_admin.pecas) as total_pc,
						count(*) as total_qtde,
						origem,
						pais,
						tbl_linha.nome,
						tbl_familia.descricao
					into temp table tmp_os_valor_mao_obra_$login_admin
					FROM tmp_os_valor_$login_admin
					left JOIN tmp_os_valor_pecas_$login_admin using(os)
					JOIN tbl_linha using(linha)
					JOIN tbl_familia USING(familia)
					GROUP BY tmp_os_valor_$login_admin.pais, tbl_linha.nome, tbl_familia.descricao, tmp_os_valor_$login_admin.origem;

					select
						nome AS linha,
						descricao AS familia,
						pais,
						origem,
						total_mo,
						total_pc AS total_pecas,
						total_qtde
					from tmp_os_valor_mao_obra_$login_admin";

		flush();

		$res = pg_exec ($con,$sql);

    if (pg_numrows($res) > 0) {


		$arquivo_nome     = "relatorio_garantias-$login_fabrica-$ano-$mes-$data.txt";
		$path             = "/www/assist/www/admin/xls/";
		$path_tmp         = "/tmp/assist/";

		$arquivo_completo     = $path.$arquivo_nome;
		$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

		echo `rm $arquivo_completo_tmp `;
		echo `rm $arquivo_completo_tmp.zip `;
		echo `rm $arquivo_completo.zip `;
		echo `rm $arquivo_completo `;

		$fp = fopen ($arquivo_completo_tmp,"w");

		fputs ($fp, "País\tOrigem\tLinha\tFamilia\tTotal Qtde\tTotal MO\t Total Peça\r\n");

		echo "<TABLE width='700' border='0' cellpadding='0' cellspacing='1' align='center' class='tabela'>";
		echo "<TR class='menu_top'>";
			echo "<TD>País</TD>";
			echo "<TD>Origem</TD>";
			echo "<TD>Linha</TD>";
			echo "<TD>Familia</TD>";
			echo "<TD>Total Qtde</TD>";
			echo "<TD>Total MO</TD>";
			echo "<TD>Total Peças</TD>";
		echo "</TR>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$origem      = trim(pg_result($res,$i,origem));
			$pais        = trim(pg_result($res,$i,pais));
			$linha       = trim(pg_result($res,$i,linha));
			$familia     = trim(pg_result($res,$i,familia));
			$total_qtde  = trim(pg_result($res,$i,total_qtde));
			$total_mo    = trim(pg_result($res,$i,total_mo));
			$total_pecas = trim(pg_result($res,$i,total_pecas));

			if(strlen($total_qtde)==0) $total_qtde = "0";
			$total_mo    = number_format($total_mo,2,",",".");
			$total_pecas = number_format($total_pecas,2,",",".");

			fputs($fp,"$pais\t");
			fputs($fp,"$origem\t");
			fputs($fp,"$linha\t");
			fputs($fp,"$familia\t");
			fputs($fp,"$total_qtde\t");
			fputs($fp,"$total_mo\t");
			fputs($fp,"$total_pecas\t");
			fputs($fp,"\r\n");

		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

		echo "<TR bgcolor='$cor'>";
			echo "<TD align='left' nowrap>";
				 echo $a_paises[$pais];
			echo"</TD>";

			echo "<TD align='left' nowrap>";
					if ($origem == "Nac") echo "Nacional";
					if ($origem == "Imp") echo "Importado";
					if ($origem == "USA") echo "Importado USA";
					if ($origem == "Asi") echo "Importado Asia";
			echo "</TD>";
			echo "<TD >$linha</TD>";
			echo "<TD >$familia</TD>";
			echo "<TD align='right' nowrap title=''>$total_qtde</TD>";
			echo "<TD align='right' nowrap title=''>$total_mo</TD>";
			echo "<TD align='right' nowrap title=''>$total_pecas</TD>";
		echo "</TR>";
		}

		fclose ($fp);
		flush();

		echo "</TABLE>";
		//gera o zip
		echo `cd $path_tmp; rm -rf $arquivo_nome.zip; zip -o $arquivo_nome.zip $arquivo_nome > /dev/null ; mv  $arquivo_nome.zip $path `;

		echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo "<tr>";
		echo "<td align='center'>
				<button onclick=\"window.location='xls/$arquivo_nome.zip'\">Download TXT</a>
				<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>(Colunas separadas com TABULAÇÃO)</font>
			</td>";
		echo "</tr>";
		echo "</table>";
		echo "</td>";
		echo "</table>";
		flush();
		echo "<br>";
	}else{
		echo "<br><span class='conteudo10' align='center'>";
		echo "Não existem OS neste período!";
		echo "</span>";
	}


	}

include "rodape.php" ?>

<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria,gerencia";
include 'autentica_admin.php';

$layout_menu = "auditoria";
$title = "Relatório OS";

$btn_acao     = $_POST['btn_acao'];
if($btn_acao=="Pesquisar"){
	$codigo_posto = trim ($_POST['codigo_posto']);
	$sua_os       = trim ($_POST['sua_os']);
	$mes          = trim ($_POST['mes']);
	$ano          = trim ($_POST['ano']);
	$familia      = trim ($_POST['familia']);
	$referencia   = trim ($_POST['referencia']);
	$detalhado_peca = trim ($_POST['detalhado_peca']);

	if(strlen($familia) == 0) $msg_erro = "Escolha a familia";
	if (strlen($mes) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}
	
	if (strlen ($sua_os) == 0)  {
		if (strlen ($mes) == 0 OR strlen ($ano) == 0)  {
		$msg_erro = "Escolha o mês e o ano para fazer a pesquisa";
		}
	}

	if(strlen($codigo_posto) > 0){
		$sql = "SELECT posto
				FROM tbl_posto_fabrica
				WHERE codigo_posto = '$codigo_posto'
				AND fabrica = $login_fabrica";
		$res = pg_exec($con, $sql);
		if(pg_numrows($res)>0) $posto = pg_result($res,0,0);
	}
}

include 'cabecalho.php';
include "javascript_pesquisas.php";

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

?>
<style>
.Conteudo{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
}
.Conteudo2{
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
}

.Erro{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	color:#CC3300;
	font-weight: bold;
	background-color:#FFFFFF;
}
</style>

<SCRIPT LANGUAGE="JavaScript">
<!--
function fnc_pesquisa_produto (campo, tipo) {
	if (campo.value != "") {
		var url = "";
		url = "produto_pesquisa.php?retorno=<? echo $PHP_SELF ?>&forma=&campo=" + campo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= document.frm_os.referencia;
		janela.descricao = document.frm_os.descricao;
		janela.linha     = document.frm_os.linha;
		janela.familia   = document.frm_os.familia;
		janela.focus();
	}
}
//-->
</SCRIPT>
<?
if(strlen($msg_erro)>0){
	echo "<table class='Tabela' width='700' cellspacing='0'  cellpadding='0' align='center'>";
	echo "<tr >";
	echo "<td  class='Erro' bgcolor='FFFFFF' align='center'><img src='imagens/proibido2.jpg' align='middle'>&nbsp; $msg_erro</td>";
	echo "</tr>";
	echo "</table><br>";
}
?>
<FORM METHOD="POST"  NAME="frm_os" ACTION="<? echo $PHP_SELF; ?>">
<table width="450" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Conteudo" bgcolor="#596D9B" align='left'>
		<td colspan='3' height='27' align='center'><FONT COLOR="#FFFFFF">PESQUISA OS</FONT> </td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td class='Conteudo'>Mês</td>
		<td class='Conteudo' colspan='2'>Ano</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
			<select name="mes" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 1 ; $i <= count($meses) ; $i++) {
				echo "<option value='$i'";
				if ($mes == $i) echo " selected";
				echo ">" . $meses[$i] . "</option>";
			}
			?>
			</select>
		</td>
		<td colspan='2'>
			<select name="ano" size="1" class="frm">
			<option value=''></option>
			<?
			for ($i = 2003 ; $i <= date("Y") ; $i++) {
				echo "<option value='$i'";
				if ($ano == $i) echo " selected";
				echo ">$i</option>";
			}
			?>
			</select>
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td class='Conteudo'>Posto</td>
		<td class='Conteudo' colspan='2'>Nome do Posto</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>
			<input type="text" name="codigo_posto" id="codigo_posto" size="8" value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_os.codigo_posto, document.frm_os.posto_nome, 'codigo')">
		</td>
		<td colspan='2'>
			<input type="text" name="posto_nome" id="posto_nome" size="30" value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens/lupa.png" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_os.codigo_posto, document.frm_os.posto_nome, 'nome')">
		</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td>Referência</td>
		<td colspan='2'>Produto Descrição </td>
	</tr>

	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td nowrap><input type="text" class="frm" name="referencia" value="<? echo $referencia ?>" size="12" maxlength="20" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto (document.frm_os.referencia, 'referencia')" <? } ?>><a href='#'><img src="imagens_admin/btn_buscar5.gif" onclick="javascript: fnc_pesquisa_produto (document.frm_os.referencia, 'referencia')"></a></td>
		<td nowrap colspan='2'><input type="text" class="frm" size="40" name="descricao" value="<? echo $descricao ?>" maxlength="50" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_produto (document.frm_os.descricao, 'descricao')" <? } ?>><a href='#'><img src="imagens_admin/btn_buscar5.gif" onclick="javascript: fnc_pesquisa_produto (document.frm_os.descricao, 'descricao')"></a></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td class='Conteudo' colspan='3'>Familia</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td colspan='3'>
			<?
				##### INÍCIO FAMÍLIA #####
				$sql = "SELECT  *
						FROM    tbl_familia
						WHERE   tbl_familia.fabrica = $login_fabrica
						ORDER BY tbl_familia.descricao;";
				$res = pg_exec ($con,$sql);

				if (pg_numrows($res) > 0) {
					echo "<select class='frm' style='width: 280px;' name='familia'>\n";
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
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td class='Conteudo' colspan='3'><INPUT TYPE="checkbox" NAME="detalhado_peca" <? if(strlen($detalhado_peca)>0) echo "checked"; ?>>&nbsp;Detalhar por peça</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan='3' align='center'><br><input type="submit" name="btn_acao" value="Pesquisar"></td>
	</tr>
	</TABLE>
</FORM>


<?
if($btn_acao=="Pesquisar" AND strlen($msg_erro)==0){

	if(strlen($data_inicial)>0 AND strlen($data_final)>0){
		$join_data = " AND tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final' ";
	}

	if(strlen($referencia)>0){
		$join_produto = " AND tbl_produto.referencia = '$referencia' ";
	}

	if(strlen($posto)>0){
		$join_posto = " AND tbl_os.posto = $posto ";
	}

	if(strlen($detalhado_peca)>0){
		$select_detalhado_peca = "
		tbl_peca.referencia        AS peca_referencia               ,
		tbl_peca.descricao         AS peca_descricao                ,
		tbl_os_item.qtde           AS qtde_item_peca                ,";
		
		$join_detalhado_peca = "
		JOIN tbl_os_produto ON  tbl_os.os                  = tbl_os_produto.os
		JOIN tbl_os_item    ON  tbl_os_produto.os_produto  = tbl_os_item.os_produto
		JOIN tbl_peca       ON  tbl_os_item.peca           = tbl_peca.peca";
	}

	
	$sql = "SELECT tbl_os.os                                            ,
			tbl_os.sua_os                                               ,
			tbl_posto_fabrica.codigo_posto                              ,
			tbl_posto.nome AS posto_nome                                ,
			tbl_os.serie                                                ,
			to_char(tbl_os.data_abertura, 'dd/mm/yyyy')   AS abertura   ,
			to_char(tbl_os.data_fechamento, 'dd/mm/yyyy') AS fechamento ,
			tbl_os.posto                                                ,
			tbl_os.consumidor_nome                                      ,
			tbl_os.consumidor_fone                                      ,
			tbl_os.produto                                              ,
			tbl_produto.referencia     AS produto_referencia            ,
			tbl_produto.descricao      AS produto_descricao             ,
			$select_detalhado_peca 
			tbl_os.defeito_reclamado_descricao                          ,
			(SELECT descricao FROM tbl_defeito_constatado WHERE tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado) AS defeito_constatado,
			(SELECT descricao FROM tbl_solucao WHERE tbl_os.solucao_os = tbl_solucao.solucao AND tbl_solucao.fabrica = $login_fabrica) As solucao_os         ,
			tbl_os.nota_fiscal                                          ,
			to_char(tbl_os.data_nf, 'dd/mm/yyyy') AS data_nf            ,
			tbl_os.revenda_nome                                         ,
			tbl_os.revenda_cnpj
		FROM tbl_os
		JOIN  tbl_produto ON tbl_produto.produto = tbl_os.produto
		JOIN  tbl_linha   ON tbl_linha.linha     = tbl_produto.linha AND tbl_linha.fabrica = $login_fabrica
		JOIN  tbl_familia ON tbl_familia.familia = tbl_produto.familia
		JOIN  tbl_posto ON tbl_posto.posto = tbl_os.posto
		JOIN  tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
		$join_detalhado_peca
		WHERE tbl_os.fabrica    = $login_fabrica
		AND tbl_produto.familia = $familia
		AND tbl_os.finalizada IS NOT NULL
		$join_data
		$join_produto
		$join_posto
		ORDER BY tbl_os.sua_os";
	#echo nl2br($sql);

	$res = pg_exec($con, $sql);

	if(pg_numrows($res)>0){
		$data = date ("dmY");
		echo "<p id='id_download' style='display:none'><a href='xls/relatorio_os-$login_fabrica.$data.xls' target='_blank'>Fazer download do arquivo em  XLS </a></p><br>";

		echo "<table width='750' align='center' border='1' cellspacing='1' cellpadding='1'>";
		$campo =  "<table width='750' align='center' border='1' cellspacing='1' cellpadding='1'>";
		$campo .=  "<tr class='Conteudo'align='left'>";
			$campo .=  "<td  align='center'>OS</td>";
			$campo .=  "<td  align='center'>PO#</td>";
			$campo .=  "<td  align='center'>AB</td>";
			$campo .=  "<td  align='center'>FC</td>";
			$campo .=  "<td  align='center'>POSTO</td>";
			$campo .=  "<td  align='center'>CONSUMIDOR</td>";
			$campo .=  "<td  align='center'>TELEFONE</td>";
			$campo .=  "<td  align='center'>PRODUTO</td>";
			$campo .=  "<td  align='center' nowrap>DEFEITO RECLAMADO</td>";
			$campo .=  "<td  align='center' nowrap>DEFEITO CONSTATADO</td>";
			$campo .=  "<td  align='center'>SOLUÇÃO</td>";
			$campo .=  "<td  align='center'>NF</td>";
			$campo .=  "<td  align='center' nowrap>DATA COMPRA</td>";
			$campo .=  "<td  align='center' nowrap>NOME REVENDA</td>";
			$campo .=  "<td  align='center' nowrap>CNPJ REVENDA</td>";
			if(strlen($detalhado_peca)>0){
			$campo .=  "<td  align='center' nowrap>PEÇA</td>";
			$campo .=  "<td  align='center' nowrap>QTDE PEÇA</td>";
			}
		$campo .=  "</tr>";

		echo "<tr class='Conteudo' bgcolor='#596D9B' align='left'>";
			echo "<td  align='center'><FONT COLOR='#FFFFFF'>OS</FONT> </td>";
			echo "<td  align='center'><FONT COLOR='#FFFFFF'>PO#</FONT> </td>";
			echo "<td  align='center'><FONT COLOR='#FFFFFF'>AB</FONT> </td>";
			echo "<td  align='center'><FONT COLOR='#FFFFFF'>FC</FONT> </td>";
			echo "<td  align='center'><FONT COLOR='#FFFFFF'>POSTO</FONT> </td>";
			echo "<td  align='center'><FONT COLOR='#FFFFFF'>CONSUMIDOR</FONT> </td>";
			echo "<td  align='center'><FONT COLOR='#FFFFFF'>TELEFONE</FONT> </td>";
			echo "<td  align='center'><FONT COLOR='#FFFFFF'>PRODUTO</FONT> </td>";
			echo "<td  align='center' nowrap><FONT COLOR='#FFFFFF'>DEFEITO RECLAMADO</FONT> </td>";
			echo "<td  align='center' nowrap><FONT COLOR='#FFFFFF'>DEFEITO CONSTATADO</FONT> </td>";
			echo "<td  align='center'><FONT COLOR='#FFFFFF'>SOLUÇÃO</FONT> </td>";
			echo "<td  align='center'><FONT COLOR='#FFFFFF'>NF</FONT> </td>";
			echo "<td  align='center' nowrap><FONT COLOR='#FFFFFF'>DATA COMPRA</FONT> </td>";
			echo "<td  align='center' nowrap><FONT COLOR='#FFFFFF'>NOME REVENDA</FONT> </td>";
			echo "<td  align='center' nowrap><FONT COLOR='#FFFFFF'>CNPJ REVENDA</FONT> </td>";
			if(strlen($detalhado_peca)>0){
			echo "<td  align='center' nowrap><FONT COLOR='#FFFFFF'>PEÇA</FONT> </td>";
			echo "<td  align='center' nowrap><FONT COLOR='#FFFFFF'>QTDE PEÇA</FONT> </td>";
			}
		echo "</tr>";


		for($i=0; $i<pg_numrows($res); $i++){
			$os                          = trim(pg_result($res, $i, os));
			$sua_os                      = trim(pg_result($res, $i, sua_os));
			$codigo_posto                = trim(pg_result($res, $i, codigo_posto));
			$posto_nome                  = trim(pg_result($res, $i, posto_nome));
			$serie                       = trim(pg_result($res, $i, serie));
			$data_abertura               = trim(pg_result($res, $i, abertura));
			$data_fechamento             = trim(pg_result($res, $i, fechamento));
			$consumidor_nome             = trim(pg_result($res, $i, consumidor_nome));
			$consumidor_fone             = trim(pg_result($res, $i, consumidor_fone));
			$produto_referencia          = trim(pg_result($res, $i, produto_referencia));
			$produto_descricao           = trim(pg_result($res, $i, produto_descricao));
			$defeito_reclamado_descricao = trim(pg_result($res, $i, defeito_reclamado_descricao));
			$solucao_os                  = trim(pg_result($res, $i, solucao_os));
			$defeito_constatado          = trim(pg_result($res, $i, defeito_constatado));
			$nota_fiscal                 = trim(pg_result($res, $i, nota_fiscal));
			$data_nf                     = trim(pg_result($res, $i, data_nf));
			$revenda_nome                = trim(pg_result($res, $i, revenda_nome));
			$revenda_cnpj                = trim(pg_result($res, $i, revenda_cnpj));
			if (strlen($revenda_cnpj) == 14) $revenda_cnpj = substr($revenda_cnpj,0,2) .".". substr($revenda_cnpj,2,3) .".". substr($revenda_cnpj,5,3) ."/". substr($revenda_cnpj,8,4) ."-". substr($revenda_cnpj,12,2);
			
			if(strlen($detalhado_peca)>0){
			$peca_referencia = trim(pg_result($res, $i, peca_referencia));
			$peca_descricao  = trim(pg_result($res, $i, peca_descricao));
			$qtde_item_peca  = trim(pg_result($res, $i, qtde_item_peca));
			}
			
			if($i%2==0) $cor = "#D9E2EF";
			else        $cor = "#EBEBEB";

			$campo .=  "<tr class='Conteudo2' align='left'>";
				$campo .=  "<td  align='center' nowrap>$sua_os</td>";
				$campo .=  "<td  align='center' nowrap>$serie</td>";
				$campo .=  "<td  align='center' nowrap>$data_abertura </td>";
				$campo .=  "<td  align='center' nowrap>$data_fechamento </td>";
				$campo .=  "<td  nowrap>$codigo_posto - $posto_nome </td>";
				$campo .=  "<td  nowrap>$consumidor_nome </td>";
				$campo .=  "<td  align='center' nowrap>$consumidor_fone </td>";
				$campo .=  "<td  nowrap>$produto_referencia - $produto_descricao </td>";
				$campo .=  "<td >$defeito_reclamado_descricao </td>";
				$campo .=  "<td nowrap>$defeito_constatado</td>";
				$campo .=  "<td nowrap>$solucao_os</td>";
				$campo .=  "<td nowrap>$nota_fiscal </td>";
				$campo .=  "<td nowrap>$data_nf</td>";
				$campo .=  "<td nowrap>$revenda_nome</td>";
				$campo .=  "<td nowrap>$revenda_cnpj</td>";
				if(strlen($detalhado_peca)>0){
				$campo .=  "<td nowrap>";
				if(strlen($peca_referencia)>0 AND strlen($peca_descricao)>0){
					$campo .= "$peca_referencia - $peca_descricao";
				}else{
					$campo .= "&nbsp;";
				}
				$campo .= "</td>";
				$campo .=  "<td nowrap>$qtde_item_peca</td>";
				}
			$campo .=  "</tr>";

			echo "<tr class='Conteudo2' bgcolor='$cor' align='left'>";
				echo "<td  align='center' nowrap>$sua_os</td>";
				echo "<td  align='center' nowrap>$serie</td>";
				echo "<td  align='center' nowrap>$data_abertura </td>";
				echo "<td  align='center' nowrap>$data_fechamento </td>";
				echo "<td  nowrap>$codigo_posto - $posto_nome </td>";
				echo "<td  nowrap>$consumidor_nome </td>";
				echo "<td  align='center' nowrap>$consumidor_fone </td>";
				echo "<td  nowrap>$produto_referencia - $produto_descricao </td>";
				echo "<td >$defeito_reclamado_descricao </td>";
				echo "<td nowrap>$defeito_constatado</td>";
				echo "<td nowrap>$solucao_os</td>";
				echo "<td nowrap>$nota_fiscal </td>";
				echo "<td nowrap>$data_nf</td>";
				echo "<td nowrap>$revenda_nome</td>";
				echo "<td nowrap>$revenda_cnpj</td>";
				if(strlen($detalhado_peca)>0){
				echo "<td nowrap>";
				if(strlen($peca_referencia)>0 AND strlen($peca_descricao)>0){
					echo"$peca_referencia - $peca_descricao";
				}else{
					echo"&nbsp;";
				}
				echo"</td>";
				echo "<td nowrap>$qtde_item_peca</td>";
				}
			echo "</tr>";

		}
		echo "</TABLE>";
		$campo .=  "</TABLE>";
			
			//GERAR XLS --------------------------------------------------------
			flush();
			$data = date ("dmY");
			echo `rm /tmp/assist/relatorio_os-$login_fabrica.xls`;
			$fp = fopen ("/tmp/assist/relatorio_os-$login_fabrica.html","w");
			fputs ($fp,$campo);
			fclose ($fp);
			flush();

			echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /www/assist/www/admin/xls/relatorio_os-$login_fabrica.$data.xls /tmp/assist/relatorio_os-$login_fabrica.html`;

			echo "<script language='javascript'>";
			echo "document.getElementById('id_download').style.display='block';";
			echo "</script>";
			//---------------------------------------------------------------------

	}else{
		echo "<h2>Nenhum resultado encontrado</h2>";
	}
}
?>

<p>

<? include "rodape.php" ?>

<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="info_tecnica";
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "tecnica";
$titulo = "Ajuda Suporte Técnico";
$title = "Ajuda Suporte Técnico";

include 'cabecalho.php';

$comunicado = $_GET['comunicado'];
if(strlen($comunicado)>0){
	$sql = "UPDATE tbl_comunicado set ativo='f' 
			WHERE comunicado = $comunicado 
			AND   tipo       = 'Ajuda Suporte Tecnico' 
			AND   fabrica    = $login_fabrica";
	$res = pg_exec($con,$sql);


}

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
?>
<? include "javascript_pesquisas.php"; ?>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>

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
	font-size: 12px;
	font-weight: normal;
}
.textarea {border: 1px solid #3b4274;}
</style>

<form name="frm_comunicado" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="btn_acao">

<table width="500" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="menu_top">
		<td colspan="4">Consulta de Solicitação de Suporte Técnico</td>
	</tr>
	<tr class="table_line">
		<td colspan="4">&nbsp;</td>
	</tr>
<tr class="table_line" bgcolor="#D9E2EF" align='left'>
		<td width='50'>&nbsp;</td>
		<td>Mês</td>
		<td>Ano</td>
		<td width='10'>&nbsp;</td>
</tr>
<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td width='10'>&nbsp;</td>
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
		<td>
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
<td width='10'>&nbsp;</td>
</tr>

	<tr class="table_line" bgcolor="#D9E2EF" align='left'>
		<td width='10'>&nbsp;</td>
		<td>Referência</td>
		<td>Produto</td>
		<td width='10'>&nbsp;</td>
	</tr>

	<tr class="table_line" bgcolor="#D9E2EF" align='left'>
		<td width='10'>&nbsp;</td>
		<td>
		<input class="frm" type="text" name="produto_referencia" size="15" maxlength="20" value="<? echo $produto_referencia ?>" > 
		&nbsp;
		<img src='imagens/btn_lupa.gif' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.produto_referencia, document.frm_comunicado.produto_descricao,'referencia')">
		</td>

		<td>
		<input class="frm" type="text" name="produto_descricao" size="20" value="<? echo $produto_descricao ?>" >
		&nbsp;
		<img src='imagens/btn_lupa.gif'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.produto_referencia, document.frm_comunicado.produto_descricao,'descricao')">
</td>
<td width='10'>&nbsp;</td>
	<tr class="table_line" bgcolor="#D9E2EF" align='left'>
<td width='10'>&nbsp;</td>
		<td>Posto</td>
		<td>Nome do Posto</td>
<td width='10'>&nbsp;</td>
	</tr>
	<tr class="table_line" bgcolor="#D9E2EF" align='left'>
<td width='10'>&nbsp;</td>
		<td>
			<input type="text" name="codigo_posto" size="15" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_comunicado.codigo_posto, document.frm_comunicado.posto_nome, 'codigo');" <? } ?> value="<? echo $codigo_posto ?>" class="frm">
			<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_comunicado.codigo_posto, document.frm_comunicado.posto_nome, 'codigo')">
		</td>
		<td>
			<input type="text" name="posto_nome" size="20" <? if ($login_fabrica == 5) { ?> onblur="javascript: fnc_pesquisa_posto (document.frm_comunicado.codigo_posto, document.frm_comunicado.posto_nome, 'nome');" <? } ?> value="<?echo $posto_nome?>" class="frm">
			<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_comunicado.codigo_posto, document.frm_comunicado.posto_nome, 'nome')">
		</td>
<td width='10'>&nbsp;</td>
	</tr>
</tr>
	<tr class="table_line">
		<td colspan="4" align='center'><input type="submit" name="btn_acao" value="Pesquisar"></td>
	</tr>


</table>
</form>

<?
$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){

	$mes = $_POST['mes'];
	$ano = $_POST['ano'];
	
	$cond_1 = " 1=1 ";
	$cond_2 = " 1=1 ";
	$cond_3 = " 1=1 ";


	if (strlen($mes) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));

		$cond_1 = " tbl_comunicado.data  between '$data_inicial' and '$data_final' ";
	}

	$produto_referencia = $_POST['produto_refernecia'];
	$produto_descricao  = $_POST['produto_descricao'];

	if(strlen($produto_referencia)>0){
		$sql = "SELECT produto 
				from tbl_produto 
				join tbl_familia using(familia) 
				where tbl_produto.referencia='$produto_referencia' 
				and tbl_familia.fabrica = $login_fabrica
				and tbl_produto.ativo = 't'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,produto);
			$cond_2 = " tbl_comunicado.produto = $produto  ";
		}
	}
	
	$posto_codigo       = $_POST['codigo_posto'];
	$posto_nome         = $_POST['posto_nome'];

	if(strlen($posto_codigo)>0){
		$sql = "SELECT posto 
				FROM tbl_posto_fabrica 
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica
				and  tbl_posto_fabrica.codigo_posto = '$posto_codigo'";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$posto = pg_result($res,0,posto);
			$cond_3 = " tbl_comunicado.posto = $posto ";
		}
	}

	$sql = "SELECT 	tbl_comunicado.comunicado                         ,
					tbl_comunicado.mensagem                           ,
					to_char(tbl_comunicado.data,'DD/MM/YYYY') as data ,
					tbl_posto_fabrica.codigo_posto                    ,
					tbl_posto.nome                                    ,
					tbl_produto.referencia                            ,
					tbl_produto.descricao                             ,
					tbl_comunicado.ativo
			FROM tbl_comunicado
			JOIN tbl_posto         on tbl_posto.posto         = tbl_comunicado.posto
			JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto 
			AND  tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_produto       on tbl_produto.produto     = tbl_comunicado.produto
			WHERE tbl_comunicado.fabrica = $login_fabrica 
			AND   tbl_comunicado.tipo = 'Ajuda Suporte Tecnico'
			AND   tbl_comunicado.posto notnull 
			AND   $cond_1
			AND   $cond_2
			AND   $cond_3
			order by tbl_comunicado.data desc";
	$res = pg_exec($con,$sql);
	//echo $sql;
	if(pg_numrows($res)>0){
		echo "<BR><BR>";
		echo "<table width='650' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse; font-size:11px;' bgcolor='#596D9B' align='center'>";
		echo "<tr>";
		echo "<td><font color='#ffffff'><B>Data</B></font></td>";
		echo "<td><font color='#ffffff'><B>Posto</B></font></td>";
		echo "<td><font color='#ffffff'><B>Produto</B></font></td>";
		echo "<td><font color='#ffffff'><B>Situação</B></font></td>";
		echo "<td><font color='#ffffff'><B>Mudar p/</B></font></td>";
		echo "</tr>";
		for($x=0;pg_numrows($res)>$x;$x++){
			$comunicado = pg_result($res,$x,comunicado);
			$mensagem   = pg_result($res,$x,mensagem  );
			$data       = pg_result($res,$x,data      );
			$codigo_posto = pg_result($res,$x,codigo_posto);
			$nome         = pg_result($res,$x,nome        );
			$referencia   = pg_result($res,$x,referencia  );
			$descricao    = pg_result($res,$x,descricao   );
			$ativo        = pg_result($res,$x,ativo       );
			
			if($ativo=='f') {$ativo = "Resolvido";}
			if($ativo=='t') {$ativo = "Aberto";}
			if($cor == "#efeeea")$cor = "#d2d7e1";
			else $cor = "#efeeea";
			
			echo "<tr bgcolor='$cor'>";
			echo "<td>$data</td>";
			echo "<td align='left'>$codigo_posto - $nome</td>";
			echo "<td align='left'>$referencia - $descricao </td>";
			echo "<td rowspan='2'>$ativo</td>";
			echo "<td rowspan='2'>"; if($ativo=='Aberto'){ echo "<a href='$PHP_SELF?comunicado=$comunicado'>Resolvido</a>"; }
			echo "</td>";
			echo "<td rowspan='2'><a href='comunicado_produto.php' target='blank'>Inserir<BR>Comunicado</a></td>";
			echo "</tr>";
			
			echo "<tr bgcolor='$cor'>";
			echo "<td colspan='3'  align='left'>". nl2br($mensagem) ."</td>";
			echo "</tr>";
		}
		echo "</table>";
	}



}


include 'rodape.php'
?>
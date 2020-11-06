<? // lorenzetti 19
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";

// SELECIONA AS FAMILIAS
$sql = "SELECT familia FROM tbl_posto_linha WHERE posto = $login_posto";
$res = pg_exec ($con,$sql);
$familia_posto = '';
for ($i=0; $i<pg_numrows($res); $i++){
	if(strlen(pg_result ($res,$i,0))){
		$familia_posto .= pg_result ($res,$i,0);
		$familia_posto .= ", ";
	}
}
$familia_posto .= "0";

$aa="aa";


if (strlen($_POST["btn_acao"]) > 0) $btn_acao = strtolower($_POST["btn_acao"]);

if (strlen($_POST["linha"]) > 0) $linha = $_POST["linha"];

if($_POST['chk_opt1']) $chk1 = $_POST['chk_opt1'];
if($_POST['chk_opt2']) $chk2 = $_POST['chk_opt2'];
if($_POST['chk_opt3']) $chk3 = $_POST['chk_opt3'];
if($_POST['chk_opt4']) $chk4 = $_POST['chk_opt4'];

if($_GET['chk_opt1'])  $chk1 = $_GET['chk_opt1'];
if($_GET['chk_opt2'])  $chk2 = $_GET['chk_opt2'];
if($_GET['chk_opt3'])  $chk3 = $_GET['chk_opt3'];
if($_GET['chk_opt4'])  $chk4 = $_GET['chk_opt4'];

if($_POST["data_inicial_01"])		$data_inicial_01    = trim($_POST["data_inicial_01"]);
if($_POST["data_final_01"])		$data_final_01      = trim($_POST["data_final_01"]);
if($_POST["produto_referencia"])	$produto_referencia = trim($_POST["produto_referencia"]);
if($_POST["produto_nome"])		$produto_nome       = trim($_POST["produto_nome"]);
if($_POST["linha"])			$linha              = trim($_POST["linha"]);
if($_POST["tipo"])			$tipo               = trim($_POST["tipo"]);

if($_GET["data_inicial_01"])		$data_inicial_01    = trim($_GET["data_inicial_01"]);
if($_GET["data_final_01"])		$data_final_01      = trim($_GET["data_final_01"]);
if($_GET["produto_referencia"])		$produto_referencia = trim($_GET["produto_referencia"]);
if($_GET["produto_nome"])		$produto_nome       = trim($_GET["produto_nome"]);
if($_GET["linha"])			$linha              = trim($_GET["linha"]);
if($_GET["tipo"])			$tipo               = trim($_GET["tipo"]);

$title = traduz("comunicados",$con,$cook_idioma)." $login_fabrica_nome";
$layout_menu = "tecnica";

include 'cabecalho.php';
include "javascript_pesquisas.php";

?>
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<style type="text/css">

.chapeu {
	color: #0099FF;
	padding: 2px;
	margin-bottom: 4px;
	margin-top: 10px;
	background-image: url(http://img.terra.com.br/i/terramagazine/tracejado3.gif);
	background-repeat: repeat-x;
	background-position: bottom;
	font-size: 13px;
	font-weight: bold;
}
.menu {
	font-size: 11px;
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

.tipo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	background-color: #D9E2EF
}

.descricao {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	background-color: #FFFFFF
}

.mensagem {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #FFFFFF
}

.txt10Normal {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}

.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	background-color: #D9E2EF;
}
</style>

<br>

<!-- MONTA ÁREA PARA EXPOSICAO DE COMUNICADO SELECIONADO -->
<?
	$sql2 = "SELECT tbl_posto_fabrica.codigo_posto           ,
					tbl_posto_fabrica.tipo_posto             ,
					tbl_posto_fabrica.pedido_em_garantia     ,
					tbl_posto_fabrica.pedido_faturado        ,
					tbl_posto_fabrica.digita_os              ,
					tbl_posto_fabrica.reembolso_peca_estoque
			FROM	tbl_posto
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			WHERE   tbl_posto_fabrica.fabrica = $login_fabrica
			AND     tbl_posto.posto   = $login_posto ";


	$res2 = pg_exec ($con,$sql2);

	if (pg_numrows ($res2) > 0) {
		$codigo_posto           = trim(pg_result($res2,0,codigo_posto));
		$tipo_posto             = trim(pg_result($res2,0,tipo_posto));
		$pedido_em_garantia     = pg_result($res2,0,pedido_em_garantia);
		$pedido_faturado        = pg_result($res2,0,pedido_faturado);
		$digita_os              = pg_result($res2,0,digita_os);
		$reembolso_peca_estoque = pg_result($res2,0,reembolso_peca_estoque);
	}

?>
<?
if($login_fabrica == 3) { // HD 56703
?>
<form name="frm_comunicado" method="get" action="comunicado_mostra_pesquisa.php">

<input type="hidden" name="acao">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo">
		<td colspan="5" align="center"><? echo strtoupper(traduz("selecione.os.parametros.para.a.pesquisa",$con,$cook_idioma));?></td>
	</tr>
	<tbody class="Conteudo" >
	<tr>
		<td nowrap width="30" align='right'>&nbsp;</td>
		<td align="left" nowrap>
			<? fecho ("data.inicial",$con,$cook_idioma);?><br>
			<input size="13" maxlength="10" type="text" name="data_inicial" id="data_inicial" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>" >
		</td>
		<td align="left">
			<? fecho ("data.final",$con,$cook_idioma);?><br>
			<input type="text" name="data_final" id="data_final" size="13" maxlength="10" class='Caixa' value="<? if (strlen($data_final) > 0) echo $data_final;  ?>" >
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr>
		<td width="10">&nbsp;</td>
		<td align="left" colspan="2">
			<?fecho("tipo",$con,$cook_idioma);?><br>
			<select class='frm' name='tipo'>
				<option value=""></option>
				<?
	$sql = "SELECT DISTINCT tipo
			FROM tbl_comunicado
			WHERE fabrica = $login_fabrica
			AND ativo='t' and tipo not in ('Ajuda Suporte Tecnico','Peças de Reposição','LGR')
			ORDER BY tipo;";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
		for($x=0;pg_numrows($res)>$x;$x++){
			$tipo_comunicado = pg_result($res,$x,tipo);
			echo "<option value='$tipo_comunicado'>";
			if($login_fabrica ==3 ){
				if($tipo_comunicado =='Comunicado') {
					echo "Comunicado Técnico";
				}elseif($tipo_comunicado =='Informativo'){
					echo "Comunicado Administrativo";
				}else {
					echo "$tipo_comunicado";
				}
			}else {
				echo "$tipo_comunicado";
			}
			echo "</option>";
		}
	}?>
			</select>
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr>
		<td width="10">&nbsp;</td>
		<td align="left" colspan="2">
			<? fecho("descricao.titulo",$con,$cook_idioma);?><br>
			<input type="text" name="descricao" size="40" class="frm">
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td align="center" nowrap>
			<? fecho("referencia",$con,$cook_idioma);?><br>
			<input type="text" name="produto_referencia" size="18" class="frm">
		<img src="imagens/btn_lupa.gif" border="0" align="absmiddle" onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.produto_referencia, document.frm_comunicado.produto_descricao, 'referencia', document.frm_comunicado.produto_voltagem)" alt="<? fecho("clique.aqui.para.pesquisar.pela.referencia.do.produto",$con,$cook_idioma);?>" style="cursor: hand;">
		</td>
		<td align="center" nowrap>
			<? fecho("descricao",$con,$cook_idioma);?><br>
			<input type="text" name="produto_descricao" size="35" class="frm">
			<input type="hidden" name="produto_voltagem">
			<img src="imagens/btn_lupa.gif" border="0" align="absmiddle" onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.produto_referencia, document.frm_comunicado.produto_descricao, 'descricao', document.frm_comunicado.produto_voltagem)" alt="<? fecho("clique.aqui.para.pesquisar.pela.descricao.do.produto",$con,$cook_idioma);?>" style="cursor: hand;">
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr>
		<td width="10">&nbsp;</td>
		<td ><input type="radio" name="administrativo" value="administrativo" class="frm" <? if ($opcao == "1") echo "checked"; ?>><? if($sistema_lingua) echo "Administractivos";else echo " Administrativos";?></td>
		<td >&nbsp;</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr align="center">
		<td colspan="6"><img src="<? if ($sistema_lingua == "ES") echo "admin_es/imagens_admin/btn_pesquisar_400.gif";else  echo "imagens/btn_pesquisar_400.gif"?>" onClick="document.frm_comunicado.acao.value='PESQUISAR'; document.frm_comunicado.submit();" style="cursor: hand;" alt="<?fecho("preencha.as.opcoes.e.clique.aqui.para.pesquisar",$con,$cook_idioma);?>"></td>
	</tr>
	</tbody>
</table>

</form>
<?
if($login_fabrica==3){
	echo "<br><center><font size=3 color='red'>A partir do dia(22/04/2008), para enviar sua dúvida terá o novo processo, entre na tela de Confirmação de Ordem de Serviço(consulta de OS) e terá opção de ENVIAR DÚVIDA AO SUPORTE TÉCNICO logo abaixo.</font></center><br>";
}
?>
<hr>
<br>
<?}?>


<!-- ------------------- Todos comunicados de um tipo -------------- -->


<?


$tipo       = $_GET ['tipo'];
$comunicado = $_GET ['comunicado'];

if (strlen ($comunicado) > 0) {
	$sql = "SELECT tipo FROM tbl_comunicado WHERE comunicado = $comunicado";
	$res = pg_exec ($con,$sql);
	$tipo = pg_result ($res,0,0);
}

if (strlen ($tipo) > 0 AND strlen ($comunicado) == 0) {

	if($login_fabrica==1){		//HD 10983
		$sql_cond1=" tbl_comunicado.pedido_em_garantia IS null ";
		$sql_cond2=" tbl_comunicado.pedido_faturado IS null ";
		$sql_cond3=" tbl_comunicado.digita_os IS null ";
		$sql_cond4=" tbl_comunicado.reembolso_peca_estoque IS null ";

		if ($pedido_em_garantia == "t")     $sql_cond1 ="  tbl_comunicado.pedido_em_garantia IS TRUE ";
		if ($pedido_faturado == "t")        $sql_cond2 ="  tbl_comunicado.pedido_faturado IS TRUE ";
		if ($digita_os == "t")              $sql_cond3 =" tbl_comunicado.digita_os IS TRUE ";
		if ($reembolso_peca_estoque == "t") $sql_cond4 ="   tbl_comunicado.reembolso_peca_estoque IS TRUE ";
		$sql_cond_total="AND ( $sql_cond1 or $sql_cond2 or $sql_cond3 or $sql_cond4) ";
	}
	$sql_cond_linha = "
						AND (tbl_comunicado.linha IN
								(
									SELECT tbl_linha.linha
									FROM tbl_posto_linha
									JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
									WHERE fabrica =$login_fabrica
										AND posto = $login_posto
								)
								OR tbl_comunicado.linha IS NULL
							)";
	$sql = "SELECT  tbl_comunicado.comunicado,
					tbl_comunicado.descricao ,
					tbl_comunicado.mensagem  ,
					tbl_comunicado.extensao  ,
					tbl_comunicado.video     ,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.produto ELSE tbl_produto.produto END AS produto,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.referencia ELSE tbl_produto.referencia END AS referencia,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.referencia ELSE tbl_produto.referencia END AS produto_referencia,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.descricao ELSE tbl_produto.descricao END AS descricao_produto,
					to_char (tbl_comunicado.data,'dd/mm/yyyy') AS data
			FROM    tbl_comunicado
			LEFT JOIN tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
			LEFT JOIN tbl_produto            ON tbl_produto.produto               = tbl_comunicado.produto
			LEFT JOIN tbl_produto prod       ON prod.produto                      = tbl_comunicado_produto.produto
			WHERE tbl_comunicado.fabrica   = $login_fabrica
			AND (tbl_comunicado.tipo_posto = $tipo_posto   OR  tbl_comunicado.tipo_posto IS NULL)
			AND ((tbl_comunicado.posto     = $login_posto) OR (tbl_comunicado.posto      IS NULL))
			AND  (tbl_comunicado.estado    = '$estado'     OR  tbl_comunicado.estado     IS NULL)
			AND    tbl_comunicado.ativo IS TRUE ";

	if($login_fabrica == 20) $sql .= " AND tbl_comunicado.pais = '$login_pais' ";
	if($login_fabrica == 3){ // HD 31530
		$sql.=" $sql_cond_linha ";
	}
	if($tipo == 'zero'){
		$tipo = "Sem Título";
		$sql .= "AND	tbl_comunicado.tipo IS NULL ";
	}else{
		$sql .= "AND	tbl_comunicado.tipo = '$tipo' ";
	}

	if ($login_fabrica == 14){
		$sql .= "AND (tbl_comunicado.familia in ($familia_posto) OR tbl_comunicado.familia IS NULL)";
	}

	//HD 10983
	if($login_fabrica==1){
		$sql.=" $sql_cond_total ";
	}
	$sql .= " ORDER BY tbl_comunicado.data DESC, tbl_produto.descricao limit 100" ;

	$res = pg_exec ($con,$sql);

	echo "<FORM NAME='frmcomunicado'>";
	$total = pg_numrows ($res);
	if($login_fabrica==19){
		echo "<table width='700' border='0' cellspacing='0' cellpadding='0' align='center'>";
		echo "<tr bgcolor = '#fafafa'>";
		echo "<td rowspan='3' width='20' valign='top'><img src='imagens/marca25.gif'></td>";
		echo "<td  class='chapeu' colspan='2' >$tipo</td>";
		echo "</tr>";
		echo "<tr bgcolor = '#fafafa'><td colspan='2' height='5'></td></tr>";
		echo "<tr bgcolor = '#fafafa'>";
		echo "<td valign='top' class='menu'>";
		echo "<dl>";
	}else{
		echo "<table width='700' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000' align='center'>";
		echo "<thead>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='5' background='admin/imagens_admin/azul.gif' >";
		fecho ("tipo.do.comunicado",$con,$cook_idioma);
		echo ": $tipo</td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td>";
		fecho ("data",$con,$cook_idioma);
		echo "</td>";
		echo "<td>";
		fecho ("Titulo/descricão",$con,$cook_idioma);
		echo "</td>";
		if ($opcao == "1"){
			echo "<td width='10'>".traduz("produto",$con,$cook_idioma)."</td>";
		}
		echo "<td>";
		fecho ("arquivo",$con,$cook_idioma);
		echo "</td>";
		echo "</tr>";
		echo "</thead>";
		echo "<tbody class='Conteudo'>";
	}
	for ($i=0; $i<$total; $i++) {
		$produto = pg_result ($res,$i,produto);
		$Xcomunicado        = trim(pg_result($res,$i,comunicado));
		$descricao          = trim(pg_result($res,$i,descricao));
		$extensao           = trim(pg_result($res,$i,extensao));
		$mensagem           = trim(pg_result($res,$i,mensagem));
		$video				= trim(pg_result($res,$i,video));
		$data               = trim(pg_result($res,$i,data));
		$produto_referencia = trim(@pg_result($res,$i,produto_referencia));
		$produto_descricao  = trim(@pg_result($res,$i,produto_descricao));
		$comunicado         = $Xcomunicado;

		if(trim($extensao) == 'm'){$extensao = 'bmp';}//modificar as extensoes de alguns arquivos esta como m onde deveria estar bmp.

		if($login_fabrica<>19){
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;

			echo "<tr bgcolor='$cor'>";
			echo "<td><B>$data</B></td>";
			echo "<td align='left'>";
			if (strlen($mensagem) > 0)
				echo "<div style='position: relative; float: right; width: 5; height: 5;'><acronym title='".traduz("mensagem",$con,$cook_idioma).": $mensagem'><img border='0' src='imagens/comentario.gif' align='top' style='cursor: hand;'></acronym></div>";
			echo "<B>$descricao</B></td>";
			if ($opcao == "1")
				echo "<td  align='left'><acronym title='".traduz("referencia",$con,$cook_idioma).": $produto_referencia | ".traduz("descricao",$con,$cook_idioma).": $produto_descricao'>$produto_descricao</acronym></td>";
			echo "<td>";
		}
		if (strlen($comunicado) > 0 and strlen($extensao) > 0 and $login_fabrica<>19)
			echo "<a href='comunicados/$comunicado.$extensao' target='_blank'>".traduz("arquivo",$con,$cook_idioma)."</a>";
		else{
			if($login_fabrica==19){
				echo "<br><dd>&nbsp;&nbsp;<b>-»</b> ";
			}else{
				echo "&nbsp;";
			}
			$gif = "comunicados/$comunicado.gif";
			$jpg = "comunicados/$comunicado.jpg";
			$pdf = "comunicados/$comunicado.pdf";
			$doc = "comunicados/$comunicado.doc";
			$rtf = "comunicados/$comunicado.rtf";
			$xls = "comunicados/$comunicado.xls";
			$zip = "comunicados/$comunicado.zip";
			$pps = "comunicados/$comunicado.pps";

	
			if (file_exists($zip) == true) echo "<a href='comunicados/$comunicado.zip' target='_blank'>";
			if (file_exists($pps) == true) echo "<a href='comunicados/$comunicado.pps' target='_blank'>";
			if (file_exists($gif) == true) echo "<a href=$PHP_SELF?comunicado=$comunicado>";
			//HD 15634
			if (file_exists($jpg) == true) echo "<a href=$PHP_SELF?comunicado=$comunicado>";
			if (file_exists($doc) == true) echo "<a href='comunicados/$comunicado.doc' target='_blank'>";
			if (file_exists($rtf) == true) echo "<a href='comunicados/$comunicado.rtf' target='_blank'>";
			if (file_exists($xls) == true) echo "<a href='comunicados/$comunicado.xls' target='_blank'>";
			if (file_exists($pdf) == true) echo "<a href='comunicados/$comunicado.pdf' target='_blank'>";

			if($login_fabrica==50 and $video<>""){
				echo "<A href=\"javascript:window.open('/assist/video.php?video=$video','_blank'," .
					 "'toolbar=no, status=no, scrollbars=no, resizable=yes, width=460, height=380');void(0);\">" .
					 "Abrir vídeo";
			}elseif($login_fabrica==19){
				if(strlen($referencia)>0) echo "$referencia - ";
				if(strlen($descricao)>0) {
					echo $descricao;
				}else{
					if(strlen($comunicado_descricao)==0){
						echo "Comunicado Sem título";
					}else{
						echo $comunicado_descricao;
					}
				}
			}else{
				fecho ("abrir.arquivo",$con,$cook_idioma);
			}
			echo "</a>";
		}
		if($login_fabrica==19){
			echo"</dd>";
		}else{
			echo "</td>";
			echo "</tr>";
			echo "<tr bgcolor='$cor'>";
			if ($mensagem =='') {
				echo "<td colspan='5' align='center' style='color:#B7B7B7;'><br>".traduz("mensagem.nao.cadastrada",$con,$cook_idioma)."<br>&nbsp;</td>";
			} else {
				echo "<td style='color:#383838;' colspan='5'><br>$mensagem<br>&nbsp;</td>";
			}
			echo "</tr>";
			$tipo_anterior = $tipo;
		}
	}

	if($login_fabrica==19){
		echo "<br>";
		echo "</td>";
		echo "<td rowspan='2'class='detalhes' width='1'></td>";
		echo "</tr>";
		echo "<tr bgcolor='#D9E2EF'>";
		echo "<td colspan='3'><img src='imagens/spacer.gif' height='3'></td>";
		echo "</tr>";
	}else{
		echo "</tbody>";
	}
	echo "</table>";

	echo "</form>\n";

	echo "<hr>";
}

if (strlen($comunicado) > 0) {
	if($login_fabrica==1){		//HD 10983
		$sql_cond1=" tbl_comunicado.pedido_em_garantia IS null ";
		$sql_cond2=" tbl_comunicado.pedido_faturado IS null ";
		$sql_cond3=" tbl_comunicado.digita_os IS null ";
		$sql_cond4=" tbl_comunicado.reembolso_peca_estoque IS null ";

		if ($pedido_em_garantia == "t")     $sql_cond1 ="  tbl_comunicado.pedido_em_garantia IS NOT FALSE ";
		if ($pedido_faturado == "t")        $sql_cond2 ="  tbl_comunicado.pedido_faturado IS NOT FALSE ";
		if ($digita_os == "t")              $sql_cond3 =" tbl_comunicado.digita_os IS TRUE ";
		if ($reembolso_peca_estoque == "t") $sql_cond4 ="   tbl_comunicado.reembolso_peca_estoque IS TRUE ";
		$sql_cond_total="AND ( $sql_cond1 or $sql_cond2 or $sql_cond3 or $sql_cond4) ";
	}

	$sql_cond_linha = "
						AND (tbl_comunicado.linha IN
								(
									SELECT tbl_linha.linha
									FROM tbl_posto_linha
									JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
									WHERE fabrica =$login_fabrica
										AND posto = $login_posto
								)
								OR tbl_comunicado.linha IS NULL
							)";

	$sql = "SELECT  tbl_comunicado.comunicado                        ,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.referencia ELSE tbl_produto.referencia END AS prod_referencia,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.descricao ELSE tbl_produto.descricao END AS prod_descricao,
					tbl_comunicado.descricao                         ,
					tbl_comunicado.mensagem                          ,
					tbl_comunicado.video     						 ,
					tbl_comunicado.tipo                              ,
					tbl_comunicado.extensao                          ,
					to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data
			FROM    tbl_comunicado
			LEFT JOIN tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
			LEFT JOIN tbl_produto            ON tbl_produto.produto               = tbl_comunicado.produto
			LEFT JOIN tbl_produto prod       ON prod.produto                      = tbl_comunicado_produto.produto
			WHERE   tbl_comunicado.fabrica    = $login_fabrica
			AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
			AND     ((tbl_comunicado.posto    = $login_posto) OR (tbl_comunicado.posto     IS NULL))
			AND     tbl_comunicado.comunicado = $comunicado
			AND    tbl_comunicado.ativo IS TRUE ";

	if($login_fabrica == 20) $sql .= " AND tbl_comunicado.pais = '$login_pais' ";
	//HD 10983
	if($login_fabrica==1){
		$sql .=" $sql_cond_total ";
	}
	if($login_fabrica == 3){ // HD 31530
		$sql.=" $sql_cond_linha ";
	}
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) == 0) {
		$msg_erro = traduz("comunicado.inexistente",$con,$cook_idioma);
	}else{
		$Xcomunicado          = trim(pg_result($res,0,comunicado));
		$referencia           = trim(pg_result($res,0,prod_referencia));
		$descricao            = trim(pg_result($res,0,prod_descricao));
		$comunicado_descricao = trim(pg_result($res,0,descricao));
		$comunicado_tipo      = trim(pg_result($res,0,tipo));
		$comunicado_mensagem  = trim(pg_result($res,0,mensagem));
		$video				  = trim(pg_result($res,0,video));
		$comunicado_data      = trim(pg_result($res,0,data));
		$comunicado_extensao  = trim(pg_result($res,0,extensao));

		$gif = "comunicados/$Xcomunicado.gif";
		$jpg = "comunicados/$Xcomunicado.jpg";
		$pdf = "comunicados/$Xcomunicado.pdf";
		$doc = "comunicados/$Xcomunicado.doc";
		$rtf = "comunicados/$Xcomunicado.rtf";
		$xls = "comunicados/$Xcomunicado.xls";
		$ppt = "comunicados/$Xcomunicado.ppt";
	}
}

if ((strlen($comunicado) > 0) && (pg_numrows($res) > 0)) {

	echo "<table  align='center' class='table' width='400'>";
	echo "<tr>";
	if($sistema_lingua <> 'ES') echo "	<td align='left'><img src='imagens/cab_comunicado.gif'></td>";
	else echo "	<td align='left'><img src='imagens/cab_comunicado_es.gif'></td>";
	echo "</tr>";
	echo "<tr>";
	echo	"<td align='center' class='tipo'><b>$comunicado_tipo</b>&nbsp;&nbsp;-&nbsp;&nbsp;$comunicado_data</td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td align='center' class='descricao'><b>$descricao</b></td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td align='center' class='mensagem'>".nl2br($comunicado_mensagem)."</td>";
	echo "</tr>";
	echo "<tr>";
	echo "	<td align='center'>&nbsp;</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td align='left' >";
	if (file_exists($gif) == true) echo "	<img src='comunicados/$Xcomunicado.gif'>";
	if (file_exists($jpg) == true) echo "<img src='comunicados/$Xcomunicado.jpg'>";
	if (file_exists($doc) == true) echo traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$Xcomunicado.doc' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.";
	if (file_exists($rtf) == true) echo traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$Xcomunicado.rtf' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.";
	if (file_exists($xls) == true) echo traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$Xcomunicado.xls' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.";
	if (file_exists($ppt) == true) echo traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$Xcomunicado.ppt' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.";
	if (file_exists($pdf) == true) {
		echo "<div class='txt10Normal'><font color='#A02828'>">traduz("se.voce.nao.possui.o.acrobat.reader",$con,$cook_idioma)."&reg;</font> , <a href='http://www.adobe.com/products/acrobat/readstep2.html'>".traduz("instale.agora",$con,$cook_idioma)."</a>.</div>";
		echo "<br>";
		echo traduz("para.visualizar.o.arquivo",$con,$cook_idioma).", <a href='comunicados/$Xcomunicado.pdf' target='_blank'>".traduz("clique.aqui",$con,$cook_idioma)."</a>.";
	}
	if ($login_fabrica==50 and $video<>''){	?>
		<P><A href="javascript:window.open('/assist/video.php?video=<?=$video?>','_blank',
			'toolbar=no, status=no, scrollbars=no, resizable=yes, width=460, height=380');void(0);">
			Assistir vídeo anexado</A></P><?
	}
	/*
	if(strlen($comunicado_extensao)>0){
		if($comunicado_extensao=='ppt')
		echo "Para visualizar o arquivo, <a href='comunicados/$Xcomunicado.ppt' target='_blank'>clique aqui</a>.";
	}
	*/
	echo "</td>";
	echo "</tr>";
	echo "</table>";

	echo "<br><br>";

	echo "<hr>";
}
?>

<!-- ------------------- Tipos de Comunicados Disponíveis -------------- -->

<?
if (strlen($comunicado) == 0){

	if($login_fabrica==1){		//HD 10983
		$sql_cond1=" tbl_comunicado.pedido_em_garantia IS null ";
		$sql_cond2=" tbl_comunicado.pedido_faturado IS null ";
		$sql_cond3=" tbl_comunicado.digita_os IS null ";
		$sql_cond4=" tbl_comunicado.reembolso_peca_estoque IS null ";

		if ($pedido_em_garantia == "t")     $sql_cond1 ="  tbl_comunicado.pedido_em_garantia IS NOT FALSE ";
		if ($pedido_faturado == "t")        $sql_cond2 ="  tbl_comunicado.pedido_faturado IS NOT FALSE ";
		if ($digita_os == "t")              $sql_cond3 =" tbl_comunicado.digita_os IS TRUE ";
		if ($reembolso_peca_estoque == "t") $sql_cond4 ="   tbl_comunicado.reembolso_peca_estoque IS TRUE ";
		$sql_cond_total="AND ( $sql_cond1 or $sql_cond2 or $sql_cond3 or $sql_cond4) ";
	}

	$sql_cond_linha = "
						AND (tbl_comunicado.linha IN
								(
									SELECT tbl_linha.linha
									FROM tbl_posto_linha
									JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
									WHERE fabrica =$login_fabrica
										AND posto = $login_posto
								)
								OR tbl_comunicado.linha IS NULL
							)";

	$sql = "SELECT	tbl_comunicado.tipo,
					count(tbl_comunicado.*) AS qtde
			FROM	tbl_comunicado
			LEFT JOIN tbl_produto USING (produto)
			LEFT JOIN tbl_linha   on tbl_produto.linha = tbl_linha.linha
			LEFT JOIN tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
			LEFT JOIN tbl_produto prod ON prod.produto = tbl_comunicado_produto.produto
			WHERE	tbl_comunicado.fabrica    = $login_fabrica
			AND    (tbl_comunicado.tipo_posto = $tipo_posto   OR  tbl_comunicado.tipo_posto      IS NULL)
			AND    ((tbl_comunicado.posto     = $login_posto) OR (tbl_comunicado.posto           IS NULL))
			AND    tbl_comunicado.ativo IS TRUE";
	if($login_fabrica == 20) $sql .= " AND tbl_comunicado.pais = '$login_pais' ";

	//HD 10983
	if($login_fabrica==1){
		$sql .=" $sql_cond_total ";
	}

	if($login_fabrica ==3){ // HD 31530
		$sql.=" $sql_cond_linha ";
	}

	$sql .=" GROUP BY tbl_comunicado.tipo ORDER BY tbl_comunicado.tipo";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<table width='400' align='center' border='0'>";
		echo "<tr bgcolor='#FF9900'>";
		echo "<td align='center' colspan='2'><font color='#ffffff' size='+1'><b>".fecho("tipos.de.comunicados.disponiveis",$con,$cook_idioma)."</b></font></td>";
		echo "</tr>";

		echo "<tr bgcolor='#FF9900'>";
		echo "<td align='center'><font color='#ffffff'><b>Tipo</b></font></td>";
		echo "<td align='center'><font color='#ffffff'><b>".traduz("qtde",$con,$cook_idioma)."</b></font></td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$cor = "#ffffff";
			if ($i % 2 == 0) $cor = '#ffeecc';

			echo "<tr bgcolor='$cor'>";

			if(pg_result ($res,$i,tipo) == ''){
				echo "<td nowrap>";
				echo "<a href='comunicado_mostra_pesquisa.php?acao=PESQUISAR&tipo=zero'> ".traduz("sem.titulo",$con,$cook_idioma)." </a>";
				echo "</td>";
				echo "<td align='right'>";
				echo pg_result ($res,$i,qtde);
				echo "</td>";
			}else{
				if(pg_result ($res,$i,tipo) =='Esquema Elétrico'){
					echo "<td nowrap>";
					echo "<a href='info_tecnica_arvore.php'>";
					echo pg_result ($res,$i,tipo);
					echo "</a>";
					echo "</td>";
					echo "<td align='right'>";
					echo pg_result ($res,$i,qtde);
					echo "</td>";
					echo "</tr>";
				}else{
					echo "<td nowrap>";
					echo "<a href='comunicado_mostra_pesquisa.php?acao=PESQUISAR&tipo=" . pg_result ($res,$i,tipo) . "'>";
					echo pg_result ($res,$i,tipo);
					echo "</a>";
					echo "</td>";
					echo "<td align='right'>";
					echo pg_result ($res,$i,qtde);
					echo "</td>";
					echo "</tr>";
				}
			}
			$total += "".pg_result ($res,$i,qtde);
		}
		if($login_fabrica ==14) {
			echo "<tr bgcolor='#ffeecc'>";
			echo "<td nowrap>";
			echo "<a href='comunicado_mostra_pesquisa.php?acao=PESQUISAR&tipo=todos'>";
			echo "Visualizar todos comunicados";
			echo "</a>";
			echo "</td>";
			echo "<td align='right'>$total";
			echo "</td>";
			echo "</tr>";
		}

		echo "</table>";
		echo "<hr>";
	}else{
		echo "<table width='400' align='center' border='0'>";
		echo "<tr bgcolor='#FF9900'>";
		echo "<td align='center' colspan='2'><font color='#ffffff' size='+1'><b>".traduz("nao.ha.comunicados.disponiveis",$con,$cook_idioma)."</b></font></td>";
		echo "</tr>";
		echo "</table>";
	}

	if($login_fabrica == 14){ // HD 44360
		include "rodape.php";
		exit;
	}
}

?>


<?
##### Consulta de comunicados #####
if($login_fabrica <> 3) { // HD 56703
?>
<form name="frm_comunicado" method="get" action="comunicado_mostra_pesquisa.php">

<input type="hidden" name="acao">

<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo">
		<td colspan="5" align="center"><? echo strtoupper(traduz("selecione.os.parametros.para.a.pesquisa",$con,$cook_idioma));?></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td align="center">
			<? fecho ("data.inicial",$con,$cook_idioma);?><br>
			<input size="13" maxlength="10" type="text" name="data_inicial" value="dd/mm/aaaa" onclick="javascript: if (this.value == 'dd/mm/aaaa') { this.value=''; }">&nbsp;<img src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript: showCal('DataInicialComunicado')" style="cursor: hand;" alt="<?fecho ("clique.aqui.para.abrir.o.calendario",$con,$cook_idioma);?>">
		</td>
		<td align="center">
			<? fecho ("data.final",$con,$cook_idioma);?><br>
			<input size="13" maxlength="10" type="text" name="data_final" value="dd/mm/aaaa" onclick="javascript: if (this.value == 'dd/mm/aaaa') { this.value=''; }">&nbsp;<img src="imagens/btn_lupa.gif" align="absmiddle" onclick="javascript: showCal('DataFinalComunicado')" style="cursor: hand;" alt="<?fecho ("clique.aqui.para.abrir.o.calendario",$con,$cook_idioma);?>">
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td align="center" colspan="2">
			<?fecho("tipo",$con,$cook_idioma);?><br>
			<select class='frm' name='tipo'>
				<option value=""></option>
				<?
	$sql = "SELECT DISTINCT tipo
			FROM tbl_comunicado
			WHERE fabrica = $login_fabrica
			AND ativo='t'
			ORDER BY tipo;";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
		for($x=0;pg_numrows($res)>$x;$x++){
			$tipo_comunicado = pg_result($res,$x,tipo);
			echo "<option value='$tipo_comunicado'>";
			if($login_fabrica ==3 ){
				if($tipo_comunicado =='Comunicado') {
					echo "Comunicado Técnico";
				}elseif($tipo_comunicado =='Informativo'){
					echo "Comunicado Administrativo";
				}else {
					echo "$tipo_comunicado";
				}
			}else {
				echo "$tipo_comunicado";
			}
			echo "</option>";
		}
	}?>
			</select>
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td align="center" colspan="2">
			<? fecho("descricao.titulo",$con,$cook_idioma);?><br>
			<input type="text" name="descricao" size="40" class="frm">
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>&nbsp;</td>
		<td align="center" nowrap>
			<? fecho("referencia",$con,$cook_idioma);?><br>
			<input type="text" name="produto_referencia" size="20" class="frm">
		<img src="imagens/btn_lupa.gif" border="0" align="absmiddle" onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.produto_referencia, document.frm_comunicado.produto_descricao, 'referencia', document.frm_comunicado.produto_voltagem)" alt="<? fecho("clique.aqui.para.pesquisar.pela.referencia.do.produto",$con,$cook_idioma);?>" style="cursor: hand;">
		</td>
		<td align="center" nowrap>
			<? fecho("descricao",$con,$cook_idioma);?><br>
			<input type="text" name="produto_descricao" size="40" class="frm">
			<input type="hidden" name="produto_voltagem">
			<img src="imagens/btn_lupa.gif" border="0" align="absmiddle" onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.produto_referencia, document.frm_comunicado.produto_descricao, 'descricao', document.frm_comunicado.produto_voltagem)" alt="<? fecho("clique.aqui.para.pesquisar.pela.descricao.do.produto",$con,$cook_idioma);?>" style="cursor: hand;">
		</td>
		<td>&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF" align="center">
		<td colspan="6"><img src="<? if ($sistema_lingua == "ES") echo "admin_es/imagens_admin/btn_pesquisar_400.gif";else  echo "imagens/btn_pesquisar_400.gif"?>" onClick="document.frm_comunicado.acao.value='PESQUISAR'; document.frm_comunicado.submit();" style="cursor: hand;" alt="<?fecho("preencha.as.opcoes.e.clique.aqui.para.pesquisar",$con,$cook_idioma);?>"></td>
	</tr>
</table>

</form>

<hr>
<br>
<?}?>

<!-- ------------------- 10 Comunicados mais recentes -------------- -->

<?
if (strlen($comunicado) == 0 and strlen($tipo) == 0){
	if($login_fabrica==1){		//HD 10983
		$sql_cond1=" tbl_comunicado.pedido_em_garantia IS null ";
		$sql_cond2=" tbl_comunicado.pedido_faturado IS null ";
		$sql_cond3=" tbl_comunicado.digita_os IS null ";
		$sql_cond4=" tbl_comunicado.reembolso_peca_estoque IS null ";

		if ($pedido_em_garantia == "t")     $sql_cond1 =" tbl_comunicado.pedido_em_garantia IS TRUE ";
		if ($pedido_faturado == "t")        $sql_cond2 =" tbl_comunicado.pedido_faturado IS TRUE ";
		if ($digita_os == "t")              $sql_cond3 =" tbl_comunicado.digita_os IS TRUE ";
		if ($reembolso_peca_estoque == "t") $sql_cond4 =" tbl_comunicado.reembolso_peca_estoque IS TRUE ";
		$sql_cond_total="AND ($sql_cond1 or $sql_cond2 or $sql_cond3 or $sql_cond4 ) ";
	}

	$sql_cond_linha = "
				AND (tbl_comunicado.linha IN
						(
							SELECT tbl_linha.linha
							FROM tbl_posto_linha
							JOIN tbl_linha ON tbl_linha.linha = tbl_posto_linha.linha
							WHERE fabrica =$login_fabrica
								AND posto = $login_posto
						)
						OR tbl_comunicado.linha IS NULL
					)";

	$sql = "SELECT	tbl_comunicado.comunicado,
					tbl_comunicado.descricao,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.referencia ELSE tbl_produto.referencia END AS referencia,
					CASE WHEN tbl_comunicado.produto IS NULL THEN prod.descricao ELSE tbl_produto.descricao END AS produto_descricao,
					to_char (tbl_comunicado.data,'dd/mm/yyyy') AS data ,
					tbl_comunicado.video,
					tbl_comunicado.tipo
			FROM	tbl_comunicado
			LEFT JOIN tbl_comunicado_produto ON tbl_comunicado_produto.comunicado = tbl_comunicado.comunicado
			LEFT JOIN tbl_produto            ON tbl_produto.produto               = tbl_comunicado.produto
			LEFT JOIN tbl_produto prod       ON prod.produto                      = tbl_comunicado_produto.produto
			WHERE	tbl_comunicado.fabrica = $login_fabrica
			AND    (tbl_comunicado.tipo_posto = $tipo_posto   OR  tbl_comunicado.tipo_posto IS NULL)
			AND     ((tbl_comunicado.posto    = $login_posto) OR (tbl_comunicado.posto      IS NULL))
			AND    tbl_comunicado.ativo IS TRUE ";

	if($login_fabrica==1){
		$sql .=" $sql_cond_total";
	}

	if($login_fabrica ==3){ // HD 31530
		$sql.=" $sql_cond_linha ";
	}

	$sql.=" ORDER BY tbl_comunicado.data DESC LIMIT 10" ;


	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='400' align='center' border='0'>";
		echo "<tr bgcolor='#669900'>";
		echo "<td align='center' colspan='4'><font color='#ffffff' size='+1'><b>";
		fecho("10.comunicados.mais.recentes",$con,$cook_idioma);
		echo "</b></font></td>";
		echo "</tr>";

		echo "<tr bgcolor='#669900'>";
		echo "<td align='center'><font color='#ffffff'><b>";
		fecho ("produto",$con,$cook_idioma);
		echo "</b></font></td>";
		echo "<td align='center'><font color='#ffffff'><b>";
		fecho("descricao",$con,$cook_idioma);
		echo "</b></font></td>";
		echo "<td align='center'><font color='#ffffff'><b>";
		fecho("data",$con,$cook_idioma);
		echo "</b></font></td>";
		echo "<td align='center'><font color='#ffffff'><b>";
		fecho("abrir",$con,$cook_idioma);
		echo "</b></font></td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$cor = "#ffffff";
			if ($i % 2 == 0) $cor = '#ccffcc';

			echo "<tr bgcolor='$cor'>";

			echo "<td nowrap>";
			echo "<font size='-1'>";
			echo pg_result ($res,$i,referencia) . " - " . pg_result ($res,$i,produto_descricao) ;
			echo "</font>";
			echo "</td>";

			echo "<td nowrap>";
			echo "<font size='-1'>";
			echo pg_result ($res,$i,descricao);
			echo "</font>";
			echo "</td>";

			echo "<td align='right' nowrap>";
			echo "<font size='-1'>";
			echo pg_result ($res,$i,data);
			echo "</font>";
			echo "</td>";

			echo "<td nowrap>";
			echo "<font size='-1'>";
				if($login_fabrica==50 and trim(pg_result($res,$i,video)<>'')){
					echo "<A href=\"javascript:window.open('/assist/video.php?video=".trim(pg_result($res,$i,video))."','_blank'," .
						 "'toolbar=no, status=no, scrollbars=no, resizable=yes, width=460, height=380');void(0);\">" .
						 "Abrir vídeo</A>";
				}else{
					echo "<a href='$PHP_SELF?comunicado=" . urlencode (pg_result ($res,$i,comunicado)) . "' target='_blank'>";
					fecho("abrir.arquivo",$con,$cook_idioma);
					echo "</a>";
					    }
			echo "</font>";
			echo "</td>";

			echo "</tr>";
		}

		echo "</table>";
	}
}
?>


<!-- MOSTRA RESULTADO DE BUSCA OU 5 PRIMEIRO REGISTROS -->
<?
if (1==2 and strlen($comunicado) == 0){
	if ($btn_acao == "pesquisar") {
		$sql = "SELECT  tbl_comunicado.comunicado                        ,
						tbl_produto.referencia AS prod_referencia        ,
						tbl_produto.descricao  AS prod_descricao         ,
						tbl_comunicado.descricao                         ,
						tbl_comunicado.mensagem                          ,
						tbl_comunicado.video     						 ,
						tbl_comunicado.tipo                              ,
						to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data
				FROM    tbl_comunicado
				LEFT JOIN    tbl_produto USING (produto)
				LEFT JOIN    tbl_linha   USING (linha)
				WHERE   tbl_comunicado.fabrica         = $login_fabrica
				AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
				AND     ((tbl_comunicado.posto           = $login_posto) OR (tbl_comunicado.posto           IS NULL))
				AND    tbl_comunicado.ativo IS TRUE
				AND ( 1=2 ";

		// por linha de produto
		if(strlen($chk1) > 0){
			if (strlen($linha) > 0) {
				$monta_sql .= "OR tbl_linha.linha = $linha ";
				$dt = 1;
			}
		}

		// por tipo de comunicado
		if(strlen($chk4) > 0){
			if (strlen($tipo) > 0) {
				$monta_sql .= "OR tbl_comunicado.tipo = '$tipo' ";
				$dt = 1;
			}
		}

		// entre datas
		if(strlen($chk2) > 0){
			if((strlen($data_inicial_01) == 10) && (strlen($data_final_01) == 10)){
				$monta_sql .= "OR (tbl_comunicado.data BETWEEN fnc_formata_data('$data_inicial_01') AND fnc_formata_data('$data_final_01')) ";
				$dt = 1;
			}
		}

		// referencia do produto
		if(strlen($chk3) > 0){
			if ($produto_referencia) {
				if ($dt == 1) $xsql = "AND ";
				else          $xsql = "OR ";

				$monta_sql .= "$xsql tbl_produto.referencia = '". $produto_referencia ."' ";
				$dt = 1;
			}
		}

		$monta_sql .= ") GROUP BY
					tbl_comunicado.comunicado,
					tbl_produto.referencia,
					tbl_produto.descricao,
					tbl_comunicado.descricao,
					tbl_comunicado.mensagem,
					tbl_comunicado.tipo,
					tbl_comunicado.data ";
				if($login_fabrica == 3)
					$monta_sql .= "ORDER BY tbl_produto.descricao ASC";
				else
					$monta_sql .= "ORDER BY tbl_comunicado.data DESC";

		// ordena sql padrao
		$sql .= $monta_sql;

		$sqlCount  = "SELECT count(*) FROM (";
		$sqlCount .= $sql;
		$sqlCount .= ") AS count";

		//echo "<br>".nl2br($sql)."<br><br>".nl2br($sqlCount)."<br><BR>";

		// ##### PAGINACAO ##### //
		require "_class_paginacao.php";

		// definicoes de variaveis
		$max_links = 11;				// máximo de links à serem exibidos
		$max_res   = 30;				// máximo de resultados à serem exibidos por tela ou pagina
		$mult_pag  = new Mult_Pag();	// cria um novo objeto navbar
		$mult_pag->num_pesq_pag = $max_res; // define o número de pesquisas (detalhada ou não) por página

		$res = $mult_pag->executar($sql, $sqlCount, $con, "otimizada", "pgsql");

		// ##### PAGINACAO ##### //

	}else{
		if($login_fabrica==1){		//HD 10983
			$sql_cond1=" tbl_comunicado.pedido_em_garantia IS null ";
			$sql_cond2=" tbl_comunicado.pedido_faturado IS null ";
			$sql_cond3=" tbl_comunicado.digita_os IS null ";
			$sql_cond4=" tbl_comunicado.reembolso_peca_estoque IS null ";

			if ($pedido_em_garantia == "t")     $sql_cond1 ="  tbl_comunicado.pedido_em_garantia IS TRUE ";
			if ($pedido_faturado == "t")        $sql_cond2 ="  tbl_comunicado.pedido_faturado IS TRUE ";
			if ($digita_os == "t")              $sql_cond3 =" tbl_comunicado.digita_os IS TRUE ";
			if ($reembolso_peca_estoque == "t") $sql_cond4 ="   tbl_comunicado.reembolso_peca_estoque IS TRUE ";
			$sql_cond_total="AND ( $sql_cond1 or $sql_cond2 or $sql_cond3 or $sql_cond4) ";
		}
		// seleciona os 5 ultimos
		$sql = "SELECT  tbl_comunicado.comunicado                        ,
						tbl_produto.referencia AS prod_referencia        ,
						tbl_produto.descricao  AS prod_descricao         ,
						tbl_comunicado.descricao                         ,
						tbl_comunicado.mensagem                          ,
						tbl_comunicado.video     						 ,
						tbl_comunicado.tipo                              ,
						to_char(tbl_comunicado.data,'dd/mm/yyyy') AS data
				FROM    tbl_comunicado
				LEFT JOIN tbl_produto USING (produto)
				LEFT JOIN tbl_linha   USING (linha)
				WHERE   tbl_comunicado.fabrica         = $login_fabrica
				AND    (tbl_comunicado.tipo_posto = $tipo_posto  OR  tbl_comunicado.tipo_posto IS NULL)
				AND     ((tbl_comunicado.posto           = $login_posto) OR (tbl_comunicado.posto           IS NULL))
				AND    tbl_comunicado.ativo IS TRUE ";
		//HD 10983
		if($login_fabrica==1){
			$sql .=" $sql_cond_total ";
		}
		$sql.=" ORDER BY tbl_comunicado.data DESC
				LIMIT 5 OFFSET 0 ";

		$sqlCount = "";
		$res = pg_exec($con,$sql);
	}

	if (pg_numrows($res) > 0) {
		echo "<table class='table' width='400' >";
		echo "<tr>";
		echo "<td align='left'><img src='imagens/cab_outrosregistrosreferentes.gif'></td>";
		echo "</tr>";
		echo "</table>";

		echo "<br>";

		echo "<table class='table' align='center' width='500' border=0>";
		for ($x = 0 ; $x < pg_numrows($res) ; $x++) {
			$comunicado           = trim(pg_result($res,$x,comunicado));
			$referencia           = trim(pg_result($res,$x,prod_referencia));
			$descricao            = trim(pg_result($res,$x,prod_descricao));
			$comunicado_descricao = trim(pg_result($res,$x,descricao));
			$comunicado_tipo      = trim(pg_result($res,$x,tipo));
			$comunicado_mensagem  = trim(pg_result($res,$x,mensagem));
			$video                = trim(pg_result($res,$x,video));
			$comunicado_data      = trim(pg_result($res,$x,data));

			echo "<tr>\n";
			echo "	<td class='txt10Normal'>$comunicado_data</td>\n";
			echo "	<td><a href='$PHP_SELF?comunicado=$comunicado'>$comunicado_tipo</a></td>\n";
			echo "	<td class='txt10Normal'>$descricao\n";
			if ($login_fabrica==50 and $video<>""){	?>
				<P><A href="javascript:window.open('/assist/video.php?video=<?$video?>','_blank',
					'toolbar=no, status=no, scrollbars=no, resizable=yes, width=460, height=380');void(0);">
					Assistir vídeo anexado</A></P><?
			}
			echo "\n\t</td>\n</tr>\n";
		}
		echo "</table>\n";
	}else{
		fecho("nao.ha.registro.para.esta.opcao",$con,$cook_idioma);
	}


	if (strlen($btn_acao) > 0) {

		// ##### PAGINACAO ##### //

		// links da paginacao
		echo "<br>";

		echo "<div>";

		if($pagina < $max_links) {
			$paginacao = pagina + 1;
		}else{
			$paginacao = pagina;
		}

		// paginacao com restricao de links da paginacao

		// pega todos os links e define que 'Próxima' e 'Anterior' serão exibidos como texto plano
		$todos_links		= $mult_pag->Construir_Links("strings", "sim");

		// função que limita a quantidade de links no rodape
		$links_limitados	= $mult_pag->Mostrar_Parte($todos_links, $coluna, $max_links);

		for ($n = 0; $n < count($links_limitados); $n++) {
			echo "<font color='#DDDDDD'>".$links_limitados[$n]."</font>&nbsp;&nbsp;";
		}

		echo "</div>";

		$resultado_inicial = ($pagina * $max_res) + 1;
		$resultado_final   = $max_res + ( $pagina * $max_res);
		$registros         = $mult_pag->Retorna_Resultado();

		$valor_pagina   = $pagina + 1;
		$numero_paginas = intval(($registros / $max_res) + 1);

		if ($valor_pagina == $numero_paginas) $resultado_final = $registros;

		if ($registros > 0){
			echo "<br>";
			echo "<div>";
			echo "Resultados de <b>$resultado_inicial</b> a <b>$resultado_final</b> do total de <b>$registros</b> registros.";
			echo "<font color='#cccccc' size='1'>";
			echo " (Página <b>$valor_pagina</b> de <b>$numero_paginas</b>)";
			echo "</font>";
			echo "</div>";
		}

		// ##### PAGINACAO ##### //
	}
}

include "rodape.php";

?>

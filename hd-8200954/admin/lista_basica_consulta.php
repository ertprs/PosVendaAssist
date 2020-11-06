<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';
include 'funcoes.php';

$erro = "";



$title = "Consulta de Lista Básica";
$layout_menu = "pedido";

include 'cabecalho.php';

?>


<script language="JavaScript">
function mostraPeca(peca){
	if (document.getElementById('dados_'+peca)){
		var style2 = document.getElementById('dados_'+peca); 
		if (style2==false) return; 
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
		}
	}
}
function fnc_pesquisa_produto_serie (campo,form) {
	if (campo.value != "") {
		var url = "";
		url = "produto_serie_pesquisa.php?campo=" + campo.value + "&form=" + form ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.focus();
	}
}


function fnc_pesquisa_produto (campo1, campo2, tipo, campo3) {
	if (tipo == "referencia") {
		var xcampo = campo1;
	}
	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}
	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo1;
		janela.descricao    = campo2;
		if (campo3 != "") {
			janela.voltagem = campo3;
		}
		janela.focus();
	}
}
</script>

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

<? if (strlen($erro) > 0) { ?>
<table width="500" align="center" border="0" cellspacing="0" cellpadding="2" class="error">
	<tr>
		<td><?echo $erro?></td>
	</tr>
</table>
<br>
<? } ?>
<BR><BR>
<form name="frm_comunicado" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="btn_acao">

<table width="500" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="menu_top">
		<td colspan="6"><? if($sistema_lingua) echo "CONSULTA DE LISTA BÁSICA";else echo "CONSULTA DE LISTA BÁSICA";?></td>
	</tr>
	<tr class="table_line">
		<td colspan="6">&nbsp;</td>
	</tr>
	<tr class="table_line">
		<td width="10">&nbsp;</td>
		<td>Número de Série</td>
		<td ><? if($sistema_lingua) echo "Referencia";else echo "Referência";?></td>
		<td ><? if($sistema_lingua) echo "Descripción";else echo "Descrição";?></td>
		<td ><? if($sistema_lingua) echo "Voltaje";else echo "Voltagem";?></td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="table_line">
		<td>&nbsp;</td>
		<td><input type="text" name="produto_serie" size="15" class="frm" value="<?echo $produto_serie?>">
		<img src='imagens_admin/btn_lupa.gif' border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto_serie (document.frm_comunicado.produto_serie,'frm_comunicado')"  style='cursor: pointer'>
		</td>
		<td><input type="text" name="produto_referencia" size="8" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_produto (document.frm_comunicado.produto_referencia, document.frm_comunicado.produto_descricao, 'referencia', document.frm_comunicado.produto_voltagem)" <? } ?> class="frm" value="<?echo $produto_referencia?>"> <img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: pointer;" align="absmiddle" alt="<? if($sistema_lingua=="ES") echo "Llene las opciones e click aquí para buscar"; else echo "Clique aqui para pesquisar postos pelo código";?>" onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.produto_referencia, document.frm_comunicado.produto_descricao, 'referencia', document.frm_comunicado.produto_voltagem)"></td>
		<td><input type="text" name="produto_descricao" size="18" <? if ($login_fabrica == 5) { ?> onblur="fnc_pesquisa_produto (document.frm_comunicado.produto_referencia, document.frm_comunicado.produto_descricao, 'descricao', document.frm_comunicado.produto_voltagem)" <? } ?> class="frm" value="<?echo $produto_descricao?>"> <img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: pointer;" align="absmiddle" alt="<? if($sistema_lingua=="ES") echo "Llene las opciones e click aquí para buscar";else echo "Clique aqui para pesquisas pela referência do aparelho.";?>" onclick="javascript: fnc_pesquisa_produto (document.frm_comunicado.produto_referencia, document.frm_comunicado.produto_descricao, 'descricao', document.frm_comunicado.produto_voltagem)"></td>
		<td><input type="text" name="produto_voltagem" size="7" class="frm" value="<?echo $produto_voltagem?>"></td>
		<td>&nbsp;</td>
	</tr>
	<tr class="table_line">
		<td colspan="6"><hr color="#EEEEEE"></td>
	</tr>
	<tr class="table_line">
		<td colspan="6"><center><img border="0" src="<?if($sistema_lingua=='ES') echo "imagens/btn_pesquisar_comunicado_es.gif"; else echo "imagens_admin/btn_pesquisar_400.gif";?>" onclick="document.frm_comunicado.btn_acao.value='CONSULTAR'; document.frm_comunicado.submit();" style="cursor: pointer;" alt="<? if($sistema_lingua=="ES") echo "Llene las opciones e click aquí para buscar";else "Preencha as opções e clique aqui para pesquisar";?>"></center></td>
	</tr>

</table>

</form>

<?

$btn_acao = $_POST['btn_acao'];
$produto_referencia    = trim($_POST['produto_referencia']);
if(strlen($btn_acao)>0 and strlen($produto_referencia)>0){
	$produto_serie          = $_POST['produto_serie'];
	$produto_descricao     = $_POST['produto_descricao'];

	$sql = "SELECT tbl_produto.referencia, tbl_produto.descricao, tbl_produto.produto, tbl_produto.linha
			FROM   tbl_produto
			JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha
			WHERE  tbl_produto.referencia = '$produto_referencia'
			AND tbl_linha.fabrica = $login_fabrica;";
	$res = @pg_exec($con,$sql);

	$linha             = @pg_result($res,0,linha);

	$referencia        = @pg_result($res,0,referencia);
	$descricao_produto = @pg_result($res,0,descricao);
	$produto           = @pg_result($res,0,produto);

	if($linha<>"302"){
		if(strlen($produto_serie)==0)$msg_erro = "Favor inserir o número de série";
		if((strlen($produto_serie)<>9 and ($produto<>"11019"))or (($produto=="11019") and strlen($produto_serie)<>18))$msg_erro = "Favor inserir o número de série";
	}

?>
<p>
<?
if(strlen($msg_erro)>0){
?>
<table width='700' border='0' align='center' cellspacing='1' cellpadding='0'>
	<tr  height='20' bgcolor='#C77E94'>
		<td align='center'><font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b><? echo $msg_erro; ?>
		</b></font></td>
	</tr>
</table>

<? } ?>
<p>
<table width='700' border='0' align='center' cellspacing='1' cellpadding='0'>
	<tr  height='20' bgcolor='#666666'>
		<td align='center'><font size='3' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b><? if($sistema_lingua == 'ES') echo "Herramienta: "; else echo "Produto: ";echo $referencia ." - ". $descricao_produto;?>
		<?if ($login_fabrica==6) echo "<BR>Série: $produto_serie ";?>
		</b></font></td>
	</tr>
</table>

<?
if (strlen ($produto) > 0 and strlen($msg_erro)==0) {
$cond_1 = " 1 = 1 ";
if(strlen($produto_serie)>0){
$cond_1 = "tbl_lista_basica.serie_inicial <= '$produto_serie'
		and tbl_lista_basica.serie_final >= '$produto_serie'";
}
		$sql =	"SELECT tbl_lista_basica.posicao       ,
						tbl_peca.peca                  ,
						tbl_peca.referencia            ,
						tbl_peca.descricao             ,
						tbl_lista_basica.serie_inicial ,
						tbl_lista_basica.serie_final   ,
						tbl_lista_basica.type          ,
						tbl_lista_peca.peca_pai        ,
						tbl_lista_peca.peca_filha
				FROM    tbl_lista_basica
				JOIN    tbl_peca USING (peca) 
				left JOIN tbl_lista_peca ON tbl_lista_basica.peca = tbl_lista_peca.peca_pai 
				WHERE   tbl_lista_basica.fabrica = $login_fabrica
				AND     tbl_lista_basica.produto = $produto
				and $cond_1";
		$sql .= "ORDER BY tbl_lista_peca.peca_pai, tbl_peca.referencia, rpad(trim (tbl_lista_basica.posicao),20,' '), tbl_peca.descricao, tbl_lista_basica.type";
	$res = pg_exec ($con,$sql);

	$xpeca_filha =  array();
		$xpeca_peca =  array();
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$peca_filha = pg_result ($res,$i,peca_filha);
		if(strlen($peca_filha)>0) $xpeca_filha[] = $peca_filha;
	}

//print_r($xpeca_filha); 
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		if ($i % 40 == 0) {
			if ($i > 0) echo "</table>";
			
			echo "<table width='700' align='center' border='0' cellspacing='1' cellpadding='0'>";
			echo "<tr  height='20' bgcolor='#666666'>";
			echo "<td align='center' width='100'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
			echo "Posição";
			echo "</b></font></td>";
			echo "<td align='center'  width='70'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Série Inicial</b></font></td>";
			echo "<td align='center' width='70'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>Série Final</b></font></td>";
			
			echo "<td align='center' width='100'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
			echo "Código Peça";
			echo "</b></font></td>";
			echo "<td align='center' width='350'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
			echo "Descrição";
			echo "</b></font></td>";
			echo "<td align='center'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#ffffff'><b>";
			echo "Ação";
			echo "</b></font></td>";
			echo "</tr>";
		}
			
		
		if ($i < pg_numrows ($res) AND strlen ($msg_erro) == 0) {
			$cor       = "#FFFFFF";
			
			

			$tem = array_search($referencia,$xpeca_peca);
			$peca = pg_result ($res,$i,peca);
			$posicao = pg_result ($res,$i,posicao);
			$referencia = pg_result ($res,$i,referencia);
			$descricao = pg_result ($res,$i,descricao);
			$serie_inicial = pg_result ($res,$i,serie_inicial);
			$serie_final = pg_result ($res,$i,serie_final);
			$type = pg_result ($res,$i,type);
			$peca_pai = pg_result ($res,$i,peca_pai);
			$peca_filha = pg_result ($res,$i,peca_filha);

			$xpeca_peca[]="$referencia";
//			next;

			if(($peca_ant == $peca and strlen($peca_pai)>0) or (in_array($peca,$xpeca_filha))) continue;

			if(strlen($peca_pai)>0){
				$b = "<B>"; $bb="</B>";
			}else{
				$b = ""; $bb="";
			}
			//--=== Tradução para outras linguas ============================= Raphael HD:1212

			if ((strlen($id) > 0) and (strlen($sistema_lingua) > 0)) {
				$sql_idioma = "SELECT * FROM tbl_peca_idioma WHERE peca = $id AND upper(idioma) = '$sistema_lingua'";

				$res_idioma = @pg_exec($con,$sql_idioma);
				if (@pg_numrows($res_idioma) >0) {
					$descricao  = trim(@pg_result($res_idioma,0,descricao));
				}
			}
			//--=== Tradução para outras linguas ===================================================================
			
		}



		echo "<tr ";
		if(strlen($b)>0){
			echo " bgcolor='#b8b7af'";
		}

		echo ">";
		echo "<td align='center' width='100'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$posicao</font></td>";
		echo "<td align='center' width='70'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$serie_inicial</font></td>";
		echo "<td align='center' width='70'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$serie_final</font></td>";
		echo "<td align='center' width='100'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$b $referencia $bb</font></td>";
		echo "<td align='left' width='350'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$descricao</font></td>";
		echo "<td align='center'>";
		if(strlen($b)>0){echo "<a href=\"javascript:mostraPeca($i);\"><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>abrir</font></a>";}
		echo "</td>";
		echo "</tr>";
		
		if(strlen($b)>0){
			echo "<tr>";
			echo "<td colspan='6'>";
			echo "<div id='dados_$i' style='position:relative; display:none; border: 1px solid #949494;background-color: #D5D4CD;width:100%;'>";
			echo "<table border='0' cellspacing='1' cellpadding='0' bgcolor='#FFFFFF'>";

			$xsql = "SELECT tbl_lista_basica.posicao , 
							tbl_lista_basica.serie_inicial , 
							tbl_lista_basica.serie_final , 
							tbl_peca.peca , 
							tbl_peca.referencia , 
							tbl_peca.descricao 
					FROM (
							SELECT tbl_lista_peca.peca_filha
							FROM tbl_lista_peca
							WHERE tbl_lista_peca.peca_pai = $peca
					) as X
					JOIN  tbl_lista_basica on tbl_lista_basica.peca = X.peca_filha
					JOIN  tbl_peca on tbl_peca.peca = tbl_lista_basica.peca
					WHERE tbl_lista_basica.produto = $produto
					AND   tbl_lista_basica.fabrica = $login_fabrica";
					if (strlen($serie)>0) {
						$xsql .= " AND tbl_lista_basica.serie_inicial < '$serie'
								  AND tbl_lista_basica.serie_final > '$serie'";
					}
			$xsql.="ORDER BY tbl_peca.referencia, tbl_peca.descricao";
			$xres = pg_exec($con,$xsql);

			if(pg_numrows($xres)>0){
				for($y=0;$y<pg_numrows($xres);$y++){
					$xposicao       = pg_result($xres,$y,posicao);
					$xserie_inicial = pg_result($xres,$y,serie_inicial);
					$xserie_final   = pg_result($xres,$y,serie_final);
					$xpeca          = pg_result($xres,$y,peca);
					$xreferencia    = pg_result($xres,$y,referencia);
					$xdescricao     = pg_result($xres,$y,descricao);
					
					echo "<tr bgcolor='#D5D4CD'>";
					echo "<td align='center' width='100'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$xposicao</font></td>";
					echo "<td align='center' width='70'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$xserie_inicial</font></td>";
					echo "<td align='center' width='70'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$xserie_final</font></td>";
					echo "<td align='center' width='100'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$b $xreferencia $bb</font></td>";
					echo "<td align='left' width='350'><font size='1' face='Geneva, Arial, Helvetica, san-serif' color='#000000'>$xdescricao</font></td>";
					echo "</tr>";
					
				}
			}
			echo "</table>";
			echo "</div>";
		echo "</td>";
		echo "</tr>";
		}
	$peca_ant = $peca;
	}
?>
</table>
<p>
<? }
}
include "rodape.php";
?>

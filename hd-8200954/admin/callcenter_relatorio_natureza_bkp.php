<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO DE NATUREZA DE CHAMADO";

include "cabecalho.php";

?>
<style>
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	color:#ffffff;
	background-color: #445AA8;
}

.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 9px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid; 
	BORDER-TOP: #6699CC 1px solid; 
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid; 
	BORDER-BOTTOM: #6699CC 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid; 
	BORDER-TOP: #990000 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid; 
	BORDER-BOTTOM: #990000 1px solid; 
	BACKGROUND-COLOR: #FF0000;
}
.Carregando{
	TEXT-ALIGN: center;
	BORDER-RIGHT: #aaa 1px solid; 
	BORDER-TOP: #aaa 1px solid; 
	FONT: 10pt Arial ;
	COLOR: #000000;
	BORDER-LEFT: #aaa 1px solid; 
	BORDER-BOTTOM: #aaa 1px solid; 
	BACKGROUND-COLOR: #FFFFFF;
	margin-left:20px;
	margin-right:20px;
}
</style>


<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->
<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<script language='javascript' src='../ajax.js'></script>
<script>
function AbreCallcenter(data_inicial,data_final,produto,natureza,status,tipo,defeito_reclamado){
janela = window.open("callcenter_relatorio_defeito_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status+"&tipo="+tipo+"&defeito_reclamado="+defeito_reclamado, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}


/* POP-UP IMPRIMIR */
	function abrir(URL) { 
		var width = 700; 
		var height = 600; 
		var left = 90; 
		var top = 90; 

		window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no'); 
	}

</script>

<? include "javascript_pesquisas.php" ?>


<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<table width='500' class='Conteudo' style='background-color: #485989' border='0' cellpadding='5' cellspacing='1' align='center'>
	<tr>
		<td class='Titulo' background='imagens_admin/azul.gif'>Relatório de Natureza do Chamado</td>
	</tr>
	<tr>
		<td bgcolor='#DBE5F5' valign='bottom'>
	
			<table width='100%' border='0' cellspacing='1' cellpadding='2' >

				<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
					<td align='right' nowrap><font size='2'>Data Inicial</td>
					<td align='left' nowrap>
						<input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
						<!--<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript:showCal('DataInicial')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">-->
					</td>
					<td align='right' nowrap><font size='2'>Data Final</td> 
					<td align='left'>
						<input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='Caixa' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
						<!--<img border="0" src="imagens/lupa.png" align="absmiddle" onclick="javascript:showCal('DataFinal')" style="cursor: hand;" alt="Clique aqui para abrir o calendário">-->
					</td>
					<td width="10">&nbsp;</td>
				</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<TD  style="width: 10px">&nbsp;</TD>
		<td  align='right' nowrap ><font size='2'>Ref. Produto</font></td>
		<td align='left' nowrap>
		<input type="text" name="produto_referencia" size="12" class='Caixa' maxlength="20" value="<? echo $produto_referencia ?>" > 
		<img src='imagens/lupa.png' border='0' align='absmiddle' style="cursor:pointer" onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'referencia')">
		</td>
		<td align='right' nowrap  ><font size='2'>Descrição</font></td>
		<td  align='left' nowrap>
		<input type="text" name="produto_descricao" size="12" class='Caixa' value="<? echo $produto_descricao ?>" >
		<img src='imagens/lupa.png'  style="cursor:pointer" border='0' align='absmiddle' onclick="javascript: fnc_pesquisa_produto (document.frm_relatorio.produto_referencia, document.frm_relatorio.produto_descricao,'descricao')">
		<TD style="width: 10px">&nbsp;</TD>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
					<td width="10">&nbsp;</td>
					<td align='right'><font size='2'>Natureza</td>
					<td align='left'>
					<select name='natureza_chamado' class='Caixa'>
					<option value=''></option>

					
					<?PHP 
					//HD39566
					$sqlx = "SELECT nome            ,
									descricao       
							FROM tbl_natureza
							WHERE fabrica=$login_fabrica
							AND ativo = 't'
							ORDER BY nome";

					$resx = pg_exec($con,$sqlx);
						if(pg_numrows($resx)>0){
							for($y=0;pg_numrows($resx)>$y;$y++){
								$nome     = trim(pg_result($resx,$y,nome));
								$descricao     = trim(pg_result($resx,$y,descricao));
								echo $nome;
								echo "<option value='$nome'";
									if($natureza_chamado == $nome) {
										echo "selected";
									}
								echo ">$descricao</option>";
							}
						
						}
					?>

					<!--<?			if($login_fabrica==6){ ?>
					<option value='Reclamação'       <? if($natureza_chamado == 'Reclamação')       echo ' selected';?>>Reclamação</option>
			<option value='Informação'       <? if($natureza_chamado == 'Informação')       echo ' selected';?>>Informação</option>
			<?if($login_fabrica <> 6){ //chamado 1237?>
				<option value='Insatisfação'     <? if($natureza_chamado == 'Insatisfação')     echo ' selected';?>>Insatisfação</option>
				<option value='Troca de produto' <? if($natureza_chamado == 'Troca de produto') echo ' selected';?>>Troca de produto</option>
			<?}?>
			<option value='Engano'           <? if($natureza_chamado == 'Engano')           echo ' selected';?>>Engano</option>
			<option value='Outras áreas'     <? if($natureza_chamado == 'Outras áreas')     echo ' selected';?>>Outras áreas</option>
			<option value='Email'            <? if($natureza_chamado == 'Email')            echo ' selected';?>>Email</option>
			<option value='Ocorrência'       <? if($natureza_chamado == 'Ocorrência')       echo ' selected';?>>Ocorrência</option>
			<option value='Fora de Linha'       <? if($natureza_chamado == 'Fora de Linha')       echo ' selected';?>>Fora de Linha</option>
<?			}else{ ?>
			<option value='Dúvida'<? if($natureza_chamado == 'Dúvida') echo ' selected';?>>Dúvida</option>
			<option value='Reclamação'<? if($natureza_chamado == 'Reclamação') echo ' selected';?>>Reclamação</option>
			<option value='Insatisfação'<? if($natureza_chamado == 'Insatisfação') echo ' selected';?>>Insatisfação</option>

<? } ?>-->

</select>
					</td>
					<td align='right'><font size='2'>Status</td> 
					<td align='left'>
					<select name="status" size="1" class='Caixa'>
					<option value=''></option>
					<?
						$sql = "select distinct status from tbl_hd_chamado where fabrica_responsavel = $login_fabrica";
						$res = pg_exec($con,$sql);
						if(pg_numrows($res)>0){
							for($x=0;pg_numrows($res)>$x;$x++){
								$status = pg_result($res,$x,status);
								echo "<option value='$status'>$status</option>";
							
							}
						
						}
					?>
					</select>

					</td>
					<td width="10">&nbsp;</td>
				</tr>
			</table><br>
			<input type='submit' style="cursor:pointer" name='btn_acao' value='Consultar'>
		</td>
	</tr>
</table>
</FORM>


<?
$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$data_inicial = $_POST['data_inicial'];
	$data_final   = $_POST['data_final'];
	$produto_referencia = $_POST['produto_referencia'];
	$produto_descricao  = $_POST['produto_descricao'];
	$natureza_chamado   = $_POST['natureza_chamado'];
	$status             = $_POST['status'];
	
	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";
	$cond_4 = " 1 = 1 ";
	if(strlen($data_inicial)>0 and $data_inicial <> "dd/mm/aaaa"){
		$xdata_inicial =  fnc_formata_data_pg(trim($data_inicial));
		$xdata_inicial = str_replace("'","",$xdata_inicial);
	}else{
		$msg_erro = "Por favor informar a data";
	}
	
	if(strlen($data_final)>0 and $data_final <> "dd/mm/aaaa"){
		$xdata_final =  fnc_formata_data_pg(trim($data_final));
		$xdata_final = str_replace("'","",$xdata_final);
	}else{
		$msg_erro = "Por favor informar a data";
	}

	if(strlen($produto_referencia)>0){
		$sql = "SELECT produto from tbl_produto where referencia='$produto_referencia' limit 1";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto = pg_result($res,0,0);
			$cond_1 = " tbl_hd_chamado_extra.produto = $produto ";
		}
	}
	if(strlen($natureza_chamado)>0){
		$cond_2 = " tbl_hd_chamado.categoria = '$natureza_chamado' ";
	}
	if(strlen($status)>0){
		$cond_3 = " tbl_hd_chamado.status = '$status'  ";
	}
	if($login_fabrica==6){
		$cond_4 = " tbl_hd_chamado.status <> 'Cancelado'  ";
	}

	if($login_fabrica==2){
		$condicoes = $produto . ";" . $natureza_chamado . ";" . $status . ";" . $posto . ";" . $xdata_inicial . ";" .$xdata_final;
	}

	if(strlen($msg_erro)==0){
		$sql = "SELECT tbl_hd_chamado.categoria,
						count(tbl_hd_chamado.hd_chamado) as qtde
				from tbl_hd_chamado
				join tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				where tbl_hd_chamado.fabrica_responsavel = $login_fabrica
				and tbl_hd_chamado.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
				and $cond_1
				and $cond_2
				and $cond_3
				and $cond_4
				GROUP by tbl_hd_chamado.categoria
				order by qtde desc
			";	

		//echo nl2br($sql);
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<table width='500' border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px'>";
			echo "<TR >\n";
			echo "<td class='menu_top' background='imagens_admin/azul.gif'>Status</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Qtde</TD>\n";
			echo "</TR >\n";
			for($y=0;pg_numrows($res)>$y;$y++){
				$categoria = pg_result($res,$y,categoria);
				$qtde              = pg_result($res,$y,qtde);
		
				$grafico_status[] = $categoria;
				$grafico_qtde[] = $qtde;
				$total = $total + $qtde;

				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
				echo "<TR bgcolor='$cor'>\n";
				echo "<TD align='left' nowrap><a href=\"javascript: AbreCallcenter('$xdata_inicial','$xdata_final','$produto','$categoria','$status','$xperiodo','$defeito_reclamado')\">";
				if($categoria == 'troca_produto') echo "Troca do Produto"; elseif($categoria == 'reclamacao_produto') echo "Reclamação de Produto";
				elseif($categoria == 'duvida_produto') echo "Dúvida sobre produto";
				elseif($categoria == 'reclamacao_empresa') echo "Reclamação de empresa";
				elseif($categoria == 'reclamacao_at') echo "Reclamação de atendimento";
				else echo "$categoria";
				
				
				echo "</a></TD>\n";
				echo "<TD align='center' nowrap>$qtde</TD>\n";
				echo "</TR >\n";
			}
			if($login_fabrica==2){//HD 36906 9/10/2008
			echo "<TR >\n";
				echo "<TD align='center' nowrap><B>Total</B></TD>\n";
				echo "<TD align='center' nowrap>$total</TD>\n";
			echo "</TR >\n";
			}
			echo "</table>";
		
		echo "<BR><BR>";
		include ("../jpgraph/jpgraph.php");
		include ("../jpgraph/jpgraph_pie.php");
		include ("../jpgraph/jpgraph_pie3d.php");
		$img = time();
		$image_graph = "png/4_call$img.png";
		
		// seleciona os dados das médias
		setlocale (LC_ALL, 'et_EE.ISO-8859-1');
	
	
		$graph = new PieGraph(550,350,"auto");
		$graph->SetShadow();

		$graph->title->Set("Relatório de Reclamação $data_inicial - $data_final");
//		$graph->title->Set("");
		$p1 = new PiePlot3D($grafico_qtde);
		$p1->SetAngle(35);
		$p1->SetSize(0.4);
		$p1->SetCenter(0.4,0.7); // x.y
		//$p1->SetLegends($gDateLocale->GetShortMonth());
		$p1->SetLegends($grafico_status);
		//$p1->SetSliceColors(array('blue','red'));
		$graph->Add($p1);
		$graph->Stroke($image_graph);
		echo "\n\n<img src='$image_graph'>\n\n";
		//	echo "<BR><a href='callcenter_relatorio_atendimento_xls.php?data_inicial=$xdata_inicial&data_final=$xdata_final&produto=$produto&natureza_chamado=$natureza_chamado&status=$status&imagem=$image_graph' target='blank'>Gerar Excel</a>";

		if($login_fabrica==2){//hd 36906 9/10/2008
			$title = "RELATORIO DE NATUREZA DE CHAMADO";
			echo "<BR><BR>";
			echo "<A HREF=\"javascript:abrir('impressao_callcenter.php?condicoes=$condicoes;$title')\">";
			echo "<IMG SRC=\"imagens/btn_imprimir_azul.gif\" BORDER='0' ALT=''>";
			echo "</A>";
		}

		}else{
			echo "<center>Nenhum Resultado Encontrado</center>";
		}
	}
}
if(strlen($msg_erro)>0){
echo "<center>$msg_erro</center>";

}

?>

<p>

<? include "rodape.php" ?>

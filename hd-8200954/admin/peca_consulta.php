<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

$btn_acao=$_POST['btn_acao'];
if($_POST['btn_acao']){
	$origem = $_POST['origem'];
	$tipo = $_POST['tipo'];

	if(strlen($origem) == 0 AND strlen($tipo) == 0)
		$msg_erro = "Informe os Parâmetros para Pesquisa";

}
$layout_menu = "cadastro";
$title = "RELAÇÃO DE PEÇAS";

include 'cabecalho.php';

?>
<style type="text/css">

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
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

.subtitulo{

	background-color: #7092BE;
	font:bold 11px Arial;
	color: #FFFFFF;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
}

</style>
<!--[if lt IE 8]>
<style>
table.tabela{
	empty-cells:show;
    border-collapse:collapse;
	border-spacing: 0px;
}
</style>
<![endif]-->
<table width='700' align='center' border='0' class='formulario' cellpadding='0' cellspacing='1'>
<form name='frm_consulta' method='post' action='<? echo $PHP_SELF; ?>'>
<?php
	if(strlen($msg_erro) > 0){ ?>
		<tr class="msg_erro"><td colspan="4"><?php echo $msg_erro; ?> </td></tr>
<?php
	}
?>
<tr class="titulo_tabela"><td colspan="5">Parâmetros de Pesquisa</td></tr>
<tr height='20'>
	<td width="100">&nbsp;</td>
	<td>Origem</td>
	<td >Tipo</td>
	<? if($login_fabrica==11) { ?>
	<td>Status</td>
	<?}?>
	<td >&nbsp;</td>
</tr>
<tr>

	<td width="100">&nbsp;</td>

	<?php 	if($login_fabrica <> 11){	$tam = 130;	} ?>

	<td width='<?= $tam; ?>'>
		<select name='origem' class="frm">
			<option selected></option>
			<option value='NAC' <? if ($origem == 'NAC') echo "selected";?>> Fabricação </option>
			<option value='IMP' <? if ($origem == 'IMP') echo "selected";?>> Importado </option>
			<option value='TER' <? if ($origem == 'TER') echo "selected";?>> Terceiros </option>

			<?php
			if($login_fabrica == 1){
?>
				<option value='FAB/SUB' <? if ($origem == 'FAB/SUB') echo " selected " ?> > <?=traduz('Fabricação/Subsidiado')?> </option>
				<option value='IMP/SUB' <? if ($origem == 'IMP/SUB') echo " selected " ?> > <?=traduz('Importado/Subsidiado')?> </option>
				<option value='TER/SUB' <? if ($origem == 'TER/SUB') echo " selected " ?> > <?=traduz('Terceiros/Subsidiado')?> </option>
				<option value='FAB/SA' <? if ($origem == 'FAB/SA') echo " selected " ?> > <?=traduz('Fabricação/Semi acabado')?> </option>
				<option value='IMP/SA' <? if ($origem == 'IMP/SA') echo " selected " ?> > <?=traduz('Importado/Semi acabado')?> </option>
			<?php
			}
			?>
		</select>
	</td>

	<?php 	if($login_fabrica <> 11){	$tam2 = 250;	} ?>

	<td width='<?= $tam2; ?>'>
		<select name='tipo' class="frm">
			<option selected></option>
			<option value='1' <? if ($tipo == 1) echo "selected";?>> Devolução obrigatória </option>
			<?php 	if ($login_fabrica == 6) {	?>
					<option value='devolucao_estoque_fabrica' <? if ($tipo == 6) echo "selected";?>> Devolução Estoque Fábrica</option>
			<?php 	}	?>
			<option value='2' <? if ($tipo == 2) echo "selected";?>> Item de aparência </option>

			<!-- HD:1899424 - FUJITSU -->
			<?php 	if($login_fabrica != 138) { ?>
					<option value='3' <? if ($tipo == 3) echo "selected";?>> Peça acumulada para kit </option>
			<?php 	} ?>

			<?php 	if($login_fabrica==3 or $login_fabrica==11) { ?>
					<option value='4' <? if ($tipo == 4) echo "selected";?>> Peça Sob Intervenção </option>
			<?php 	} else {	?>
					<option value='4' <? if ($tipo == 4) echo "selected";?>> Peça retorno para conserto </option>
			<?php 	} ?>
			<option value='5' <? if ($tipo == 5) echo "selected";?>> Bloqueada para garantia </option>
			<option value='6'  <? if ($tipo == 6)  echo "selected";?>>      <?php echo ($login_fabrica == 153 ) ? "Componentes" : "Acessórios"  ?>                      </option>
			<option value='8'  <? if ($tipo == 8)  echo "selected";?>>      Peça Crítica            </option>
			<option value='9'  <? if ($tipo == 9)  echo "selected";?>>      Produto Acabado         </option>
			<?php 	if($login_fabrica==94) { ?>
					<option value='7'  <? if ($tipo == 7)  echo "selected";?>> 	Aguardando Inspeção </option>
			<? } ?>
			<?php 	if($login_fabrica==3 or $login_fabrica==11) { ?>
					<option value='7'  <? if ($tipo == 7)  echo "selected";?>> 	Aguardando Inspeção </option>
					<option value='10' <? if ($tipo == 10) echo "selected";?>> 	Mero Desgaste 		</option>

					<?php 	if($login_fabrica==3 or $login_fabrica==51) { ?>
						<option value='11' <? if ($tipo == 11) echo "selected";?>> Troca Obrigatória </option>
					<?php 	} 	?>

					<?php 	if($login_fabrica==11) { ?>
							<option value='13' <? if ($tipo == 13) echo "selected";?>> Reembolso 				</option>
							<option value='14' <? if ($tipo == 14) echo "selected";?>> Peça crítica única na OS </option>
					<?php 	} 	?>

					<option value='12' <? if ($tipo == 12) echo "selected";?>> Pre-Selecionada </option>
					
					<?php 	if($login_fabrica==3) { 	?>
							<option value='15' <? if ($tipo == 15) echo "selected";?>> Peça sob Intervenção Carteira</option>
					<?php 	} 	?>
			<?php 	} 	?>

			<?php 	if($login_fabrica==51) { ?>
					<option value='8' <? if ($tipo == 8) echo "selected";?>> 	Peça Crítica 		</option>
					<option value='11' <? if ($tipo == 11) echo "selected";?>> 	Troca Obrigatória 	</option>
			<?php 	} 	?>
			
			<?php 	if($login_fabrica==153 or $login_fabrica == 35) { ?>
					<option value='20' <? if ($tipo == 20) echo "selected";?>> 	 Bloqueada para venda  </option>
			<?php 	} 	?>
			<?php if(in_array($login_fabrica, array(35))){ ?>
				<option value="21" <?php if($tipo == 21){ echo "SELECTED"; } ?> >Peça Crítica Venda</option>
			<?php } ?>
			<!-- //HD- 67817 paulo cesar  -->
			<?php 	if ($login_fabrica==45){ ?>
					<option value='16' <? if ($tipo == 16) echo "selected";?>> Gera Troca Produto</option>
			<?php 	} 	?>
			<?php 	if ($login_fabrica == 80) {
						$arrOptions = array("17" => "Peças sem lista básica",
											"18" => "Peças com lista básica",
											"19" => "Peças Inativas"
											);

						foreach ($arrOptions as $key => $value) {
							echo '<option value="' , $key , '" ';
							if ($tipo == $key) {
								echo ' selected="selected" ';
							}
							echo '>' , $value , '</option>';
						}
					}
			?>
		</select>
	</td>

	<?php 	if($login_fabrica==11) { ?>

	<td>
		<select name='status' class="frm">
			<option selected></option>
			<option value='ativo' 	<?php 	if ($status == 'ativo')   echo "selected";?>> Ativo 	</option>
			<option value='inativo' <?php 	if ($status == 'inativo') echo "selected";?>> Inativo 	</option>
		</select>
	</td>

	<?php 	}	?>

	<td>
		<input type='hidden' name='btn_acao' value=''>
		<input type="button" value="Pesquisar" border=0 onclick="javascript: document.frm_consulta.btn_acao.value='Consultar'; document.frm_consulta.submit();" >
	</td>

</tr>
<tr>
	<td colspan="5">&nbsp;</td>
</tr>
</form>
</table>
<br>
<?
//echo $login_fabrica;
$mens = '';

if (strlen($tipo) > 0 OR strlen($origem) > 0 or strlen($status) >0 or ($login_fabrica==11 and strlen($btn_acao) > 0)){
	$sql = "SELECT  *
			FROM	tbl_peca
			WHERE	fabrica               = $login_fabrica ";

	switch ($origem){
		case 'NAC':		$sql .= "AND (origem = 'NAC' OR origem = '1' OR origem = 'FAB') ";	$mens .= " FABRICAÇÃO - ";	break;
		case 'IMP':		$sql .= "AND (origem = 'IMP' OR origem = '2') ";					$mens .= " IMPORTADA - ";	break;
		case 'TER':		$sql .= "AND origem = 'TER' ";										$mens .= " TERCEIRO - ";	break;
		case 'FAB/SUB': $sql .= "AND origem = 'FAB/SUB' "; $mens .= " FABRICAÇÃO/SUBSIDIADO - "; break;
		case 'IMP/SUB': $sql .= "AND origem = 'IMP/SUB' "; $mens .= " IMPORTADO/SUBSIDIADO - "; break;
		case 'TER/SUB': $sql .= "AND origem = 'TER/SUB' "; $mens .= " TERCEIRO/SUBSIDIADO - "; break;
		case 'FAB/SA': $sql .= "AND origem = 'FAB/SA' "; $mens .= " FABRICAÇÃO/SEMI ACABADO - "; break;
		case 'IMP/SA': $sql .= "AND origem = 'IMP/SA' "; $mens .= " IMPORTADO/SUBSIDIADO - "; break;
		default:	break;
	}

	switch ($tipo){
		case 1:		$sql .= "AND devolucao_obrigatoria IS true ";	$mens .= " DEVOLUÇÃO OBRIGATÓRIA "; break;
		case 2:		$sql .= "AND item_aparencia IS true ";			$mens .= " ITEM DE APARÊNCIA ";	break;
		case 3:		$sql .= "AND acumular_kit IS true ";			$mens .= " PEÇA ACUMULADA PARA KIT "; break;
		case 4:		$sql .= "AND retorna_conserto IS true ";		if ($login_fabrica==3 or $login_fabrica==11 ) { $mens .= "PEÇA SOB INTERVENÇÃO "; } else { $mens .= " PEÇA RETORNO PARA CONSERTO "; }	break;
		case 5:		$sql .= "AND bloqueada_garantia IS true ";		$mens .= " BLOQUEADA PARA GARANTIA "; break;
		case 6:		$sql .= "AND acessorio IS true ";				$mens .= " ACESSÓRIO "; break;
		case 7:		$sql .= "AND aguarda_inspecao IS true ";		$mens .= " AGUARDANDO INSPEÇÃO "; break;
		case 8:		$sql .= "AND peca_critica IS true ";			$mens .= " PEÇA CRÍTICA "; break;
		case 9:		$sql .= "AND produto_acabado IS true ";			$mens .= " PRODUTO ACABADO "; break;
		case 10:	$sql .= "AND mero_desgaste IS true ";			$mens .= " MERO DESGASTE "; break;
		case 11:	$sql .= "AND troca_obrigatoria IS true ";		$mens .= " TROCA OBRIGATÓRIA "; break;
		case 12:	$sql .= "AND pre_selecionada IS true ";			$mens .= " PRE-SELECIONADA "; break;
		case 13:	$sql .= "AND reembolso IS true ";				$mens .= " REEMBOLSO "; break;
		case 14:	$sql .= "AND peca_unica_os IS true ";			$mens .= " PEÇA CRÍTICA ÚNICA NA OS "; break;
		case 15:	$sql .= " AND intervencao_carteira IS true ";	$mens .= " PEÇA SOB INTERVENÇÃO CARTEIRA "; break;
		//HD- 67817 paulo cesar
		case 16:	if ($login_fabrica==45){$sql .= "AND gera_troca_produto IS true ";$mens .= " GERA TROCA PRODUTO ";} break;
		case 17:	$sql .= " AND peca NOT IN (SELECT DISTINCT peca FROM tbl_lista_basica WHERE fabrica = $login_fabrica) "; $mens.= 'PEÇAS SEM LISTA BÁSICA '; break;
		case 18:	$sql.= " AND peca IN (SELECT DISTINCT peca FROM tbl_lista_basica WHERE fabrica = $login_fabrica) "; $mens.= ' PEÇAS COM LISTA BÁSICA '; break;
		case 19:	$sql.= " AND ativo IS FALSE "; $mens.= ' PEÇAS INATIVAS '; break;
		case 20: 	$sql .= " AND bloqueada_venda is true "; $mens .= " BLOQUEADA PARA VENDA "; break;
		case 21: 	$sql .= " AND peca_critica_venda IS true ";	$mens .= " PEÇA CRÍTICA VENDA ";		break;
		case 'devolucao_estoque_fabrica': $sql .= " AND parametros_adicionais LIKE '%\"devolucao_estoque_fabrica\":\"t\"%' ";
		default:	break;
	}

	switch ($status){
		case 'ativo':		$sql .= "AND tbl_peca.ativo IS TRUE ";			break;
		case 'inativo':		$sql .= "AND tbl_peca.ativo IS FALSE ";		break;
	}


	$sql .= " ORDER BY ativo DESC, descricao ASC, referencia  ASC ";

	// * funcao time * //
	//$time_start = getmicrotime();
	// * funcao time * //

	$res = pg_exec ($con,$sql);

	// * funcao time * //
	//$time_end = getmicrotime();
	//TempoExec($PHP_SELF, $sql, $time_start, $time_end);
	// * funcao time * //

	if (pg_numrows($res) == 0){
		echo "<table width='700' align='center' border='0' class='conteudo' cellpadding='0' cellspacing='0'>";
		echo "<tr>";
		echo "<td align='center' colspan='5'><H3><b>Nenhum resultado encontrado</b></H3></td>";
		echo "</tr>";
	}elseif (pg_numrows($res) > 0){
		echo "<table width='700' align='center' border='0' class='tabela' cellpadding='0' cellspacing='1'>";
		echo "<tr class='menu_top' height='20'>";
		echo "<td class='titulo_tabela' colspan='8'><b>$mens</b></td>";
		echo "</tr>";

		echo "<tr class='titulo_coluna' height='20'>";
		echo "<td align='center'><b>Referência</b></td>";
		echo "<td align='center'><b>Descrição</b></td>";
		echo "<td align='center'><b>Origem</b></td>";
		echo "<td align='center'><b>Unid</b></td>";

		//hd-3625122 - fputti
		if ($login_fabrica == 171) {
			echo "<td align='center'><b>Referência Fábrica</b></td>";
		}

		if($login_fabrica <> 11) {
			echo "<td align='center'><b>Peso</b></td>";
		}else{
			echo "<td align='center'><b>Tipo</b></td>";
			echo "<td align='center'><b>Status</b></td>";
		}

		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$ativo                  = pg_result($res,$i,ativo);
			$devolucao_obrigatoria  = pg_result($res,$i,devolucao_obrigatoria);
			$item_aparencia         = pg_result($res,$i,item_aparencia);
			$retorna_conserto       = pg_result($res,$i,retorna_conserto);
			$acumular_kit           = pg_result($res,$i,acumular_kit);
			$bloqueada_garantia     = pg_result($res,$i,bloqueada_garantia);
			$acessorio              = pg_result($res,$i,acessorio);
			$aguarda_inspecao       = pg_result($res,$i,aguarda_inspecao);
			$peca_critica           = pg_result($res,$i,peca_critica);
			$produto_acabado        = pg_result($res,$i,produto_acabado);
			$mero_desgaste          = pg_result($res,$i,mero_desgaste);
			$pre_selecionada        = pg_result($res,$i,pre_selecionada);
			$reembolso              = pg_result($res,$i,reembolso);
			$peca_unica_os          = pg_result($res,$i,peca_unica_os);
			$gera_troca_produto     = pg_result($res,$i,gera_troca_produto);
			$tipo='';
			$tipo2='';

			if($devolucao_obrigatoria =='t') $tipo ="DEVOLUÇÃO OBRIGATÓRIA ";
			if($item_aparencia        =='t') $tipo ="ITEM DE APARÊNCIA ";
			if($acumular_kit          =='t') $tipo ="PEÇA ACUMULADA PARA KIT ";
			if($retorna_conserto      =='t') $tipo ="PEÇA SOB INTERVENÇÃO ";
			if($bloqueada_garantia    =='t') $tipo ="BLOQUEADA PARA GARANTIA ";
			if($acessorio             =='t') $tipo ="ACESSÓRIO ";
			if($aguarda_inspecao      =='t') $tipo ="AGUARDANDO INSPEÇÃO ";
			if($peca_critica          =='t') $tipo ="PEÇA CRÍTICA ";
			if($produto_acabado       =='t') $tipo ="PRODUTO ACABADO ";
			if($mero_desgaste         =='t') $tipo ="MERO DESGASTE ";
			if($pre_selecionada       =='t') $tipo ="PRE-SELECIONADA ";
			if($reembolso             =='t') $tipo ="REEMBOLSO ";
			if($peca_unica_os         =='t') $tipo ="PEÇA CRÍTICA ÚNICA NA OS ";
			//HD- 67817 paulo cesar
			if($gera_troca_produto    =='t') $tipo ="GERA TROCA PRODUTO";


			if($devolucao_obrigatoria =='t') $tipo2 .="DEVOLUÇÃO OBRIGATÓRIA\n";
			if($item_aparencia        =='t') $tipo2 .="ITEM DE APARÊNCIA\n";
			if($acumular_kit          =='t') $tipo2 .="PEÇA ACUMULADA PARA KIT\n";
			if($retorna_conserto      =='t') $tipo2 .="PEÇA SOB INTERVENÇÃO\n";
			if($bloqueada_garantia    =='t') $tipo2 .="BLOQUEADA PARA GARANTIA\n";
			if($acessorio             =='t') $tipo2 .="ACESSÓRIO\n";
			if($aguarda_inspecao      =='t') $tipo2 .="AGUARDANDO INSPEÇÃO\n";
			if($peca_critica          =='t') $tipo2 .="PEÇA CRÍTICA\n";
			if($produto_acabado       =='t') $tipo2 .="PRODUTO ACABADO\n";
			if($mero_desgaste         =='t') $tipo2 .="MERO DESGASTE\n";
			if($pre_selecionada       =='t') $tipo2 .="PRE-SELECIONADA\n";
			if($reembolso             =='t') $tipo2 .="REEMBOLSO\n";
			if($peca_unica_os         =='t') $tipo2 .="PEÇA CRÍTICA ÚNICA NA OS\n";
			//HD- 67817 paulo cesar
			if($gera_troca_produto    =='t') $tipo2 .="GERA TROCA PRODUTO\n";

			if($ativo=='t')  $peca_status='Ativo';
			else             $peca_status='Inativo';

			$descricao = pg_fetch_result($res, $i, 'descricao');
			$encoding = mb_detect_encoding($descricao);

			if (strtoupper($encoding) == "UTF-8") {
				$result_descricao = iconv("UTF-8", "ISO-8859-1", $descricao);
				if ($result_descricao) {
					$descricao = $result_descricao;
				}
			}

			$bg = ($i%2 == 0) ? '#F7F5F0' : '#F1F4FA';
			echo "<tr class='table_line' height='18' bgcolor='$bg'>";
			echo "<td align='left' >".pg_result ($res,$i,referencia)."</td>";
			echo "<td align='left' >". $descricao ."</td>";
			echo "<td align='center'>".pg_result ($res,$i,origem)."</td>";
			echo "<td align='center'>".pg_result ($res,$i,unidade)."</td>";

			//hd-3625122 - fputti
			if($login_fabrica == 171) {
				echo "<td align='center'>".pg_result($res,$i,referencia_fabrica)."</td>";
			}

			if($login_fabrica <> 11) {
				echo "<td align='right' style='padding-right:5px'>".pg_result ($res,$i,peso)."</td>";
			}else{
				echo "<td align='center' valign='center' style='font-size: 9px'><acronym title='$tipo2' style='cursor: help;'>$tipo</td>";
				echo "<td align='center'>$peca_status</td>";
			}
			echo "</tr>";
		}

		echo "</table>";


		if($login_fabrica==11) {
			flush();
			$data = date ("d/m/Y H:i:s");

			$arquivo_nome     = "relatorio-pecas-$login_fabrica.xls";
			$path             = "/www/assist/www/admin/xls/";
			$path_tmp         = "/tmp/";

			$arquivo_completo     = $path.$arquivo_nome;
			$arquivo_completo_tmp = $path_tmp.$arquivo_nome;

			echo `rm $arquivo_completo_tmp `;
			echo `rm $arquivo_completo `;

			$fp = fopen ($arquivo_completo_tmp,"w");

			fputs ($fp,"<html>");
			fputs ($fp,"<head>");
			fputs ($fp,"<title>Relatório de peças - $data");
			fputs ($fp,"</title>");
			fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
			fputs ($fp,"</head>");
			fputs ($fp,"<body>");
			fputs ($fp, "<table width='700' align='center' border='0' class='conteudo' cellpadding='0' cellspacing='1'>");
			fputs ($fp, "<tr class='menu_top' height='20'>");
			fputs ($fp, "<td align='center' colspan='100%'><b>$mens</b></td>");
			fputs ($fp, "</tr>");

			fputs ($fp, "<tr class='menu_top' height='20'>");
			fputs ($fp, "<td align='center'><b>Referência</b></td>");
			fputs ($fp, "<td align='center'><b>Descrição</b></td>");
			fputs ($fp, "<td align='center'><b>Origem</b></td>");
			fputs ($fp, "<td align='center'><b>Unid</b></td>");
			fputs ($fp, "<td align='center'><b>Tipo</b></td>");
			fputs ($fp, "<td align='center'><b>Status</b></td>");
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$ativo                  = pg_result($res,$i,ativo);
				$devolucao_obrigatoria  = pg_result($res,$i,devolucao_obrigatoria);
				$item_aparencia         = pg_result($res,$i,item_aparencia);
				$retorna_conserto       = pg_result($res,$i,retorna_conserto);
				$acumular_kit           = pg_result($res,$i,acumular_kit);
				$bloqueada_garantia     = pg_result($res,$i,bloqueada_garantia);
				$acessorio              = pg_result($res,$i,acessorio);
				$aguarda_inspecao       = pg_result($res,$i,aguarda_inspecao);
				$peca_critica           = pg_result($res,$i,peca_critica);
				$produto_acabado        = pg_result($res,$i,produto_acabado);
				$mero_desgaste          = pg_result($res,$i,mero_desgaste);
				$pre_selecionada        = pg_result($res,$i,pre_selecionada);
				$reembolso              = pg_result($res,$i,reembolso);
				$peca_unica_os          = pg_result($res,$i,peca_unica_os);
				$tipo='';


				if($devolucao_obrigatoria =='t') $tipo .="DEVOLUÇÃO OBRIGATÓRIA ";
				if($item_aparencia        =='t') $tipo .="ITEM DE APARÊNCIA ";
				if($acumular_kit          =='t') $tipo .="PEÇA ACUMULADA PARA KIT ";
				if($retorna_conserto      =='t') $tipo .="PEÇA SOB INTERVENÇÃO ";
				if($bloqueada_garantia    =='t') $tipo .="BLOQUEADA PARA GARANTIA ";
				if($acessorio             =='t') $tipo .="ACESSÓRIO ";
				if($aguarda_inspecao      =='t') $tipo .="AGUARDANDO INSPEÇÃO ";
				if($peca_critica          =='t') $tipo .="PEÇA CRÍTICA ";
				if($produto_acabado       =='t') $tipo .="PRODUTO ACABADO ";
				if($mero_desgaste         =='t') $tipo .="MERO DESGASTE ";
				if($pre_selecionada       =='t') $tipo .="PRE-SELECIONADA ";
				if($reembolso             =='t') $tipo .="REEMBOLSO ";
				if($peca_unica_os         =='t') $tipo .="PEÇA CRÍTICA ÚNICA NA OS ";
				if($ativo=='t')  $peca_status='Ativo';
				else             $peca_status='Inativo';

				$bg = ($i%2 == 0) ? '#fbfbfb' : '#FFFFFF';
				fputs ($fp, "<tr class='table_line' height='18' bgcolor='$bg'>");
				fputs ($fp, "<td align='left' >".pg_result ($res,$i,referencia)."</td>");
				fputs ($fp, "<td align='left' >".pg_result ($res,$i,descricao)."</td>");
				fputs ($fp, "<td align='center'>".pg_result ($res,$i,origem)."</td>");
				fputs ($fp, "<td align='center'>".pg_result ($res,$i,unidade)."</td>");
				fputs ($fp, "<td align='center' valign='center' style='font-size: 8.5px' nowrap>$tipo</td>");
				fputs ($fp, "<td align='center'>$peca_status</td>");
				fputs ($fp, "</tr>");
			}
			fputs ($fp, " </TABLE>");

			echo ` cp $arquivo_completo_tmp $path `;
			$data = date("Y-m-d").".".date("H-i-s");

			echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo_completo $arquivo_completo_tmp `;
			echo "<br>";
			echo "<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
			echo"<tr>";
			echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='xls/$arquivo_nome' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
			echo "</tr>";
			echo "</table>";
		}
	}
}
?>

<p>

<? include "rodape.php"; ?>

 <?
include 'dbconfig.php';
include 'dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';
$layout_menu = "callcenter";
include "cabecalho.php";
include "javascript_pesquisas.php";

$btn_consultar = $_POST['btn_consultar'];

$regiao             = $_POST['regiao'];
$consumidor_estado  = $_POST['consumidor_estado'];
$codigo_posto       = trim($_POST['codigo_posto']);
$nome_posto       = trim($_POST['nome_posto']);
$produto_referencia = trim($_POST['produto_referencia']);
$produto_descricao = trim($_POST['produto_descricao']);
?>
<script type='text/javascript'>
function fnc_pesquisa_produto2 (campo, campo2, tipo, mapa_linha) {
	var xcampo = null;
	if (tipo == "tudo" ) {
		var xcampo = campo;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "produto_pesquisa_2.php?campo=" + xcampo.value + "&tipo=" + tipo;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=600, height=400, top=18, left=0");
		janela.referencia   = campo;
		janela.descricao    = campo2;
		janela.produto 		= document.getElementById( 'produto' );
		janela.focus();
	}else{
			alert( 'Informe parte da informação para realizar a pesquisa!' );
		
		}
}
</script>
<style>
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
margin: 0 auto;
}
.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
.formulario{
background-color:#D9E2EF;
font:11px Arial;
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

.msg_sucesso{
background-color: green;
font: bold 16px "Arial";
color: #FFFFFF;
text-align:center;
width: 700px;
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
	font-family: Arial, Verdana, Geneva, Helvetica, sans-serif;
	font-size: 11px;
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

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}
</style>

<?

if($_GET['msg']){
	$msg = $_GET[ 'msg' ];
	var_dump($msg);
}

?>

<br/>
<FORM name='frm_pesquisa' METHOD='POST' ACTION='<?=$PHP_SELF?>'>
<TABLE width='700' align='center' border='0' cellspacing='0' cellpadding='2'>
	<TR bgcolor='#596d9b' style='font:bold 14px Arial; color:#FFFFFF;'>
		<TD colspan='5'>Parametros de pesquisa</TD>
	</TR>
	<tr>
		<td colspan='5' class='table_line'>&nbsp;</td>
	</tr>
	<tr>
		<td class="table_line"> &nbsp; </td>
		<td class="table_line">
			<label for="regiao_chk">Região</label>
		</td>
		<td class="table_line" colspan="2">
			<select name="regiao" id="regiao" style='width:131px; font-size:11px' class='frm'>
				<option value=""></option>
				<option value="NORTE" <?if ($regiao == 'NORTE') echo "selected";?>>Norte</option>
				<option value="NORDESTE" <?if ($regiao == 'NORDESTE') echo "selected";?>>Nordeste</option>
				<option value="CENTROOESTE" <?if ($regiao == 'CENTROOESTE') echo "selected";?>>Centro-Oeste</option>
				<option value="SUDESTE" <?if ($regiao == 'SUDESTE') echo "selected";?>>Sudeste</option>
				<option value="SUL" <?if ($regiao == 'SUL') echo "selected";?>>Sul</option>
			</select>
		</td>
		<td class="table_line"> &nbsp; </td>
	</tr>
	<TR>
		<TD class="table_line" style="text-align: left;">&nbsp;</TD>
		<TD class="table_line">Estado do Consumidor</TD>
		<TD class="table_line" style="text-align: left;" colspan="2">
			<select name="consumidor_estado" id='consumidor_estado' style='width:131px; font-size:11px' class='frm'>
				<? $ArrayEstados = array('','AC','AL','AM','AP',
											'BA','CE','DF','ES',
											'GO','MA','MG','MS',
											'MT','PA','PB','PE',
											'PI','PR','RJ','RN',
											'RO','RR','RS','SC',
											'SE','SP','TO'
										);
				for ($i=0; $i<=27; $i++){
					echo"<option value='".$ArrayEstados[$i]."'";
					if ($consumidor_estado == $ArrayEstados[$i]) echo " selected";
					echo ">".$ArrayEstados[$i]."</option>\n";
				}?>
			</select>
		</TD>
		<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	</TR>
	<TR>
		<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
		<TD rowspan="2" width="180" class="table_line">Posto</TD>
		<TD width="180" class="table_line">Código do Posto</TD>
		<TD width="180" class="table_line">Nome do Posto</TD>
		<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	</TR>
	<TR>
		<TD class="table_line" style="text-align: center;">&nbsp;</TD>
		<TD class="table_line" align="left" nowrap><INPUT TYPE="text" NAME="codigo_posto" SIZE="8" value='<?=$codigo_posto?>' class='frm'><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'codigo')"></TD>
		<TD width="151" class="table_line" style="text-align: left;" nowrap><INPUT TYPE="text" NAME="nome_posto" size="15"  class='frm' value='<?=$nome_posto?>'> <IMG src="imagens/lupa.png" style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto,document.frm_pesquisa.nome_posto,'nome')"></TD>
		<TD width="19" class="table_line" style="text-align: center;">&nbsp;</TD>
	</TR>
	<TR>
		<TD colspan="5" class="table_line"><hr color='#eeeeee'></TD>
	</TR>
	<TR>
		<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
		<TD rowspan="2" width="180" class="table_line">Aparelho</TD>
		<TD width="100" class="table_line">Referência</TD>
		<TD width="180" class="table_line">Descrição</TD>
		<TD width="19" class="table_line" style="text-align: left;">&nbsp;</TD>
	</TR>
	<TR>
		<TD class="table_line" style="text-align: center;">&nbsp;</TD>
		<TD class="table_line" align="left"><INPUT TYPE="text" NAME="produto_referencia" SIZE="8"  class='frm' value='<?=$produto_referencia?>'><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'referencia')"></TD>
		<TD class="table_line" style="text-align: left;"><INPUT TYPE="text" NAME="produto_nome" size="15"  class='frm' value='<?=$produto_nome?>'><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisas pela referência do aparelho." onclick="javascript: fnc_pesquisa_produto (document.frm_pesquisa.produto_referencia,document.frm_pesquisa.produto_nome,'descricao')"></TD>
		<TD class="table_line" style="text-align: center;">&nbsp;</TD>
	</TR>
	<TR>
		<TD class="table_line" colspan='5' style="text-align: center;"><input type='submit' name='btn_consultar' value='Consultar'>
		</TD>
	</TR>
</table>
<br/>
<?php

	if(isset($btn_consultar)) {

		if(!empty($regiao)) {
			$estados = array();
			switch ( strtoupper($regiao) ) {
		 		case 'SUL':
		 			$aTmp    = array('PR','SC','RS');
		 			$estados = array_merge($estados,$aTmp);
		 			unset($aTmp);
		 		break;

		 		case 'SUDESTE':
		 			$aTmp    = array('SP','MG','RJ','ES');
		 			$estados = array_merge($estados,$aTmp);
		 			unset($aTmp);
		 		break;

		 		case 'CENTROOESTE':
		 			$aTmp    = array('MT','MS','GO','DF');
		 			$estados = array_merge($estados,$aTmp);
		 			unset($aTmp);
		 		break;

		 		case 'NORDESTE':
		 			$aTmp    = array('AL','BA','CE','MA','PB','PE','PI','RN','SE');
		 			$estados = array_merge($estados,$aTmp);
		 			unset($aTmp);
		 		break;

		 		case 'NORTE':
		 			$aTmp    = array('AC','AP','AM','PA','RR','RO','TO');
		 			$estados = array_merge($estados,$aTmp);
		 			unset($aTmp);
		 		break;

		 		default:
		 			$cond1 = ' 1=1 ';
		 			break;
		 	}
			$estados_string = implode("','",$estados);
		 	$cond1         = " AND tbl_os.consumidor_estado IN ('{$estados_string}') ";
		}

		if(!empty($consumidor_estado)) {
			$cond2 =   " AND tbl_os.consumidor_estado ='$consumidor_estado' ";
		}

		if(!empty($codigo_posto)) {
			if (strlen($codigo_posto) > 0){
				$cond3 = " AND tbl_posto_fabrica.codigo_posto = '". $codigo_posto."' ";
			}
		}
	
		if (!empty($produto_referencia)) {
			$sql = "Select produto from tbl_produto where referencia_pesquisa = '$produto_referencia' ";
			$res = pg_exec($con,$sql);
			if(pg_numrows($res)>0){
				$produto = pg_result($res,0,0);
				$cond4 = " AND tbl_os.produto = $produto ";
			}
		}

		################TABELA OS SEM ATENDIMENTO OU FINALIZACAO#######################
		$data_referencia = date('Y-m-d');

		$sql = "
		SELECT
		tbl_defeito_constatado.descricao AS defeito_descricao,
		COUNT(tbl_os.os) AS qtde_os

		FROM
		tbl_os
		JOIN tbl_defeito_constatado ON tbl_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado
		JOIN tbl_posto_fabrica ON tbl_os.posto=tbl_posto_fabrica.posto AND tbl_os.fabrica=tbl_posto_fabrica.fabrica

		WHERE
		tbl_os.fabrica = $login_fabrica
		AND tbl_os.cliente_admin = $login_cliente_admin
		AND tbl_os.data_fechamento IS NOT NULL
		AND tbl_os.data_fechamento >= '$data_referencia'::date - INTERVAL '1 YEAR'
		AND tbl_os.posto<>6359
		AND tbl_os.excluida IS NOT TRUE
		$cond1
		$cond2
		$cond3
		$cond4

		GROUP BY
		tbl_defeito_constatado.descricao

		ORDER BY
		COUNT(tbl_os.os) DESC
		";
		$res = pg_query( $con,$sql );
		if( pg_num_rows( $res ) >0 ) {?>

<?php
	echo "<div align='center' style='position: relative; left: 25'>";
?>

<div class="texto_avulso" style="width:700px;">Este relatório considera somente OS fechadas no último ano</div>

<br>

<table class='tabela' width='700' cellspacing="1" align='center'>
	<tr class='subtitulo'>
		<td colspan='9'>Defeitos em Ordens de Serviço</td>
	</tr>
	<tr class='titulo_coluna'>
		<td>Defeito</td>
		<td>Quantidade de OS</td>
	</tr>
	
	<?php
		$total = 0;

		for( $i=0; $i< pg_num_rows($res);  $i++ ) {
			$cor = "";
			$medio= "";

			$defeito_descricao		= pg_result($res,$i, 'defeito_descricao');
			$qtde_os		 		= pg_result($res,$i, 'qtde_os');

			$cor = ($i%2) ? "#F7F5F0" : "#F1F4FA";
			
			$total += intval($qtde_os);
	?>
	<tr bgcolor="<?=$cor?>">
		<td><?=$defeito_descricao;?></td>
		<td><?=$qtde_os?></td>
	</tr>
	<?php }   ?>
	<tr class='titulo_coluna'>
		<td>TOTAL DE OS</td>
		<td><?=$total?></td>
	</tr>
</table>
<?php
	}else{
		echo "<p>Nenhum resultado encontrado</p>";
		
	}
	
}
?>

<? include "rodape.php";?>

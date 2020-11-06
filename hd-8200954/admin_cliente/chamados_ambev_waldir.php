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

if ($_POST['btn_acao']) {

	$hd_chamado_alert = $_POST['hd_chamado_alert'];

	if (strlen($hd_chamado_alert)==0) {
		$msg_erro = 'Chamado Inválido';
	}

		########################################################################
		//Rotinas de inserção
		$res = pg_query ($con,"BEGIN TRANSACTION;");
		$msg_erro = pg_errormessage($con);

		$sql = "INSERT INTO tbl_hd_chamado (
		   hd_chamado            ,
		   admin                 ,
		   cliente_admin         ,
		   data                  ,
		   status                ,
		   atendente             ,
		   fabrica_responsavel   ,
		   titulo                ,
		   categoria             ,
		   fabrica
		  )values(
		   DEFAULT                 ,
		   2977                    ,
		   3890                    ,
		   current_timestamp       ,
		   'Aberto'                ,
		   2977                    ,
		   30                      ,
		   'Atendimento interativo',
		   'reclamacao_produto'    ,
		   30) RETURNING hd_chamado";

//echo nl2br($sql) . '<br />';

        $res_chamado = pg_query($con,$sql);
        $msg_erro = pg_errormessage($con);
	    if (strlen($msg_erro)==0) {
			$hd_chamado = pg_result($res_chamado,0,0 );
		}

		$hd_chamado_alert 	= $_POST[ 'hd_chamado_alert' ];
		$admin				= $_POST[ 'admin' ];
		$nome				= $_POST[ 'tinrazaosocial' ];
		$sobrenome			= $_POST[ 'tinclisobrenome' ];
		$fone				= $_POST[ 'tinclifone1' ];
		$email				= $_POST[ 'tincliemail' ];
		$cidade				= $_POST[ 'tinclicidade' ];
		$estado				= $_POST[ 'tinestado' ];
		$endereco			= $_POST[ 'tinendereco' ];
		$numero				= $_POST[ 'tinnumero' ];
		$bairro				= $_POST[ 'tinbairro' ];
		$complemento		= $_POST[ 'tincomplemento' ];
		$cep				= $_POST[ 'tincep' ] ;
		$rg_freezer			= $_POST[ 'tinrgfreezer' ];
		$sintomas			= $_POST[ 'tinsintomas' ];
		$tincaso			= $_POST[ 'tincaso' ];
		$produto = $_POST['produto'];
		if( strlen( trim($rg_freezer) ) == 0 ){
			$rg_freezer = 'null';
		}


	$sql_cidade = "SELECT cidade FROM tbl_cidade WHERE nome = '$cidade'";
	$res_cidade = pg_query( $con,$sql_cidade );
	
	if(pg_num_rows($res_cidade)>0){
		$id_cidade = pg_fetch_result($res_cidade,0,0);
		$msg_erro .= pg_errormessage($con);
	}else{
		$sql = "INSERT INTO tbl_cidade(nome, estado)values(upper('$cidade'),upper('$estado'))";
		$res = pg_query($con,$sql);
		$msg_erro .= pg_errormessage($con);
		$res    = pg_query ($con,"SELECT CURRVAL ('seq_cidade')");
		$id_cidade = pg_fetch_result ($res,0,0);
	}

	if (strlen($msg_erro)==0) {
		 $sqlins = "INSERT INTO tbl_hd_chamado_extra (
			   hd_chamado,
			   produto,
			   nome,
			   endereco,
			   numero,
			   bairro,
			   cep,
			   fone,
			   cidade)
			   VALUES (
			   $hd_chamado,
			   $produto,
			   '$nome',
			   '$endereco',
			   '$numero',
			   '$bairro',
			   '$cep',
			   '$fone',
			   '$id_cidade')";

		$resins = pg_query ($con,$sqlins);
		$msg_erro .= pg_errormessage($con);
	}
//echo nl2br($sqlins)  . '<br />';
	if (strlen($msg_erro)==0) {
			$sql = "INSERT INTO tbl_hd_chamado_item(
								hd_chamado_item,
								hd_chamado   ,
								data         ,
								comentario   ,
								admin        ,
								interno      ,
								produto      ,
								serie        ,
								defeito_reclamado_descricao,
								status_item,
								tincaso
								)values(
								DEFAULT                           ,
								$hd_chamado                       ,
								current_timestamp                 ,
								'Insercao de Produto para Os' ,
								$login_admin                      ,
								't'                              ,
								'$produto'                       ,
								'$rg_freezer'                          ,
								'$sintomas'                ,
								'Aberto',
								'$tincaso'
								)
								RETURNING hd_chamado_item";
		$res_item = pg_query( $con, $sql );
		$msg_erro .= pg_errormessage($con);

//echo nl2br($sql)  . '<br />';
################### fim da inserção
		
		if (strlen( $msg_erro) > 0){
				$res = pg_query ($con,"ROLLBACK TRANSACTION");
				$msg_erro = pg_errormessage($con);
				echo "<script language='javascript'>
				//window.location = '$PHP_SELF?msg=$msg_erro';
				</script>";
		}else{
			$res = pg_query ($con,"COMMIT TRANSACTION");
			$sql = "UPDATE tbl_hd_chamado_alert set admin = $login_admin where hd_chamado_alert = $hd_chamado_alert";
			$res = pg_query($con,$sql);
			//var_dump($msg_erro);
			echo "<script language='javascript'>
				//window.location = '$PHP_SELF?msg=Gravado com Sucesso!';
			</script>";
		}
	} 
}
if (($login_admin <> '3033' and $login_admin <> '3032') and empty($btn_consultar)) {
if (strlen($_GET['hd_chamado_alert'])>0) {
	$hd_chamado_alert = $_GET['hd_chamado_alert'];
	$sql = "SELECT *from tbl_hd_chamado_alert where hd_chamado_alert = $hd_chamado_alert";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res)>0) {
		
		$num_field = pg_num_fields($res); 
		
		$colunas = "4";

		if ($num_field>0) {
		echo "<form method='post' name='frm_principal' action=''>";
		echo "<table width='100%' align='center' class='formulario'>";
			for($i=0;$i<pg_num_fields($res);$i++) {
				
				$valor = pg_result($res,0,$i);
				$label = pg_field_name($res,$i);
				$label2 = pg_field_name($res,$i);

				$label = str_replace('tin','',$label);
				$label = str_replace('_',' ',$label);
				$label = str_replace('cli','Cliente ',$label);
				

				if (($i%$colunas)==0) {
					echo "</tr><tr valign='top'>";
				}

				echo "	<td width='300' align='left'><div class='titulo_tabela' width='100%'>$label</div><br>";
				if (strlen($valor)<=50) {
					
					/*if( $label2 == 'tinmodelofreezer' )
					{
						$readonly = '';
					}
					else{
						$readonly = "readonly = true";
						}
					*/
					echo "<input type='text' name='$label2' value='$valor' id='$label2' size='40' class='frm' $readonly>";
					if( $label2 == 'tinmodelofreezer' )
					{
						echo "<input type='hidden' name='produto' id='produto' />";
						echo "<img src='imagens/lupa.png' border='0' align='absmiddle'
				style='cursor: pointer'
				onclick=\"javascript: fnc_pesquisa_produto2 (document.frm_principal.$label2,document.frm_principal.$label2,'tudo')\">";
					}
					echo "</td>";
				} else {
					echo "<textarea rows='10' cols='35' class='frm' readonly='true'>$valor</textarea>";
				}
				
			}
		}
		echo "<tr><td colspan='4'><input type='submit' value='Confirmar' name='btn_acao'></td></tr>";
		echo "</table></form>";
	}
}
}

if (($login_admin <> '3033' and $login_admin <> '3032') and empty($btn_consultar)) {
	$sql = "SELECT *,to_char(data_leitura,'dd/mm/yyyy') as data_leitura2 from tbl_hd_chamado_alert where admin is null";

	$res = pg_query($con,$sql);

	if (pg_num_rows($res)>0){
		echo"<br>";
		echo "<table class='tabela' cellpading='3' cellspacing='1' align='center' width='700px'>";
		echo "<tr class='titulo_coluna'>";
		echo "<td>Data/Hora</td>";
		echo "<td>Cliente Nome</td>";
		echo "<td>Cidade</td>";
		echo "<td>UF</td>";
		echo "</tr>";

		for ($i=0;$i<pg_num_rows($res);$i++) {

			$hd_chamado_alert  = pg_result($res,$i,'hd_chamado_alert');
			$data_hora         = pg_result($res,$i,'data_leitura2');
			$cliente_nome      = pg_result($res,$i,'tinrazaosocial');
			$cidade            = pg_result($res,$i,'tinclicidade');
			$estado            = pg_result($res,$i,'tinestado');

			$cor = ($i%2)  ? "#F7F5F0" : "#F1F4FA";

			echo "<tr onclick='javascript:window.location = \"$PHP_SELF?hd_chamado_alert=$hd_chamado_alert\"' style='cursor:pointer' bgcolor=$cor>";
			echo "<td>$data_hora</td>";
			echo "<td>$cliente_nome</td>";
			echo "<td>$cidade</td>";
			echo "<td>$estado</td>";
			echo "</tr>";
		}
	echo '</table>';
	}
	?>
	<?php
		################TABELA DOS ERROS DE INTEGRAÇÃO QUE FORAM CORRIGIDOS#######################

		$sql = "SELECT tbl_hd_chamado_item.hd_chamado,tbl_hd_chamado_item.hd_chamado_item, to_char(tbl_hd_chamado_item.data,'dd/mm/yyyy') as data_abertura, tbl_hd_chamado_extra.nome, 
					   tbl_cidade.nome as cidade, tbl_cidade.estado, tbl_hd_chamado_item.tincaso
				FROM tbl_hd_chamado_item 
				JOIN tbl_hd_chamado USING(hd_chamado)
				JOIN tbl_hd_chamado_extra on tbl_hd_chamado_item.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				LEFT JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
				WHERE tbl_hd_chamado.fabrica = $login_fabrica
				and tbl_hd_chamado_item.tincaso IS NOT NULL
				AND tbl_hd_chamado_item.os IS NULL";
		$res = pg_query( $con,$sql );
		if( pg_num_rows( $res ) >0 ) {?>
	<br />
	<table class='tabela' width='700px' align='center'>
		<tr class='subtitulo'>
			<td colspan='5'>Atendimentos integrados sem ordem de serviço</td>
		</tr>
		<tr class='titulo_coluna'>
			<td>HD Chamado</td>
			<td>Abertura</td>
			<td>Consumidor</td>
			<td>Cidade</td>
			<td>UF</td>
		</tr>
		
		<?php
			for( $i=0; $i< pg_num_rows($res);  $i++ ) {
				$hd_chamado 		= pg_result($res,$i,'hd_chamado');
				$hd_chamado_item  	= pg_result($res,$i,'hd_chamado_item');
				$abertura			= pg_result($res,$i,'data_abertura');
				$consumidor			= pg_result($res,$i,'nome');
				$cidade				= pg_result($res,$i,'cidade');
				$uf					= pg_result($res,$i,'estado');
				$tincaso			= pg_result($res,$i,'tincaso');
				
				$cor = ($i%2) ? "#F7F5F0" : "#F1F4FA";
		?>
		<tr bgcolor="<?=$cor?>">
			<td><a href='pre_os_cadastro_sac_esmaltec.php?callcenter=<?=$hd_chamado?>&categoria=reclamacao_produto&tincaso=<?=$tincaso?>&hd_chamado_item=<?=$hd_chamado_item?>'><?=$hd_chamado ?></a></td>
			<td><?=$abertura?></td>
			<td><?=$consumidor?></td>
			<td><?=$cidade?></td>
			<td><?=$uf?></td>
		</tr>
		<?php }   ?>
	</table>
	<?php
		}
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
				<option value="SUL" <?if ($regiao == 'SUL') echo "selected";?>>Sul</option>
				<option value="SUDESTE" <?if ($regiao == 'SUDESTE') echo "selected";?>>Sudeste</option>
				<option value="BR-NEES" <?if ($regiao == 'BR-NEES') echo "selected";?>>Nordeste + E.S.</option>
				<option value="BR-NCO" <?if ($regiao == 'BR-NCO') echo "selected";?>>Norte + C.O.</option>
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
		<?if(!empty($btn_consultar)) {?>
		<input type='button' value='Voltar para tela principal' onclick="window.location='chamados_ambev.php'">
		<?}?>
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
		 		case 'BR-NEES':
		 			$aTmp    = array('AL','BA','CE','MA','PB','PE','PI','RN','SE','ES');
		 			$estados = array_merge($estados,$aTmp);
		 			unset($aTmp);
		 			break;
		 		case 'BR-NCO':
		 			$aTmp    = array('AC','AP','AM','PA','RR','RO','TO','GO','MT','MS','DF');
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

		$sql = "SELECT	tbl_os.os,
						tbl_posto.nome,
						tbl_posto_fabrica.codigo_posto,
						tbl_produto.referencia,
						tbl_produto.descricao,
						to_char(tbl_os.data_digitacao,'DD/MM/YYYY') as data_digitacao,
						to_char(tbl_os.visita_agendada,'DD/MM/YYYY') as visita_agendada,
						to_char(tbl_os.finalizada,'DD/MM/YYYY HH:MI') as finalizada,
						tbl_os.consumidor_cidade,
						tbl_os.consumidor_estado,
						tbl_os.os_reincidente
				FROM tbl_os
				JOIN tbl_hd_chamado_item on tbl_hd_chamado_item.os = tbl_os.os
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto and tbl_posto_fabrica.fabrica = $login_fabrica
				JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
				JOIN tbl_produto ON tbl_produto.produto = tbl_os.produto
				WHERE tbl_os.fabrica = $login_fabrica
				AND cliente_admin = $login_cliente_admin
				$cond1
				$cond2
				$cond3
				$cond4 order by os,consumidor_estado";
		$res = pg_query( $con,$sql );
		if( pg_num_rows( $res ) >0 ) {?>

<?php
	echo "<div align='center' style='position: relative; left: 25'>";
		echo "<table border='0' cellspacing='1' cellpadding='0' align='center' style='margin-left:15%'>";
		
		echo "<tr height='18'>";
		echo "<td width='18' bgcolor='#8FFFFF'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; Ordem de Serviço Reincidente</b></font></td>";
//		HD 311077
		echo '</tr><tr height="18">';
		echo "<td width='18' bgcolor='#3DFF8B'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; Ordem de Serviço com 24 Horas</b></font></td>";
		echo '</tr><tr height="18">';
		echo "<td width='18' bgcolor='#FFFF7A'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; Ordem de Serviço com 48 Horas</b></font></td>";
		echo '</tr><tr height="18">';
		echo "<td width='18' bgcolor='#FF9933'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; Ordem de Serviço com 72 Horas</b></font></td>";
		echo '</tr><tr height="18">';
		echo "<td width='18' bgcolor='#FF3D3D'>&nbsp;</td>";
		echo "<td align='left'><font size='1'><b>&nbsp; Ordem de Serviço com acima de 72 Horas</b></font></td>";
		echo "</tr>";
//		Fim HD 311077
		echo "<tr height='3'><td colspan='2'></td></tr>";
		echo "</table>";
		
?><br />

<table class='tabela' width='700px' align='center'>
	<tr class='subtitulo'>
		<td colspan='9'>Ordens de Serviço AMBEV</td>
	</tr>
	<tr class='titulo_coluna'>
		<td>OS</td>
		<td>Data Digitação</td>
		<td>Posto</td>
		<td>Produto</td>
		<td>Data Agendamento</td>
		<td>Cidade Cliente</td>
		<td>Estado Cliente</td>
		<td>Finalizada</td>
		<td>Tempo Médio(Dias)</td>
	</tr>
	
	<?php
		
		$data_atual = date('Y-m-d');
		$da = explode('-',$data_atual);

		$ano2 = $da[0];
		$mes2 = $da[1];
		$dia2 = $da[2];

		for( $i=0; $i< pg_num_rows($res);  $i++ ) {
			$cor = "";
			$medio= "";

			$os			 		= pg_result($res,$i,'os');
			$nome				= pg_result($res,$i,'nome');
			$codigo_posto		= pg_result($res,$i,'codigo_posto');
			$data_digitacao		= pg_result($res,$i,'data_digitacao');
			$consumidor_cidade	= pg_result($res,$i,'consumidor_cidade');
			$consumidor_estado	= pg_result($res,$i,'consumidor_estado');
			$visita_agendada	= pg_result($res,$i,'visita_agendada');
			$referencia			= pg_result($res,$i,'referencia');
			$descricao			= pg_result($res,$i,'descricao');
			$finalizada			= pg_result($res,$i,'finalizada');
			
			$os_reincidente		= pg_result($res,$i,'os_reincidente');

			if(!empty($finalizada)) {
				$sqlm="SELECT '$finalizada'::date - '$data_digitacao'::date";
				$resm = pg_query($con,$sqlm);
				$medio = pg_fetch_result($resm,0,0);
			}

//			$cor = ($i%2) ? "#F7F5F0" : "#F1F4FA";

		// HD 311077
			$data_final = implode("-", array_reverse(explode("/", $data_digitacao)));
			$df = explode ('-',$data_final);

			$ano1 = $df[0];
			$mes1 = $df[1];
			$dia1 = $df[2];

			$timestamp1 = mktime(0,0,0,$mes1,$dia1,$ano1);
			$timestamp2 = mktime(4,12,0,$mes2,$dia2,$ano2);

			$segundos_diferenca = $timestamp1 - $timestamp2;

			$dias_diferenca = $segundos_diferenca / (60 * 60 * 24);

			$dias_diferenca = abs($dias_diferenca);

			$dias_diferenca = floor($dias_diferenca);

			if (strlen($finalizada)==0) {
				switch($dias_diferenca) {
					case 0:
					case 1:
						$cor = '#3DFF8B';
						break;
					case 2:
						$cor = '#FFFF7A';
						break;
					case 3:
						$cor = '#FF9933';
						break;
					default:
						$cor = '#FF3D3D';
				}
			}
		//fim HD 311077
			if ($os_reincidente=='t') {
				$cor = '#8FFFFF';
			}
	?>
	<tr bgcolor="<?=$cor?>">
		<td><? if ($login_admin <> '3033' and $login_admin <> '3032') {?><a href='os_press.php?os=<?=$os?>' target='_blank'><?=$os ?></a><?}else {echo $os; } ?></td>
		<td><?=$data_digitacao;?></td>
		<td><?=$codigo_posto.'-'.$nome?></td>
		<td><?=$referencia.'-'.$descricao?></td>
		<td><?=$visita_agendada;?></td>
		<td><?=$consumidor_cidade?></td>
		<td><?=$consumidor_estado?></td>
		<td><?=$finalizada?></td>
		<td><?=$medio?></td>
	</tr>
	<?php }   ?>
	<tfoot>
	<tr style='background-color:#0000CC;color:#FFFFFF;font-size:15px'>
		<td colspan='8'>Total de OS:</td>
		<td ><?=pg_num_rows($res)?></td>
	</tr>
	</tfoot>
</table>
<?php
	}else{
		echo "<p>Nenhum resultado encontrado</p>";
		
	}
	
}
?>

<? include "rodape.php";?>

<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="financeiro";
include "autentica_admin.php";
include 'funcoes.php';

$pedido   = $_GET["pedido"];
if (strlen($_POST["pedido"]) > 0){
	$pedido = $_POST["pedido"];
}
$aprova       = trim($_GET['aprova']);
//$tipo = $_GET["tipo"];

$btn_acao    = trim($_POST["btn_acao"]);
$select_acao = trim($_POST["select_acao"]);

if(strlen($btn_acao)>0 AND strlen($select_acao)>0){

	$qtde_pedido = trim($_POST["qtde_pedido"]);
	$observacao  = trim($_POST["observacao"]);

	if (strlen($qtde_pedido)==0){
		$qtde_pedido = 0;
	}

	for ($x=0;$x<$qtde_pedido;$x++){

		$Xpedido = trim($_POST["check_".$x]);

		if (strlen($Xpedido) > 0 AND strlen($msg_erro) == 0){

			$res_os = pg_exec($con,"BEGIN TRANSACTION");

			if($select_acao == "aprovar"){
				$sql = "UPDATE tbl_pedido SET
								data_aprovacao = CURRENT_TIMESTAMP,
								status_pedido  = null,
								admin          = $login_admin
						WHERE tbl_pedido.fabrica = $login_fabrica
						AND   tbl_pedido.pedido  = $Xpedido ";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

			if($select_acao == "recusar"){
				$sql = "UPDATE tbl_pedido SET
								status_pedido = 17,
								admin          = $login_admin
						WHERE tbl_pedido.fabrica = $login_fabrica
						AND   tbl_pedido.pedido  = $Xpedido ";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

			if($select_acao == "aguardando_aprovacao"){
				$sql = "UPDATE tbl_pedido SET status_pedido = 18
						WHERE tbl_pedido.fabrica = $login_fabrica
						AND   tbl_pedido.pedido  = $Xpedido
						AND   tbl_pedido.status_pedido = 17";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}


			if ($select_acao == "excluir"){
				$sql = "SELECT os, data_fechamento, finalizada
						INTO TEMP tmp_filizola_pedido_os
						FROM tbl_os
						WHERE fabrica        = $login_fabrica
						AND   pedido_cliente = $Xpedido
						AND   data_fechamento IS NOT NULL ";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "UPDATE tbl_os SET data_fechamento = NULL, finalizada = null
						WHERE fabrica        = $login_fabrica
						AND   pedido_cliente = $Xpedido";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "UPDATE tbl_os SET pedido_cliente = NULL
						WHERE fabrica        = $login_fabrica
						AND   pedido_cliente = $Xpedido";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				/*PEDIDO CLIENTE*/
				$sql = "UPDATE tbl_os_item SET pedido_cliente = NULL
						WHERE pedido_cliente = $Xpedido";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
				/*PEDIDO POSTO*/
				$sql = "UPDATE tbl_os_item SET pedido = NULL
						WHERE pedido= $Xpedido";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "UPDATE tbl_os_revenda SET pedido_cliente = NULL
						WHERE fabrica        = $login_fabrica
						AND   pedido_cliente = $Xpedido";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "UPDATE tbl_os SET data_fechamento = XX.data_fechamento ,finalizada = XX.finalizada
						FROM (	SELECT os, data_fechamento, finalizada
								FROM tmp_filizola_pedido_os
						) as XX
						WHERE fabrica        = $login_fabrica
						AND   tbl_os.os      = XX.os ";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "UPDATE tbl_pedido SET fabrica = 0
						WHERE tbl_pedido.fabrica = $login_fabrica
						AND   tbl_pedido.pedido  = $Xpedido ";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);

				$sql = "DROP TABLE tmp_filizola_pedido_os";
				$res = pg_exec($con,$sql);
				$msg_erro .= pg_errormessage($con);
			}

			if (strlen($msg_erro)==0){
				$res = pg_exec($con,"COMMIT TRANSACTION");
			}else{
				$res = pg_exec($con,"ROLLBACK TRANSACTION");
			}
		}
	}
}

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$layout_menu = "callcenter";
$title = "APROVAÇÃO DE PEDIDOS";

include "cabecalho.php";

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



#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}
</style>


<script language="JavaScript">

var ok = false;
var cont=0;
function checkaTodos() {
	f = document.frm_pesquisa2;

	if (!ok) {
		for (i=0; i<f.length; i++){
			if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = true;
				ok=true;
				if (document.getElementById('linha_'+i)) {
					document.getElementById('linha_'+i).style.backgroundColor = "#F0F0FF";
					document.getElementById('check_'+i).style.backgroundColor = "#F0F0FF";
				}
			}
		}
	}else{
		for (i=0; i<f.length; i++) {
				if (f.elements[i].type == "checkbox"){
				f.elements[i].checked = false;
				ok=false;
				if (document.getElementById('linha_'+i)) {
					document.getElementById('linha_'+i).style.backgroundColor = "#FFFFFF";
					document.getElementById('check_'+i).style.backgroundColor = "#FFFFFF";
				}
			}
		}

	}

}

function setCheck(theCheckbox,mudarcor,mudacor2,cor){
	if (document.getElementById(theCheckbox)) {
//		document.getElementById(theCheckbox).checked = (document.getElementById(theCheckbox).checked ? false : true);
	}
	if (document.getElementById(mudarcor)) {
		document.getElementById(mudarcor).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
	}
	if (document.getElementById(mudacor2)) {
		document.getElementById(mudacor2).style.backgroundColor  = (document.getElementById(theCheckbox).checked ? "#FFF8D9" : cor);
	}
}

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>

</script>
<?  include "javascript_calendario_new.php";
    include_once '../js/js_css.php';
?>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datepick({startdate:'01/01/2000'});
		$('#data_final').datepick({startDate:'01/01/2000'});
		$("#data_inicial").mask("99/99/9999");
		$("#data_final").mask("99/99/9999");

		$("input[@rel='data_nf']").mask("99/99/9999");
	});
</script>


<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[2] + " - " + row[1];
	}

	function formatResult(row) {
		return row[2];
	}

	/* Busca pelo Código */
	$("#posto_codigo").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[2];}
	});

	$("#posto_codigo").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#posto_codigo").val(data[2]) ;
		//alert(data[2]);
	});

});
</script>

<script>
function abreObs(os,codigo_posto,sua_os){
	janela = window.open("obs_os_troca.php?os=" + os + "&codigo_posto=" + codigo_posto +"&sua_os=" + sua_os,"formularios",'resizable=1,scrollbars=yes,width=400,height=250,top=0,left=0');
	janela.focus();
}
</script>

<? include "javascript_pesquisas.php";

if($btn_acao == 'Pesquisar'){

	$data_inicial = trim($_POST['data_inicial']);
	$data_final   = trim($_POST['data_final']);
	$aprova       = trim($_POST['aprova']);
	$tipo         = trim($_POST['tipo']);
	$pedido       = trim($_POST['pedido']);

	if (strlen($data_inicial) > 0) {
		$xdata_inicial = formata_data ($data_inicial);
		$xdata_inicial = $xdata_inicial." 00:00:00";
	}

	if (strlen($data_final) > 0) {
		$xdata_final = formata_data ($data_final);
		$xdata_final = $xdata_final." 23:59:59";
	}
	if($aprova=='aprovados' and (strlen($data_inicial) == 0 or strlen($data_final) == 0)) {
		$msg_erro = "Data Inválida";
	}

	if($xdata_inicial > $xdata_final)
		$msg_erro = "Data Inválida";
}

$exportar_pedido = trim($_GET['exportar_pedido']);

/* Link Provisorio para exportação de pedido de Clientes, até automatização, pois o Eduardo faz a aprovação de pedido e precisa faturar no mesmo dia - Fabio */
if ($login_fabrica == 7){
	if (strlen($exportar_pedido)>0 AND $exportar_pedido == 1){
		if (strlen($msg_erro) == 0 ) {
			$ret = "";
			system("/www/cgi-bin/filizola/exporta-pedido.pl",$ret);
			if ($ret <> "0") {
				$msg_erro .= "Não foi possível exortar os pedidos aprovados ($ret). Tente novamente.";
			}else{
				echo "<p align='center' style='font-size: 12px; font-family: verdana;'><FONT COLOR='#0000FF'><b>Pedidos exportados. O pedido foi enviado para a área de FTP e por Email.</FONT></b></p>";
			}
		}
	}else{
		echo "<center><h3 style='align:center'><a href='$PHP_SELF?exportar_pedido=1'>Clique aqui para Exportar Pedidos Aprovados</a></h3></center>";
	}
}

?>


<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<TABLE width="700" align="center" border="0" cellspacing='0' cellpadding='0' class='formulario'>
<?php if(strlen($msg_erro) > 0){ ?>
		<tr class="msg_erro"><td colspan="3"><?php echo $msg_erro; ?> </td></tr>
<?php } ?>
<tr class="titulo_tabela"><td colspan="3">Parâmetros de Pesquisa</td></tr>

<TBODY>
<TR>
	<td width="180">&nbsp;</td>
	<td>
		<table width="100%" border='0'>
			<tr>
				<TD width="170">Número do Pedido<br>
					<input type="text" name="pedido" id="pedido" size="20" maxlength="20" value="<? echo $pedido ?>" class="frm">
				</TD>
				<TD>Número do Ordem de Serviço<br>
					<input type="text" name="numero_os" id="numero_os" size="20" maxlength="20" value="<? echo $numero_os ?>" class="frm">
				</TD>
			</tr>
		</table>
	</td>

	<TD></TD>
</TR>

<TR>
	<td width="100">&nbsp;</td>
	<td>
		<table width="100%" border="0">
			<tr>
				<TD width="170">Data Inicial<br><input type="text" name="data_inicial" id="data_inicial" size="11" maxlength="10" value="<? echo $data_inicial ?>" class="frm"></TD>
				<TD>Data Final<br><input type="text" name="data_final" id="data_final" size="11" maxlength="10" value="<? echo $data_final ?>" class="frm"></TD>
			</tr>
		</table>
	</td>
</TR>
<TR>
	<td width="100">&nbsp;</td>
	<TD colspan= '2'>Tipo de Pedido<br>
	<?
	$sql = "SELECT  tbl_tipo_pedido.tipo_pedido,
					tbl_tipo_pedido.descricao
			FROM    tbl_tipo_pedido
			WHERE   tbl_tipo_pedido.fabrica = $login_fabrica
			ORDER BY tbl_tipo_pedido.descricao;";
	$res1 = @pg_exec ($con,$sql);

	if (pg_numrows($res1) > 0) {
		echo "<select name='tipo' class='frm'>\n";
		echo "<option value=''></option>";

		for ($i = 0 ; $i < pg_numrows ($res1) ; $i++){
			$aux_tipo      = trim(pg_result($res1,$i,tipo_pedido));
			$aux_descricao = trim(pg_result($res1,$i,descricao));

			echo "<option value='$aux_tipo'";
			if ($aux_tipo == $tipo) echo " selected";
			echo ">$aux_descricao</option>\n";
		}
		echo "</select>";
		echo "</td>";
	}
	?>

	</TD>
</TR>
<tr>
	<td width="100">&nbsp;</td>
	<td colspan='2'>
		<fieldset style="width:350px;">
			<legend>Origem</legend>
			<INPUT TYPE="radio" NAME="origem_cliente" value='t' <? if(trim($origem_cliente) == 't')  echo "checked='checked'"; ?>>Cliente &nbsp;&nbsp;&nbsp;
			<INPUT TYPE="radio" NAME="origem_cliente" value='f' <? if(trim($origem_cliente) == 'f') echo "checked='checked'"; ?>>PTA &nbsp;&nbsp;&nbsp;
			<INPUT TYPE="radio" NAME="origem_cliente" value='x' <? echo "checked='checked'"; ?>>Todos
		</fieldset>
	</td>
</tr>
<tr>
	<td width="100">&nbsp;</td>
	<td colspan='2'>
		<fieldset style="width:350px;">
		<legend>Mostrar os Pedidos</legend>

			<INPUT TYPE="radio" NAME="aprova" value='aprovacao' <? if(trim($aprova) == 'aprovacao' OR trim($aprova)==0) echo "checked='checked'"; ?>>Aguardando aprovação &nbsp;&nbsp;&nbsp;
			<INPUT TYPE="radio" NAME="aprova" value='reprovados' <? if(trim($aprova) == 'reprovados') echo "checked='checked'"; ?>>Reprovados &nbsp;&nbsp;&nbsp;
			<INPUT TYPE="radio" NAME="aprova" value='aprovados' <? if(trim($aprova) == 'aprovados') echo "checked='checked'"; ?>>Aprovados &nbsp;&nbsp;&nbsp;
			</fieldset>
	</td>
</tr>
</tbody>
<tr><td colspan="3">&nbsp;</td>
<TR>

	<TD colspan="3" align="center">

		<input type='hidden' name='btn_acao' value=''>
		<input type="button" style="background:url(imagens_admin/btn_pesquisar_400.gif); width:400px;cursor:pointer;" value="&nbsp;" onclick="javascript: if ( document.frm_pesquisa.btn_acao.value == '' ) { document.frm_pesquisa.btn_acao.value='Pesquisar'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" alt='Clique AQUI para pesquisar'>
	</TD>
</TR>
</table>
</form>


<?
if (strlen($btn_acao)  > 0 AND strlen($msg_erro)==0 OR strlen($pedido) > 0 ) {

	$sql = "SELECT	distinct tbl_pedido.pedido                                           ,
					TO_CHAR(tbl_pedido.data,'DD/MM/YYYY')       AS data         ,
					tbl_pedido.posto                                            ,
					tbl_pedido.fabrica                                          ,
					tbl_posto.nome                                              ,
					tbl_posto.cnpj                                              ,
					tbl_pedido.total                                            ,
					(	SELECT  sum (
						tbl_pedido_item.qtde * tbl_pedido_item.preco * (1 + (
											CASE WHEN tbl_pedido_item.ipi IS NOT NULL AND tbl_pedido_item.ipi > 0 THEN
												tbl_pedido_item.ipi
											ELSE tbl_peca.ipi
											END
											/ 100))
						) as total
						FROM  tbl_pedido_item
						JOIN  tbl_peca        USING (peca)
						WHERE tbl_pedido_item.pedido = tbl_pedido.pedido
						AND   tbl_pedido.fabrica     = $login_fabrica
					)                                           AS total_com_ipi,
					tbl_tipo_pedido.descricao                   AS tipo_pedido  ,
					tbl_condicao.descricao                      AS condicao     ,
					tbl_pedido.origem_cliente                                   ,
					tbl_pedido.pedido_os                                        ,
					tbl_admin.login
			FROM        tbl_pedido
			JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido
			JOIN        tbl_tipo_pedido      ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido
			JOIN        tbl_posto            ON tbl_pedido.posto       = tbl_posto.posto
			LEFT JOIN   tbl_condicao         ON tbl_pedido.condicao    = tbl_condicao.condicao
			LEFT JOIN   tbl_admin            ON  tbl_admin.admin       = tbl_pedido.admin
			LEFT JOIN tbl_os_item   ON  tbl_os_item.peca           = tbl_pedido_item.peca
						AND ( tbl_os_item.pedido_cliente = tbl_pedido.pedido
						OR tbl_os_item.pedido = tbl_pedido.pedido )
			LEFT JOIN tbl_os_produto  ON tbl_os_produto.os_produto = tbl_os_item.os_produto
			LEFT JOIN tbl_os          ON tbl_os.os                 = tbl_os_produto.os
			OR tbl_os.pedido_cliente = tbl_pedido.pedido
			WHERE       tbl_pedido.fabrica          = $login_fabrica
			AND         tbl_pedido.finalizado       IS NOT NULL
			AND         tbl_pedido.troca            IS NOT TRUE
			AND         (tbl_pedido.status_pedido <> 14 OR tbl_pedido.status_pedido IS NULL )
			AND         tbl_pedido.data            > '2008-08-01'
			";
	if(strlen($tipo) > 0){
		$sql .= " AND         tbl_pedido.tipo_pedido = $tipo";
	}

	if(strlen($aprova) == 0){
		$sql .= " AND         tbl_pedido.data_aprovacao   IS NULL
				  AND         tbl_pedido.exportado        IS NULL
				  AND         (tbl_pedido.status_pedido = 18 OR tbl_pedido.status_pedido <> 17 OR tbl_pedido.status_pedido IS NULL )";
	}elseif($aprova=="aprovacao"){
		$sql .= " AND         tbl_pedido.data_aprovacao   IS NULL
				  AND         tbl_pedido.exportado        IS NULL
				  AND         (tbl_pedido.status_pedido = 18 OR tbl_pedido.status_pedido <> 17 OR tbl_pedido.status_pedido IS NULL )";
	}elseif($aprova=="reprovados"){
		$sql .= " AND         tbl_pedido.status_pedido = 17 ";
	}elseif($aprova=="aprovados"){
		$sql .= " AND         tbl_pedido.data_aprovacao IS NOT NULL";
	}

	if (strlen($pedido)>0){
		$sql .= " AND tbl_pedido.pedido = $pedido ";
	}

	if (strlen($numero_os)>0){
		$sql .= " AND tbl_os.fabrica=$login_fabrica AND tbl_os.sua_os = '$numero_os' ";
	}

	if (strlen($origem_cliente)>0){
		if($origem_cliente != 'x'){
			if($origem_cliente == 'f'){
				$sql .= " AND tbl_pedido.origem_cliente IS NOT TRUE ";
			}else{
				$sql .= " AND tbl_pedido.origem_cliente = '$origem_cliente' ";
			}
		}
	}

	if (strlen($xdata_inicial) > 0 AND strlen($xdata_final) > 0) {
		$sql .= " AND tbl_pedido.data BETWEEN '$xdata_inicial' AND '$xdata_final' ";
	}

	if($login_fabrica == 10){
		$sql.=" AND tbl_pedido.pedido_loja_virtual IS TRUE ";
	}
	$sql .= " ORDER BY data ASC ";
if($ip=='200.228.76.11') {
	#echo nl2br($sql);
}
#exit;
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){

		echo "<BR><BR><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";

		echo "<input type='hidden' name='data_inicial'   value='$data_inicial'>";
		echo "<input type='hidden' name='data_final'     value='$data_final'>";
		echo "<input type='hidden' name='aprova'         value='$aprova'>";

		echo "<table width='950' border='0' align='center' cellpadding='3' cellspacing='1' class='tabela'>";
		echo "<tr class='titulo_coluna'>";
		echo "<td><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: pointer;' align='center'></td>";
		echo "<td>Pedido</td>";
		echo "<td>Data</td>";
		echo "<td>Cliente</td>";
		echo "<td>CNPJ</td>";
		echo "<td>Tipo</td>";
		echo "<td>Origem (OS/Compra)</td>";
		echo "<td>Solicitante (PTA/Cliente)</td>";
		echo "<td>Admin</td>";
		echo "<td>Condição</td>";
		echo "<td>Total</td>";
		echo "</tr>";

		$cores = '';
		$qtde_intervencao = 0;

		for ($x=0; $x<pg_numrows($res);$x++){

			$pedido			= pg_result($res, $x, pedido);
			$data			= pg_result($res, $x, data);
			$posto			= pg_result($res, $x, posto);
			$fabrica		= pg_result($res, $x, fabrica);
			$nome			= pg_result($res, $x, nome);
			$cnpj			= pg_result($res, $x, cnpj);
			$total			= pg_result($res, $x, total);
			$total_com_ipi	= pg_result($res, $x, total_com_ipi);
			$tipo_pedido	= pg_result($res, $x, tipo_pedido);
			$condicao		= pg_result($res, $x, condicao);
			$origem_cliente	= pg_result($res, $x, origem_cliente);
			$pedido_os		= pg_result($res, $x, pedido_os);
			$login          = pg_result ($res,$x,login);
			/* HD 40787 */
			$total = $total_com_ipi;

			$cores++;
			$cor = ($cores % 2 == 0) ? "#F7F5F0": '#F1F4FA';

			echo "<tr bgcolor='$cor' id='linha_$x'>";
			echo "<td align='center' width='0'>";
			if($aprova == "aprovacao" or $aprova='reprovados'){ // HD 49956
				echo "<input type='checkbox' name='check_$x' id='check_$x' value='".$pedido."' onclick=\"setCheck('check_$x','linha_$x','linha_aux_$x','$cor');\" ";
				if (strlen($msg_erro)>0){
					if (strlen($_POST["check_".$x])>0){
						echo " CHECKED ";
					}
				}
				echo ">";
			}
			if($aprova == "aprovados"){
				echo "<input type='checkbox' name='check_$x' id='check_$x' value='".$pedido."' onclick=\"setCheck('check_$x','linha_$x','linha_aux_$x','$cor');\" ";
				if (strlen($msg_erro)>0){
					if (strlen($_POST["check_".$x])>0){
						echo " CHECKED ";
					}
				}
				echo ">";
			}
			echo "</td>";
			echo "<td nowrap ><a href='pedido_admin_consulta.php?pedido=$pedido&alterar=1'  target='_blank'>".$pedido."</a></td>";
			echo "<td nowrap >".$data. "</td>";
			echo "<td>".$nome. "</td>";
			echo "<td nowrap title='".$cnpj." - ".$nome."'>".$cnpj."</td>";
			echo "<td nowrap>". $tipo_pedido ."</td>";
			if($pedido_os =='t'){
				$pedido_os_descricao = " Ordem Serviço";
			}else{
				$pedido_os_descricao = " Compra Manual";
			}
			echo "<td align='center' style='font-size: 9px; font-family: verdana' nowrap>". $pedido_os_descricao ."</td>";
			if($origem_cliente == 't'){
				$origem_descricao = "Cliente";
			}else{
				$origem_descricao = "PTA";
			}
			echo "<td align='center' nowrap>".$origem_descricao ."</td>";
			echo "<td align='left' nowrap>". $login."</td>";
			echo "<td align='left' nowrap>". $condicao ."</td>";
			echo "<td align='right' nowrap>". number_format($total,2,",",".") ."</td>";
			echo "</tr>";
		}
		echo "<input type='hidden' name='qtde_pedido' value='$x'>";

		echo "<tr>";
		echo "<td height='20' colspan='12' align='center'> ";
		if(trim($aprova) == 'aprovacao' or trim($aprova) == 'reprovados'){
			echo "&nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;";
			echo "<select name='select_acao' size='1' class='frm'>";
			if(trim($aprova) == 'aprovacao') {
				echo "<option value=''></option>";
				echo "<option value='aprovar'";  if ($_POST["select_acao"] == "aprovar")  echo " selected"; echo ">APROVAR</option>";
				echo "<option value='recusar'";  if ($_POST["select_acao"] == "recusar")  echo " selected"; echo ">REPROVAR</option>";
				if($login_fabrica==7){
				echo "<option value='excluir'";  if ($_POST["select_acao"] == "excluir")  echo " selected"; echo ">EXCLUIR</option>";
				}
			}elseif(trim($aprova) == 'reprovados'){
				echo "<option value=''></option>";
				echo "<option value='aprovar'";  if ($_POST["select_acao"] == "aprovar")  echo " selected"; echo ">APROVAR</option>";
				echo "<option value='aguardando_aprovacao'";  if ($_POST["select_acao"] == "aguardando_aprovacao")  echo " selected"; echo ">AGUARDANDO APROVAÇÃO</option>";
			}
			echo "</select>";
			#echo "&nbsp;&nbsp; <font color='#FFFFFF'><b>Motivo:<b></font> <input class='frm' type='text' name='observacao' id='observacao' size='30' maxlength='250' value='' >";
			echo "&nbsp;&nbsp;<input type='button' style='background:url(imagens_admin/btn_gravar.gif); width:75px; cursor:pointer;' value='&nbsp;' onclick=\"javascript: if (document.frm_pesquisa2.select_acao.value == 'excluir'){
					if (!confirm('Deseja excluir os pedidos selecionados? \\n\\n Os pedidos serão excluídos e um novo pedido será gerado para as OS.\\n\\n Deseja continuar?')){
						return;
					}
				}
				document.frm_pesquisa2.submit()

			\" style='cursor: hand;' border='0' align='absmiddle'></td>";
		}
		echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
		echo "</table>";
		echo "</form>";
	}else{
		echo "<center>Nenhum pedido encontrado.</center>";
	}
	$msg_erro = '';
}

include "rodape.php" ?>
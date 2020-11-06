<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
$admin_privilegios="call_center";
include "autentica_admin.php";
include 'funcoes.php';

$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$busca      = $_GET["busca"];
	if (strlen($q)>2){

		$sql = "SELECT  tbl_posto.nome    AS nome       ,
					tbl_posto.cnpj        AS cpf        ,
					tbl_posto.posto       AS cliente
			FROM  tbl_posto
			JOIN  tbl_posto_consumidor USING(posto)
			WHERE tbl_posto_consumidor.fabrica = $login_fabrica ";

		$sql .= ($busca == "cnpj") ? " AND tbl_posto.cnpj LIKE '%$q%' " : " AND tbl_posto.nome ILIKE '%$q%' ";
		
		$sql .= " ORDER BY tbl_posto.nome ";

		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj    = trim(pg_result($res,$i,cpf));
				$nome    = trim(pg_result($res,$i,nome));
				$cliente = trim(pg_result($res,$i,cliente));
				echo "$cnpj|$nome|$cliente";
				echo "\n";
			}
		}
	}
	exit;
}

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
				$sql = "UPDATE tbl_pedido SET data_aprovacao = CURRENT_TIMESTAMP,status_pedido=null
						WHERE tbl_pedido.fabrica = $login_fabrica
						AND   tbl_pedido.pedido  = $Xpedido ";
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

if(strlen($_POST['btn_gera']) > 0) {
	$cnpj = trim($_POST["cnpj"]);
	$nome = trim($_POST["nome"]);
	if(strlen($cnpj) > 0) {
		system("/www/cgi-bin/filizola/gera-pedido-cliente.pl $cnpj",$ret);
	}else{
		$msg_erro = " Por favor, preenche CNPJ do cliente";
	}
	
}

$layout_menu = "callcenter";
$title = "Geração de pedido";

include "cabecalho.php";

?>

<? include "javascript_calendario_new.php"; ?>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>

<script>
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
					document.getElementById('linha_aux_'+i).style.backgroundColor = "#F0F0FF";
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
					document.getElementById('linha_aux_'+i).style.backgroundColor = "#FFFFFF";
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

$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}

	/* OFFF Busca pelo Código */
	$("#cnpj").autocomplete("<?echo $PHP_SELF.'?busca=cnpj'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#cnpj").result(function(event, data, formatted) {
		$("#nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 3,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#nome").result(function(event, data, formatted) {
		$("#cnpj").val(data[0]) ;
	});

});
</script>

<? 
if(strlen($msg_erro) > 0){
	echo "<p align='center' style='font-size: 14px; font-family: verdana;'><FONT COLOR='#FF0000'><b>$msg_erro</FONT></b></p>";
}

?>

<form name="frm_pesquisa" method="post" action="<?echo $PHP_SELF?>">

<TABLE width="450" align="center" border="0" cellspacing='0' cellpadding='0' class='PesquisaTabela'>

<caption>Gerar Pedido</caption>

<TBODY>
<TR>
	<TD>CNPJ<br>
		<input type='hidden' name='cliente'>
		<input class="frm" type="text" name="cnpj" id='cnpj' size="17" maxlength="18" value="<? echo $cnpj ?>"></TD>
	<TD>Cliente<br>
		<input class="frm" type="text" name="nome" id='nome' size="40" maxlength="50" value="<? echo $nome ?>"></TD>
</TR>

</tbody>
<tr>
	<td colspan="2" align='center'>
		<br>
		<input type='submit' name='btn_gera' value='Gerar Pedido'>
	</td>
</tr>
</table>
</form>

<? if(isset($cnpj)) {
		$sql = " SELECT	distinct tbl_pedido.pedido                                           ,
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
			JOIN        tbl_tabela           ON tbl_pedido.tabela      = tbl_tabela.tabela
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
			AND         tbl_pedido.data::date = current_date
			AND         tbl_pedido.data_aprovacao   IS NULL
			AND         tbl_pedido.exportado        IS NULL
			AND         (tbl_pedido.status_pedido = 18 OR tbl_pedido.status_pedido <> 17 OR tbl_pedido.status_pedido IS NULL )
			AND         tbl_posto.cnpj = '$cnpj'
			AND         origem_cliente ";
		$res = pg_exec($con,$sql);

		if(pg_numrows($res)>0){

			echo "<BR><BR><FORM name='frm_pesquisa2' METHOD='POST' ACTION='$PHP_SELF'>";

			echo "<table width='950' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 11px' bgcolor='#FFFFFF'>";
			echo "<tr>";
			echo "<td bgcolor='#485989'><img border='0' src='imagens_admin/selecione_todas.gif' onclick='javascript: checkaTodos()' alt='Selecionar todos' style='cursor: hand;' align='center'></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Pedido</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Data</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Cliente</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>CNPJ</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Tipo</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Origem (OS/Compra)</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Admin</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Condição</B></font></td>";
			echo "<td bgcolor='#485989'><font color='#FFFFFF'><B>Total</B></font></td>";
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
				$login          = pg_result($res, $x, login);
				$total = $total_com_ipi;

				$cores++;
				$cor = ($cores % 2 == 0) ? "#FEFEFE": '#E8EBEE';

				echo "<tr bgcolor='$cor' id='linha_$x'>";
				echo "<td align='center' width='0'>";
				echo "<input type='checkbox' name='check_$x' id='check_$x' value='".$pedido."' onclick=\"setCheck('check_$x','linha_$x','linha_aux_$x','$cor');\" ";
				if (strlen($msg_erro)>0){
					if (strlen($_POST["check_".$x])>0){
						echo " CHECKED ";
					}
				}
				echo ">";
				echo "</td>";
				echo "<td style='font-size: 9px; font-family: verdana' nowrap ><a href='pedido_admin_consulta.php?pedido=$pedido&alterar=1'  target='_blank'>".$pedido."</a></td>";
				echo "<td style='font-size: 9px; font-family: verdana' nowrap >".$data. "</td>";
				echo "<td align='left' style='font-size: 9px; font-family: verdana'>".$nome. "</td>";
				echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap title='".$cnpj." - ".$nome."'>".$cnpj."</td>";
				echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>". $tipo_pedido ."</td>";
				if($pedido_os =='t'){
					$pedido_os_descricao = " Ordem Serviço";
				}else{
					$pedido_os_descricao = " Compra Manual";
				}
				echo "<td align='center' style='font-size: 9px; font-family: verdana' nowrap>". $pedido_os_descricao ."</td>";
				echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>". $login."</td>";
				echo "<td align='left' style='font-size: 9px; font-family: verdana' nowrap>". $condicao ."</td>";
				echo "<td align='right' style='font-size: 9px; font-family: verdana' nowrap>". number_format($total,2,",",".") ."</td>";
				echo "</tr>";
			}
			echo "<input type='hidden' name='qtde_pedido' value='$x'>";

			echo "<tr>";
			echo "<td height='20' bgcolor='#485989' colspan='100%' align='left'> ";
			echo "&nbsp;&nbsp;&nbsp;&nbsp;<img border='0' src='imagens/seta_checkbox.gif' align='absmiddle'> &nbsp; <font color='#FFFFFF'><B>COM MARCADOS:</B></font> &nbsp;";
			echo "<select name='select_acao' size='1' class='frm'>";
			echo "<option value=''></option>";
			echo "<option value='aprovar'";  if ($_POST["select_acao"] == "aprovar")  echo " selected"; echo ">APROVAR</option>";
			echo "</select>";
			echo "<input type='hidden' name='btn_acao' value='Pesquisar'>";
			echo "</table>";
			echo "</form>";
		}
	}
?>


<? include "rodape.php" ?>
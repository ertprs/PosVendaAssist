<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';


$layout_menu = "cadastro";
$title = "Cadastramento de Serviços Realizados";
include 'cabecalho.php';

?>

<style type="text/css">

input { 
background-color: #ededed; 
font: 12px verdana;
color:#363738;
border:1px solid #969696;
}
</style>
<?
echo "<a name='topo'></a>";
echo "<form name='frm_relacionamento_familia' method='post' action='$PHP_SELF'>";
echo "<BR>";
echo "<table width='700' border='0' align='center' cellpadding='3' cellspacing='3' style='font-family: verdana; font-size: 12px'>";
if (strlen($msg_erro) > 0) { 
echo "<tr>";
	echo $msg_erro; 
echo "</tr>";
} 
echo "<tr>";
echo "<td align='left' bgcolor='#d9e2ef'><font color='#596d9b'><B>Relacionamento de FAMILIA com #######</B></font></td>";
echo "</tr>";

echo "<tr>";
echo "<td align='center'>";
echo "Familia: &nbsp;&nbsp;<select name='familia' style='width: 300px;'>";
	echo "<option value=''>ESCOLHA</option>";
	
	$sql="SELECT
				descricao, 
				familia 
		FROM tbl_familia 
		WHERE fabrica= $login_fabrica 
		ORDER BY descricao";
		$res = @pg_exec($con, $sql);
		if (pg_numrows($res) > 0) {
			for($i=0; $i< pg_numrows($res); $i++ ){
			$cod_familia  = trim(pg_result($res,$i,familia));
			$descricao_familia = trim(pg_result($res, $i, descricao));
			echo "<option value='$cod_familia'>$descricao_familia</option>";
			}
		}	
echo "</select>&nbsp;&nbsp;<input type='submit' name='btn_carregar' value='Carregar'>";
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td><a href='#defeito_reclamado'>Defeito Reclamado</a>&nbsp&nbsp - &nbsp&nbsp<a href='#defeito_constatado'>Defeito Constatado</a>&nbsp&nbsp - &nbsp&nbsp<a href='#causa_defeito'>Causa do Defeito</a>&nbsp&nbsp - &nbsp&nbsp<a href='#servico_realizado'>Serviço Realizado</a>";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "<BR>";

$btn_carregar= $_POST['btn_carregar'];
if(strlen($btn_carregar)>0){


echo "<table width='700' border='0' align='center' cellpadding='3' cellspacing='3' style='font-family: verdana; font-size: 12px'>";
echo "<tr>";
echo "<td align='left' colspan='2' bgcolor='#d9e2ef'><a name='defeito_reclamado'>Defeito Reclamado</a></td>";
echo "</tr>";
echo "<tr>";

		$sql="SELECT
 				descricao, 
				defeito_reclamado 
			FROM tbl_defeito_reclamado 
			WHERE fabrica= $login_fabrica 
			ORDER BY descricao";
	$res = @pg_exec($con, $sql);
	if (pg_numrows($res) > 0) {	
		for($i=0; $i< pg_numrows($res); $i++ ){
		$cod_defeito_reclamado = trim(pg_result($res,$i,defeito_reclamado));
		$descricao_defeito_reclamado = trim(pg_result($res, $i, descricao));
		echo "<TD align='left'><input type='checkbox' name='defeito_reclamado_$i' value='$cod_defeito_reclamado'>$descricao_defeito_reclamado</td>";
		if(($i % 2)>0){echo "</tr>";}
		}
			echo "<input type='hidden' name='qtde_defeito_reclamado' value='$i'>";
		}else{
			echo "Nenhuma Familia encontrada para esta Fábrica";
		}
echo "</tr>";

//botoes acao
//botoes acao
echo "<TR>";
echo "<TD colspan='2' align='center'><BR><input type='submit' name='btn_acao' value='gravar'>&nbsp;&nbsp;<input type='reset' name='btn_acao' value='limpar'>";
echo "</TD>";
echo "</TR>";
//botoes acao
//botoes acao

echo "<tr>";
echo "<td align='right' colspan='2'><a name='defeito_constatado'></a> <a href='#topo'><font size='1'>Voltar ao Topo</font></A></td>";
echo "</tr>";
echo "<tr>";
echo "<td align='left' colspan='2'  bgcolor='#d9e2ef'>Defeito Constatado</td>";
echo "</tr>";
echo "<tr>";
		$sql="SELECT
 				descricao, 
				defeito_constatado 
			FROM tbl_defeito_constatado
			WHERE fabrica= $login_fabrica 
			ORDER BY descricao";
	$res = @pg_exec($con, $sql);
	if (pg_numrows($res) > 0) {	
		for($i=0; $i< pg_numrows($res); $i++ ){
		$cod_defeito_constatado = trim(pg_result($res,$i,defeito_constatado));
		$descricao_defeito_constatado = trim(pg_result($res, $i, descricao));
		echo "<TD align='left'><input type='checkbox' name='defeito_constatado_$i' value='$cod_defeito_constatado'>$descricao_defeito_constatado</td>";
		if(($i % 2)>0){echo "</tr>";}
		}
		echo "<input type='hidden' name='qtde_defeito_constatado' value='$i'>";
		}else{
			echo "Nenhuma Familia encontrada para esta Fábrica";
		}
echo "</tr>";

//botoes acao
//botoes acao
echo "<TR>";
echo "<TD colspan='2' align='center'><BR><input type='submit' name='btn_acao' value='gravar'>&nbsp;&nbsp;<input type='reset' name='btn_acao' value='limpar'>";
echo "</TD>";
echo "</TR>";
//botoes acao
//botoes acao

echo "<tr>";
echo "<td align='right' colspan='2'><a name='causa_defeito'></a><a href='#topo'><font size='1'>Voltar ao Topo</font></A></td>";
echo "</tr>";
echo "<tr>";
echo "<td align='left' colspan='2'  bgcolor='#d9e2ef'>Causa do Defeito</td>";
echo "</tr>";
echo "<tr>";
		$sql="SELECT
 				descricao, 
				causa_defeito 
			FROM tbl_causa_defeito 
			WHERE fabrica= $login_fabrica 
			ORDER BY descricao";
	$res = @pg_exec($con, $sql);
	if (pg_numrows($res) > 0) {	
		for($i=0; $i< pg_numrows($res); $i++ ){
		$cod_causa_defeito = trim(pg_result($res,$i,causa_defeito));
		$descricao_causa_defeito = trim(pg_result($res, $i, descricao));
		echo "<TD align='left'><input type='checkbox' name='causa_defeito_$i' value='$cod_causa_defeito'>$descricao_causa_defeito</td>";
		if(($i % 2)>0){echo "</tr>";}
		}		
		echo "<input type='hidden' name='qtde_causa_defeito' value='$i'>";
		}
echo "</tr>";

//botoes acao
//botoes acao
echo "<TR>";
echo "<TD colspan='2' align='center'><BR><input type='submit' name='btn_acao' value='gravar'>&nbsp;&nbsp;<input type='reset' name='btn_acao' value='limpar'>";
echo "</TD>";
echo "</TR>";
//botoes acao
//botoes acao

echo "<tr>";
echo "<td align='right' colspan='2'><a name='servico_realizado'></A><a href='#topo'><font size='1'>Voltar ao Topo</font></A></td>";
echo "</tr>";
echo "<tr>";
echo "<td align='left' colspan='2'  bgcolor='#d9e2ef'>Serviço Realizado</td>";
echo "</tr>";
echo "<tr>";
	$sql="SELECT
 				descricao, 
				servico_realizado 
			FROM tbl_servico_realizado 
			WHERE fabrica= $login_fabrica 
			ORDER BY descricao";
	$res = @pg_exec($con, $sql);
	if (pg_numrows($res) > 0) {	
		for($i=0; $i< pg_numrows($res); $i++ ){
		$cod_servico_realizado = trim(pg_result($res,$i,servico_realizado));
		$descricao_servico_realizado = trim(pg_result($res, $i, descricao));
		echo "<TD align='left'><input type='checkbox' name='servico_realizado_$i' value='$cod_servico_realizado'>$descricao_servico_realizado</td>";
		if(($i % 2)>0){echo "</tr>";}
		}
		echo "<input type='hidden' name='qtde_servico_realizado' value='$i'>";
		}
echo "</tr>";
echo "</table>";

//botoes acao
//botoes acao
echo "<table width='500' border='0' cellspacing='2' cellpadding='3' align='center' style='font-family: verdana; font-size: 12px'>";
echo "<TR>";
echo "<TD align='center'><BR><input type='submit' name='btn_acao' value='gravar'>&nbsp;&nbsp;<input type='reset' name='btn_acao' value='limpar'>";
echo "</TD>";
echo "</TR>";
echo "</TABLE>";
//botoes acao
//botoes acao
echo "</form>";
}

echo "<BR><BR><BR>";


echo "<table width='500' border='0' cellspacing='2' cellpadding='3' align='center' style='font-family: verdana; font-size: 12px'>";
echo "<TR>";
echo "<TD align='center'>
<a href='linha_cadastro.php'>Linha</a><BR>
<a href='familia_cadastro-tk.php'>Familia</a><BR>
<a href='defeito_reclamado_cadastro-tk.php'>Defeito Reclamado</a><BR>
<a href='defeito_constatado_cadastro-tk.php'>Defeito Constatado</a><BR>
<a href='defeito_reclamado_cadastro-tk.php'>Defeito Reclamado</a><BR>
<a href='causa_defeito_cadastro-tk.php'>Causa Defeito</a><BR>
<a href='servico_realizado_cadastro-tk.php'>Serviço Realizado</a><BR>";
echo "</TD>";
echo "</TR>";
echo "</TABLE>";


include "rodape.php";
?>
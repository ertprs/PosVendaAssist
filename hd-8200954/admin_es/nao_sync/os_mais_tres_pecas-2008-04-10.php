<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

$layout_menu = "auditoria";
$titulo = "REPORTES DE OS CON 3 PIEZAS O MAS";
$title = "REPORTES DE OS CON 3 PIEZAS O MAS";

include 'cabecalho.php';
include "javascript_pesquisas.php"; 
?>
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language="javascript" src="js/assist.js"></script>
<script language='javascript' src='ajax.js'></script>

<?
$meses = array(1 => "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");

echo "<form name='frm_consulta' method='post' action='$PHP_SELF'>";
echo "<BR><BR><BR><table border='0' cellspacing='0' cellpadding='6' align='center' bgcolor='#596D9B' style='font-family: verdana; font-size: 12px'>";
echo "<tr>";
echo "<td colspan='2'><font color='#FFFFFF'><B>Relación de las OS con más de 3 piezas</B></FONT>";
echo "</td>";
echo "</tr>";
echo "<tr bgcolor='#D9E2EF'>";
echo "<td >Mes: ";
echo "<select name='mes' size='1' class='frm'>";
echo "<option value=''></option>";
        for ($i = 1 ; $i <= count($meses) ; $i++) {
        echo "<option value='$i'";
        if ($mes == $i) echo " selected";
        echo ">" . $meses[$i] . "</option>";
        }
echo "</select>";
echo "</td>";
echo "<td>Año: ";
echo "<select name='ano' size='1' class='frm'>";
echo "<option value=''></option>";
for ($i = 2003 ; $i <= date("Y") ; $i++) {
echo "<option value='$i'";
if ($ano == $i) echo " selected";
echo ">$i</option>";
}
echo "</select>";

echo "</td>";
echo "</tr>";

echo "<tr bgcolor='#D9E2EF'>";
echo "<td>";
echo "Código del Servicio: <input type='text' name='codigo_posto' size='8' value='$codigo_posto' class='frm'>";
?>
<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'codigo')">
<?
echo "</td>";
echo "<td>";
echo "Nombre del Servicio: <input type='text' name='posto_nome' size='30' value='$posto_nome' class='frm'>";
?>
<img border="0" src="imagens_admin/btn_lupa.gif" style="cursor: hand;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome')">
<?
//echo "<img border='0' src='imagens_admin/btn_lupa.gif' style='cursor: hand;' align='absmiddle' alt='Clique aqui para pesquisar postos pelo código' onclick='javascript: fnc_pesquisa_posto (document.frm_consulta.codigo_posto, document.frm_consulta.posto_nome, 'nome')'>";
echo "</td>";
echo "</tr>";

echo "<tr bgcolor='#D9E2EF'>";
echo "<td colspan='2'><input type='submit' name='btn_acao' value='Exhibir'>";
echo "</td>";
echo "</tr>";
echo "</table>";


$mes= $_POST['mes'];
$ano= $_POST['ano'];

$codigo_posto= $_POST['codigo_posto'];
$posto_nome= $_POST['posto_nome'];

//tratamento de datas
$data_inicial = date("Y-m-d", mktime(0, 0, 0, $mes, 1, $ano));
$data_final = date("Y-m-t", mktime(0, 0, 0, $mes, 1, $ano));


#$data_inicial = '2006-08-22 00:00:00';
#$data_final   = '2006-08-22 23:59:59';



$btn_acao= $_POST['btn_acao'];
if (strlen($btn_acao)>0){


if (strlen($mes)==0){
echo "<script>alert('Por favor elija el mês');</script> ";
exit;
}
if (strlen($ano)==0){
echo "<script>alert('Por favor elija el ano');</script> ";
exit;
}








                
	$sql = "SELECT tbl_posto_fabrica.codigo_posto                   ,
			tbl_os.os, tbl_posto.nome                               ,
			tbl_os.sua_os                                           ,
			TO_CHAR (tbl_os.data_abertura,'DD/MM/YYYY') AS abertura ,
 			tbl_produto.descricao                                   ,
			tbl_os.produto
			FROM tbl_os
			JOIN tbl_posto ON tbl_os.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto 
			AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
			JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
			JOIN tbl_produto_pais ON tbl_produto.produto = tbl_produto_pais.produto AND tbl_produto_pais.pais = '$login_pais'
			WHERE tbl_os.os IN (
				SELECT tbl_os.os 
				FROM tbl_os 
				JOIN tbl_os_produto USING (os) 
				JOIN tbl_os_item USING (os_produto)
				JOIN tbl_servico_realizado USING (servico_realizado)
				JOIN tbl_posto         ON tbl_os.posto = tbl_posto.posto
				JOIN tbl_posto_fabrica ON tbl_os.posto = tbl_posto_fabrica.posto 
				AND tbl_os.fabrica = tbl_posto_fabrica.fabrica
				WHERE tbl_os.fabrica = $login_fabrica";
				if(strlen($codigo_posto)>0) $sql .=" AND tbl_posto_fabrica.codigo_posto = $codigo_posto";
				$sql .= "AND   tbl_os_item.digitacao_item BETWEEN '$data_inicial' AND '$data_final' 
				AND   tbl_servico_realizado.troca_de_peca
				AND   tbl_posto.pais = '$login_pais'
				GROUP BY tbl_os.os
				HAVING SUM (tbl_os_item.qtde) >= 3 )
			ORDER BY tbl_posto.nome, tbl_produto.descricao, tbl_os.sua_os";

	$res = pg_exec ($con,$sql);
	
	$qtde_os = pg_numrows ($res);
	if($qtde_os>0){
	echo "<BR><BR><center>Relación de OS con 3 o más piezas en el pedido</center><BR>Total de $qtde_os OS encuentradas";
	echo "<BR><BR><table border='0' cellpadding='4' cellspacing='1' bgcolor='#9FB5CC' align='center' style='font-family: verdana; font-size: 11px'>";
	echo "<tr>";
	echo "<td><B>OS</B></td>";
	echo "<td><B></B></td>";
	echo "<td><B>Nombre del Servicio</B></td>";
	echo "<td><B>Herramienta</B></td>";
	echo "</td>";
	echo "</tr>";
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$os           = pg_result ($res,$i,os);
		$sua_os       = pg_result ($res,$i,sua_os);
		$codigo_posto = pg_result ($res,$i,codigo_posto);
		$nome         = pg_result ($res,$i,nome);
		$produto      = pg_result ($res,$i,produto);

		$descricao    = pg_result ($res,$i,descricao);

			$sql_idioma = "SELECT * FROM tbl_produto_idioma WHERE produto = $produto AND upper(idioma) = 'ES'";
		
			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$descricao  = trim(@pg_result($res_idioma,0,descricao));
			}

		$cor = "#FFFFFF"; 
		if ($i % 2 == 0) $cor = '#D2E6FF';
		echo "<tr bgcolor='$cor'>";
		echo "<td><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></td>";
		echo "<td align='left'>$codigo_posto</td>";
		echo "<td align='left'>$nome</td>";
		echo "<td align='left'>$descricao</td>";
		echo "</td>";
		echo "</tr>";
	}
	echo "</table>";
	}else{
	echo "<center>Ningun resultado encontrado</center>";
	}
}


include "rodape.php";


?>


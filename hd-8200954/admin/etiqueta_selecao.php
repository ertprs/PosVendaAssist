<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="gerencia";
include "autentica_admin.php";

$layout_menu = "gerencia";
$title = "ETIQUETAS DE OSs";

?>

<style type="text/css">
td {
	text-align:  left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size:   11px;
	font-weight: normal;
	color:       #000000;
}
</style>

<body bgcolor="#FFFFFF" text="#000000" topmargin=0 leftmargin=0 marginwidth=0 marginheight=0>

<?
$btn_acao = trim($_POST["btn_acao"]);

if (strlen($btn_acao) > 0) {

	// INICIALIZA A SESSÃO
	session_start();
	
	// PEGA A CHAVE DO ARRAY
	$chave = array_keys($sess_os);
	
	// faz um FOR para ir pegando as OSs selecionadas
	$os_conc = '';
	
	for($i=0; $i<sizeof($chave); $i++) {
		$indice = $chave[$i];
	
		if (trim($_POST['os_etiqueta_'.$i]) > 0){
			$os_conc .= trim($_POST['os_etiqueta_'.$i]);
			if ($i < $total_os) $os_conc .= ", ";
			$etiqueta[$indice][$os] = $_POST['os_etiqueta_'.$i];
		}
	}
	
	$_SESSION['sess_os'] = $etiqueta; // grava sessao
	
	if (strlen($os_conc) > 0 ) $os_conc .= "0"; // acerta concatenacao de OSs
	
	$sql =	"SELECT tbl_os.consumidor_revenda                                      ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura    ,
					tbl_produto.referencia                                         ,
					tbl_produto.descricao                                          ,
					tbl_posto.nome                             AS posto_nome       ,
					tbl_os.cliente                                                 ,
					tbl_os.revenda                                                 ,
					tbl_cliente.nome                           AS cliente_nome     ,
					tbl_cliente.endereco                       AS cliente_endereco ,
					tbl_cliente.numero                         AS cliente_numero   ,
					tbl_cliente.bairro                         AS cliente_bairro   ,
					tbl_cliente.cep                            AS cliente_cep      ,
					tbl_cidade_cliente.nome                    AS cliente_cidade   ,
					tbl_cidade_cliente.estado                  AS cliente_estado   ,
					tbl_revenda.nome                           AS revenda_nome     ,
					tbl_revenda.endereco                       AS revenda_endereco ,
					tbl_revenda.numero                         AS revenda_numero   ,
					tbl_revenda.bairro                         AS revenda_bairro   ,
					tbl_revenda.cep                            AS revenda_cep      ,
					tbl_cidade_revenda.nome                    AS revenda_cidade   ,
					tbl_cidade_revenda.estado                  AS revenda_estado
			FROM tbl_os
			JOIN tbl_produto ON  tbl_produto.produto = tbl_os.produto
			JOIN tbl_posto   ON  tbl_posto.posto     = tbl_os.posto
			LEFT JOIN tbl_cliente on tbl_cliente.cliente = tbl_os.cliente
			LEFT JOIN tbl_revenda on tbl_revenda.revenda = tbl_os.revenda
			LEFT JOIN tbl_cidade tbl_cidade_cliente on tbl_cidade_cliente.cidade =  tbl_cliente.cidade
			LEFT JOIN tbl_cidade tbl_cidade_revenda on tbl_cidade_revenda.cidade =  tbl_revenda.cidade
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_os.os in ($os_conc)";
	$res = pg_exec($con,$sql);
	
	$total_etiqueta = pg_numrows($res);
	
	echo "<TABLE align='left' class='table_line' width='780' align='left' border=0 cellpadding='2' cellspacing='4'>";
	
	echo "<tr>";
	echo "<td nowrap>OS</td>";
	echo "<td>REFERENCIA</td>";
	echo "<td>DESCRICAO</td>";
	echo "<td>POSTO</td>";
	echo "<td>ABERTURA</td>";
	echo "<td nowrap>CONSUMIDOR</td>";
	echo "</tr>";

	for($j=0; $j<$total_etiqueta; $j++) {
		$consumidor_revenda = trim(pg_result($res,$j,consumidor_revenda));
		$data_abertura      = trim(pg_result($res,$j,data_abertura));
		$produto_referencia = trim(pg_result($res,$j,referencia));
		$produto_descricao  = trim(pg_result($res,$j,descricao));
		$posto_nome         = trim(pg_result($res,$j,posto_nome));
		
		if ($consumidor_revenda == "C" AND strlen($cliente) > 0) {
			$nome     = trim(pg_result($res,$j,cliente_nome));
		}else{
			$nome     = trim(pg_result($res,$j,revenda_nome));
		}
		
		$produto_referencia = strtoupper($produto_referencia);
		$produto_descricao  = strtoupper($produto_descricao);
		$posto_nome         = strtoupper($posto_nome);
		$nome               = strtoupper($nome);
		
		echo "<tr>";
		echo "<td nowrap>$os</td>";
		echo "<td>$produto_referencia</td>";
		echo "<td>$produto_descricao</td>";
		echo "<td>$posto_nome</td>";
		echo "<td>$data_abertura</td>";
		echo "<td nowrap>$nome</td>";
		echo "</tr>";
		
	}
	echo "</TABLE>";
}
?>

</body>
<script language="JavaScript">
//	window.print();
</script>

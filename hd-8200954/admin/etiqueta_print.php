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
	$sql =	"SELECT tbl_os.consumidor_revenda                                      ,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura    ,
					tbl_produto.referencia                                         ,
					tbl_produto.descricao                                          ,
					tbl_posto.nome                             AS posto_nome       ,
					tbl_os.revenda                                                 ,
					tbl_os.consumidor_nome                     AS cliente_nome     ,
					tbl_os.consumidor_endereco                 AS cliente_endereco ,
					tbl_os.consumidor_numero                   AS cliente_numero   ,
					tbl_os.consumidor_bairro                   AS cliente_bairro   ,
					tbl_os.consumidor_cep                      AS cliente_cep      ,
					tbl_os.consumidor_cidade                   AS cliente_cidade   ,
					tbl_os.consumidor_estado                   AS cliente_estado   ,
					tbl_revenda.nome                           AS revenda_nome     ,
					tbl_revenda.endereco                       AS revenda_endereco ,
					tbl_revenda.numero                         AS revenda_numero   ,
					tbl_revenda.bairro                         AS revenda_bairro   ,
					tbl_revenda.cep                            AS revenda_cep      ,
					tbl_cidade_revenda.nome                    AS revenda_cidade   ,
					tbl_cidade_revenda.estado                  AS revenda_estado
			FROM tbl_etiqueta_os
			JOIN tbl_os ON tbl_os.os = tbl_etiqueta_os.os
						AND tbl_os.fabrica = $login_fabrica
			JOIN tbl_produto ON  tbl_produto.produto = tbl_os.produto
			JOIN tbl_posto   ON  tbl_posto.posto     = tbl_os.posto
			LEFT JOIN tbl_cliente on tbl_cliente.cliente = tbl_os.cliente
			LEFT JOIN tbl_revenda on tbl_revenda.revenda = tbl_os.revenda
			LEFT JOIN tbl_cidade tbl_cidade_cliente on tbl_cidade_cliente.cidade =  tbl_cliente.cidade
			LEFT JOIN tbl_cidade tbl_cidade_revenda on tbl_cidade_revenda.cidade =  tbl_revenda.cidade
			WHERE tbl_os.fabrica = $login_fabrica
			AND   tbl_etiqueta_os.impressao IS NULL;";
	$res = pg_exec($con,$sql);
	
	$total_etiqueta = pg_numrows($res);
	echo "<table width='780' border='0' cellpadding='2' cellspacing='4'>";
	
	//Foi alterado para mostrar os dados do consumidor - HD 19954
	$i = 1;
	for($j = 0 ; $j < $total_etiqueta ; $j++) {
		$consumidor_revenda = trim(pg_result($res,$j,consumidor_revenda));
		$data_abertura      = trim(pg_result($res,$j,data_abertura));
		$produto_referencia = trim(pg_result($res,$j,referencia));
		$produto_descricao  = trim(pg_result($res,$j,descricao));
		$posto_nome         = trim(pg_result($res,$j,posto_nome));
		$revenda            = trim(pg_result($res,$j,revenda));
		
		if ($consumidor_revenda == "C") {
			$nome     = trim(pg_result($res,$j,cliente_nome));
			$endereco = trim(pg_result($res,$j,cliente_endereco));
			$numero   = trim(pg_result($res,$j,cliente_numero));
			$bairro   = trim(pg_result($res,$j,cliente_bairro));
			$cidade   = trim(pg_result($res,$j,cliente_cidade));
			$cep      = trim(pg_result($res,$j,cliente_cep));
			$estado   = trim(pg_result($res,$j,cliente_estado));
		}else{
			$nome     = trim(pg_result($res,$j,revenda_nome));
			$endereco = trim(pg_result($res,$j,revenda_endereco));
			$numero   = trim(pg_result($res,$j,revenda_numero));
			$bairro   = trim(pg_result($res,$j,revenda_bairro));
			$cidade   = trim(pg_result($res,$j,revenda_cidade));
			$cep      = trim(pg_result($res,$j,revenda_cep));
			$estado   = trim(pg_result($res,$j,revenda_estado));
		}

		$produto_referencia = strtoupper($produto_referencia);
		$produto_descricao  = strtoupper($produto_descricao);
		$posto_nome         = strtoupper($posto_nome);
		$nome               = strtoupper($nome);
		$endereco           = strtoupper($endereco);
		$bairro             = strtoupper($bairro);
		$cidade             = strtoupper($cidade);
		$estado             = strtoupper($estado);



		echo "<TR>";
		echo "<TD width='52%' nowrap>Sr.(a) <B>$nome</B><br>";
		echo "Seu produto - <B>$produto_descricao</B><br>";
		echo "Modelo <B>$produto_referencia</B><br>";
		echo "Posto - <B>$posto_nome</B><br>";
		echo "Data: <B>$data_abertura</B></TD>";
		echo "<TD nowrap>Cons.: <B>$nome</B><br>";
		echo "Endereço: <B>$endereco</B> &nbsp; Nº: <B>$numero</B> <br>Bairro: <B>$bairro</B><br>";
		echo "Cidade: <B>$cidade / $estado</B><br>";
		echo "CEP: <B>".substr($cep,0,5)."-".substr($cep,5,3)."</B></TD>";
		echo "</TR>";
		if ($i % 10 != 0) echo "<tr><td colspan='2' height='14'>&nbsp;</td></tr>";
		
		$i++;
	}
	echo "</TABLE>";

?>

</body>

<?
$sql =	"UPDATE tbl_etiqueta_os SET
			impressao = current_timestamp
		WHERE impressao IS NULL;";
$res = pg_exec($con,$sql);
?>

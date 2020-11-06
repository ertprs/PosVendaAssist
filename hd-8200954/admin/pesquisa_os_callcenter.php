<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Pesquisa Consumidores.. </title>
<meta http-equiv=pragma content=no-cache>
</head>

<body style="margin: 0px 0px 0px 0px;">
<img src="imagens/pesquisa_consumidor.gif">
<script type="text/javascript">
	function openOS(os) {		
		window.open('os_press.php?os=' + os, '_blank');
		this.close();
	}
</script>
<?

if (strlen($_GET["consumidor_nome"]) > 0) {
	$consumidor_cpf = strtoupper (trim ($_GET["consumidor_cpf"]));

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nome do consumidor</b>: <i>$nome</i></font>";
	echo "<p>";

	$sql = "SELECT  OS.os                                   ,
					OS.consumidor_nome                      ,
					OS.consumidor_cpf                       ,
					OS.serie                                ,
					PR.referencia      AS produto_referencia,
					PR.descricao       AS produto_descricao ,
					PO.nome            AS posto_nome        ,
					PF.codigo_posto    AS posto_codigo
			FROM tbl_os            OS
			JOIN tbl_posto         PO ON OS.posto   = PO.posto
			JOIN tbl_posto_fabrica PF ON OS.posto   = PF.posto AND OS.fabrica = PF.fabrica
			JOIN tbl_produto       PR ON OS.produto = PR.produto
			WHERE fabrica        = $login_fabrica
			AND   consumidor_cpf = '%$consumidor_cpf%'";
	$res = pg_exec ($con,$sql);


	if (pg_numrows ($res) == 0) {
		echo "<h1>Consumidor '$nome' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}elseif (strlen($_GET["consumidor_cpf"]) > 0) {

	$consumidor_cpf = $cpf = strtoupper (trim ($_GET["consumidor_cpf"]));
	$consumidor_cpf = str_replace (".","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("-","",$consumidor_cpf);
	$consumidor_cpf = str_replace ("/","",$consumidor_cpf);
	$consumidor_cpf = str_replace (" ","",$consumidor_cpf);
	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>CPF do consumidor</b>: <i>$cpf</i></font>";
	echo "<p>";

	$sql = "SELECT  OS.os                                                 ,
					OS.sua_os                                             ,
					OS.consumidor_nome                                    ,
					OS.consumidor_cpf                                     ,
					OS.consumidor_cep                                     ,
					OS.consumidor_fone                                    ,
					OS.consumidor_endereco                                ,
					OS.consumidor_numero                                  ,
					OS.consumidor_complemento                             ,
					OS.consumidor_bairro                                  ,
					OS.consumidor_cidade                                  ,
					OS.consumidor_estado                                  ,
					OS.serie                                              ,
					OS.nota_fiscal                                        ,
					TO_CHAR(OS.data_nf,'DD/MM/YYYY') AS data_nf           ,
						PR.referencia                    AS produto_referencia,
						PR.descricao                     AS produto_descricao ,
					PO.posto                         AS posto             ,
					PO.nome                          AS posto_nome        ,
					PF.codigo_posto                  AS posto_codigo
			FROM tbl_os            OS
			JOIN tbl_posto         PO ON OS.posto   = PO.posto
			JOIN tbl_posto_fabrica PF ON OS.posto   = PF.posto AND OS.fabrica = PF.fabrica
			JOIN tbl_produto       PR ON OS.produto = PR.produto
			WHERE OS.fabrica        = $login_fabrica
			AND   consumidor_cpf = '$consumidor_cpf'
			";

			if($login_fabrica == 101){
				$sql .= " UNION			
 			SELECT  hce.os,
					hce.sua_os,
					hce.nome AS consumidor_nome, 
					hce.cpf AS consumidor_cpf,
					hce.cep AS consumidor_cep, 
					hce.fone AS consumidor_fone,
					hce.endereco AS consumidor_endereco,
					hce.numero AS consumidor_numero,                  
					hce.complemento AS consumidor_complemento, 
					hce.bairro AS consumidor_bairro, 
					c.nome AS consumidor_cidade,
					c.estado AS consumidor_estado,
					hce.serie,
					hce.nota_fiscal, 
					TO_CHAR(hce.data_nf,'DD/MM/YYYY') AS data_nf,
					PR.referencia AS produto_referencia,
					PR.descricao AS produto_descricao ,
					PO.posto AS posto,
					PO.nome  AS posto_nome,
                    PF.codigo_posto AS posto_codigo
			FROM tbl_hd_chamado_extra hce
				join tbl_cidade c on c.cidade = hce.cidade
				JOIN tbl_hd_chamado    HC on HC.hd_chamado = hce.hd_chamado
				JOIN tbl_posto         PO ON hce.posto   = PO.posto
				JOIN tbl_posto_fabrica PF ON hce.posto   = PF.posto  and hc.fabrica = PF.fabrica
				JOIN tbl_produto       PR ON hce.produto = PR.produto
			WHERE cpf = '$consumidor_cpf' and hc.fabrica = $login_fabrica ";
			}


	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
		echo "<h1>CPF/CNPJ '$cpf' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}elseif (strlen($_GET["nota_fiscal"]) > 0) {

	$nota_fiscal = strtoupper (trim ($_GET["nota_fiscal"]));

	echo "<font face='Arial, Verdana, Times, Sans' size='2'>Pesquisando por <b>nota fiscal do consumidor</b>: <i>$nota_fiscal</i></font>";
	echo "<p>";

	$sql = "SELECT  OS.os                                                 ,
					OS.sua_os                                             ,
					OS.consumidor_nome                                    ,
					OS.consumidor_cpf                                     ,
					OS.consumidor_cep                                     ,
					OS.consumidor_fone                                    ,
					OS.consumidor_endereco                                ,
					OS.consumidor_numero                                  ,
					OS.consumidor_complemento                             ,
					OS.consumidor_bairro                                  ,
					OS.consumidor_cidade                                  ,
					OS.consumidor_estado                                  ,
					OS.serie                                              ,
					OS.nota_fiscal                                        ,
					TO_CHAR(OS.data_nf,'DD/MM/YYYY') AS data_nf           ,
					PR.referencia                    AS produto_referencia,
					PR.descricao                     AS produto_descricao ,
					PO.posto                         AS posto             ,
					PO.nome                          AS posto_nome        ,
					PF.codigo_posto                  AS posto_codigo
			FROM tbl_os            OS
			JOIN tbl_posto         PO ON OS.posto   = PO.posto
			JOIN tbl_posto_fabrica PF ON OS.posto   = PF.posto AND OS.fabrica = PF.fabrica
			JOIN tbl_produto       PR ON OS.produto = PR.produto
			WHERE OS.fabrica        = $login_fabrica
			AND   nota_fiscal = '$nota_fiscal'";

	$res = pg_exec ($con,$sql);
	if (pg_numrows ($res) == 0) {
		echo "<h1>Nota Fiscal '$nota_fiscal' não encontrado</h1>";
		echo "<script language='javascript'>";
		echo "setTimeout('window.close()',2500);";
		echo "</script>";
		exit;
	}
}

/*if (pg_numrows($res) == 1) {
	echo "<script language='javascript'>";
	echo "cliente.value     ='".pg_result($res,0,cliente)."'; ";
	echo "nome.value        ='".str_replace("'","",pg_result($res,0,nome))."'; ";
	echo "cpf.value         ='".pg_result($res,0,cpf)."'; ";
	echo "rg.value          ='".pg_result($res,0,rg)."'; ";
	echo "cidade.value      ='".pg_result($res,0,nome_cidade)."'; ";
	echo "fone.value        ='".pg_result($res,0,fone)."'; ";
	echo "endereco.value    ='".str_replace("'","",pg_result($res,0,endereco))."'; ";
	echo "numero.value      ='".pg_result($res,0,numero)."'; ";
	echo "complemento.value ='".pg_result($res,0,complemento)."'; ";
	echo "bairro.value      ='".pg_result($res,0,bairro)."'; ";
	echo "cep.value         ='".pg_result($res,0,cep)."'; ";
	echo "estado.value      ='".pg_result($res,0,estado)."'; ";
	if ($_GET["proximo"] == "t") echo "proximo.focus(); ";
	echo "this.close(); ";
	echo "</script>";
	exit;
}
*/
if (pg_numrows ($res) > 0 ) {
	echo "<script language='JavaScript'>";
	echo "<!--\n";
	echo "this.focus();\n";
	echo "// -->\n";
	echo "</script>\n";

	echo "<table width='100%' border='0' style='font-size:10px;font-family:Verdana;'>\n";

		echo "<TR bgcolor='#CCCCCC'>";
			echo "<TD><B>OS</B></TD>";
			echo "<TD><B>Produto</B></TD>";
			echo "<TD><B>Série</B></TD>";
			echo "<TD><B>Posto</B></TD>";
		echo "</TR>";

	$contador_res = pg_numrows($res);
	for ( $i = 0 ; $i < $contador_res; $i++ ) {
		$os                      = trim(pg_result($res,$i,os));
		$sua_os                  = trim(pg_result($res,$i,sua_os));
		$consumidor_nome         = trim(pg_result($res,$i,consumidor_nome));
		$consumidor_cpf          = trim(pg_result($res,$i,consumidor_cpf));
		$consumidor_cep          = trim(pg_result($res,$i,consumidor_cep));
		$consumidor_fone         = trim(pg_result($res,$i,consumidor_fone));
		$consumidor_endereco     = trim(pg_result($res,$i,consumidor_endereco));
		$consumidor_numero       = trim(pg_result($res,$i,consumidor_numero));
		$consumidor_complemento  = trim(pg_result($res,$i,consumidor_complemento));
		$consumidor_bairro       = trim(pg_result($res,$i,consumidor_bairro));
		$consumidor_cidade       = trim(pg_result($res,$i,consumidor_cidade));
		$consumidor_estado       = trim(pg_result($res,$i,consumidor_estado));
		$serie              = trim(pg_result($res,$i,serie));
		$nota_fiscal        = trim(pg_result($res,$i,nota_fiscal));
		$nf_data            = trim(pg_result($res,$i,data_nf));
		$produto_referencia = trim(pg_result($res,$i,produto_referencia));
		$produto_descricao  = trim(pg_result($res,$i,produto_descricao));
		$posto              = trim(pg_result($res,$i,posto));
		$posto_nome         = trim(pg_result($res,$i,posto_nome));
		$posto_codigo       = trim(pg_result($res,$i,posto_codigo));

		echo "<tr>\n";
		echo "<td><a href='#' onclick='openOS($os)'>$sua_os</a></td>\n";
		echo "<td title='$produto_referencia - $produto_descricao'>$produto_descricao</td>\n";
		echo "<td>$serie</td>\n";
		echo "<td>$posto_nome</td>\n";

		echo "<td>\n";
		if ($_GET['forma'] == 'reload') {
			$retorno = $_GET['retorno'];
			echo "<a href=\"javascript: opener.document.location = '$retorno?cliente=$cliente' ; this.close() ;\" > " ;
		}else{
			echo "<a href=\"javascript: produto_referencia.value='$produto_referencia'; produto_nome.value='$produto_descricao';produto_serie.value='$serie';produto_nf.value='$nota_fiscal';produto_nf_data.value='$nf_data';sua_os.value='$sua_os';posto_codigo.value='$posto_codigo';posto_nome.value='$posto_nome';";
			// HD 14549
			if($login_fabrica ==11){
				echo "consumidor_nome.value='$consumidor_nome'; consumidor_cep.value='$consumidor_cep'; consumidor_fone.value='$consumidor_fone';consumidor_endereco.value='$consumidor_endereco'; consumidor_numero.value='$consumidor_numero'; consumidor_complemento.value='$consumidor_complemento'; consumidor_bairro.value='$consumidor_bairro'; consumidor_cidade.value='$consumidor_cidade'; consumidor_estado.value='$consumidor_estado'; abas.triggerTab(3);";
			}

			if ($_GET["proximo"] == "t") echo "proximo.focus(); ";
			echo "this.close(); \">\n";
		}
		echo "</a>\n";
		echo "</td>\n";


		echo "</tr>";
	}
	echo "</table>\n";
}

?>


</body>
</html>

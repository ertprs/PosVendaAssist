<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
include "autentica_usuario.php";
include 'funcoes.php';

$extrato = $_GET['extrato'];
if(strlen($extrato)==0){
	$extrato = $_POST['extrato'];
}

if(strlen($extrato)==0){
	$msg_erro .= "<p>Nenhum extrato selecionado</p>";
}


if(strlen($msg_erro)==0){

	$sql = "SELECT  tbl_os.os                                                                ,
					tbl_os.sua_os                                                            ,
					to_char (tbl_os.data_digitacao,'DD/MM/YYYY')                 AS data_digitacao  ,
					to_char (tbl_os.data_abertura ,'DD/MM/YYYY')                 AS abertura        ,
					to_char (tbl_os.data_fechamento ,'DD/MM/YYYY')               AS data_fechamento ,
					tbl_os.nota_fiscal                                                              ,
					tbl_os.serie                                                                    ,
					tbl_os.consumidor_nome                                                          ,
					tbl_produto.referencia                                                          ,
					tbl_produto.descricao                                                           ,
					tbl_os.sinalizador
		FROM        tbl_os
		JOIN        tbl_os_extra        ON  tbl_os_extra.os                = tbl_os.os
		JOIN      tbl_produto           ON  tbl_produto.produto            = tbl_os.produto
		WHERE	  tbl_os_extra.extrato = $extrato
		ORDER BY   tbl_os.sua_os asc ";
	$res = pg_query($con,$sql);

	if (@pg_num_rows($res) == 0) {
		echo "<h1>Nenhum resultado encontrado.</h1>";
	}else{
		$tmp_xls = "/tmp/assist/extrato-os-$login_posto.xls";
		echo `rm -f $tmp_xls`;

		$fp = fopen ($tmp_xls, "w");

		fputs ($fp,"<html>");
		fputs ($fp,"<head>");
		fputs ($fp,"<title>OS do Extrato $extrato");
		fputs ($fp,"</title>");
		fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
		fputs ($fp,"</head>");
		fputs ($fp,"<body>");

		fputs ($fp,"<p>Ordens de Serviços do Extrato $extrato</p>");

		fputs ($fp,"<TABLE width='750' border='0' align='center' border='0' cellspacing='1' cellpadding='1'>");
		fputs ($fp, "<TR bgcolor='#000000'>");
		fputs ($fp, "<TD><font color='#FFFFFF'>OS</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>SÉRIE</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>NF. COMPRA</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>DIGITAÇÃO</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>ABERTURA</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>FECHAMENTO</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>CONSUMIDOR</font></TD>");
		fputs ($fp, "<TD><font color='#FFFFFF'>PRODUTO</font></TD>");
		fputs ($fp, "</TR>");

		for ($i = 0 ; $i < pg_num_rows ($res) ; $i++) {
			$os                      = trim(pg_result ($res,$i,os));
			$sua_os                  = trim(pg_result ($res,$i,sua_os));
			$data_digitacao          = trim(pg_result ($res,$i,data_digitacao));
			$abertura                = trim(pg_result ($res,$i,abertura));
			$nota_fiscal             = trim(pg_result ($res,$i,nota_fiscal));
			$serie                   = trim(pg_result ($res,$i,serie));
			$consumidor_nome         = trim(pg_result ($res,$i,consumidor_nome));
			$produto_nome            = trim(pg_result ($res,$i,descricao));
			$produto_referencia      = trim(pg_result ($res,$i,referencia));
			$data_fechamento         = trim(pg_result ($res,$i,data_fechamento));
			//HD 204146: Fechamento automático de OS
			$sinalizador = pg_result ($res, $i, sinalizador);

			if ($sinalizador == "18") {
				$sinalizador = " <font color=#FF0000>(F. AUT)</font>";
			}
			else {
				$sinalizador = "";
			}

			$cor = ($i % 2 == 0) ?  "#F1F4FA" : $cor = "#F7F5F0";

			fputs ($fp,  "<TR class='table_line' style='background-color: $cor;'>");
			fputs ($fp,  "<TD nowrap>".$sua_os."&nbsp;</a></TD>");
			fputs ($fp,  "<TD nowrap>".$serie."</TD>");
			fputs ($fp,  "<TD align='center'>".$nota_fiscal."</TD>");
			fputs ($fp,  "<TD align='center'>".$data_digitacao."</TD>");
			fputs ($fp,  "<TD align='center'>".$abertura."</TD>");
			fputs ($fp,  "<TD align='center'>".$data_fechamento.$sinalizador."</TD>");
			fputs ($fp,  "<TD nowrap>".$consumidor_nome."</TD>");
			fputs ($fp,  "<TD nowrap>".$produto_referencia ."-".$produto_nome."</TD>");
		}
		fputs ($fp,"</table>");
		fputs ($fp,"</body>");
		fputs ($fp,"</html>");
		fclose ($fp);

		$arquivo = "/www/assist/www/admin/xls/extrato-os-$login_posto.xls";
		rename($tmp_xls, $arquivo);
//		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f $arquivo $tmp_xls`;

		header("Content-type: application/save");
		header("Content-Length:".filesize($arquivo));
		header('Content-Disposition: attachment; filename="' . $arquivo . '"');
		header('Expires: 0');
		header('Pragma: no-cache');
		readfile("$arquivo");
		exit;
	}
}
if (strlen($msg_erro)>0){
	echo "<p>".$msg_erro."</p>";
}
?>

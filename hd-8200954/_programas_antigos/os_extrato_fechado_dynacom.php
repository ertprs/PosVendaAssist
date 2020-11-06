<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";

$layout_menu = "os";
$title = "Extratos";

include "cabecalho.php";

?>

<p>

<script language="JavaScript">

function date_onkeydown() {
  if (window.event.srcElement.readOnly) return;
  var key_code = window.event.keyCode;
  var oElement = window.event.srcElement;
  if (window.event.shiftKey && String.fromCharCode(key_code) == "T") {
        var d = new Date();
        oElement.value = String(d.getMonth() + 1).padL(2, "0") + "/" +
                         String(d.getDate()).padL(2, "0") + "/" +
                         d.getFullYear();
        window.event.returnValue = 0;
    }
    if (!window.event.shiftKey && !window.event.ctrlKey && !window.event.altKey) {
        if ((key_code > 47 && key_code < 58) ||
          (key_code > 95 && key_code < 106)) {
            if (key_code > 95) key_code -= (95-47);
            oElement.value =
                oElement.value.replace(/[dma]/, String.fromCharCode(key_code));
        }
        if (key_code == 8) {
            if (!oElement.value.match(/^[dma0-9]{2}\/[dma0-9]{2}\/[dma0-9]{4}$/))
                oElement.value = "dd/mm/aaaa";
            oElement.value = oElement.value.replace(/([dma\/]*)[0-9]([dma\/]*)$/,
                function ($0, $1, $2) {
                    var idx = oElement.value.search(/([dma\/]*)[0-9]([dma\/]*)$/);
                    if (idx >= 5) {
                        return $1 + "a" + $2;
                    } else if (idx >= 2) {
                        return $1 + "m" + $2;
                    } else {
                        return $1 + "d" + $2;
                    }
                } );
            window.event.returnValue = 0;
        }
    }
    if (key_code != 9) {
        event.returnValue = false;
    }
}

</script>

<style type="text/css">

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
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
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

</style>

<?

// INICIO DA SQL
$sql = "SELECT   tbl_extrato.extrato                                    ,
				 to_char(tbl_extrato.aprovado, 'DD/MM/YYYY') AS aprovado,
				 tbl_extrato.mao_de_obra                                ,
				 tbl_extrato.pecas                                      ,
				 tbl_extrato.total                                      
		FROM     tbl_extrato
		WHERE    tbl_extrato.fabrica = $login_fabrica
		AND      tbl_extrato.posto   = $login_posto
		AND      tbl_extrato.aprovado IS NOT NULL
		ORDER BY tbl_extrato.data_geracao DESC";
$res = pg_exec ($con,$sql);

//if ($ip == '192.168.0.55') echo $sql;

	echo "<TABLE width='580' align='center' border='0' cellspacing='1' cellpadding='1'>\n";
	echo "<FORM METHOD=POST NAME=frm_extrato ACTION=\"$PHP_SELF\">";

if (pg_numrows($res) > 0) {

	echo "	<TR class='menu_top'>\n";
	echo "		<TD align=\"center\">EXTRATO Nº</TD>\n";
	echo "		<TD align=\"center\">PAGAMENTO</TD>\n";
	echo "		<TD align=\"center\">NOTA FISCAL</TD>\n";
	echo "		<TD align=\"center\">TOTAL</TD>\n";
	echo "		<TD align=\"center\" width=10>&nbsp;</TD>\n";
	echo "	</TR>\n";
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++){
		$extrato		= trim(pg_result($res,$i,extrato));
		$aprovado		= trim(pg_result($res,$i,aprovado));
		//$nota_fiscal	= trim(pg_result($res,$i,nota_fiscal));
		$total			= trim(pg_result($res,$i,total));
		$total			= number_format($total, 2, ',', ' ');
		
		//////////////////////////////////////////////
		$cor = "#F7F5F0"; 
		$btn = 'amarelo';
		if ($i % 2 == 0) {
			$cor = '#F1F4FA';
			$btn = 'azul';
		}

		echo "	<TR class='table_line' style='background-color: $cor;'>\n";
		echo "		<TD align='left' style='padding-left:7px;'>$extrato</TD>\n";
		echo "		<TD align='center'>$aprovado</TD>\n";
		echo "		<TD align='center'>$nota_fiscal</TD>\n";
		echo "		<TD align='right'  style='padding-right:3px;'>$total</TD>\n";
		echo "		<TD><a href='os_extrato_print.php?extrato=$extrato'><img src='imagens/btn_imprimir_".$btn.".gif'></a></TD>\n";
		echo "</TR>\n";
	}
	echo "<input type='hidden' name='total' value='$i'>";

}else{

	echo "	<TR class='table_line'>\n";
	echo "		<TD align=\"center\">NENHUM EXTRATO FOI ENCONTRADO</TD>\n";
	echo "	</TR>\n";
	echo "	<TR>\n";
	echo "		<TD align=\"center\">\n";
	echo "			<br><a href='menu_os.php'><img src='imagens/btn_voltar.gif'></a>";
	echo "		</TD>\n";
	echo "	</TR>\n";

}

echo "</form>";
echo "</TABLE>\n";
?>
<p>

<p>
<? include "rodape.php"; ?>
<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($_POST["btn_acao"]) > 0) {
	$btn_acao = trim($_POST["btn_acao"]);
}

if ($btn_finalizar == 1) {

	if (strlen($_POST["fabrica"]) > 0) {
		$aux_fabrica = "'". trim($_POST["fabrica"]) ."'";
	}else{
		$msg_erro = "Selecione uma fábrica.";
	}

	if (strlen($msg_erro) == 0) $listar = "ok";
	
	if (strlen($msg_erro) > 0) 
		$fabrica          = $_POST["fabrica"];

}

$layout_menu = "cadastro";
$title = "Consulta de Mensalidades";
include 'cabecalho.php';
?>

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
	text-align: CENTER;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.mgs_erro {
	text-align: CENTER;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>

<? 
	if($msg_erro){
?>
<table width='700px' align='center' border='0' bgcolor='#FFFFFF' cellspacing="1" cellpadding="0">
<tr align='center'>
	<td class='error'>
		<? echo $msg_erro; ?>
	</td>
</tr>
</table>
<?	} ?> 

<p>

<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<TABLE width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<TR>
		<TD class="menu_top"><div align="center"><b>Pesquisa por Fábrica</b></div></TD>
	</TR>
	<TR>
		<TD class='table_line'>Selecione a Fábrica</TD>
	</TR>
	<TR>
		<TD class='table_line'>
		<!-- começa aqui -->
			<?
			$sql = "SELECT   fabrica, nome
					FROM    tbl_fabrica
					ORDER BY nome";
			$res = pg_exec ($con,$sql);
			
			if (pg_numrows($res) > 0) {
				echo "<select class='frm' style='width: 130px;' name='fabrica'>\n";
				echo "<option value=''>ESCOLHA</option>\n";
				
				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_fabrica = trim(pg_result($res,$x,fabrica));
					$aux_nome  = trim(pg_result($res,$x,nome));
					
					echo "<option value='$aux_fabrica'"; 
					if ($fabrica == $aux_fabrica) echo " SELECTED "; 
					echo ">$aux_nome</option>\n";
				}
				echo "</select>\n";
			}
			?>
		</TD>
	</TR>
	<TR>
		<input type='hidden' name='btn_finalizar' value='0'>
		<TD class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'></TD>
	</TR>
</TABLE>

</FORM>

<!-- =========== FIM DO FORMULÁRIO FRM_PESQUISA =========== -->

<?
if ($listar == "ok") {
	
		echo "<br>";

		$sql = "SELECT  tbl_fabrica.nome
				FROM   tbl_fabrica
				WHERE  tbl_fabrica.fabrica = $fabrica";
		$res = pg_exec ($con,$sql);

		$nome          = trim(pg_result($res,0,nome));

		echo "<b><font size='2'>Resultado de pesquisa pela $nome</font></b>";

		echo "<br><br>";
		
		echo "<TABLE width='650' border='1' cellspacing='1' cellpadding='1' align='center'>";
		
		echo "<TR>";

		echo "<TD width='10%' class='table_line' nowrap>";
		echo "<b>MÊS</b>";
		echo "</TD>";

		echo "<TD width='10%' class='table_line' nowrap>";
		echo "<b>ANO</b>";
		echo "</TD>";

		
		echo "<TD width='10%' class='table_line' nowrap>";
		echo "<b>QTDE PEDIDO</b>";
		echo "</TD>";
		
		echo "<TD width='10%' class='table_line' nowrap>";
		echo "<b>ÚLTIMO PEDIDO</b>";
		echo "</TD>";

		echo "<TD width='10%' class='table_line' nowrap>";
		echo "<b>QTDE OS</b>";
		echo "</TD>";

		echo "<TD width='10%' class='table_line' nowrap>";
		echo "<b>ÚLTIMA OS</b>";
		echo "</TD>";
		
		echo "<TD width='10%' class='table_line' nowrap>";
		echo "<b>QTDE CALLCENTER</b>";
		echo "</TD>";
		
		echo "<TD width='10%' class='table_line' nowrap>";
		echo "<b>ÚLTIMO CALLCENTER</b>";
		echo "</TD>";

		echo "<TD width='10%' class='table_line' nowrap>";
		echo "<b>VALOR</b>";
		echo "</TD>";

		echo "<TD width='10%' class='table_line' nowrap>";
		echo "&nbsp;";
		echo "</TD>";

		echo "</TR>";


		$sql = "SELECT  tbl_mensalidade.fabrica          ,   
						tbl_mensalidade.mes              , 
						tbl_mensalidade.ano              , 
						tbl_mensalidade.ultimo_pedido    , 
						tbl_mensalidade.ultima_os        , 
						tbl_mensalidade.qtde_pedido      , 
						tbl_mensalidade.qtde_os          ,
						tbl_mensalidade.qtde_callcenter  ,
						tbl_mensalidade.ultimo_callcenter,
						tbl_mensalidade.valor            ,
						tbl_fabrica.nome             
			FROM tbl_mensalidade
			JOIN tbl_fabrica USING (fabrica)
			WHERE tbl_mensalidade.fabrica = $fabrica
			ORDER BY tbl_mensalidade.ano ASC,tbl_mensalidade.mes ASC";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			$mes                = trim(pg_result($res,$i,mes));
			$ano                = trim(pg_result($res,$i,ano));
			$ultimo_pedido      = trim(pg_result($res,$i,ultimo_pedido));
			$ultima_os          = trim(pg_result($res,$i,ultima_os));
			$qtde_pedido        = trim(pg_result($res,$i,qtde_pedido));
			$qtde_os            = trim(pg_result($res,$i,qtde_os));
			$qtde_callcenter    = trim(pg_result($res,$i,qtde_callcenter));
			$ultimo_callcenter  = trim(pg_result($res,$i,ultimo_callcenter));
			$valor              = trim(pg_result($res,$i,valor));
			
			$ultimo_pedido      = number_format($ultimo_pedido,0,'','.');
			$ultima_os          = number_format($ultima_os,0,'','.');
			$qtde_pedido        = number_format($qtde_pedido,0,'','.');
			$qtde_os            = number_format($qtde_os,0,'','.');
			$qtde_callcenter    = number_format($qtde_callcenter,0,'','.');
			$ultimo_callcenter  = number_format($ultimo_callcenter,0,'','.');
			$valor              = number_format($valor,2,',','.');

			echo "<TR>";
			echo "<TD width='10%'>$mes</font></TD>";
			echo "<TD width='10%'>$ano</TD>";
			echo "<TD width='10%'>$qtde_pedido</TD>";
			echo "<TD width='10%'>$ultimo_pedido</TD>";
			echo "<TD width='10%'>$qtde_os</TD>";
			echo "<TD width='10%'>$ultima_os</TD>";
			echo "<TD width='10%'>$qtde_callcenter</TD>";
			echo "<TD width='10%'>$ultimo_callcenter</TD>";
			echo "<TD width='10%'>$valor</TD>";
			echo "<TD><a href='mensalidade_cadastro.php?mes=$mes&ano=$ano&fabrica=$fabrica'><img src='imagens_admin/btn_alterar_azul.gif'></a></TD>\n";
			echo "</TR>";

		}//fim for

		echo "</TABLE>";
	}else{
		echo "<TABLE width='600'>";
		echo "<TR>";
		echo "<TD colspan = '8' class='msg_erro'>";
		echo "Não foram encontrado mensalidades para esta fábrica";
		echo "</TD>";
		echo "</TR>";
		echo "</TABLE>";
	}
}//fim OK

include("rodape.php");
?>


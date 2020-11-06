<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria,gerencia";
include 'autentica_admin.php';

$layout_menu = "auditoria";
$title = "Auditoria -  Peças Pendentes por Estoque";

include 'cabecalho.php';
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Arial;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #485989;
}
.Conteudo {
	font-family: Arial;
	font-size: 11px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 11px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 8px;
}
</style>
<script language="JavaScript">
function fnc_pesquisa_peca (campo, campo2, tipo) {
	if (tipo == "referencia" ) {
		var xcampo = campo;
	}

	if (tipo == "descricao" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "peca_pesquisa.php?forma=&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.referencia= campo;
		janela.descricao= campo2;
		janela.focus();
	}
}
</script>
<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js"></script>

<form name="frm_pesquisa" method="POST" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">
<table width="450" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo">
		<td colspan="4">Preencha os campos para realizar a pesquisa.</td>
	</tr>
<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>Data Inicial</td>
		<td>Data Final</td>
</tr>
<tr class="Conteudo" bgcolor="#D9E2EF">
<TD ><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaInicial_01')" style="cursor:pointer" alt="Clique aqui para abrir o calendário"></TD>
<TD><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaFinal_01')" style="cursor:pointer" alt="Clique aqui para abrir o calendário"></TD>
</tr>

<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>Código Peça</td>
		<td>Descrição Peça</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td><input class='frm' type="text" name="referencia" value="<? echo $referencia ?>" size="15" maxlength="20"><a href="javascript: fnc_pesquisa_peca (document.frm_pesquisa.referencia,document.frm_pesquisa.descricao,'referencia')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a></td>
		<td><input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="30" maxlength="50"><a href="javascript: fnc_pesquisa_peca (document.frm_pesquisa.referencia,document.frm_pesquisa.descricao,'descricao')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a></td>
	</tr>
		<tr bgcolor="#D9E2EF">
		<td colspan="2" align="center"><img border="0" src="imagens/btn_pesquisar_400.gif" onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
	</table>


<?
$btn_acao = $_POST['acao'];

if(strlen($btn_acao)>0){
	$referencia = $_POST['referencia'];
	$descricao = $_POST['descricao'];

	$cond_1 = " 1=1 ";
	$sql = "select peca from tbl_peca where referencia='$referencia' and fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
	 $peca = pg_result($res,0,0);
	 $cond_1 = " tbl_pedido_item.peca = $peca ";
	}
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_inicial_01"]) == 0 or $_POST["data_inicial_01"]=='dd/mm/aaaa') {
			$erro .= "Favor informar a data inicial para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_inicial   = trim($_POST["data_inicial_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			//if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0) ." 00:00:00";
			if (strlen($erro) == 0) $aux_data_inicial = @pg_result ($fnc,0,0);
		}
	}

	if (strlen($erro) == 0) {
		if (strlen($_POST["data_final_01"]) == 0 or $_POST["data_final_01"] == 'dd/mm/aaaa') {
			$erro .= "Favor informar a data final para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_final   = trim($_POST["data_final_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			//if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0) ." 23:59:59";
			if (strlen($erro) == 0) $aux_data_final = @pg_result ($fnc,0,0);
		}
	}

	if (strlen($erro) == 0) {

		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='auditoria_pecas_pendentes_pecas_xls_hmlg.php?data_inicial=$aux_data_inicial&data_final=$aux_data_final&peca=$peca' target='_blank'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo em EXCEL</font></a>.<br><font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Você pode ver, imprimir e salvar a tabela para consultas off-line.</font></td>";
		echo "</tr>";
		echo "</table>";


	$sql = "select tbl_peca.referencia,
				tbl_peca.descricao,
				tbl_peca.peca,
				count(tbl_pedido_item.peca) as qtde
			from tbl_pedido_item
			JOIN tbl_peca on tbl_pedido_item.peca = tbl_peca.peca
			JOIN tbl_pedido on tbl_pedido.pedido = tbl_pedido_item.pedido
			where (tbl_pedido.pedido_blackedecker NOTNULL OR posto IN(14301,20321,6359))
			and tbl_pedido.data > '2007-01-01 00:00:00'
			and $cond_1
			AND tbl_pedido.data between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59'
			AND tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada < tbl_pedido_item.qtde 
			AND tbl_pedido.fabrica = $login_fabrica
			GROUP BY
			tbl_peca.referencia,
			tbl_peca.descricao,tbl_peca.peca
			order by tbl_peca.referencia";
//echo $sql; exit;
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<BR><BR><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='500'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='3' height='20'><font size='2'>TOTAL DE PEÇAS PENDENTES (NÃO FATURADAS PELO FABRICANTE)</font></td>";
		echo "</tr>";
	
		echo "<tr class='Titulo'>";
		echo "<td >Código</td>";
		echo "<td >Descrição</td>";
		echo "<td >Qtde</td>";
		echo "</tr>";
	
		$total = pg_numrows($res);
		$total_pecas = 0;
		for ($i=0; $i<pg_numrows($res); $i++){
	
			$referencia          = trim(pg_result($res,$i,referencia));
			$descricao           = trim(pg_result($res,$i,descricao));
			$peca           = trim(pg_result($res,$i,peca));
			$qtde                = trim(pg_result($res,$i,qtde));
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
			$total_pecas = $total_pecas + $qtde;
			echo "<tr class='Conteudo'align='center'>";
			echo "<td bgcolor='$cor' align='center' nowrap><a href='$PHP_SELF?peca=$peca&xdata_inicial=$aux_data_inicial&xdata_final=$aux_data_final'>$referencia</a></td>";
			echo "<td bgcolor='$cor' align='left' nowrap><a href='$PHP_SELF?peca=$peca&xdata_inicial=$aux_data_inicial&xdata_final=$aux_data_final'>$descricao</a></td>";
			echo "<td bgcolor='$cor' nowrap>$qtde&nbsp;</td>";
			echo "</tr>";
		}
		echo "<tr class='Conteudo'>";
		echo "<td colspan='2'><B>Total</b></td>";
		echo "<td >$total_pecas</td>";
		echo "</tr>";
		echo "</table>";
	}else{
	echo "<br><center>Nenhum resultado encontrado</center>";
	}
	}
}
if (strlen($erro) > 0) {
	?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'>
			<? echo $erro ?>
			
	</td>
</tr>
</table>
<?
}

$peca = $_GET['peca'];
$xdata_inicial = $_GET['xdata_inicial'];
$xdata_final =  $_GET['xdata_final'];
if(strlen($peca)>0 and strlen($xdata_inicial)>0  and strlen($xdata_final)>0){
	$sql = "select	 tbl_pedido.pedido,
					to_char(tbl_pedido.data,'DD/MM/YYYY') AS data_pedido,
					tbl_pedido.pedido_blackedecker as lenoxx,
					tbl_peca.referencia, 
					tbl_peca.descricao, 
					tbl_peca.peca,
					tbl_posto.nome,
					tbl_posto_fabrica.codigo_posto,
					tbl_peca.retorna_conserto,
					tbl_peca.bloqueada_garantia,
					sum (tbl_pedido_item.qtde - (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)) as pendente
			FROM tbl_pedido_item 
			JOIN tbl_peca on tbl_pedido_item.peca = tbl_peca.peca 
			JOIN tbl_pedido on tbl_pedido.pedido = tbl_pedido_item.pedido 
			JOIN tbl_posto on tbl_posto.posto = tbl_pedido.posto
			JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
			and tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE (tbl_pedido.pedido_blackedecker NOTNULL OR tbl_posto.posto IN(14301,20321,6359))
			AND tbl_pedido.data > '2007-01-01 00:00:00' 
			AND tbl_pedido.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
			AND tbl_pedido_item.peca = $peca 
			AND tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada < tbl_pedido_item.qtde 
			AND tbl_pedido.fabrica = $login_fabrica
			GROUP BY tbl_pedido.pedido,
				tbl_pedido.data,
				tbl_pedido.pedido_blackedecker,
				tbl_peca.referencia, 
				tbl_peca.descricao, 
				tbl_peca.peca,
				tbl_posto.nome,
				tbl_peca.retorna_conserto,
				tbl_peca.bloqueada_garantia,
				tbl_posto_fabrica.codigo_posto
			order by tbl_pedido.data";
	$res = pg_exec ($con,$sql);

	/*			JOIN tbl_os_item on tbl_os_item.pedido_item  = tbl_pedido_item.pedido_item
			JOIN tbl_os_produto on tbl_os_produto.os_produto = tbl_os_item.os_produto
			JOIN tbl_os on tbl_os.os = tbl_os_produto.os*/

	if (pg_numrows($res) > 0) {
		$peca_referencia          = trim(pg_result($res,0,referencia));
		$peca_descricao           = trim(pg_result($res,0,descricao));

		echo "<BR><BR><table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='500'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='7' background='imagens_admin/azul.gif' height='20'><font size='2'>$peca_referencia - $peca_descricao </font></td>";
		echo "</tr>";

		echo "<tr class='Titulo'>";
		echo "<td >Telecontrol</td>";
		echo "<td >Lenoxx</td>";
		echo "<td >Data</td>";
		echo "<td >Posto</td>";
		echo "<td >Qtde</td>";
		if ($login_fabrica==11){
			echo "<td >Qtde Autorizada</td>";
		}
		echo "</tr>";

		for($y=0;pg_numrows($res)>$y;$y++){
			$pedido                   = trim(pg_result($res,$y,pedido));
			$lenoxx                   = trim(pg_result($res,$y,lenoxx));
			$data_pedido              = trim(pg_result($res,$y,data_pedido));
			$nome                     = trim(pg_result($res,$y,nome));
			$codigo_posto             = trim(pg_result($res,$y,codigo_posto));
			$pendente                 = trim(pg_result($res,$y,pendente));
			$peca                     = trim(pg_result($res,$y,peca));

			$retorna_conserto         = trim(pg_result($res,$y,retorna_conserto));
			$bloqueada_garantia       = trim(pg_result($res,$y,bloqueada_garantia));

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			echo "<tr  class='Conteudo'>";
			echo "<td  bgcolor='$cor' ><a href='pedido_admin_consulta.php?pedido=$pedido' target='blank'>$pedido</a></td>";
			echo "<td  bgcolor='$cor' >$lenoxx</td>";
			echo "<td  bgcolor='$cor' >$data_pedido</td>";
			echo "<td  bgcolor='$cor' align='left'>$codigo_posto - $nome</td>";
			echo "<td  bgcolor='$cor' >$pendente</td>";

			if ($login_fabrica==11 AND ($retorna_conserto=='t' OR $bloqueada_garantia=='t')){
				$qtde_peca_autorizada = "";
				$sql2 = "SELECT count(*) as contador
						FROM tbl_os_item
						WHERE pedido = $pedido 
						AND peca = $peca
						AND admin IS NOT NULL";
				$res2 = pg_exec ($con,$sql2);
				$qtde_peca_autorizada = pg_result($res2,0,contador);
				if ($qtde_peca_autorizada>0) {
					$sql2 = "SELECT count(*) as contador
							FROM tbl_os_status
							WHERE status_os in (64,73)
							AND os IN (
								SELECT os 
								FROM tbl_os_produto 
								JOIN tbl_os_item USING(os_produto)
								WHERE pedido = $pedido
								AND peca = $peca
							)
							";
					$res2 = pg_exec ($con,$sql2);
					$qtde_peca_autorizada = pg_result($res2,0,contador);
					if ($qtde_peca_autorizada>0){
						$fonte = "style='color:blue'";
					}else{
						$fonte="";
						$qtde_peca_autorizada="-";
					}
				}else{
					$fonte="";
					$qtde_peca_autorizada = "-";
				}
				echo "<td  bgcolor='$cor' $fonte>$qtde_peca_autorizada</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
	}
}

include "rodape.php" ;
?>
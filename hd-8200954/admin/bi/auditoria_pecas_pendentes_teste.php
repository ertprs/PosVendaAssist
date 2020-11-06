<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria,gerencia";
include 'autentica_admin.php';

$layout_menu = "auditoria";
$title = "Auditoria -  OSs com peças não atendidas";

$janela = $_GET["janela"];
$posto  = $_GET["posto"];
$todos  = trim($_GET['todos']);


$cond_1 = " 1 = 1 ";
if($login_fabrica==11) {
	$cond_1 = " tbl_pedido.data > '2006-12-01' ";
	$cond_2 = " AND tbl_os_item.faturamento_item is null";
}

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");



if(strlen($posto) > 0 AND $janela=="abrir"){
?>
<style type="text/css">
#Formulario {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: none;
	border: 1px solid #596D9B;
	color:#000000;
	background-color: #D9E2EF;
}
#Formulario tbody th{
	text-align: left;
	font-weight: bold;
}
#Formulario tbody td{
	text-align: left;
	font-weight: none;
}
#Formulario caption{
	color:#FFFFFF;
	text-align: center;
	font-weight: bold;
	background-image: url("imagens_admin/azul.gif");
}
</style>
<?
	$sql = "SELECT tbl_posto.nome         ,
				   tbl_posto_fabrica.codigo_posto
			FROM tbl_posto
			JOIN tbl_posto_fabrica USING(posto)
			WHERE fabrica = $login_fabrica
			AND   posto   = $posto ";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {

		$codigo_posto = trim(pg_result($res,0,codigo_posto));
		$nome         = trim(pg_result($res,0,nome))        ;

		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='700'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='7' background='imagens_admin/azul.gif' height='20'><font size='2'>TOTAL DE OS'S COM PEÇAS PENDENTES (NÃO FATURADAS PELO FABRICANTE)</font></td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='7' height='20'><font size='2'>$codigo_posto - $nome</font></td>";
		echo "</tr>";

		$sql = "SELECT distinct 
					tbl_os.os                                             ,
					tbl_os.sua_os                                         ,
					tbl_os.data_abertura                                  ,
					to_char(tbl_os.data_abertura,'dd/mm/yyyy') AS abertura,
					tbl_pedido_item.pedido AS pedido_telecontrol          ,
					tbl_pedido.pedido_blackedecker AS pedido_logix        ,
					tbl_peca.referencia                                   ,
					tbl_peca.descricao                                    ,
					tbl_pedido_item.qtde                                  ,
					tbl_pedido_item.qtde_faturada                         ,
					tbl_os_item.digitacao_item::date                      ,
					tbl_pedido.exportado::date
				FROM tbl_os
				JOIN tbl_os_produto USING(os)
				JOIN tbl_os_item USING(os_produto)
				JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item 
									AND tbl_pedido_item.peca = tbl_os_item.peca 
									AND tbl_pedido_item.qtde_faturada + qtde_cancelada < tbl_pedido_item.qtde
				JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido
				JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
				WHERE tbl_pedido.pedido in (
						SELECT pedido 
						FROM tbl_pedido 
						WHERE fabrica     = $login_fabrica
						AND posto         = $posto
						AND status_pedido in (1,2,5)
						AND pedido_blackedecker NOTNULL
				)
				AND $cond_1 $cond_2
				ORDER BY tbl_os.data_abertura,
						 tbl_peca.referencia";
		$res = pg_exec ($con,$sql);
//echo $sql; 
		if (pg_numrows($res) > 0) {
			echo "<tr class='Titulo'>";
			echo "<td >OS</td>";
			echo "<td >Abertura</td>";
			echo "<td >Peça</td>";
			echo "<td >Qtde.        </td>";
			echo "<td >Qtde. Faurada</td>";
			echo "<td >Pedido</td>";
			echo "<td >Pedido Fabricante</td>";
			echo "</tr>";
		
			$total = pg_numrows($res);
	
			for ($i=0; $i<pg_numrows($res); $i++){
		
				$os                  = trim(pg_result($res,$i,os));
				$sua_os              = trim(pg_result($res,$i,sua_os));
				$abertura            = trim(pg_result($res,$i,abertura));
				$referencia          = trim(pg_result($res,$i,referencia));
				$descricao           = trim(pg_result($res,$i,descricao));
				$qtde                = trim(pg_result($res,$i,qtde));
				$qtde_faturada       = trim(pg_result($res,$i,qtde_faturada));
				$pedido              = trim(pg_result($res,$i,pedido_telecontrol));
				$pedido_fabricante   = trim(pg_result($res,$i,pedido_logix));

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';
				
				echo "<tr class='Conteudo'align='center'>";
				echo "<td nowrap><a href='os_press?os=$os'>$sua_os&nbsp;</a></td>";
				echo "<td bgcolor='$cor' nowrap>$abertura&nbsp;</td>";
				echo "<td bgcolor='$cor' align='left' nowrap>$referencia - $descricao&nbsp;</td>";
				echo "<td bgcolor='$cor' nowrap>$qtde&nbsp;</td>";
				echo "<td bgcolor='$cor' nowrap>$qtde_faturada&nbsp;</td>";
				
				echo "<td bgcolor='$cor' ><a href=pedido_admin_consulta.php?pedido=$pedido target=_blank>$pedido&nbsp;</a></td>";
				echo "<td bgcolor='$cor' >$pedido_fabricante&nbsp;</td>";
				echo "</tr>";
			}
			echo "</table>";
		 } else {
			echo "Nenhum resultado encontrado";
		 }
	}
	exit;
}

include 'cabecalho.php';
?>

<script language="JavaScript">

function fnc_pesquisa_posto (campo, campo2, tipo) {
	if (tipo == "codigo" ) {
		var xcampo = campo;
	}

	if (tipo == "nome" ) {
		var xcampo = campo2;
	}

	if (xcampo.value != "") {
		var url = "";
		url = "posto_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
		janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
		janela.retorno = "<? echo $PHP_SELF ?>";
		janela.posto_codigo	= campo;
		janela.porto_nome	= campo2;
		janela.focus();
	}
}
function fnc_ver_posto(posto) {
	var url = "<? echo $PHP_SELF ?>?janela=abrir&posto=autorizar&posto="+posto;
	janela_aut = window.open(url, "_blank", "toolbar=no, location=no, status=no, scrollbars=yes, directories=no, width=750, height=400, top=18, left=0");
	janela_aut.focus();
}

</script>

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

<form name='frm_posto' method='post' action='<?=$PHP_SELF?>'>
<TABLE width="650" align="center" border="0" cellspacing="0" cellpadding="2" id='Formulario'>
	<CAPTION>Pesquisa</CAPTION>
	<TBODY>
	<TR>
		<TH>Mês</TH>
		<TD>
			<select name="mes" size="1" class="frm">
				<option value=''></option>
				<?
				for ($i = 1 ; $i <= count($meses) ; $i++) {
					echo "<option value='$i'";
					if ($mes == $i) echo " selected";
					echo ">" . $meses[$i] . "</option>";
				}
				?>
			</select>
		</TD>
		<TH>Ano</TH>
		<TD>
			<select name="ano" size="1" class="frm">
				<option value=''></option>
				<?
				for ($i = 2003 ; $i <= date("Y") ; $i++) {
					echo "<option value='$i'";
					if ($ano == $i) echo " selected";
					echo ">$i</option>";
				}
				?>
			</select>
		</TD>
	</TR>
	<TR>
		<TH>Código do Posto</TH>
		<TD><input class='frm' type='text' name='posto_codigo' size='15' value='".$posto_codigo."'>&nbsp;<img src='imagens/lupa.png' border='0' align='absmiddle' style='cursor:pointer' onclick=\"javascript: fnc_pesquisa_posto (document.frm_posto.posto_codigo,document.frm_posto.posto_nome,'codigo')\"></A>
			</select>
		</TD>
		<TH>Nome</TH>
		<TD><input class='frm' type='text' name='posto_nome' size='50' value='".$posto_nome."' >&nbsp;<img src='imagens/lupa.png' style='cursor:pointer' border='0' align='absmiddle' onclick=\"javascript: fnc_pesquisa_posto (document.frm_posto.posto_codigo,document.frm_posto.posto_nome,'nome')\" style='cursor:pointer;'></A>
		</TD>

	</TR>
	</tbody>
	<TFOOT>
	<TR>
		<input type='hidden' name='btn_finalizar' value='0'>
		<TD colspan="4"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'>
		</TD>
	</TR>
	</TFOOT>
</TABLE>


<?


echo "<a href='$PHP_SELF?todos=1'>Visualizar todos os Postos</a>";

if(strlen($posto) > 0){
	$sql = "SELECT tbl_posto.nome         ,
				   tbl_posto_fabrica.codigo_posto
			FROM tbl_posto
			JOIN tbl_posto_fabrica USING(posto)
			WHERE fabrica = $login_fabrica
			AND   posto   = $posto ";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {

		$codigo_posto = trim(pg_result($res,0,codigo_posto));
		$nome         = trim(pg_result($res,0,nome))        ;

		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='700'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='7' background='imagens_admin/azul.gif' height='20'><font size='2'>TOTAL DE OS'S COM PEÇAS PENDENTES (NÃO FATURADAS PELO FABRICANTE)</font></td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='7' height='20'><font size='2'>$codigo_posto - $nome</font></td>";
		echo "</tr>";

		$sql = "SELECT distinct 
					tbl_os.os                                             ,
					tbl_os.sua_os                                         ,
					tbl_os.data_abertura                                  ,
					to_char(tbl_os.data_abertura,'dd/mm/yyyy') AS abertura,
					tbl_pedido_item.pedido AS pedido_telecontrol          ,
					tbl_pedido.pedido_blackedecker AS pedido_logix        ,
					tbl_peca.referencia                                   ,
					tbl_peca.descricao                                    ,
					tbl_pedido_item.qtde                                  ,
					tbl_pedido_item.qtde_faturada                         ,
					tbl_os_item.digitacao_item::date                      ,
					tbl_pedido.exportado::date
				FROM tbl_os
				JOIN tbl_os_produto USING(os)
				JOIN tbl_os_item USING(os_produto)
				JOIN tbl_pedido_item ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item 
									AND tbl_pedido_item.peca = tbl_os_item.peca 
				JOIN tbl_pedido ON tbl_pedido_item.pedido = tbl_pedido.pedido
				JOIN tbl_peca ON tbl_os_item.peca = tbl_peca.peca
				WHERE tbl_pedido.pedido in (
						SELECT pedido 
						FROM tbl_pedido 
						WHERE fabrica     = $login_fabrica
						AND posto         = $posto
						AND status_pedido IN (2,5)
						AND $cond_1
						AND pedido_blackedecker NOTNULL
				)
				 $cond_2

				ORDER BY tbl_os.data_abertura,
						 tbl_peca.referencia";
		$res = pg_exec ($con,$sql);
//if ($ip=="189.47.44.88")echo $sql; 
		if (pg_numrows($res) > 0) {
			echo "<tr class='Titulo'>";
			echo "<td >OS</td>";
			echo "<td >Abertura</td>";
			echo "<td >Peça</td>";
			echo "<td >Qtde.        </td>";
			echo "<td >Qtde. Faurada</td>";
			echo "<td >Pedido</td>";
			echo "<td >Pedido Fabricante</td>";
			echo "</tr>";
		
			$total = pg_numrows($res);
	
			for ($i=0; $i<pg_numrows($res); $i++){
		
				$os                  = trim(pg_result($res,$i,os));
				$sua_os              = trim(pg_result($res,$i,sua_os));
				$abertura            = trim(pg_result($res,$i,abertura));
				$referencia          = trim(pg_result($res,$i,referencia));
				$descricao           = trim(pg_result($res,$i,descricao));
				$qtde                = trim(pg_result($res,$i,qtde));
				$qtde_faturada       = trim(pg_result($res,$i,qtde_faturada));
				$pedido              = trim(pg_result($res,$i,pedido_telecontrol));
				$pedido_fabricante   = trim(pg_result($res,$i,pedido_logix));

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';
				
				echo "<tr class='Conteudo'align='center'>";
				echo "<td nowrap><a href='os_press?os=$os'>$sua_os&nbsp;</a></td>";
				echo "<td bgcolor='$cor' nowrap>$abertura&nbsp;</td>";
				echo "<td bgcolor='$cor' align='left' nowrap>$referencia - $descricao&nbsp;</td>";
				echo "<td bgcolor='$cor' nowrap>$qtde&nbsp;</td>";
				echo "<td bgcolor='$cor' nowrap>$qtde_faturada&nbsp;</td>";
				
				echo "<td bgcolor='$cor' ><a href=pedido_admin_consulta.php?pedido=$pedido target=_blank>$pedido&nbsp;</a></td>";
				echo "<td bgcolor='$cor' >$pedido_fabricante&nbsp;</td>";
				echo "</tr>";
			}
			echo "</table>";
		 } else {
			echo "Nenhum resultado encontrado";
		 }
	}
}else{
	if ($todos==1){
		/*$sql_posto = "SELECT DISTINCT
						tbl_posto.posto,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome
					FROM tbl_pedido 
					JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_pedido.posto
					JOIN tbl_posto ON tbl_posto.posto = tbl_pedido.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					AND tbl_pedido.status_pedido = 2 
					AND tbl_pedido.data > '2006-12-01' 
					AND tbl_pedido.pedido_blackedecker NOTNULL
					ORDER BY tbl_posto.nome ASC";
		*/
		$sql_posto = "SELECT DISTINCT
						tbl_posto.posto,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto.nome
					FROM tbl_posto_fabrica 
					JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
					ORDER BY tbl_posto.nome ASC";
		$res_posto = pg_exec ($con,$sql_posto);
		$qtde_postos = pg_numrows($res_posto);
		if ($qtde_postos > 0) {
			echo "<br><br><center><font face='Verdana' size='2px'>Clique sobre o código do posto para listar apenas as suas pendências</center><br><b style='color:red'>Aguarde... Gerando relatório.</b>";
			echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='700'>";
			echo "<tr class='Titulo'>";
			echo "<td colspan='5'background='imagens_admin/azul.gif' height='20'><font size='2'>TOTAL DE OS'S COM PEÇAS PENDENTES (NÃO FATURADAS PELO FABRICANTE)</font></td>";
			echo "</tr>";
		
			echo "<tr class='Titulo'>";
			echo "<td >CÓDIGO DO POSTO</td>";
			echo "<td >NOME DO POSTO</td>";
			echo "<td >TOTAL</td>";
			echo "</tr>";
			for ($j=0; $j<$qtde_postos; $j++){
				$posto			= trim(pg_result($res_posto,$j,posto));
				$codigo_posto	= trim(pg_result($res_posto,$j,codigo_posto));
				$nome			= trim(pg_result($res_posto,$j,nome));

				$sql = "SELECT count(distinct os) as total
						FROM ( SELECT pedido
								FROM tbl_pedido 
								WHERE fabrica     = $login_fabrica
								AND status_pedido = 2
								AND $cond_1
								AND posto=$posto
								AND pedido_blackedecker NOTNULL
						) t_pedido
						JOIN tbl_pedido_item ON tbl_pedido_item.pedido      = t_pedido.pedido AND tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada < tbl_pedido_item.qtde
						JOIN tbl_os_item     ON tbl_pedido_item.pedido_item = tbl_os_item.pedido_item AND tbl_pedido_item.peca = tbl_os_item.peca
						JOIN tbl_os_produto USING(os_produto)
						WHERE 1=1 $cond_2";
				echo $sql;exit;
				$res = pg_exec ($con,$sql);
				if (pg_numrows($res) > 0) {
					for ($i=0; $i<pg_numrows($res); $i++){
						$total                   = trim(pg_result($res,$i,total))       ;
						
						if($cor=="#F1F4FA")
							$cor = '#F7F5F0';
						else
							$cor = '#F1F4FA';
				
						echo "<tr class='Conteudo'align='center'>";
						echo "<td bgcolor='$cor' ><a href='javascript: fnc_ver_posto($posto);'>$codigo_posto</a></td>";
						echo "<td bgcolor='$cor' align='left'>$nome</td>";
						echo "<td bgcolor='$cor' >$total</td>";
						$total_geral = $total + $total_geral;
						echo "</tr>";
						flush();
					}
				}
			}
			echo "<tr><td colspan='2'> Total</td><td>$total_geral</td></tr>";
			echo "</table>";
		}
	}
}

include "rodape.php" ;
?>
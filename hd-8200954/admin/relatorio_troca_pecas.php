<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria,gerencia";
include 'autentica_admin.php';


# Pesquisa pelo AutoComplete AJAX
$q = strtolower($_GET["q"]);
if (isset($_GET["q"])){
	$tipo_busca = $_GET["busca"];
	
	if (strlen($q)>3){
		$sql = "SELECT tbl_posto.cnpj, tbl_posto.nome, tbl_posto_fabrica.codigo_posto
				FROM tbl_posto
				JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
				WHERE tbl_posto_fabrica.fabrica = $login_fabrica ";
		
		if ($tipo_busca == "codigo"){
			$sql .= " AND tbl_posto_fabrica.codigo_posto = '$q' ";
		}else{
			$sql .= " AND UPPER(tbl_posto.nome) like UPPER('%$q%') ";
		}

		$sql .= " LIMIT 50 ";
		
		$res = pg_exec($con,$sql);
		if (pg_numrows ($res) > 0) {
			for ($i=0; $i<pg_numrows ($res); $i++ ){
				$cnpj = trim(pg_result($res,$i,cnpj));
				$nome = trim(pg_result($res,$i,nome));
				$codigo_posto = trim(pg_result($res,$i,codigo_posto));
				echo "$codigo_posto|$nome|$cnpj";
				echo "\n";
			}
		}
	}
	exit;
}

$btn_acao = $_POST['acao'];

if (strlen($btn_acao)>0){
	if($_GET["data_inicial"]) $data_inicial = $_GET["data_inicial"];
    if($_POST["data_inicial"]) $data_inicial = $_POST["data_inicial"];
    if($_GET["data_final"]) $data_final = $_GET["data_final"];
    if($_POST["data_final"]) $data_final = $_POST["data_final"];
	
	if(empty($data_inicial) OR empty($data_final)){
        $msg_erro = "Data Inválida";
    }
	
	if(strlen($msg_erro)==0){
        list($di, $mi, $yi) = explode("/", $data_inicial);
        if(!checkdate($mi,$di,$yi)) 
            $msg_erro = "Data Inválida";
    }
    if(strlen($msg_erro)==0){
        list($df, $mf, $yf) = explode("/", $data_final);
        if(!checkdate($mf,$df,$yf)) 
            $msg_erro = "Data Inválida";
    }

    if(strlen($msg_erro)==0){
        $aux_data_inicial = "$yi-$mi-$di";
        $aux_data_final = "$yf-$mf-$df";
    }
    if(strlen($msg_erro)==0){
        if(strtotime($aux_data_final) < strtotime($aux_data_inicial)){
            $msg_erro = "Data Inválida.";
        }

	if($data_inicial && $data_final){
		if (strtotime($aux_data_inicial) < strtotime($aux_data_final . ' -60 day')) {
			$msg_erro ="Intervalo de data nÃo pode ser maior que 60 dias";
				}

		}
    }	
	
}



$layout_menu = "gerencia";
$title = "GERÊNCIA - RELATÓRIO DE PEÇAS TROCADAS";

include 'cabecalho.php';
?>

<style type="text/css">
.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial" !important;
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}


.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.texto_avulso{
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width:700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
</style>

<?include "javascript_pesquisas.php"; ?>

<? include "javascript_calendario.php"; ?>
<script type="text/javascript" src="js/jquery.tablesorter.pack.js"></script> 
<script>
$(document).ready(function(){
	$.tablesorter.defaults.widgets = ['zebra'];
	$("#relatorio").tablesorter();

});
</script>

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
	}else{
        alert("Informar toda ou parte da informação para realizar a pesquisa!");
}
</script>

<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});
</script>

<script type='text/javascript' src='js/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="js/jquery.autocomplete.css" />
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>


<script language="JavaScript">
$().ready(function() {

	function formatItem(row) {
		return row[0] + " - " + row[1];
	}
	
	function formatResult(row) {
		return row[0];
	}
	
	/* Busca pelo Código */
	$("#codigo_posto").autocomplete("<?echo $PHP_SELF.'?busca=codigo'; ?>", {
		minChars: 5,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[0];}
	});

	$("#codigo_posto").result(function(event, data, formatted) {
		$("#posto_nome").val(data[1]) ;
	});

	/* Busca pelo Nome */
	$("#posto_nome").autocomplete("<?echo $PHP_SELF.'?busca=nome'; ?>", {
		minChars: 5,
		delay: 150,
		width: 350,
		matchContains: true,
		formatItem: formatItem,
		formatResult: function(row) {return row[1];}
	});

	$("#posto_nome").result(function(event, data, formatted) {
		$("#codigo_posto").val(data[0]) ;
		//alert(data[2]);
	});

});
</script>


<script language='javascript' src='ajax.js'></script>

<?
if (strlen($msg_erro) > 0) {
	?>
<table width="700" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='msg_erro'>
			<? echo $msg_erro ?>
			
	</td>
</tr>
</table>
<?
}
?>


<form name="frm_pesquisa" method="POST" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">
<table width="700px" align="center" border="0" cellspacing="1" cellpadding="1" class="formulario">
	

	
	<tr class="titulo_tabela">
		<td colspan="4">Parâmetros de Pesquisa</td>
	</tr>
	
	<tr>
		<td colspan="4">&nbsp;</td>
	</tr>
	<tr>
		<td width='*'></td>
		<td width='30%' align='left'>Data Inicial</td>
		<td width='45%' align='left'>Data Final</td>
		<td width='5%'></td>
	</tr>
	<tr>
		<td width='10%'></td>
		<TD>
			<INPUT size="12" maxlength="10" TYPE="text" class='frm' NAME="data_inicial" id="data_inicial" value="<? if (strlen($data_inicial) > 0) echo $data_inicial; ?>">
		</TD>
		<TD>
			<INPUT size="12" maxlength="10" TYPE="text" class='frm'  NAME="data_final" id="data_final" value="<? if (strlen($data_final) > 0) echo $data_final; ?>">
		</TD>
		<td width='10%'></td>
	</tr>

	<tr >
		<td width='10%'></td>
		<td >Código Peça</td>
		<td >Descrição Peça</td>
		<td width='10%'></td>
	</tr>
	
	<tr>
		<td width='10%'></td>
		
		<td ><input class='frm' type="text" name="referencia" value="<? echo $referencia ?>" size="18" maxlength="20"><a href="javascript: fnc_pesquisa_peca (document.frm_pesquisa.referencia,document.frm_pesquisa.descricao,'referencia')"><IMG SRC="imagens/lupa.png" ></a></td>
		<td ><input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="40" maxlength="50"><a href="javascript: fnc_pesquisa_peca (document.frm_pesquisa.referencia,document.frm_pesquisa.descricao,'descricao')"><IMG SRC="imagens/lupa.png" ></a></td>
		
		<td width='10%'></td>
	</tr>
	<tr>
		<td width='10%'></td>
		<td >Código Posto</td>
		<td >Nome Posto</td>
		<td width='10%'></td>
	</tr>
	<tr>
		<td width='10%'></td>
		<td>
			<input type='text' name='codigo_posto' id='codigo_posto' size='18' value='<? echo $codigo_posto ?>' class='frm'>
			<img border="0" src="imagens/lupa.png" style="cursor:pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'codigo')">
		</td>
		<td>
			<input type='text' name='posto_nome' id='posto_nome' size='40' value='<? echo $posto_nome ?>' class='frm'>
			<img border="0" src="imagens/lupa.png" style="cursor:pointer;" align="absmiddle" alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_pesquisa.codigo_posto, document.frm_pesquisa.posto_nome, 'nome')">
		</td>
		<td width='10%'></td>
	</tr>
	<tr>
		<td colspan="4" align="center">
		<br>	Tipo de troca
		</td>
		
	</tr>
	<tr>
		<td colspan="4" align="center">
		<select name='tipo_troca' size='1' style='width:250px' class='frm'>
		<option value='' ></option>
		<?
		$sql = "SELECT servico_realizado, descricao 
				FROM tbl_servico_realizado
				WHERE fabrica = $login_fabrica
				AND troca_de_peca is true
				AND  ativo is true";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			for($x=0;pg_numrows($res)>$x;$x++){
				$servico_realizado = pg_result($res,$x,servico_realizado);
				$servico_realizado_descricao = pg_result($res,$x,descricao);
				echo "<option value='$servico_realizado' >$servico_realizado_descricao</option>";
			}
		}
		?>
		</select>
		</td>
	</tr>
	<tr>
			<td colspan="4" align="center"><br>
			<input type='button' value='Pesquisar' onclick="document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: pointer;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
	</table>
	<br />

<?


if(strlen($btn_acao)>0){
	flush();
	$referencia = $_POST['referencia'];
	$descricao = $_POST['descricao'];
	
	
	
	$codigo_posto = $_POST['codigo_posto'];
	$posto_nome   = $_POST['posto_nome'];
	$tipo_troca  = $_POST['tipo_troca'];

	$cond_2 = " 1=1 ";
	if(strlen($tipo_troca)>0){
		$cond_2 = " tbl_servico_realizado.servico_realizado = $tipo_troca ";
	}
	
	$cond_3 = " 1=1 ";
	if(strlen($codigo_posto)>0){
		$sql = "SELECT posto from tbl_posto_fabrica where codigo_posto='$codigo_posto' and fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$posto = pg_result($res,0,posto);
			$cond_3 = " X.posto = $posto ";
		}
	}
	
	$cond_1 = " 1=1 ";
	if(strlen($referencia)>0){
		$sql = "select peca from tbl_peca where referencia='$referencia' and fabrica = $login_fabrica";
		$res = pg_exec($con,$sql);

		if(pg_numrows($res)>0){
		 $peca = pg_result($res,0,0);
		 $cond_1 = " tbl_os_item.peca = $peca ";
		}
	}

	
	if (strlen($msg_erro) == 0) {
		$sql = "
				SELECT tbl_os_extra.os, tbl_os_produto.os_produto, tbl_extrato.posto
				INTO TEMP tmp_rtp_$login_admin
				FROM tbl_extrato
				JOIN tbl_os_extra on tbl_extrato.extrato = tbl_os_extra.extrato AND tbl_os_extra.i_fabrica=tbl_extrato.fabrica
				JOIN tbl_os_produto on tbl_os_produto.os = tbl_os_extra.os
				WHERE tbl_extrato.fabrica = $login_fabrica
				AND tbl_extrato.data_geracao between '$aux_data_inicial 00:00:00' and '$aux_data_final 23:59:59' ;

				CREATE INDEX tmp_rtp_OS_$login_admin        ON tmp_rtp_$login_admin(os);
				CREATE INDEX tmp_rtp_POSTO_$login_admin     ON tmp_rtp_$login_admin(posto);
				CREATE INDEX tmp_rtp_OSPRODUTO_$login_admin ON tmp_rtp_$login_admin(os_produto);

				SELECT tbl_posto_fabrica.codigo_posto      ,
					tbl_posto.nome as nome_posto           ,
					tbl_peca.referencia as referencia_peca , 
					tbl_peca.descricao  as descricao_peca  ,
					tbl_servico_realizado.descricao as servico_descricao,
					count(*) as qtde
				FROM tmp_rtp_$login_admin X
				JOIN tbl_os_item on X.os_produto = tbl_os_item.os_produto AND tbl_os_item.fabrica_i=$login_fabrica
				JOIN tbl_servico_realizado on tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado and tbl_servico_realizado.troca_de_peca and $cond_2
				JOIN tbl_peca on tbl_os_item.peca = tbl_peca.peca and tbl_peca.fabrica = $login_fabrica
				JOIN tbl_posto on tbl_posto.posto = X.posto
				JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
				and tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE $cond_1
				AND $cond_3
				GROUP by 
					tbl_posto_fabrica.codigo_posto  ,
					tbl_posto.nome                  ,
					tbl_peca.referencia             ,
					tbl_peca.descricao              ,
					tbl_servico_realizado.descricao
				ORDER BY qtde desc
				";
		// echo nl2br($sql);exit;
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {


			?>
			
			<table border='0' cellpadding='2' cellspacing='1' class='tabela' align='center' width='700'>
			<tr>
				<td colspan='5' class="titulo_tabela" >
				<?
					echo " Relatório de: ";
					echo $_POST["data_inicial"]; 
					echo " até ";
					echo $_POST["data_final"]; 
				?>
				</td>
			</tr>
			
			<tr class='titulo_coluna'>
				<td >Código</td>
				<td >Posto</td>
				<td >Peca</td>
				<td >Serviço</td>
				<td >Qtde</td>
			</tr>
			
			<?
			$total = pg_numrows($res);
			$total_pecas = 0;
			for ($i=0; $i<pg_numrows($res); $i++){

				$nome                    = trim(pg_result($res,$i,nome_posto));
				$codigo_posto            = trim(pg_result($res,$i,codigo_posto));
				$qtde                    = trim(pg_result($res,$i,qtde));
				$peca_referencia         = pg_result($res,$i,referencia_peca);
				$peca_descricao          = pg_result($res,$i,descricao_peca);
				$servico_descricao       = pg_result($res,$i,servico_descricao);
				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';
				$total_pecas = $total_pecas + $qtde;
				echo "<tr align='center'>";
				echo "<td bgcolor='$cor' align='center' nowrap>$codigo_posto</td>";
				echo "<td bgcolor='$cor' align='left' nowrap>$nome</td>";
				echo "<td bgcolor='$cor' align='left' nowrap>$peca_referencia - $peca_descricao</td>";
				echo "<td bgcolor='$cor' align='left' nowrap>$servico_descricao</td>";
				echo "<td bgcolor='$cor' nowrap>$qtde&nbsp;</td>";
				echo "</tr>";
			}
			echo "<tr class='titulo_coluna'>";
			echo "<td colspan='4'><B>Total</b></td>";
			echo "<td >$total_pecas</td>";
			echo "</tr>";
			echo "</table>";
		}else{
		echo "<br><center>Nenhum resultado encontrado</center>";
		}
	}
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
					sum (tbl_pedido_item.qtde - (tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada)) as pendente
			FROM tbl_pedido_item 
			JOIN tbl_peca on tbl_pedido_item.peca = tbl_peca.peca 
			JOIN tbl_pedido on tbl_pedido.pedido = tbl_pedido_item.pedido 
			JOIN tbl_posto on tbl_posto.posto = tbl_pedido.posto
			JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto 
			and tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE tbl_pedido.pedido_blackedecker NOTNULL 
			AND   tbl_pedido.data > '2007-01-01 00:00:00' 
			AND   tbl_pedido.data BETWEEN '$xdata_inicial 00:00:00' AND '$xdata_final 23:59:59'
			AND   tbl_pedido.fabrica    = $login_fabrica
			AND    tbl_pedido_item.peca = $peca 
			AND tbl_pedido_item.qtde_faturada + tbl_pedido_item.qtde_cancelada < tbl_pedido_item.qtde 
			GROUP BY tbl_pedido.pedido,
				tbl_pedido.data,
				tbl_pedido.pedido_blackedecker,
				tbl_peca.referencia, 
				tbl_peca.descricao, 
				tbl_peca.peca,
				tbl_posto.nome,
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
		echo "</tr>";

		for($y=0;pg_numrows($res)>$y;$y++){
			$pedido                   = trim(pg_result($res,$y,pedido));
			$lenoxx                   = trim(pg_result($res,$y,lenoxx));
			$data_pedido              = trim(pg_result($res,$y,data_pedido));
			$nome                     = trim(pg_result($res,$y,nome));
			$codigo_posto             = trim(pg_result($res,$y,codigo_posto));
			$pendente                 = trim(pg_result($res,$y,pendente));
			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';
			echo "<tr  class='Conteudo'>";
			echo "<td  bgcolor='$cor' ><a href='pedido_admin_consulta.php?pedido=$pedido' target='blank'>$pedido</a></td>";
			echo "<td  bgcolor='$cor' >$lenoxx</td>";
			echo "<td  bgcolor='$cor' >$data_pedido</td>";
			echo "<td  bgcolor='$cor' align='left'>$codigo_posto - $nome</td>";
			echo "<td  bgcolor='$cor' >$pendente</td>";
			echo "</tr>";
		}
		echo "</table>";
	
	}
}

include "rodape.php" ;
?>

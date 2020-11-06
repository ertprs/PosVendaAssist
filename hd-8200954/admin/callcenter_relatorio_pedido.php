<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO DE ATENDIMENTO";

$btn_acao = $_POST['btn_acao'];

if (strlen($btn_acao)>0){
	$codigo_posto = $_POST['codigo_posto'];
	$nome_posto         = $_POST['nome_posto'];	
	$referencia   = $_POST['referencia'];
	$data_inicial = $_POST['data_inicial'];
	$data_final = $_POST['data_final'];
	
	$cond_1 = " and 1 = 1 ";
		$cond_2 = " and 1 = 1 ";
		$cond_3 = " and 1 = 1 ";


		if(strlen($codigo_posto)>0){
			$sql="SELECT posto FROM tbl_posto_fabrica join tbl_posto using(posto) where fabrica=$login_fabrica and codigo_posto='$codigo_posto'";
			$res = pg_exec($con,$sql);
			   
			if( pg_num_rows($res) > 0 ){
					$posto=pg_result($res,0,'posto');
				    $cond_1 = " and tbl_pedido.posto=$posto";
			}else{
				$msg_erro = 'Posto não existe';
			}
			

		}

		if(strlen($referencia)>0){
			$sql = "SELECT peca
					FROM tbl_peca 
					WHERE fabrica=$login_fabrica and referencia='$referencia';";
			$res = pg_exec ($con,$sql);
			if(pg_num_rows($res)>0){
				
				$peca = pg_result ($res,0,'peca');
				$cond_3=" and tbl_pedido_item.peca = '$peca'";
				$join = " JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido ";
				
			}else{
				$msg_erro = "Peça não existe";
			}
		}
	

    $data_inicial = $_REQUEST["data_inicial"];
    $data_final = $_REQUEST["data_final"];
    
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
        if(($aux_data_final < $aux_data_inicial) or ($aux_data_final > date('Y-m-d'))){
            $msg_erro = "Data Inválida";
        }
    }
	
}


include "cabecalho.php";

?>
<style>

.Carregando{
	TEXT-ALIGN: center;
	BORDER-RIGHT: #aaa 1px solid;
	BORDER-TOP: #aaa 1px solid;
	FONT: 10pt Arial ;
	COLOR: #000000;
	BORDER-LEFT: #aaa 1px solid;
	BORDER-BOTTOM: #aaa 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
	margin-left:20px;
	margin-right:20px;
}

table.tabela tr td{
    font-family: verdana;
    font-size: 11px;
    border-collapse: collapse;
    border:1px solid #596d9b;
}

.titulo_tabela{
    background-color:#596d9b;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.titulo_coluna{
    background-color:#596d9b;
    font: bold 11px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}
</style>


<!--
<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
-->

<? include "javascript_calendario.php"; //adicionado por Fabio 27-09-2007 ?>
<script type="text/javascript" charset="utf-8">
	$(function(){
		$('#data_inicial').datePicker({startDate:'01/01/2000'});
		$('#data_final').datePicker({startDate:'01/01/2000'});
		$("#data_inicial").maskedinput("99/99/9999");
		$("#data_final").maskedinput("99/99/9999");
	});

/* POP-UP IMPRIMIR */
	function abrir(URL) {
		var width = 700;
		var height = 500;
		var left = 90;
		var top = 90;

		window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
	}
</script>


<script language='javascript' src='../ajax.js'></script>
<script>
function AbreCallcenter(data_inicial,data_final,produto,natureza,status,tipo,posto,atendente,tipo_data){
janela = window.open("callcenter_relatorio_atendimento_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status+"&tipo="+tipo+"&posto="+posto+"&atendente="+atendente+"&tipo_data="+tipo_data, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
</script>

<? include "javascript_pesquisas.php" ;
if(strlen($msg_erro)>0){

echo "<table class='msg_erro' align='center' width='700px'>";
	echo "<tr>";
		echo "<td>";
			echo "$msg_erro";
		echo "</td>";
	echo "</tr>";	
echo "</table>";

}
?>
<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<table width='700' class='formulario' border='0' cellpadding='3' cellspacing='0' align='center'>
	<tr class="titulo_tabela">
		<td>Parâmetros de Pesquisa</td>
	</tr>
	<tr><td colspan="6">&nbsp;</td></tr>
	<tr>
		<td align="center">
			<table width='100%' border='0' cellspacing='0' cellpadding='2' class="formulario">
				<tr>
					<td width="110">&nbsp;</td>
					
					<td align='left'>Data Inicial</td>
					
					<td>Data Final</td>
					
					<td width="10px">&nbsp;</td>
				</tr>
				
				<tr>
					<td width="110">&nbsp;</td>
					
					<td align='left'>
						<input type="text" name="data_inicial" id="data_inicial" size="10" maxlength="10" class='frm' value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class='frm'>
					</td>
					
					<td align='left'>
						<input type="text" name="data_final" id="data_final" size="10" maxlength="10" class='frm' value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class='frm'>
					</td>
					
					<td width="10px">&nbsp;</td>
				</tr>
				
				<tr>
					<td>&nbsp;</td>
					<td>Cód. Posto</td>
					
					<td>Nome Posto</td>
					
					<TD style="width: 10px">&nbsp;</TD>
				</tr>
				
				<tr>
					<td>&nbsp;</td>
					
					<td align='left'nowrap>
					    <INPUT TYPE="text" NAME="codigo_posto" SIZE="10" value='<?=$codigo_posto?>' class='frm'>
					    <IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto,document.frm_relatorio.nome_posto,'codigo')" >
					</td>
					
					<TD align='left' nowrap>
						<INPUT TYPE="text" NAME="nome_posto" size="30" value='<?=$nome_posto?>' class='frm'> <IMG src="imagens/lupa.png" style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="javascript: fnc_pesquisa_posto (document.frm_relatorio.codigo_posto,document.frm_relatorio.nome_posto,'nome')" >
					</TD>
					
					<td>&nbsp;</td>
				</tr>
				
				<tr>
					<TD style="width: 110px">&nbsp;</TD>
					<td>Cód. Peça</td>
					
					<td>Descrição Peça</td>
					
					<TD style="width: 10px">&nbsp;</TD>
				</tr>
				
				<tr>
					<td>&nbsp;</td>
					
					<td align='left'nowrap>
					    <input type="text" name="referencia" value="<? echo $referencia ?>" size="10" maxlength="20" class='frm'>
					    
					    <a href="javascript: fnc_pesquisa_peca (document.frm_relatorio.referencia,document.frm_relatorio.descricao,'referencia')"><IMG SRC="imagens/lupa.png" ></a>
					</td>
					
					<td align='left'nowrap>
						<input type="text" name="descricao" value="<? echo $descricao ?>" size="30" maxlength="50" class='frm'><a href="javascript: fnc_pesquisa_peca (document.frm_relatorio.referencia,document.frm_relatorio.descricao,'descricao')"><IMG SRC="imagens/lupa.png" ></a>
					</td>
					
					<td>&nbsp;</td>
				</tr>
				
			</table>
			
			<br>
			
			<input type='submit' style="cursor:pointer" name='btn_acao' value='Consultar'>
		</td>
	</tr>
</table>
</FORM>
<br>

<?

if (strlen($msg_erro)==0){
	if(strlen($btn_acao)>0){
		
		

		

		$cond_2=" and tbl_pedido.data between '$data_inicial 00:00:00' and '$data_final 23:59:59'";

		$condicao = $cond_2 . $cond_1 . $cond_3;
		
		$sql = "SELECT distinct tbl_pedido.pedido as pedido, tbl_pedido.validade as validade, tbl_status_pedido.descricao as pedido_descricao, tbl_tipo_pedido.descricao as descricao_tipo, tbl_posto.nome AS nome, tbl_posto_fabrica.codigo_posto as codigo, tbl_pedido.data AS data
		FROM tbl_pedido 
		$join
		JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto 
		JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido and tbl_tipo_pedido.fabrica=$login_fabrica
		JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido 
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica=$login_fabrica 
		WHERE tbl_pedido.fabrica=$login_fabrica and tbl_pedido.status_pedido <> 4 and tbl_pedido.status_pedido <> 14 $condicao order by tbl_pedido.pedido desc ;";

		$res = pg_exec ($con,$sql);
			echo "<table align='center' class='tabela' border='0' cellspacing='1' width='700px'>";
			echo "<tr class='titulo_coluna'>
					<td>Pedido</td>
					<td>Descrição</td>
					<td>Tipo Pedido</td>
					<td>Posto</td>
					<td>Data do Pedido</td>
					<td>Atraso</td>
					<td>Validade</td>
				</tr>";

		if (pg_numrows($res) > 0) {
			for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
				$pedido = pg_result ($res,$i,pedido);
				$validade = pg_result ($res,$i,validade);
				$pedido_descricao = pg_result ($res,$i,pedido_descricao);
				$descricao_tipo = pg_result ($res,$i,descricao_tipo);
				$nome = pg_result ($res,$i,nome);
				$codigo = pg_result ($res,$i,codigo);
				$data = pg_result ($res,$i,data);
				
				
				$timestamp = mktime(date("H")+3, date("i"), date("s"), date("m"), date("d"), date("Y"), 0);
				$data_hora = gmdate("Y/m/d", $timestamp);

				//defino data 1 
				$ano1 = substr($data_hora,0,4); 
				$mes1 = substr($data_hora,5,2); 
				$dia1 = substr($data_hora,8,2); 

				//defino data 2 
				$ano2 = substr($data,0,4);
				$mes2 = substr($data,5,2);
				$dia2 = substr($data,8,2); 

				//calculo timestam das duas datas 
				$timestamp1 = mktime(0,0,0,$mes1,$dia1,$ano1); 
				$timestamp2 = mktime(0,0,0,$mes2,$dia2,$ano2);

				$segundos_diferenca = $timestamp1 - $timestamp2; 
				$dias_diferenca = $segundos_diferenca / (60 * 60 * 24); 
				$dias_diferenca = floor($dias_diferenca);

				if($dias_diferenca==1){
					$dias_diferenca=$dias_diferenca." dia";
				}else{
					$dias_diferenca=$dias_diferenca." dias";
				}

				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

				$data = substr($data,8,2) . "/" .substr($data,5,2) . "/" . substr($data,0,4);
		
				echo "<tr style='background-color: $cor;'>
						<td><a href='pedido_admin_consulta.php?pedido=$pedido' target ='_blank'><font color='#000000'>$pedido</font></a></td><td>$pedido_descricao</td>
						<td>$descricao_tipo</td>
						<td align='left'>$codigo - $nome</td>
						<td>$data</td>
						<td>$dias_diferenca</td>
						<td>$validade</td>
					</tr>";	
			}		
		
			echo "</table>";
			
		
		
		}else{
			echo "<center>Nenhum Resultado Encontrado</center>";
		}
	}
}


include "rodape.php" ?>

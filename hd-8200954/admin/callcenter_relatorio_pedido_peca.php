<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO DE ATENDIMENTO";

include "cabecalho.php";

$btn_acao = $_POST['btn_acao'];
if (strlen($btn_acao)>0)

{

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
    }
	
	
		$codigo_posto   = $_POST['codigo_posto'];
	$nome_posto   = $_POST['nome_posto'];

	$cond_1 = " and 1 = 1 ";
	$cond_2 = " and 1 = 1 ";
	$cond_3 = " and 1 = 1 ";

	if( strlen($nome_posto)>0 and strlen($codigo_posto)>0 ){
		
		$sql="	SELECT posto 
				
				FROM tbl_posto_fabrica 
								
				where fabrica=$login_fabrica 
				and codigo_posto='$codigo_posto'";
			// echo nl2br($sql);
		$res=pg_exec($con,$sql);
		if( pg_num_rows($res) >0 ){
			$posto_pesquisa = pg_fetch_result($res,0,'posto');
			
			$cond_1 = " and tbl_pedido.posto=$posto_pesquisa";
		}else{
			$msg_erro = "Posto não encontrado";
		}
	} 

	$cond_2=" and tbl_pedido.data between '$data_inicial 00:00:00' and '$data_final 23:59:59'";	
	
	$referencia   = $_POST['referencia'];
	if(strlen($referencia)>0){
			$sql = "SELECT peca
					FROM tbl_peca 
					WHERE fabrica=$login_fabrica and referencia='$referencia';";
			$res = pg_query ($con,$sql);
		
		if( pg_num_rows($res)>0){
			$peca = pg_result ($res,0,'peca');
			$cond_3=" and tbl_pedido_item.peca = '$peca'";
			$join = " JOIN tbl_pedido_item ON tbl_pedido_item.pedido = tbl_pedido.pedido ";
		}else{
			$msg_erro = "Peça não encontrada";
		}
	}

	$condicao = $cond_2 . $cond_1 . $cond_3;


}

?>
<style>
.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: bold;
	color:#ffffff;
	background-color: #445AA8;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

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
	font-size: 9px;
	font-weight: normal;
}
.ConteudoBranco {
	font-family: Arial;
	font-size: 9px;
	color:#FFFFFF;
	font-weight: normal;
}
.Mes{
	font-size: 9px;
}
.Caixa{
	BORDER-RIGHT: #6699CC 1px solid;
	BORDER-TOP: #6699CC 1px solid;
	FONT: 8pt Arial ;
	BORDER-LEFT: #6699CC 1px solid;
	BORDER-BOTTOM: #6699CC 1px solid;
	BACKGROUND-COLOR: #FFFFFF;
}
.Exibe{
	font-family: Arial, Helvetica, sans-serif;
	font-size: 8 px;
	font-weight: none;
	color: #000000;
	text-align: center;
}
.Erro{
	BORDER-RIGHT: #990000 1px solid;
	BORDER-TOP: #990000 1px solid;
	FONT: 10pt Arial ;
	COLOR: #ffffff;
	BORDER-LEFT: #990000 1px solid;
	BORDER-BOTTOM: #990000 1px solid;
	BACKGROUND-COLOR: #FF0000;
}
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
    width: 700px;
    margin: 0 auto;
}

.formulario{
    background-color:#D9E2EF;
    font:11px Arial;
    text-align:left;
}

.espaco{
    padding-left: 100px;
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
    echo "<div class='msg_erro'>{$msg_erro}</div>";
}
?>


<FORM name="frm_relatorio" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<table width='700' class='formulario' border='0' cellpadding='3' cellspacing='2' align='center'>
	<tr>
		<td class='titulo_tabela' colspan='100%'>Parâmetros de Pesquisa</td>
	</tr>
	<tr>
	    <td width='*' class='espaco'>
	        Data Inicial<br>
	        <input type="text" name="data_inicial" id="data_inicial" size="12" maxlength="10" class='frm'  value="<? if (strlen($data_inicial) > 0) echo $data_inicial; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
	    </td>
	    <td width='55%'>
	        Data Final<br>
	        <input type="text" name="data_final" id="data_final" size="12" maxlength="10" class='frm'  value="<? if (strlen($data_final) > 0) echo $data_final; else echo "dd/mm/aaaa"; ?>" onclick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';">
	    </td>
	</tr>
	<tr>
	    <td class='espaco'>
	    	Código Posto<br>
	    	<input TYPE="text" NAME="codigo_posto" value="<?=$codigo_posto?>" SIZE="12" class='frm'><IMG src="imagens/lupa.png" style="cursor:pointer " align='absmiddle' alt="Clique aqui para pesquisar postos pelo código" onclick="fnc_pesquisa_posto (document.frm_relatorio.codigo_posto,document.frm_relatorio.nome_posto,'codigo')" >
	    </td>
	    <td>
	    	Nome Posto<br>
	    	<input TYPE="text" NAME="nome_posto" value="<?=$nome_posto?>" size="35" class='frm'>
	    	<IMG src="imagens/lupa.png"  style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas postos pelo nome" onclick="fnc_pesquisa_posto (document.frm_relatorio.codigo_posto,document.frm_relatorio.nome_posto,'nome')" >
	    </td>
	</tr>
	<tr>
	    <td class='espaco'>
	        Código Peça<br>
	        <input type="text" name="referencia" value="<? echo $referencia ?>" class='frm' size="12" maxlength="20">
	        <img src="imagens/lupa.png"  style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas peça pelo código" onclick="fnc_pesquisa_peca (document.frm_relatorio.referencia,document.frm_relatorio.descricao,'referencia')" >
	    </td>
	    <td>
	        Descrição Peça<br>
	        <input type="text" name="descricao" value="<? echo $descricao ?>" class='frm' size="35" maxlength="50">
	        <img src="imagens/lupa.png"  style="cursor:pointer" align='absmiddle' alt="Clique aqui para pesquisas peça pelo nome" onclick="javascript: fnc_pesquisa_peca (document.frm_relatorio.referencia,document.frm_relatorio.descricao,'descricao')" >
	    </td>
	<tr>
		<td align='center' colspan='100%' ><input type='submit' style="cursor:pointer; margin: 10px 0;" name='btn_acao' value='Consultar'></td>
	</tr>
</table>
</FORM>
<br>
<?


if (strlen($msg_erro) == 0){
	if(strlen($btn_acao)>0){
		$data_inicial = $_POST['data_inicial'];
		$data_final   = $_POST['data_final'];


		$sql = "SELECT distinct 
					tbl_pedido.pedido as pedido, 
					tbl_pedido.validade as validade, 
					tbl_status_pedido.descricao as pedido_descricao, 
					tbl_tipo_pedido.descricao as descricao_tipo, 
					tbl_posto.nome AS nome, 
					tbl_posto_fabrica.codigo_posto as codigo, 
					tbl_pedido.data AS data
				FROM tbl_pedido 
				$join
				JOIN tbl_posto ON tbl_pedido.posto = tbl_posto.posto 
				JOIN tbl_tipo_pedido ON tbl_pedido.tipo_pedido = tbl_tipo_pedido.tipo_pedido and tbl_tipo_pedido.fabrica=$login_fabrica
				JOIN tbl_status_pedido ON tbl_pedido.status_pedido = tbl_status_pedido.status_pedido 
				JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto and tbl_posto_fabrica.fabrica=$login_fabrica 
				WHERE tbl_pedido.fabrica=$login_fabrica and tbl_pedido.status_pedido <> 4 and tbl_pedido.status_pedido <> 14 $condicao order by tbl_pedido.pedido desc ;";

		$res = pg_exec ($con,$sql);
		

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

					$cor = "#F7F5F0"; 
					$btn = 'amarelo';
					if ($i % 2 == 0) 
					{
						$cor = '#F1F4FA';
						$btn = 'azul';
					}
					
					
					
					$data = substr($data,8,2) . "/" .substr($data,5,2) . "/" . substr($data,0,4);
				echo "<table align='center' width='700px' class='tabela' border='1' cellspacing='2' cellpadding='1'>";
					echo "<tr class='titulo_coluna'>";
						echo "<td>Pedido</td>";
						echo "<td>Descrição</td>";
						echo "<td>Tipo Pedido</td>";
						echo "<td>Posto</td>";
						echo "<td>Data do Pedido</td>";
						echo "<td>Atraso</td>";
						echo "<td>Validade</td>";
					echo "</tr>";
				
					echo "<tr style='background-color: $cor;'>";
						echo "<td><a href='pedido_admin_consulta.php?pedido=$pedido' target ='_blank'>$pedido</a></td>";
						echo "<td>$pedido_descricao</td>";
						echo "<td>$descricao_tipo</td>";
						echo "<td>$codigo - $nome</td>";
						echo "<td>$data</td>";
						echo "<td>$dias_diferenca</td>";
						echo "<td>$validade</td>";
					echo "</tr>";
				
					echo "<tr style='background-color: $cor;'>";
						echo "<td colspan='7'>";
							echo "<table align='center' border='0' cellspacing='1' cellpadding='5' class='tabela' style='width:100%;'>";
								echo "<tr  class='titulo_coluna'>";
									echo "<td>Peça</td>";
									echo "<td>Qtde Pedida</td>";
									echo "<td>Qtde Faturada</td>";
									echo "<td>Qtde Cancelada</td>";
								echo "</tr>";

						$sql2 = "SELECT tbl_pedido_item.qtde, tbl_pedido_item.qtde_faturada, tbl_pedido_item.qtde_cancelada , tbl_peca.referencia, tbl_peca.descricao as descricao_peca
							FROM tbl_pedido_item 
							JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca and tbl_peca.fabrica=$login_fabrica 
							WHERE tbl_pedido_item.pedido=$pedido";
							$res2 = pg_exec ($con,$sql2);	
							for ($z = 0 ; $z < pg_numrows ($res2) ; $z++) {
								$qtde = pg_result ($res2,$z,qtde);
								$qtde_faturada = pg_result ($res2,$z,qtde_faturada);
								$qtde_cancelada = pg_result ($res2,$z,qtde_cancelada);
								$referencia = pg_result ($res2,$z,referencia);
								$descricao_peca = pg_result ($res2,$z,descricao_peca);
							$cor2 = ($z % 2) ? "#F1F4FA" : "#F7F5F0";
								echo "<tr style='background-color: $cor2;'>";
									echo "<td align='left'>$referencia - $descricao_peca</td>";
									echo "<td>$qtde</td>";
									echo "<td>$qtde_faturada</td>";
									echo "<td>$qtde_cancelada</td>";
								echo "</tr>";

							}
							echo "</table>";
						echo "</td>";
					echo "</tr>";
			echo "</table>";
			echo "<br>";
		}
		

		
		}else{
			echo "<center>Nenhum Resultado Encontrado</center>";
		}
	}

}
?>

<p>

<? include "rodape.php" ?>

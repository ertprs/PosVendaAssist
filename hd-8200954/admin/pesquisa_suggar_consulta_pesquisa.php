<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO PERÍODO DE ATENDIMENTO";


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
</style>

<script>
function AbreCallcenter(data_inicial,data_final,produto,natureza,status){
janela = window.open("callcenter_relatorio_periodo_atendimento_callcenter.php?data_inicial=" +data_inicial+ "&data_final=" +data_final+ "&produto=" +produto+ "&natureza=" +natureza+ "&status=" +status, "Callcenter",'scrollbars=yes,width=750,height=450,top=315,left=0');
	janela.focus();
}
</script>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language='javascript' src='../ajax.js'></script>

<? include "javascript_pesquisas.php" ?>



<?
	$x_data_inicial = $data_inicial       = $_GET['data_inicial'];
	$x_data_final   = $data_final         = $_GET['data_final'];
	$produto            = $_GET['produto'];


	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";

	if(strlen($produto)>0){
		$sql = "select tbl_produto.referencia,
						tbl_produto.descricao, tbl_produto.produto
				FROM tbl_produto
							where tbl_produto.produto = $produto";
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			$produto_referencia = pg_result($res,0,referencia);
			$produto_descricao = pg_result($res,0,descricao);
			$produto = pg_result($res,0,produto);
			$cond_1 = "tbl_os.produto = $produto";
		}
	}

	if (strlen($x_data_inicial) > 0 AND strlen($x_data_final) > 0){
		$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
		$x_data_final   = fnc_formata_data_pg($x_data_final);
		$y_data_inicial = substr($x_data_inicial,9,2) . substr($x_data_inicial,6,2) . substr($x_data_inicial,1,4);
		$y_data_final = substr($x_data_final,9,2) . substr($x_data_final,6,2) . substr($x_data_final,1,4);
		
		if ($x_data_inicial != "null") {
			$data_inicial = substr($x_data_inicial,9,2) . "/" . substr($x_data_inicial,6,2) . "/" . substr($x_data_inicial,1,4);
		}else{
			$data_inicial = "";
			$erro .= " Preencha correto o campo Data Inicial.<br> ";
		}
		
		if ($x_data_final != "null") {
			$data_final = substr($x_data_final,9,2) . "/" . substr($x_data_final,6,2) . "/" . substr($x_data_final,1,4);
		}else{
			$data_final = "";
			$erro .= " Preencha correto o campo Data Final.<br> ";
		}

		if(strlen($erro) == 0){
			$cond_2 = " tbl_suggar_questionario.data_input between $x_data_inicial and $x_data_final ";
		}
	}

	if(strlen($msg_erro)==0){

######################################
		$sql = "select 	tbl_suggar_questionario.questionario          ,
						tbl_suggar_questionario.os                    ,
						tbl_produto.referencia as produto_referencia  ,
						tbl_os.consumidor_nome,
						tbl_os.consumidor_cidade,
						tbl_os.consumidor_estado,
						tbl_os.consumidor_fone
				from tbl_suggar_questionario 
				join tbl_os on tbl_os.os = tbl_suggar_questionario.os and $cond_1
				JOIN tbl_produto on tbl_produto.produto = tbl_os.produto
				WHERE $cond_2 AND satisfeito is not true";
		echo '<!--',$sql,'-->';

		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<table width='100%' border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px; font-family:verdana;'>";
			echo "<TR >\n";
		//	echo "<td class='menu_top' background='imagens_admin/azul.gif'>#</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>OS</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Consumidor</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Produto</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Cidade</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Estado</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Telefone</TD>\n";

			echo "</TR >\n";
			for($y=0;pg_numrows($res)>$y;$y++){
				$questionario       = pg_result($res,$y,questionario);
				$os           = pg_result($res,$y,os);
				$produto_referencia         = pg_result($res,$y,produto_referencia);
				$consumidor_nome            = pg_result($res,$y,consumidor_nome);
				$consumidor_cidade        = pg_result($res,$y,consumidor_cidade);
				$consumidor_estado= pg_result($res,$y,consumidor_estado);
				$consumidor_fone          = pg_result($res,$y,consumidor_fone);
			
				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
				echo "<TR bgcolor='$cor'>\n";
			//	echo "<TD align='center' nowrap><a href='cadastra_callcenter.php?callcenter=$callcenter' target='blank'>$questionario</a></TD>\n";
				echo "<TD align='left' nowrap>$os</TD>\n";
				echo "<TD align='center' nowrap>$consumidor_nome</TD>\n";
				echo "<TD align='center' nowrap>$produto_referencia</TD>\n";
				echo "<TD align='left' nowrap>$consumidor_cidade</TD>\n";
				echo "<TD align='center' nowrap>$consumidor_estado</TD>\n";
				echo "<TD align='left' nowrap>$consumidor_fone</TD>\n";
				echo "</TR >\n";
			}
			echo "</table>";
			echo "<center>Quantidade de registros: ";
			echo pg_numrows($res);
			echo "</center>";
		}


	}

?>

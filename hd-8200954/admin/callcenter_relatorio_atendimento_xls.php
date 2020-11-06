<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';

include 'autentica_admin.php';

include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO DE ATENDIMENTO";

include "cabecalho.php";

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



<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>
<script language='javascript' src='../ajax.js'></script>


<? include "javascript_pesquisas.php" ?>
<?	$data = date ("d-m-Y-H-i");

	echo `mkdir /tmp/assist`;
	echo `chmod 777 /tmp/assist`;
	//echo `rm /var/www/assist/www/download/tabela-$data-$nome_fabrica-$login_posto.xls`;
	echo `rm /tmp/assist/callcenter_relatorio_atendimento-$login_fabrica-$data.html`;
	echo `rm /tmp/assist/callcenter_relatorio_atendimento-$login_fabrica-$data.xls`;
	echo `rm /var/www/assist/www/download/callcenter_relatorio_atendimento-$login_fabrica-$data.zip`;

	$fp = fopen ("/tmp/assist/callcenter_relatorio_atendimento-$login_fabrica-$data.html","w");


	fputs ($fp,"<html>");
	fputs ($fp,"<head>");
	fputs ($fp,"<title>Relatório de Atendimento</title>");
	fputs ($fp,"<meta name='Author' content='TELECONTROL NETWORKING LTDA'>");
	fputs ($fp,"</head>");
	fputs ($fp,"<body>");


	$xdata_inicial = $_GET['data_inicial'];
	$xdata_final   = $_GET['data_final'];
	$produto       = $_GET['produto'];
	$natureza_chamado   = $_GET['natureza_chamado'];
	$status             = $_GET['status'];
	$image_graph        = $_GET['imagem'];
	
	$cond_1 = " 1 = 1 ";
	$cond_2 = " 1 = 1 ";
	$cond_3 = " 1 = 1 ";

	if(strlen($produto)>0){
			$cond_1 = " tbl_hd_chamado_extra.produto = $produto ";
	}

	if(strlen($natureza_chamado)>0){
		$cond_2 = " tbl_hd_chamado.categoria = '$natureza_chamado' ";
	}
	if(strlen($status)>0){
		$cond_3 = " tbl_hd_chamado.status = '$status'  ";
	}

	if(strlen($msg_erro)==0){
		$sql = "SELECT tbl_hd_chamado.status,
						count(tbl_hd_chamado.hd_chamado) as qtde
				from tbl_hd_chamado
				join tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
				where tbl_hd_chamado.fabrica_responsavel = $login_fabrica
				and tbl_hd_chamado.data between '$xdata_inicial 00:00:00' and '$xdata_final 23:59:59'
				and $cond_1
				and $cond_2
				and $cond_3
				GROUP BY tbl_hd_chamado.status
					order by qtde desc
			";	
		echo $sql;
		if($ip=="201.43.29.144")echo $sql;
		$res = pg_exec($con,$sql);
		if(pg_numrows($res)>0){
			echo "<table width='500' border='0' align='center' cellpadding='1' cellspacing='2' style='border:#485989 1px solid; background-color: #e6eef7;font-size:11px'>";
			echo "<TR >\n";
			echo "<td class='menu_top' background='imagens_admin/azul.gif'>Status</TD>\n";
			echo "<TD class='menu_top' background='imagens_admin/azul.gif'>Qtde</TD>\n";
			echo "</TR >\n";
			for($y=0;pg_numrows($res)>$y;$y++){
				$status_desc = pg_result($res,$y,status);
				$qtde   = pg_result($res,$y,qtde);
				$grafico_status[] = $status_desc;
				$grafico_qtde[] = $qtde;

				if ($y % 2 == 0) {$cor = '#F1F4FA';}else{$cor = '#e6eef7';}
				echo "<TR bgcolor='$cor'>\n";
				echo "<TD align='left' nowrap>$status_desc</TD>\n";
				echo "<TD align='center' nowrap>$qtde</TD>\n";
				echo "</TR >\n";
			}
			echo "</table>";
		
		echo "<BR><BR>";
		
		fputs ($fp,"<img src='$image_graph'>\n\n");

			//gera o xls
		echo `htmldoc --webpage --size 297x210mm --fontsize 11 --left 8mm -f /tmp/assist/callcenter_relatorio_atendimento-$login_fabrica-$data.xls /tmp/assist/callcenter_relatorio_atendimento-$login_fabrica-$data.html`;

		//gera o zip
		echo `cd /tmp/assist/; rm -rf callcenter_relatorio_atendimento-$login_fabrica-$data.zip; zip -o callcenter_relatorio_atendimento-$login_fabrica-$data.zip callcenter_relatorio_atendimento-$login_fabrica-$data.xls > /dev/null`;

		//move o zip para "/var/www/assist/www/download/"
		echo `mv  /tmp/assist/callcenter_relatorio_atendimento-$login_fabrica-$data.zip /var/www/assist/www/download/callcenter_relatorio_atendimento-$login_fabrica-$data.zip`;

		echo"<table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>";
		echo"<tr>";
		echo "<td align='center'<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>Clique aqui para fazer o </font><a href='../download/callcenter_relatorio_atendimento-$login_fabrica-$data.zip'><font face='Arial, Verdana, Times, Sans' size='2' color='#0000FF'>download do arquivo</font></a>.</td>";
		echo "</tr>";
		echo "</table>";


		}
	}


?>

<p>

<? include "rodape.php" ?>

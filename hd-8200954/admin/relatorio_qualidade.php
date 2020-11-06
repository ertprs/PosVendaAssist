<?
/*Sql esta mto pesado, encaminhei para o Túlio verificar, porem ele e o Wellington estão alterando a estrutura das tbl_os_item, tbl_faturamento, tbl_faturamento_item. Aguardar mundaça para melhorar o sql.  Takashi*/
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios = "gerencia";
include 'autentica_admin.php';

$layout_menu = "gerencia";
$title = "Relatório de Qualidade";

include "cabecalho.php";
$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
?>

<style type="text/css">


a:link.top   { color:#ffffff; }
a:visited.top{ color:#ffffff; }
a:hover.top  { color:#ffffff; }

.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

</style>
<script language="JavaScript">
function mostraOS(qual){
	if (document.getElementById(qual)){
		var style2 = document.getElementById(qual); 
		if (style2==false) return; 
		if (style2.style.display=="block"){
			style2.style.display = "none";
		}else{
			style2.style.display = "block";
		}
	}
}
</script>
<form name="frm_consulta" method="post" action="<?echo $PHP_SELF?>">

<input type="hidden" name="acao">
<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
	<tr class="Titulo" height="30">
		<td align="center">Selecione os parâmetros para a pesquisa.</td>
	</tr>
</table>
	<table width="400" align="center" border="0" cellspacing="0" cellpadding="2">
<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td width='50%' align='center'> Mês</td>
		<td width='50%' align='center'> Ano</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
		<td width='50%' align='center'>
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
		</td>
		<td width='50%' align='center'>
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
		</td>

	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td colspan='2' align='center'><br><input type="submit" name="btn_acao" value="Pesquisar"></td>
	</tr>
</table>

</form>
<?
flush();
echo "<br>";
$btn_acao = $_POST['btn_acao'];
if(strlen($btn_acao)>0){
	$mes = $_POST['mes'];
	$ano = $_POST['ano'];
	if (strlen($mes) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	}

/*MENOR*/
	echo "<center>Aguarde</center>";	flush(); 
	/*
			SELECT os_item,os_produto,digitacao_item,peca,pedido
		INTO TEMP tmp_rq1_$login_admin
		FROM   tbl_os_item 
		WHERE  tbl_os_item.digitacao_item BETWEEN '$data_inicial' AND '$data_final';
		
		create index tmp_rq1_os_produto_$login_admin ON tmp_rq1_$login_admin(os_produto);
		
		SELECT DISTINCT tbl_os_produto.os , X.digitacao_item, X.peca,X.pedido
		INTO TEMP tmp_rq2_$login_admin
		FROM tmp_rq1_$login_admin X
		JOIN tbl_os_produto USING(os_produto);
	*/
	$sql = "
		SELECT os_item,os,digitacao_item,peca,pedido
		INTO TEMP tmp_rq2_$login_admin
		FROM tbl_os
		JOIN tbl_os_produto USING(os)
		JOIN tbl_os_item    USING(os_produto)
		WHERE tbl_os.fabrica = 6
		AND   tbl_os_item.digitacao_item BETWEEN '$data_inicial' AND '$data_final';
		
		CREATE INDEX  tmp_rq2_pedido_$login_admin         ON tmp_rq2_$login_admin(pedido);
		CREATE INDEX  tmp_rq2_peca_$login_admin           ON tmp_rq2_$login_admin(peca);
		CREATE INDEX  tmp_rq2_data_digitacao_$login_admin ON tmp_rq2_$login_admin(digitacao_item);
		";
	flush(); 
	$res = pg_exec($con,$sql);

	$sql = "SELECT DISTINCT X.os,
		F.emissao - to_char(X.digitacao_item, 'YYYY-MM-DD')::date as intervalo
		INTO TEMP tmp_rq3_$login_admin
		FROM  tmp_rq2_$login_admin X
		JOIN  tbl_faturamento_item FI ON X.peca        = FI.peca AND  X.pedido = FI.pedido
		JOIN  tbl_faturamento      F  ON F.faturamento = FI.faturamento
		WHERE F.fabrica = $login_fabrica
		AND   F.emissao  < X.digitacao_item + '10 days'::interval ;

		CREATE INDEX tmp_rq3_OS_$login_admin ON tmp_rq3_$login_admin(os);

		SELECT 	tbl_os.os                       ,
			tbl_os.sua_os                   ,
			tbl_posto.nome                  ,
			tbl_posto_fabrica.codigo_posto  ,
			tbl_produto.referencia          ,
			tbl_produto.descricao           ,
			intervalo
		FROM tmp_rq3_$login_admin  X
		JOIN tbl_os            ON tbl_os.os       = X.os
		JOIN tbl_posto         ON tbl_posto.posto = tbl_os.posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND  tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN tbl_produto       ON tbl_os.produto  = tbl_produto.produto
		ORDER BY intervalo";
	flush(); //echo nl2br($sql);exit;
	$res = pg_exec($con,$sql);
	$qtde_menor = pg_numrows($res);

/*MAIOR*/
	$sql = "SELECT DISTINCT X.os,
		F.emissao - to_char(X.digitacao_item, 'YYYY-MM-DD')::date as intervalo
		INTO TEMP tmp_rq4_$login_admin
		FROM  tmp_rq2_$login_admin X
		JOIN  tbl_faturamento_item FI ON X.peca        = FI.peca AND  X.pedido = FI.pedido
		JOIN  tbl_faturamento      F  ON F.faturamento = FI.faturamento
		WHERE F.fabrica = $login_fabrica
		AND   F.emissao > X.digitacao_item + '10 days'::interval ;

		CREATE INDEX tmp_rq4_OS_$login_admin ON tmp_rq4_$login_admin(os);

		SELECT 	tbl_os.os                       ,
			tbl_os.sua_os                   ,
			tbl_posto.nome                  ,
			tbl_posto_fabrica.codigo_posto  ,
			tbl_produto.referencia          ,
			tbl_produto.descricao           ,
			intervalo
		FROM tmp_rq4_$login_admin  X
		JOIN tbl_os            ON tbl_os.os       = X.os
		JOIN tbl_posto         ON tbl_posto.posto = tbl_os.posto
		JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND  tbl_posto_fabrica.fabrica = $login_fabrica
		JOIN tbl_produto       ON tbl_os.produto  = tbl_produto.produto
		ORDER BY intervalo";
	$xres = pg_exec($con,$sql);
	$qtde_maior = pg_numrows($xres);
	flush(); echo ".";//nl2br($sql);
if(strlen($qtde_maior)>0 or strlen($qtde_menor)>0){
	$total = $qtde_menor + $qtde_maior;
	$xqtde_menor = round((100*$qtde_menor)/$total,2);
	$xqtde_maior = round((100*$qtde_maior)/$total,2);

	$xqtde_total = array($xqtde_menor,$xqtde_maior);

	echo "<table width='650' align='center' border='0' cellspacing='1' cellpadding='4' style='font-size:11px; font-family:verdana;'>";
	echo "<tr>";
	echo "<td align='left' bgcolor='#596D9B' ><font color='#FFFFFF'><b>Descrição</b></FONT></td>";
	echo "<td bgcolor='#596D9B'><font color='#FFFFFF'><b>$meses[$mes]/$ano</b></FONT></td>";
	echo "<td bgcolor='#596D9B'><font color='#FFFFFF'><b>%</b></FONT></td>";
	echo "<td bgcolor='#596D9B'><font color='#FFFFFF'><b>Detalhes</b></FONT></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td align='left'><b>Qtde de OS's atendidas no prazo de 10 dias</b></td>";
	echo "<td>$qtde_menor</td>";
	echo "<td>$xqtde_menor</td>";
	echo "<td><a href=\"javascript:mostraOS('dados_menor');\">Abrir</a></td>";
	echo "</tr>";
	if($qtde_menor>0){
		echo "<tr>";
		echo "<td colspan='4'>";
		echo "<div id='dados_menor' style='position:relative; display:none; border: 1px solid #949494;width:650px;'>";
		echo "<table width='650' align='center' border='0' cellspacing='1' cellpadding='2' bgcolor='#667a9d' style='font-size:10px; font-family:verdana;'>";
		echo "<tr>";
		echo "<td><b><font color='#FFFFFF'>OS</FONT></b></td>";
		echo "<td><b><font color='#FFFFFF'>Posto</FONT></b></td>";
		echo "<td><b><font color='#FFFFFF'>Produto</FONT></b></td>";
		echo "<td><b><font color='#FFFFFF'>Intervalo</FONT></b></td>";
		echo "</tr>";
		
		for($x=0;$x<$qtde_menor;$x++){
			
			$os                 = pg_result($res,$x,os          );
			$sua_os             = pg_result($res,$x,sua_os      );
			$posto_nome         = pg_result($res,$x,nome        );
			$codigo_posto       = pg_result($res,$x,codigo_posto);
			$produto_referencia = pg_result($res,$x,referencia  );
			$produto_descricao  = pg_result($res,$x,descricao   );
			$intervalo          = pg_result($res,$x,intervalo   );
			
			$cor = "#efeeea"; 
			if ($x % 2 == 0) $cor = '#d2d7e1';
	
			echo "<tr bgcolor='$cor'>";
			echo "<td><a href='os_press.php?os=$os' target='blank'>$sua_os</a></td>";
			echo "<td align='left' >$codigo_posto $posto_nome </td>";
			echo "<td align='left' >$produto_referencia - $produto_descricao</td>";
			echo "<td >$intervalo</td>";
			echo "</tr>";
		}
	
		echo "</table>";
		echo "</div>";
		echo "</td>";
		echo "</tr>";
	}
	echo "<tr>";
	echo "<td align='left'><b>Qtde de OS's posterior ao prazo de 10 dias</b></td>";
	echo "<td>$qtde_maior</td>";
	echo "<td>$xqtde_maior</td>";
	echo "<td><a href=\"javascript:mostraOS('dados_maior');\">Abrir</a></td>";
	echo "</tr>";
	if($qtde_maior>0){
		echo "<tr>";
		echo "<td colspan='4'>";
		echo "<div id='dados_maior' style='position:relative; display:none; border: 1px solid #949494;width:650px;'>";
		echo "<table width='650' align='center' border='0' cellspacing='1' cellpadding='2' bgcolor='#667a9d' style='font-size:10px; font-family:verdana;'>";
		echo "<tr>";
		echo "<td><b><font color='#FFFFFF'>OS</FONT></b></td>";
		echo "<td><b><font color='#FFFFFF'>Posto</FONT></b></td>";
		echo "<td><b><font color='#FFFFFF'>Produto</FONT></b></td>";
		echo "<td><b><font color='#FFFFFF'>Intervalo</FONT></b></td>";
		echo "</tr>";
		
		for($y=0;$y<$qtde_maior;$y++){
			
			$os                 = pg_result($xres,$y,os          );
			$sua_os             = pg_result($xres,$y,sua_os      );
			$posto_nome         = pg_result($xres,$y,nome        );
			$codigo_posto       = pg_result($xres,$y,codigo_posto);
			$produto_referencia = pg_result($xres,$y,referencia  );
			$produto_descricao  = pg_result($xres,$y,descricao   );
			$intervalo          = pg_result($xres,$y,intervalo   );
			
			$cor = "#efeeea"; 
			if ($y % 2 == 0) $cor = '#d2d7e1';
	
			echo "<tr bgcolor='$cor'>";
			echo "<td><a href='os_press.php?os=$os' target='blank'>$sua_os</a></td>";
			echo "<td align='left'>$codigo_posto $posto_nome </td>";
			echo "<td align='left'>$produto_referencia - $produto_descricao</td>";
			echo "<td>$intervalo</td>";
			echo "</tr>";
		}
	
		echo "</table>";
		echo "</div>";
		echo "</td>";
		echo "</tr>";
	}

	echo "</tr>";
	echo "<tr>";
	echo "<td colspan='4'>";
	echo "<div id='dados_maior' style='position:absolute; display:none; border: 1px solid #949494;background-color: #b8b7af;width:593px;'></div>";
	echo "</td>";
	echo "</tr>";
	echo "</table>";

	include ("../jpgraph/jpgraph.php");
	include ("../jpgraph/jpgraph_pie.php");
	include ("../jpgraph/jpgraph_pie3d.php");

	// nome da imagem
	$img = time();
	$image_graph = "png/03_$img.png";
	
	// seleciona os dados das médias
	setlocale (LC_ALL, 'et_EE.ISO-8859-1');


	//$data = array(40,60,21,33);
	$xlegenda = array("Inferior a 10 dias", "Superior a 10 dias");
	$graph = new PieGraph(400,200,"auto");
	$graph->SetShadow();
	
	$graph->title->Set("Relatório Atendimento de OS");
	$graph->title->SetFont(FF_FONT1,FS_BOLD);
	
	//$pieplot->SetColor('red'); 
	
	$p1 = new PiePlot3D($xqtde_total);
	$p1->SetSize(0.5);
	$p1->SetCenter(0.45);
	$p1->SetLegends($xlegenda);
	//$p1->SetColor('blue','red');
	$p1->SetSliceColors(array('blue','red'));
	$graph->Add($p1);

	$graph->Stroke($image_graph);
	echo "\n\n<img src='$image_graph'>\n\n";
}
}
include "rodape.php"; 

?>
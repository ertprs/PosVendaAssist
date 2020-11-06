<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';


if($_POST['qtde_itens']){

	$qtde_itens = $_POST['qtde_itens'];
	$fabrica    = $_POST['fabrica'];
	
	$sql = "SELECT tipo_pedido FROM tbl_tipo_pedido WHERE fabrica = $fabrica AND upper(descricao) = 'GARANTIA'";
	$res = pg_query($con,$sql);
	if(pg_numrows($res) > 0){
		$tipo_pedido = pg_result($res,0,0);
	}

	$sql = "SELECT condicao FROM tbl_condicao WHERE fabrica = $fabrica AND upper(descricao) = 'GARANTIA'";
	$res = pg_query($con,$sql);
	if(pg_numrows($res) > 0){
		$condicao = pg_result($res,0,0);
	}
	
	$res = pg_query($con,'BEGIN');

	$posto = ($fabrica  == 51) ? 20682 : 4311;

	$sql = "INSERT INTO tbl_pedido (
					posto          ,
					fabrica        ,
					condicao       ,
					tipo_pedido    
				) VALUES (
					$posto         ,
					$fabrica       ,
					$condicao      ,
					$tipo_pedido    
				)";
	$res = @pg_query ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen($msg_erro) == 0){
		$res = @pg_query ($con,"SELECT CURRVAL ('seq_pedido')");
		$pedido  = @pg_fetch_result ($res,0,0);
	}

	if (strlen ($msg_erro) == 0) {
		for($i = 0; $i < $qtde_itens; $i++){

			$peca = $_POST['peca_'.$i];
			$qtde = $_POST['qtde_pedido_'.$i];
			
			if( strlen($qtde) > 0 AND $qtde > 0 AND strlen($peca) > 0){
				$sql = "INSERT INTO tbl_pedido_item (
											pedido ,
											peca   ,
											qtde
										) VALUES (
											$pedido ,
											$peca   ,
											$qtde
										)";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
			if (strlen($msg_erro) == 0) {
				$sql = "SELECT fn_valida_pedido_item ($pedido,$peca,$fabrica) ";
				$res = @pg_query ($con,$sql);
				$msg_erro = pg_errormessage($con);
			}
		}
	}

	if (strlen ($msg_erro) == 0) {
		$sql = "SELECT fn_pedido_finaliza ($pedido,$fabrica)";
		$res = @pg_query ($con,$sql);
		$msg_erro = pg_errormessage($con);
	}

	if (strlen ($msg_erro) == 0) {
		$res = pg_query ($con,"COMMIT TRANSACTION");
		$msg = "Pedido $pedido foi finalizado com sucesso";
	}else{
		$res = pg_query ($con,"ROLLBACK TRANSACTION");
	}
}

echo "<html>";
echo "<head>";
echo "<title>Compra Manual</title>";
?>
<link rel="stylesheet" href="../js/tinybox2/style.css" />
<link type="text/css" rel="stylesheet" href="css/css.css">
<style type='text/css' screen='all'>
.frm {
	background-color:#F0F0F0;
	border:1px solid #888888;
	font-family:Verdana;
	font-size:8pt;
	font-weight:bold;
}
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
	padding: 3px 0;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;

}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
		padding: 2px;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.sucesso{
    background-color:#008000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.espaco{padding:0 0 0 80px; }
</style>
<script src='js/jquery.js'></script>
<script src='http://www.telecontrol.com.br/js/shortcut.js'></script>
</head>
<body>

<?
include 'menu.php' ;
    function comparaSelect($valor1, $valor2){
        $valor1 == $valor2 ? $select = " selected style='color: #f00' " : $select = "";	
	
	    return $select;
    }
    
	$res_fabrica = pg_exec($con,"SELECT fabrica, nome FROM tbl_fabrica WHERE fabrica IN($telecontrol_distrib)");

	$fabrica = $_POST['fabrica'];
	if(pg_numrows($res_fabrica)){
		while($dado_fabrica = pg_fetch_array($res_fabrica))
			$combo .= "<option value='{$dado_fabrica[0]}' ".comparaSelect($dado_fabrica[0],$fabrica).">{$dado_fabrica[1]}</option>\n";
?>
<br><br>
<?php if(!empty($msg)){?>
		<table align='center' class='sucesso' width='700px' border='0'>
			<tr><td><?=$msg?></td></tr>
		</table>
<?php } ?>

<?php if(!empty($msg_erro)){?>
		<table align='center' class='msg_erro' width='700px' border='0'>
			<tr><td><?=$msg_erro?></td></tr>
		</table>
<?php } ?>

<table align='center' class='formulario' width='700px' border='0'>
	<tr>
		<td class='titulo_tabela'>Filtro de Fábrica&nbsp;
			<img src="../imagens/help.png" alt="Ajuda da Tela"  onclick='showHelp()'
				style='vertical-align:middle;cursor:help;'
				title='clique ou digite "h" para ajuda sobre as colunas.' />
		</td>
	</tr>
	<tr>
		<td style='padding: 20px; text-align: center; font-weight: normal;'>
		<form method='post'>Fábrica &nbsp;
				<select name='fabrica' class='frm' style='width: 350px;'>
					<option value='' <?=comparaSelect('0',$fabrica)?>>Selecione o Fabricante</option>
					<option value='0' <?=comparaSelect('0',$fabrica)?>>Todos</option>
					<?=$combo?>
				</select>
				<br>
				<input type="checkbox" value="c" name="peca_critica" <? echo ($_POST['peca_critica']) ? "CHECKED" : ""; ?> title="Incluir peças críticas no relatório">Incluir Peça Crítica
				<input type="checkbox" value="t" name="peca_obrigatoria" <? echo ($_POST['peca_obrigatoria']) ? "CHECKED" : ""; ?> title="Incluir peças de devolução obrigatória no relatório">Incluir Peças de Troca Obrigatória
				<br><br>
				<input type='submit' value=' Filtrar Dados ' />
			</form>
 	   </td>
	</tr>
</table>
<br>
<?	} 
	// Fim Ederson
?>
<script type="text/javascript" src='../js/tinybox2/tinybox.js'></script>
<script type="text/javascript">
	function showHelp() {
		TINY.box.show({iframe:'manual_compra_manual.html',width:600,height:550});
	}
	shortcut.add(
		'h',
		function() {
			showHelp();
	});
</script>
<?
$cond = " WHERE (tbl_peca.peca_critica IS FALSE OR tbl_peca.troca_obrigatoria IS FALSE) ";

if($_POST['peca_critica'] AND !$_POST['peca_obrigatoria']){
	$cond = " WHERE tbl_peca.troca_obrigatoria IS FALSE ";
} else if($_POST['peca_obrigatoria'] AND !$_POST['peca_critica']){
	$cond = " WHERE tbl_peca.peca_critica IS FALSE ";
} else if($_POST['peca_critica'] AND $_POST['peca_obrigatoria']) {
	$cond = " WHERE 1=1 ";
}

$sqlP = "SELECT DISTINCT tbl_embarque_item.peca, FALSE AS faturar, 0 as qtde1, 0 AS qtde2, 0 AS qtde_embarcada
		INTO TEMP tmp_peca_embarcada 
		FROM tbl_embarque_item
		JOIN tbl_embarque USING (embarque);

		UPDATE tmp_peca_embarcada 
                   SET qtde1 = (
		                 SELECT SUM(qtde) FROM tbl_embarque_item
				   JOIN tbl_embarque USING (embarque)
				  WHERE tmp_peca_embarcada.peca = tbl_embarque_item.peca
				    AND tbl_embarque.faturar IS NULL
				)
		  FROM tbl_embarque_item JOIN tbl_embarque USING(embarque)
		 WHERE tmp_peca_embarcada.peca = tbl_embarque_item.peca 
		   AND tbl_embarque.faturar IS NULL;

		UPDATE tmp_peca_embarcada SET qtde2 = tbl_posto_estoque.qtde FROM tbl_posto_estoque WHERE tbl_posto_estoque.peca = tmp_peca_embarcada.peca;

		UPDATE tmp_peca_embarcada SET qtde_embarcada =qtde1+qtde2;

";
$resP = pg_query($con,$sqlP);

//echo nl2br($sqlP);		

//echo pg_last_error();die;
if($fabrica == '' or $fabrica == '0') {
	$sql = "
            SELECT tbl_peca.referencia, tbl_peca.descricao, x_pedido_51.*
              FROM x_pedido_51
              JOIN tbl_peca USING (peca)
             UNION 
            SELECT tbl_peca.referencia, tbl_peca.descricao, x_pedido_81.*
              FROM x_pedido_81
              JOIN tbl_peca USING (peca)
             ORDER BY qtde_comprar desc";
	$sql = "SELECT tbl_peca.peca,tbl_peca.referencia, tbl_peca.descricao, x_pedido_51.*, round(media_ok * qtdeX/2,0) as  estoque_minimo, 
			((round(media_ok * qtdeX/2,0) + qtde ) - (x_pedido_51.estoque + x_pedido_51.fabrica + x_pedido_51.transp + tmp_peca_embarcada.qtde_embarcada)) as comprar,
			tmp_peca_embarcada.qtde_embarcada
			FROM x_pedido_51 
			JOIN tbl_peca USING (peca) 
			JOIN tmp_peca_embarcada ON x_pedido_51.peca = tmp_peca_embarcada.peca
			$cond
			UNION 
			SELECT tbl_peca.peca,tbl_peca.referencia, tbl_peca.descricao, x_pedido_81.*, round(media_ok * qtdeX/2,0) as  estoque_minimo, 
			((round(media_ok * qtdeX/2,0) + qtde ) - (x_pedido_81.estoque + x_pedido_81.fabrica + x_pedido_81.transp + tmp_peca_embarcada.qtde_embarcada)) as comprar,
			tmp_peca_embarcada.qtde_embarcada
			FROM x_pedido_81 
			JOIN tbl_peca USING (peca) 
			JOIN tmp_peca_embarcada ON x_pedido_81.peca = tmp_peca_embarcada.peca
			$cond
			ORDER BY comprar desc";
} else {
    $sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, x_pedido_$fabrica.*  FROM x_pedido_$fabrica JOIN tbl_peca USING (peca) ORDER BY x_pedido_$fabrica.qtde_comprar desc ";
	$sql = "SELECT tbl_peca.peca,tbl_peca.referencia, tbl_peca.descricao, x_pedido_$fabrica.*, media * qtdeX/2 as  estoque_minimo, 
			((media * qtdeX/2 + qtde ) - (x_pedido_$fabrica.estoque + x_pedido_$fabrica.fabrica + x_pedido_$fabrica.transp + tmp_peca_embarcada.qtde_embarcada)) as comprar,
			tmp_peca_embarcada.qtde_embarcada
			FROM x_pedido_$fabrica 
			JOIN tbl_peca USING (peca)
			JOIN tmp_peca_embarcada ON x_pedido_$fabrica.peca = tmp_peca_embarcada.peca
			$cond
			ORDER BY comprar desc";
}
$res  = pg_exec ($con,$sql);

if(pg_num_rows($res)==0) {
	echo "<br><center><b>Não existem peças para compra manual</center></b>";
	include "rodape.php";
	exit;
}?>

<script>
	function ocultar_mostrar(){ 
		$('.ocultar').toggle();
	} 
</script>

<form name="frm_pedido" action="" method="post">
<table id='tabela' align='center' border='0' cellspacing='1' cellpadding='1' class='tabela'>
	<tr>
		<td colspan='16' style='text-align: right; padding: 2px; border: none;'>
			<a href='#' class='mostrar' id='campos_mostrar' onclick='ocultar_mostrar();' >MOSTRAR / OCULTAR CAMPOS DE CÁLCULOS</a>
		</td>
	</tr>
	<tr class='titulo_tabela'>
		<td colspan='17'>Peças para Compra Manual</td>
	</tr>
	<tr class='titulo_coluna'>
		<td rowspan='2' valign='middle'>Referência</td>
		<td rowspan='2' valign='middle'>Descrição</td>
		<td rowspan='2' valign='middle'>Comprar</td>
		<? if($fabrica != '' and $fabrica != '0') { ?>
			<td rowspan='2' valign='middle'>Qtde p/ Pedido</td>
		<? } ?>
		<td class='ocultar' colspan='8'>Cálculos</td>
		<td rowspan='2' valign='middle'>Mínimo</td>
		<td rowspan='2' valign='middle'>Pedido</td>
		<td rowspan='2' valign='middle'>Estoque <br> Embarcado</td>
		<td rowspan='2' valign='middle'>Fábrica</td>
		<td rowspan='2' valign='middle'>Transp.</td>
	</tr>

<tr class='titulo_coluna'>
	<td class='ocultar'>90d</td>
	<td class='ocultar'>60d</td>
	<td class='ocultar'>30d</td>
	<td class='ocultar'>maior</td>
	<td class='ocultar'>qtde de PA</td>
	<td class='ocultar'>media</td>
	<td class='ocultar'>desvio</td>
	<td class='ocultar'>media OK</td>
</tr>
<?
	$qtde_itens = 0;

	for ($i = 0 ; $i < pg_num_rows($res) ; $i++) {

		$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
		
		$estoque_minimo = pg_result ($res,$i,estoque_minimo);
		$comprar        = pg_result ($res,$i,comprar);
		$peca           = pg_result ($res,$i,peca);

		echo "<tr bgcolor='$cor'>";

		    echo "<td title='Referência'>";				
		        echo pg_result ($res,$i,referencia);
		    echo "</td>";

		    echo "<td title='Descrição'>";
		        echo pg_result ($res,$i,descricao);
		    echo "</td>";

		
		$comprar_title  = "Total comprar: $comprar";
		if ($comprar <= 0) {
			$comprar = '0';
		} else {
			$cor = "#FF9999";
		}
		
		
		echo "<td align='right' bgcolor='$cor' title='$comprar_title'>";
		echo $comprar = ceil($comprar);
		echo "</td>";
		if($fabrica != '' and $fabrica != '0'){
			echo "<td align='center'>";
			if($comprar > 0){
				$qtde_itens++;
				echo "<input type='hidden' name='peca_{$i}' value='$peca' size='2'>";
				echo "<input type='text' name='qtde_pedido_{$i}' value='$comprar' size='2'>";
			}
			echo "</td>";
		}

		echo "<td align='right' title='90d' bgcolor='#0099FF' class='ocultar'>";
		echo pg_result ($res,$i,dias90);
		echo "</td>";

		echo "<td align='right' title='60d' bgcolor='#0099FF' class='ocultar'>";
		echo pg_result ($res,$i,dias60);
		echo "</td>";

		echo "<td align='right' title='30d' bgcolor='#0099FF' class='ocultar'>";
		echo pg_result ($res,$i,dias30);
		echo "</td>";

		echo "<td align='right' title='maior' class='ocultar'>";
		echo pg_result ($res,$i,maior);
		echo "</td>";

		echo "<td align='right' title='qtdex' class='ocultar'>";
		echo pg_result ($res,$i,qtdex);
		echo "</td>";

		echo "<td align='right' title='media' class='ocultar'>";
		echo number_format(pg_result ($res,$i,media),2,'.',',');
		echo "</td>";

		echo "<td align='right' title='desvio' class='ocultar'>";
		echo number_format(pg_result ($res,$i,desvio),2,'.',',');
		echo "</td>";

		echo "<td align='right' title='media OK' class='ocultar'>";
		echo number_format(pg_result ($res,$i,media_ok),2,'.',',');
		echo "</td>";

		echo "<td align='right' title='Estoque Mínimo'>";
		echo ceil($estoque_minimo);
		echo "</td>";

		echo "<td align='right' title='Pedido (Desconsiderando qtde de peça de posto em atraso:".pg_result ($res,$i,qtde_atraso).")' bgcolor='#33CC00'>";
		echo pg_result ($res,$i,qtde);
		echo "</td>";

		echo "<td align='right' title='Estoque'>";
		echo  pg_result ($res,$i,qtde_embarcada);
		echo "</td>";

		echo "<td align='right' title='Fábrica'>";
		echo pg_result ($res,$i,fabrica);
		echo "</td>";

		echo "<td align='right' title='Transp.'>";
		echo pg_result ($res,$i,transp);
		echo "</td>";


		echo "</tr>";
	}
	
	if($fabrica != '' and $fabrica != '0'){
		echo "<td align='center' colspan='17'>";
		if($qtde_itens > 0){
			echo "<input type='hidden' name='qtde_itens' value='$qtde_itens'>";
			echo "<input type='hidden' name='fabrica' value='$fabrica'>";
			echo "<input type='submit' value='Gravar Pedido' >";
		}
		echo "</td>";
	}
	echo "</table>";
echo "</form>";
	flush();

	include "rodape.php"; ?>

</body>
</html>

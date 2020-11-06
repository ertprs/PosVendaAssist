<?

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";

include 'autentica_admin.php';

include 'funcoes.php';




$layout_menu = "cadastro";
$title = "CLASSE DE PEDIDOS";

include 'cabecalho.php';

$msg_erro = "";


$sql = "SELECT  tbl_classe_pedido.classe,
				tbl_classe_pedido.codigo_classe,
				tbl_classe_pedido.prazo,
				tbl_classe_pedido.valor_minimo,
				tbl_classe_pedido.prioridade,
				tbl_classe_pedido.mensagem,
				tbl_condicao.limite_minimo,
				tbl_empresa.descricao
		FROM tbl_classe_pedido 
		LEFT JOIN tbl_marca ON tbl_classe_pedido.marca = tbl_marca.marca 
		LEFT JOIN tbl_empresa ON tbl_marca.empresa = tbl_empresa.empresa 
		LEFT JOIN tbl_condicao_empresa ON tbl_empresa.empresa = tbl_condicao_empresa.empresa 
		LEFT JOIN tbl_condicao ON tbl_condicao_empresa.condicao = tbl_condicao.condicao  
		WHERE tbl_classe_pedido.ativo IS TRUE 
		AND tbl_classe_pedido.fabrica = {$login_fabrica}";
$res = pg_query($sql);
if(pg_num_rows($res)>0){
	$resArray = pg_fetch_all($res);	
}else{
	$msg_erro = "Nenhuma classe cadastrada ou ativa";
}



?>

<style type="text/css">
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
.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
	margin:auto;
	width:700px;
}
.sucesso{
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
	margin:auto;
	width:700px;
}
.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
}
.subtitulo{
    background-color: #7092BE;
    font:bold 11px Arial;
    color: #FFFFFF;
}
table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
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
.espaco{padding-left:100px;}
</style>

<script src="http://code.jquery.com/jquery-latest.min.js"></script>


<?php if(strlen($msg_erro) == 0){

?>
<table width="700" align="center" border="0" class="tabela" cellspacing="1">
	<tbody>		
		<tr>
			<td colspan="8" class="titulo_tabela">Classe de Pedidos Ativas</td>
		</tr>
		<tr class="titulo_coluna">
			<td align="center" width="100"><b>Codigo</b></td>
			<td align="center" width="100"><b>Classe pedido</b></td>
			<td align="center"><b>Prioridade</b></td>
			<td align="center"><b>Valor Mínimo Pedido</b></td>
			<td align="center"><b>Valor Mínimo Parcelas</b></td>
			<td align="center"><b>Empresa</b></td>
			<td align="center"><b>Mensagem</b></td>
			<td align="center"><b>Prazo (dias)</b></td>
		</tr>
		<?php

		for($i=0;$i<count($resArray);$i++){			
			$codigo = $resArray[$i]['codigo_classe'];
			$classe_pedido = $resArray[$i]['classe'];
			$prioridade = $resArray[$i]['prioridade'];
			$valor_minimo = number_format($resArray[$i]['valor_minimo'],2,',','.');
			$valor_minimo_parcela = number_format($resArray[$i]['limite_minimo'],2,',','.');
			$empresa = $resArray[$i]['descricao'];
			$mensagem = $resArray[$i]['mensagem'];
			$prazo = $resArray[$i]['prazo'];
			
			if($i % 2 == 0){
				$color = "#F7F5F0";
			}else{
				$color = "#F1F4FA";
			}

			echo  '	<tr bgcolor='.$color.'>
						<td align=center >'.$codigo.'</td>
						<td align=left >'.$classe_pedido.'</td>
						<td align=center >'.$prioridade.'</td>
						<td align=right >'.$valor_minimo.'</td>
						<td align=right >'.$valor_minimo_parcela.'</td>
						<td align=left >'.$empresa.'</td>
						<td align=left >'.$mensagem.'</td>
						<td align=center >'.$prazo.'</td>
				 	</tr>';
		}

		?>
		
			
		</tr>	
	</tbody>
</table>


<?php

}else{
	echo "<p>$msg_erro</p>";
} ?>






<? include "rodape.php"; ?>

</body>
</html>

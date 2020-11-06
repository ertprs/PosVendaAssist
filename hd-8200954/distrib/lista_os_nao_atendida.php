<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';


$title='OS Não atendidas pelo Distribuidor';
$gera_automatico = trim($_GET["gera_automatico"]);

if ($gera_automatico != 'automatico'){
	include 'autentica_usuario.php';
}

#include "gera_relatorio_pararelo_include.php";

include "cabecalho.php";

?>

<html>
<head>
<title>Itens da NF de Entrada</title>
</head>

<body>

<? include 'menu.php' ?>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 0px solid;
	color:#ffffff;
	background-color: #596D9B
}

.link{
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
}

</style>

<center><h1>LISTA DE OS's NÃO ATENDIDAS PELO DISTRIB</h1></center>

<?

if (strlen(trim($_POST["btn_acao"])) > 0) $btn_acao = trim($_POST["btn_acao"]);
if (strlen(trim($_GET["btn_acao"])) > 0)  $btn_acao = trim($_GET["btn_acao"]);

if (strlen(trim($_POST["data_inicial"])) > 0) $data_inicial = trim($_POST["data_inicial"]);
if (strlen(trim($_GET["data_inicial"])) > 0)  $data_inicial = trim($_GET["data_inicial"]);

if (strlen(trim($_POST["data_final"])) > 0) $data_final = trim($_POST["data_final"]);
if (strlen(trim($_GET["data_final"])) > 0)  $data_final = trim($_GET["data_final"]);


if (strlen($data_inicial) == 0) {
	$sql="select (current_date - interval'5 day')::date as data";
	$res = pg_exec ($con,$sql);
	$data_inicial = trim(pg_result($res,0,data));
}else{
	$data_inicial = substr ($data_inicial,6,4) . "-" . substr ($data_inicial,3,2) . "-" . substr ($data_inicial,0,2) ;
}
if (strlen($data_final) == 0) {
	$data_final = date('Y-m-d');
}else{
	$data_final = substr ($data_final,6,4) . "-" . substr ($data_final,3,2) . "-" . substr ($data_final,0,2) ;
}


/*
//ENCONTRAR TODOS OS FATURAMENTOS QUE FORAM ATENDIDOS PELO DISTRIB NO PERIODO ESPECIFICADO PELO USUARIO
$sql = "select tbl_faturamento_item.os,
			tbl_faturamento_item.faturamento_item,
			tbl_faturamento_item.peca
		from tbl_faturamento 
		join tbl_faturamento_item using(faturamento) 
		where tbl_faturamento.fabrica=3 
			and tbl_faturamento.distribuidor = 4311 
			and tbl_faturamento_item.os is not null 
			and tbl_faturamento.emissao < current_date
			and tbl_faturamento.emissao > (current_date - interval'9999 day')::date";
//echo "sql: $sql";
$res_atend = pg_exec ($con,$sql);
$array_faturamento="";
if (pg_numrows($res_atend) > 0) {
	for($i=0; $i < pg_numrows($res_atend); $i++){
		$peca	= trim(pg_result($res_atend,$i,peca));
		$os		= trim(pg_result($res_atend,$i,os));
		$faturamento_item= trim(pg_result($res_atend,$i,faturamento_item));
		if(strlen($array_faturamento[$os][$peca])==0)
			$array_faturamento[$os][$peca]= true;
			//echo "<br><font color='red'>Atenção: OS: $os apresenta mais de uma peca: $peca </font>";
	}
}*/

//ENCONTRA TODOS OS FATURAMENTOS QUE FORAM ATENDIDOS PELA BRITANIA NO PERIODO ESPECIFICADO PELO USUARIO

/*				//se não tiver nota do distrib verifica se está em embarque e exibe numero do embarque
				$sql  = "SELECT tbl_embarque.embarque
						FROM tbl_embarque 
						JOIN tbl_embarque_item USING (embarque) 
						WHERE tbl_embarque_item.os_item = $os_item 
						AND tbl_embarque.faturar IS NULL";
				$resX = pg_exec ($con,$sql);
				
				if (pg_numrows ($resX) > 0) {
					echo "Embarque " . pg_result ($resX,0,embarque);
				}else{
					echo "<acronym title='Pendente com o fabricante.' style='cursor:help;'> $nota_fiscal_distrib";
				}
*/



if (strlen($btn_acao) > 0 && strlen($msg_erro) == 0) {
	#include "gera_relatorio_pararelo.php";
}

if ($gera_automatico != 'automatico' and strlen($msg_erro)==0){
	#include "gera_relatorio_pararelo_verifica.php";
}

if (strlen($msg_erro) > 0) { ?>
	<table width="600" border="0" cellspacing="0" cellpadding="2" align="center" class="Error">
		<tr>
			<td><?echo $msg_erro?></td>
		</tr>
	</table>
	<br>
	<? 
} 


$data_inicial_x = substr($data_inicial,8,2)."/".substr($data_inicial,5,2)."/".substr($data_inicial,0,4) ;
$data_final_x   = substr($data_final,8,2)."/".substr($data_final,5,2)."/".substr($data_final,0,4) ;

echo "<table width='550' border='1' cellspacing='1' cellpadding='3' align='center'>\n";
echo "<form name='frm_per' method='POST' action='$PHP_SELF'>";
echo "<tr><td colspan='10'>\n";
echo "Notas de Saída da Britania no período de <input type='text' name='data_inicial' id='data_inicial' size='12' maxlength='11' value='$data_inicial_x'>\n";	
echo " <input type='text' name='data_final' id='data_final' size='12' maxlength='10' value='$data_final_x'>\n";	
echo "<INPUT TYPE='submit' name='btn_acao' id='btn_acao' value='Pesquisar'>";
echo "</td></tr>\n";
echo "</form>\n";
echo "</table>";


if (strlen($btn_acao)>0 and strlen($msg_erro)==0) {
	$sql = "
		SELECT tbl_faturamento_item.os, 
			tbl_faturamento_item.peca, 
			tbl_peca.referencia, 
			tbl_faturamento_item.faturamento_item, 
			tbl_faturamento_item.faturamento, 
			TO_CHAR(emissao,'DD/MM/YYYY') AS dt_emissao, 
			TO_CHAR (conferencia,'DD/MM/YYYY') AS dt_conferencia, 
			tbl_faturamento.posto, 
			tbl_faturamento.nota_fiscal, 
			tbl_os.sua_os, 
			descricao ,
			tbl_embarque_item.embarque_item,
			tbl_os_produto.os_produto,
			CASE WHEN (conferencia > (CURRENT_DATE - INTERVAL'5 day')::DATE ) 
				THEN 0
				ELSE 1
			END AS ATRASADO
		FROM tbl_faturamento 
		JOIN tbl_faturamento_item ON tbl_faturamento.faturamento = tbl_faturamento_item.faturamento
		JOIN tbl_os				ON tbl_faturamento_item.os	 = tbl_os.os
		JOIN tbl_peca			ON tbl_peca.peca			 = tbl_faturamento_item.peca 
		JOIN tbl_os_produto		ON tbl_os_produto.os		 = tbl_os.os
		JOIN tbl_os_item		ON tbl_os_item.os_produto	 = tbl_os_produto.os_produto
		LEFT JOIN tbl_embarque_item	ON tbl_embarque_item.os_item = tbl_os_item.os_item 
		LEFT JOIN tbl_embarque		ON tbl_embarque.embarque	 = tbl_embarque_item.embarque 
		WHERE tbl_faturamento.fabrica = 3 
			AND tbl_faturamento.posto = 4311 
			AND tbl_os.posto         <> 4311 
			AND tbl_faturamento_item.os IS NOT NULL 
			AND tbl_faturamento.emissao > '$data_inicial'
			AND tbl_faturamento.emissao < '$data_final' 
			AND tbl_embarque.faturar    IS NOT NULL
		ORDER BY emissao";


	$sql = "
			SELECT
				tbl_faturamento_item.os, 
				tbl_faturamento_item.peca, 
				tbl_peca.referencia, 
				tbl_peca.descricao,
				tbl_faturamento_item.faturamento_item, 
				tbl_faturamento_item.faturamento, 
				TO_CHAR(tbl_faturamento.emissao,'DD/MM/YYYY') AS dt_emissao, 
				TO_CHAR(tbl_faturamento.conferencia,'DD/MM/YYYY') AS dt_conferencia, 
				tbl_faturamento.posto,
				tbl_faturamento.nota_fiscal,
				tbl_os.sua_os,
				CURRENT_DATE - tbl_os.data_abertura         AS dias_aberta,
				tbl_embarque_item.embarque,
				tbl_embarque_item.embarque_item,
				tbl_os_produto.os_produto,
				TO_CHAR(CURRENT_DATE - tbl_faturamento.conferencia,'DD')  AS dias_conferencia,
				CASE WHEN (tbl_faturamento.conferencia > (CURRENT_DATE - INTERVAL'5 day')::DATE ) 
					THEN 0
					ELSE 1
				END AS ATRASADO
			FROM tbl_faturamento
			JOIN tbl_faturamento_item   ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
			JOIN tbl_peca               ON tbl_peca.peca                    = tbl_faturamento_item.peca
			JOIN tbl_os                 ON tbl_os.os                        = tbl_faturamento_item.os      AND tbl_os.fabrica IN (".implode(",", $fabricas).")
			JOIN tbl_os_produto         ON tbl_os_produto.os                = tbl_os.os
			JOIN tbl_os_item            ON tbl_os_item.os_produto           = tbl_os_produto.os_produto AND tbl_os_item.peca = tbl_faturamento_item.peca
			LEFT JOIN tbl_embarque_item ON tbl_embarque_item.os_item        = tbl_os_item.os_item
			JOIN tbl_embarque           ON tbl_embarque.embarque            = tbl_embarque_item.embarque
			WHERE tbl_faturamento.fabrica IN (".implode(",", $fabricas).")
			AND tbl_faturamento.posto     = $login_posto
			AND tbl_os.posto              <> $login_posto
			AND tbl_faturamento_item.os   IS NOT NULL
			AND tbl_faturamento.emissao < '$data_final' 
			AND tbl_faturamento.emissao > '$data_inicial'
			AND tbl_faturamento.conferencia IS NOT NULL
			AND tbl_embarque.faturar        IS NULL
			AND CURRENT_DATE - tbl_faturamento.conferencia > 3
			";
	echo nl2br($sql);
	exit;
	$res = pg_exec ($con,$sql);

	echo "<table width='650' border='1' cellspacing='1' cellpadding='3' align='center'>\n";
	echo "<tr>\n";
	echo "<td class='menu_top' width='20'>#</td>\n";
	echo "<td class='menu_top'>PEÇA</td>\n";
	echo "<td class='menu_top'>DESCRIÇÃO</td>\n";
	echo "<td class='menu_top'>OS</td>\n";
	echo "<td class='menu_top'>QTDE DIAS ABERTURA</td>\n";
	echo "<td class='menu_top'>NOTA FISCAL</td>\n";
	echo "<td class='menu_top'>DATA <br> EMISSÃO</td>\n";
	echo "<td class='menu_top'>DATA <br> CONFERÊNCIA</td>\n";
	echo "<td class='menu_top'>QTDE DIAS APÓS CONFERÊNCIA</td>\n";
	echo "</tr>\n";
			
	$c=0;

	if (pg_numrows($res) > 0) {

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

			$peca			= trim(pg_result($res,$i,peca)) ;
			$os				= trim(pg_result($res,$i,os));
			$conferencia	= trim(pg_result($res,$i,dt_conferencia));
			$referencia		= trim(pg_result($res,$i,referencia));
			$fat_item		= trim(pg_result($res,$i,faturamento_item));
			$descricao		= trim(pg_result($res,$i,descricao)) ;
			$faturamento	= trim(pg_result($res,$i,faturamento)) ;
			$sua_os			= trim(pg_result($res,$i,sua_os)) ;
			$dias_aberta	= trim(pg_result($res,$i,dias_aberta)) ;
			$posto			= trim(pg_result($res,$i,posto)) ;
			$nota_fiscal	= trim(pg_result($res,$i,nota_fiscal)) ;
			$emissao		= trim(pg_result($res,$i,dt_emissao)) ;
			$dias_conferencia=trim(pg_result($res,$i,dias_conferencia));
			$atrasado		= trim(pg_result($res,$i,atrasado));
			$embarque		= trim(pg_result($res,$i,embarque));
			$embarque_item	= trim(pg_result($res,$i,embarque_item)) ;
			$os_produto		= trim(pg_result($res,$i,os_produto)) ;

			$sql_parcial = "
				SELECT tbl_embarque.posto, tbl_embarque_item.embarque, osx.os_item, tbl_embarque_item.pedido_item, tbl_embarque_item.peca, tbl_embarque_item.qtde 
				FROM (
					SELECT DISTINCT oss.os_item
					FROM (
						SELECT tbl_os.os, tbl_os_item.os_item
						FROM tbl_os
						JOIN tbl_os_produto USING (os)
						JOIN tbl_os_item    USING (os_produto)
						JOIN tbl_embarque_item USING (os_item)
						JOIN tbl_embarque      USING (embarque)
						WHERE tbl_embarque.distribuidor  = $login_posto
						AND   tbl_os.os                  = $os
						AND   tbl_embarque.faturar       IS NULL 
						AND tbl_embarque_item.impresso   IS NULL
					) oss 
					JOIN tbl_os                 ON tbl_os.os                     = oss.os AND tbl_os.os = $os
					JOIN tbl_os_produto         ON oss.os                        = tbl_os_produto.os
					JOIN tbl_os_item            ON tbl_os_produto.os_produto     = tbl_os_item.os_produto
					JOIN tbl_servico_realizado  ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado
					LEFT JOIN tbl_embarque_item ON tbl_os_item.os_item           = tbl_embarque_item.os_item
					LEFT JOIN tbl_pedido_cancelado ON tbl_pedido_cancelado.os = tbl_os.os AND tbl_pedido_cancelado.pedido = tbl_os_item.pedido AND tbl_pedido_cancelado.peca = tbl_os_item.peca
					WHERE (tbl_servico_realizado.troca_de_peca OR tbl_servico_realizado.troca_produto OR tbl_servico_realizado.ressarcimento)
					AND tbl_embarque_item.os_item IS NULL
					AND tbl_pedido_cancelado.pedido IS NULL
				) osx
				JOIN tbl_os_item        ON osx.os_item           = tbl_os_item.os_item
				JOIN tbl_embarque_item  ON osx.os_item           = tbl_embarque_item.os_item
				JOIN tbl_embarque       ON tbl_embarque.embarque = tbl_embarque_item.embarque";
			$resParcial = pg_exec ($con,$sql_parcial);
			if (pg_numrows($resParcial)>0){
				continue;
			}



			$sql = "select posto,
						os, 
						nota_fiscal, 
						emissao, 
						tbl_faturamento_item.peca
			FROM tbl_faturamento
			JOIN tbl_faturamento_item USING(faturamento)
			WHERE fabrica    IN (".implode(",", $fabricas).")
			AND distribuidor = $login_posto 
			AND os           = $os 
			AND peca         = $peca";
			$res2 = pg_exec ($con,$sql);

			$cor = "#ffffff";
			if ($c % 2 == 0) {
				$cor = "#DDDDEE";
			}
			if ($atrasado) {
				$cor = "#FED1C5";
			}
			if (strlen($embarque_item)==0){
				$cor = "#A4FFA4";
			}

			if ($dias_aberta>15){
				$dias_aberta = "<b style='color:red'>".$dias_aberta."<b>";
			}

			if (strlen($embarque)>0){
				$sql = "SELECT nota_fiscal
						FROM tbl_faturamento
						WHERE tbl_faturamento.fabrica      IN (".implode(",", $fabricas).")
						AND   tbl_faturamento.distribuidor = $login_posto
						AND   tbl_faturamento.embarque     = $embarque";
				$res3 = pg_exec ($con,$sql);
			}
			//pg_numrows($res2) == 0 OR		
			if (pg_numrows($res2) == 0 OR pg_numrows($res3) == 0 ){
				$c++;

				$teste = 0 ;

				echo "<tr style='font-size: 10px' bgcolor='$cor'>\n";
				echo "<td align='center' nowrap>".$teste. ($c+1) . "&nbsp;</td>\n";
				echo "<td align='left' nowrap>$referencia</td>\n";
				echo "<td nowrap align='left'>$descricao</td>\n";
				echo "<td align='left'><a href='../os_press.php?os=$os' target='_blank' class='link'>$sua_os</a></td>\n";
				echo "<td nowrap align='center'>$dias_aberta</td>\n";
				echo "<td align='left'>
					<a href='nf_entrada_item.php?faturamento=$faturamento' target='_blank' class='link'>$nota_fiscal</a>
				</td>\n";
				echo "<td align='center'>$emissao</td>\n";
				echo "<td align='center'>$conferencia </td>\n";
				echo "<td align='center'>$dias_conferencia </td>\n";
				echo "</tr>\n";
				
			}else{
				echo "<tr>\n";
				echo "<td colspan='1'>$os - $emissao\n";
				echo "</td>\n";
				echo "</tr>\n";
			/*	echo "<tr style='font-size: 10px' bgcolor='$cor'>\n";
				echo "<td align='left' nowrap>" . ($c+1) . "&nbsp;&nbsp;&nbsp;</td>\n";
				echo "<td align='left' nowrap>$faturamento</td>\n";
				echo "<td align='left' nowrap>$peca</td>\n";
				echo "<td nowrap align='center'>$descricao</td>\n";
				echo "<td align='left' nowrap><a href='os_press.php?os=$os' target='_blank' class='link'>$os</a></td>\n";
				echo "<td align='center' nowrap>$sua_os</font></td>\n";
				echo "<td nowrap align='left'>$dias_aberta</td>\n";
				echo "<td align='center'>$nota_fiscal</td>\n";
				echo "<td align='center'>$emissao</td>\n";
				echo "<td nowrap align='center'><font color='red'>ENCONTRADO</font></td>\n";
				echo "</tr>\n";
			*/
			}
		}

	}
	if ($c == 0){
		echo "<tr><td colspan='9' align='center'>NADA ENCONTRADO NO PERÍODO DE $data_inicial_x A $data_final_x</tr></td>";
	}
}
	echo "</table>\n";
?>
</body>
<p>
<? include "rodape.php"; ?>
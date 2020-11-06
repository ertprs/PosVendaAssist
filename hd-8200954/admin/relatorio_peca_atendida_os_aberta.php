<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria,gerencia";
include 'autentica_admin.php';

$layout_menu = "Gerencia";
$title = "Ordens de Serviço atendidas que continuam em aberto a mais de 15 dias";

include 'cabecalho.php';
?>

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

<?
$posto = $_GET["posto"];

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

		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='400'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='7' background='imagens_admin/azul.gif' height='20'><font size='2'>OSs QUE TIVERAM PEÇAS ATENDIDAS A MAIS DE 15 DIAS E AINDA NÃO FORAM FINALIZADAS PELO POSTO</font></td>";
		echo "</tr>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='7' height='20'><font size='2'>$codigo_posto - $nome</font></td>";
		echo "</tr>";

		$sql = "SELECT * FROM (
					SELECT DISTINCT tbl_os.os, tbl_os.sua_os, tbl_os.excluida, tbl_os.data_abertura
							FROM (
								SELECT  os, 
										sua_os, 
										excluida, 
										data_abertura 
								FROM tbl_os 
								WHERE fabrica = $login_fabrica 
								AND posto = $posto 
								AND finalizada IS NULL 
								AND excluida IS FALSE
							) tbl_os
							JOIN tbl_os_produto  USING (os)
							JOIN tbl_os_item     USING (os_produto)
							JOIN tbl_pedido_item USING (pedido_item)
							JOIN tbl_pedido      ON tbl_pedido_item.pedido = tbl_pedido.pedido
							JOIN (SELECT tbl_faturamento_item.pedido, tbl_faturamento_item.peca	
								FROM tbl_faturamento_item
								JOIN tbl_faturamento USING (faturamento)
								WHERE tbl_faturamento.fabrica = $login_fabrica
								AND   tbl_faturamento.emissao + INTERVAL'15 days' < CURRENT_DATE
							) fat ON tbl_pedido_item.pedido = fat.pedido AND tbl_pedido_item.peca = fat.peca
							WHERE tbl_pedido.data > '2006-12-13'
							AND   tbl_os_item.qtde <= tbl_pedido_item.qtde_faturada 
					EXCEPT
					SELECT DISTINCT tbl_os.os, tbl_os.sua_os,tbl_os.excluida, data_abertura
						FROM (SELECT os, sua_os,excluida, data_abertura FROM tbl_os WHERE fabrica = $login_fabrica AND posto = $posto AND finalizada IS NULL) tbl_os
						JOIN tbl_os_produto  USING (os)
						JOIN tbl_os_item     USING (os_produto)
						JOIN tbl_pedido_item USING (pedido_item)
						JOIN tbl_pedido      ON tbl_pedido_item.pedido = tbl_pedido.pedido
						WHERE tbl_pedido.data > '2006-12-13'
						AND   (tbl_os_item.qtde > tbl_pedido_item.qtde_faturada OR tbl_pedido_item.qtde_faturada IS NULL )
				) as abertas
				ORDER BY abertas.data_abertura";
		//if ($ip=='201.71.54.144') echo nl2br($sql);
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<tr class='Titulo'>";
			echo "<td >OS</td>";
			echo "<td >Abertura</td>";
			echo "</tr>";
		
			$total = pg_numrows($res);
	
			for ($i=0; $i<pg_numrows($res); $i++){
				$os            = trim(pg_result($res,$i,os));
				$sua_os        = trim(pg_result($res,$i,sua_os));
				$data_abertura = trim(pg_result($res,$i,data_abertura));
				$data_abertura = substr($data_abertura,8,2)."/".substr($data_abertura,5,2)."/".substr($data_abertura,0,4);

				if($cor=="#F1F4FA")$cor = '#F7F5F0';
				else               $cor = '#F1F4FA';
				
				echo "<tr class='Conteudo'align='center'>";
				echo "<td nowrap><a href='os_press?os=$os' target='_blank'>$sua_os&nbsp;</a></td>";
				echo "<td bgcolor='$cor' nowrap>$data_abertura&nbsp;</td>";
				echo "</tr>";
			}
			echo "</table>";
		}
	}
}else{

	$sql = "SELECT  abertas.posto                 ,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome                ,
					COUNT(abertas.os) as quantidade
			FROM (
				SELECT DISTINCT tbl_os.os, tbl_os.posto 
					FROM (	SELECT os, posto 
							FROM tbl_os 
							WHERE fabrica = $login_fabrica 
							AND finalizada IS NULL
							AND excluida IS FALSE
					) tbl_os
					JOIN tbl_os_produto  USING (os)
					JOIN tbl_os_item     USING (os_produto)
					JOIN tbl_pedido_item USING (pedido_item)
					JOIN tbl_pedido      ON tbl_pedido_item.pedido = tbl_pedido.pedido
					JOIN (	SELECT tbl_faturamento_item.pedido, tbl_faturamento_item.peca	
							FROM tbl_faturamento_item
							JOIN tbl_faturamento USING (faturamento)
							WHERE tbl_faturamento.fabrica = $login_fabrica
							AND   tbl_faturamento.emissao + INTERVAL'15 days' < CURRENT_DATE
					) fat ON tbl_pedido_item.pedido = fat.pedido AND tbl_pedido_item.peca = fat.peca
					WHERE tbl_pedido.data > '2006-12-13'
					AND   tbl_os_item.qtde <= tbl_pedido_item.qtde_faturada 
				EXCEPT
				SELECT DISTINCT tbl_os.os, tbl_os.posto
					FROM (	SELECT os, posto 
							FROM tbl_os 
							WHERE fabrica = $login_fabrica 
							AND finalizada IS NULL
					) tbl_os
					JOIN tbl_os_produto  USING (os)
					JOIN tbl_os_item     USING (os_produto)
					JOIN tbl_pedido_item USING (pedido_item)
					JOIN tbl_pedido      ON tbl_pedido_item.pedido = tbl_pedido.pedido
					WHERE tbl_pedido.data > '2006-12-13'
					AND   (tbl_os_item.qtde > tbl_pedido_item.qtde_faturada OR tbl_pedido_item.qtde_faturada IS NULL )
			) as abertas
			JOIN tbl_posto using(posto)
			JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto and fabrica = $login_fabrica
			GROUP BY abertas.posto                 ,
					 tbl_posto_fabrica.codigo_posto,
					 tbl_posto.nome                
			ORDER BY tbl_posto.nome";
	//if ($ip=='201.71.54.144') echo nl2br($sql);
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {

		echo "<br><center><font face='Verdana' size='3px'>Clique sobre o código do posto para listar apenas as suas pendências</center>";
		echo "<table border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' align='center' width='700'>";
		echo "<tr class='Titulo'>";
		echo "<td colspan='5'background='imagens_admin/azul.gif' height='20'><font size='2'>TOTAL DE OS'S QUE TIVERAM PEÇAS ATENDIDAS A MAIS DE 15 DIAS E NÃO FORAM FINALIZADAS PELO POSTO</font></td>";
		echo "</tr>";
	
		echo "<tr class='Titulo'>";
		echo "<td >CÓDIGO DO POSTO</td>";
		echo "<td >NOME DO POSTO</td>";
		echo "<td >TOTAL</td>";
		echo "</tr>";
	
		for ($i=0; $i<pg_numrows($res); $i++){
			$posto                   = trim(pg_result($res,$i,posto))       ;
			$nome                    = trim(pg_result($res,$i,nome))        ;
			$codigo_posto            = trim(pg_result($res,$i,codigo_posto));
			$total                   = trim(pg_result($res,$i,quantidade))       ;
			
			if($cor=="#F1F4FA")
				$cor = '#F7F5F0';
			else
				$cor = '#F1F4FA';
	
			echo "<tr class='Conteudo'align='center'>";
			echo "<td bgcolor='$cor' ><a href='$PHP_SELF?posto=$posto' target='_blank'>$codigo_posto&nbsp;</a></td>";
			echo "<td bgcolor='$cor' align='left'>$nome&nbsp;</td>";
			echo "<td bgcolor='$cor' >$total&nbsp;</td>";
			$total_geral = $total + $total_geral;
	
			echo "</tr>";
		}
		echo "<tr><td colspan='2'> Total</td><td>$total_geral</td></tr>";
		echo "</table>";

	}else{

		echo "<p style='text-align: center; font-size: 16px;'> Nenhum resultado encontrado! </p>";

	}
}
include "rodape.php" ;
?>
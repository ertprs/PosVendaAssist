<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

$msg = "";
$layout_menu = "os";
$title = "Peças Retornáveis do Extrato";
include "cabecalho.php";

$periodo_extrato = $_GET['periodo_extrato'];
$perido = explode("/",$periodo_extrato);
//echo $perido[0];
//echo $perido[1];
$sql = "SELECT	endereco     ,
				cidade       , 
				estado       , 
				cnpj         , 
				ie           , 
				razao_social ,
				cep 
		from tbl_fabrica
		where fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);
	$fabrica_endereco = pg_result($res,0,endereco);
	$fabrica_cidade   = pg_result($res,0,cidade);
	$fabrica_estado   = pg_result($res,0,estado);
	$fabrica_cnpj     = pg_result($res,0,cnpj);
	$fabrica_ie       = pg_result($res,0,ie);
	$fabrica_razao_social = pg_result($res,0,razao_social);
	$fabrica_cep      = pg_result($res,0,cep);



	if (strlen($perido) > 0) {
		$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $perido[0], 1, $perido[1]));
		$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $perido[0], 1, $perido[1]));
	}


	$sql = "SELECT	tbl_peca.peca, 
					tbl_peca.referencia,
					tbl_peca.descricao,
					SUM(tbl_os_item.qtde) AS qtde, 
					tbl_peca.ipi,
					(tbl_tabela_item.preco * 0.3) as preco
			FROM tbl_extrato 
			JOIN tbl_os_extra on tbl_os_extra.extrato = tbl_extrato.extrato
			JOIN tbl_os_produto on tbl_os_produto.os = tbl_os_extra.os
			JOIN tbl_os_item on tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIN tbl_servico_realizado on tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado 
			JOIN tbl_peca on tbl_peca.peca = tbl_os_item.peca
			LEFT JOIN tbl_tabela_item on tbl_tabela_item.peca = tbl_peca.peca 
			AND tbl_tabela_item.tabela = 135
			WHERE tbl_extrato.fabrica= $login_fabrica 
			AND tbl_extrato.posto = $login_posto
			AND tbl_extrato.data_geracao between '$data_inicial' and '$data_final'
			AND tbl_servico_realizado.gera_pedido is true 
			AND tbl_servico_realizado.troca_de_peca is true
			AND tbl_peca.devolucao_obrigatoria is true
			GROUP BY tbl_peca.peca, 
					tbl_peca.referencia,
					tbl_peca.descricao,
					tbl_peca.ipi,
					tbl_tabela_item.preco
			ORDER BY tbl_peca.referencia";
	$res = pg_exec($con,$sql);
	//echo $sql;


	if(pg_numrows($res)>0){
		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='600' >";
		echo "<tr>";
		echo "<td>Natureza <br> <b>Devolução de Garantia</b> </td>";
		echo "<td>CFOP <br> <b>$cfop</b> </td>";
		echo "<td>Emissao <br> <b>$data</b> </td>";
		echo "</tr>";
		echo "</table>";

		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='600' >";
		echo "<tr>";
		echo "<td>Razão Social <br> <b>$fabrica_razao_social</b> </td>";
		echo "<td>CNPJ <br> <b>$fabrica_cnpj</b> </td>";
		echo "<td>Inscrição Estadual <br> <b>$fabrica_ie</b> </td>";
		echo "</tr>";
		echo "</table>";

		$fabrica_cep = substr ($fabrica_cep,0,2) . "." . substr ($fabrica_cep,2,3) . "-" . substr ($fabrica_cep,5,3) ;
		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='600' >";
		echo "<tr>";
		echo "<td>Endereço <br> <b>$fabrica_endereco </b> </td>";
		echo "<td>Cidade <br> <b>$fabrica_cidade</b> </td>";
		echo "<td>Estado <br> <b>$fabrica_estado</b> </td>";
		echo "<td>CEP <br> <b>$c</b> </td>";
		echo "</tr>";
		echo "</table>";

		echo "<table border='1' cellspacing='0' cellpadding='3' border-color='#000' style='border-collapse:collapse;font-size:12px' width='600' >";
		echo "<tr align='center'>";
		echo "<td><b>Código</b></td>";
		echo "<td><b>Descrição</b></td>";
		echo "<td><b>NF Origem</b></td>";
		echo "<td><b>Qtde.</b></td>";
		echo "<td><b>Unitário</b></td>";
		echo "<td><b>Total</b></td>";
		echo "<td><b>% ICMS</b></td>";
		echo "<td><b>% IPI</b></td>";
		echo "</tr>";

		for($x=0;pg_numrows($res)>$x;$x++){
			$referencia = pg_result($res,$x,referencia);
			$peca       = pg_result($res,$x,peca);
			$descricao  = pg_result($res,$x,descricao);
			$qtde       = pg_result($res,$x,qtde);
			$preco      = pg_result($res,$x,preco);
			$ipi        = pg_result($res,$x,ipi);
			$total_item = $preco * $qtde;
			
			$xsql = " select aliq_icms from tbl_faturamento_item join tbl_faturamento using(faturamento) where peca = $peca and posto = $login_posto order by tbl_faturamento.emissao desc limit 1";
			$xres = pg_exec($con,$xsql);
			
			$aliq_icms = @pg_result($xres,0,0);
			echo "<tr bgcolor='#ffffff' style='font-color:#000000 ; align:left ; font-size:10px ' >";
			echo "<td align='left'>$referencia</td>";
			echo "<td align='left'>$descricao</td>";
			echo "<td align='left'>$nota_fiscal</td>";
			echo "<td align='right'>$qtde</td>";
			echo "<td align='right' nowrap>" . number_format ($preco,2,",",".") . "</td>";
			echo "<td align='right' nowrap>" . number_format ($total_item,2,",",".") . "</td>";
			echo "<td align='right'>" . $aliq_icms . "</td>";
			echo "<td align='right'>" . $ipi. "</td>";
			echo "</tr>";

		}
	}
?>




<? include "rodape.php"; ?>

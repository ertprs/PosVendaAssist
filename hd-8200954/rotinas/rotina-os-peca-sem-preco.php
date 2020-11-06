<?php

try {

	include dirname(__FILE__) . '/../dbconfig.php';
	include dirname(__FILE__) . '/../includes/dbconnect-inc.php';
	require_once dirname(__FILE__) . '/funcoes.php';

	$vet_peca_sem_preco  = array(11,24,35,40,52,74,80,86,72,161,172);
	$vet_peca_sem_preco  = implode(",", $vet_peca_sem_preco);

	$sql = "DROP TABLE IF EXISTS tmp_os_peca_sem_preco";
	$res = pg_query($con,$sql);

	$sqlf = "SELECT  fabrica FROM tbl_fabrica 
		WHERE ativo_fabrica IS TRUE AND (fabrica in ($vet_peca_sem_preco) or fabrica >= 85) ";
	$resf = pg_query($con,$sqlf);

	for($i=0;$i< pg_num_rows($resf);$i++){
		$fabrica = pg_fetch_result($resf,$i,'fabrica');
		if($i == 0 ){
			$cond = "SELECT 	tbl_os.os,
						tbl_os.sua_os,
						tbl_os.fabrica,
						tbl_os_item.peca
				INTO tmp_os_peca_sem_preco ";
		}else{
			$cond = "INSERT INTO tmp_os_peca_sem_preco (os, sua_os,fabrica,peca)
				SELECT 	tbl_os.os,
						tbl_os.sua_os,
						tbl_os.fabrica,
						tbl_os_item.peca ";
		}
		$sql = "$cond
				FROM tbl_os
				JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.os
				JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				JOIN tbl_servico_realizado ON tbl_os_item.servico_realizado = tbl_servico_realizado.servico_realizado 
				AND gera_pedido IS TRUE
				LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_os_item.peca 
				AND tbl_tabela_item.tabela in(SELECT tabela FROM tbl_tabela WHERE (tabela_garantia IS TRUE or descricao ~* 'gar' or sigla_tabela ~* 'gar') and  fabrica = $fabrica)
				WHERE ( tbl_os.fabrica = $fabrica)
				AND tbl_os.data_fechamento ISNULL
				AND tbl_os.finalizada ISNULL
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_os_item.pedido ISNULL
				AND tbl_os_item.pedido_item ISNULL
				AND tbl_tabela_item.preco ISNULL";
		$res = pg_query($con,$sql);
	}
} catch (Exception $e) {
	$msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
	Log::envia_email($vet,APP, $msg );
}


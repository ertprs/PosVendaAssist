<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios="cadastros";
include 'autentica_admin.php';

for( $r=0;$r < 20000 ; $r++ ){
	$os_copia = "5479617";
	$res = pg_exec ($con,"BEGIN TRANSACTION");
	$sql = "INSERT into tbl_os (fabrica,posto,data_abertura,data_nf,consumidor_nome, revenda_cnpj,revenda_nome,consumidor_cidade,consumidor_estado,produto,serie,revenda_fone,defeito_reclamado_descricao,defeito_constatado,consumidor_cpf,consumidor_revenda,nota_fiscal,garantia_produto,solucao_os,qtde_produtos,consumidor_endereco,consumidor_numero,consumidor_cep,consumidor_bairro) select fabrica,posto,data_abertura,data_nf,consumidor_nome, revenda_cnpj,revenda_nome,consumidor_cidade,consumidor_estado,produto,serie,revenda_fone,defeito_reclamado_descricao,defeito_constatado,consumidor_cpf,consumidor_revenda,nota_fiscal||'$r',garantia_produto,solucao_os,qtde_produtos,consumidor_endereco,consumidor_numero,consumidor_cep,consumidor_bairro from tbl_os where os=$os_copia;";
	$res = pg_exec ($con,$sql);
	$msg_erro = @pg_errormessage($con);

	$res = pg_exec ($con,"SELECT CURRVAL ('seq_os')");
	$os  = pg_result ($res,0,0);

	$sql = "SELECT fn_valida_os($os,60)";
	$res1 = pg_exec ($con,$sql);
	$msg_erro = @pg_errormessage($con);

	$sql = "SELECT os_produto FROM tbl_os_produto WHERE os = $os_copia";
	$res1 = pg_exec ($con,$sql);
	$msg_erro = @pg_errormessage($con);

	if(pg_numrows($res1)>0) {
		for($i=0;$i<pg_numrows($res1);$i++){
			$os_produto_copia = pg_result($res1,$i,os_produto);

			$sql = "INSERT into tbl_os_produto (os,produto,mao_de_obra) SELECT $os,produto,mao_de_obra FROM tbl_os_produto WHERE os_produto=$os_produto_copia;";

			$res = pg_exec ($con,$sql);
			$msg_erro = @pg_errormessage($con);

			$res = pg_exec ($con,"SELECT CURRVAL ('seq_os_produto')");
			$os_produto  = @pg_result ($res,0,0);

			$sql = "SELECT os_item FROM tbl_os_item WHERE os_produto = $os_produto_copia";
			$res2 = pg_exec ($con,$sql);
			$msg_erro = @pg_errormessage($con);
			if(pg_numrows($res2)>0){
				for($j=0;$j<pg_numrows($res2);$j++){
					$os_item_copia = pg_result($res2,$j,os_item);

					$sql = "INSERT INTO tbl_os_item (peca,qtde,defeito,servico_realizado,os_produto) select peca,qtde,defeito,servico_realizado,$os_produto from tbl_os_item where os_produto=$os_produto_copia;";
					$res = pg_exec ($con,$sql);
					$msg_erro = @pg_errormessage($con);

				}
			}
		}
	}
	if (strlen ($msg_erro) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		echo "$os";
		if($r%20==0) echo "<br>";
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		echo "$msg_erro";
	}
}




 ?>
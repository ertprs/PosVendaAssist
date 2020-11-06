<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="financeiro";
include "autentica_admin.php";



$layout_menu = "financeiro";
$title = "  ";


include "cabecalho.php"; 

	$sqlq = "SELECT linha,familia,defeito_reclamado,defeito_constatado,solucao FROM tbl_diagnostico where fabrica = 1";
	$resq = pg_query($con,$sqlq);
	if(pg_num_rows($resq) > 0){
		$conta = 0;
		for($qq =0;$qq<pg_num_rows($resq);$qq++) {
			$linha = pg_fetch_result($resq,$qq,'linha');
			$familia = pg_fetch_result($resq,$qq,'familia');
			$defeito_reclamado = pg_fetch_result($resq,$qq,'defeito_reclamado');
			$defeito_constatado = pg_fetch_result($resq,$qq,'defeito_constatado');
			$solucao = pg_fetch_result($resq,$qq,'solucao');

			$sqll = " SELECT l.linha
					FROM tbl_linha l
					JOIN tbl_linha l2 on l.nome = l2.nome
					where l2.linha = $linha
					and   l.fabrica = 93;";
			$resl = pg_query($con,$sqll);
			if(pg_num_rows($resl) > 0){
				$linha = pg_fetch_result($resl,0,'linha');
			}else{
				echo "linha $linha";
				die;
			}

			$sqll = " SELECT l.familia
					FROM tbl_familia l
					JOIN tbl_familia l2 on l.descricao = l2.descricao
					where l2.familia = $familia
					and   l.fabrica = 93;";
			$resl = pg_query($con,$sqll);
			if(pg_num_rows($resl) > 0){
				$familia = pg_fetch_result($resl,0,'familia');
			}else{
				echo "familia $familia";
				die;
			}

			$sqll = " SELECT l.defeito_reclamado
					FROM tbl_defeito_reclamado l
					JOIN tbl_defeito_reclamado l2 on l.descricao = l2.descricao
					where l2.defeito_reclamado = $defeito_reclamado
					and   l.fabrica = 93;";
			$resl = pg_query($con,$sqll);
			if(pg_num_rows($resl) > 0){
				$defeito_reclamado = pg_fetch_result($resl,0,'defeito_reclamado');
			}else{
				echo "defeito_reclamado $defeito_reclamado";
				die;
			}

			
			$sqll = " SELECT l.defeito_constatado
					FROM tbl_defeito_constatado l
					JOIN tbl_defeito_constatado l2 on l.descricao = l2.descricao
					where l2.defeito_constatado = $defeito_constatado
					and   l.fabrica = 93;";
			$resl = pg_query($con,$sqll);
			if(pg_num_rows($resl) > 0){
				$defeito_constatado = pg_fetch_result($resl,0,'defeito_constatado');
			}else{
				echo "defeito_constatado $defeito_constatado";
				die;
			}

			$sqll = " SELECT l.solucao
					FROM tbl_solucao l
					JOIN tbl_solucao l2 on l.descricao = l2.descricao
					where l2.solucao = $solucao
					and   l.fabrica = 93;";
			$resl = pg_query($con,$sqll);
			if(pg_num_rows($resl) > 0){
				$solucao = pg_fetch_result($resl,0,'solucao');
			}else{
				echo "solucao $solucao";
				die;
			}

			$sqli = " SELECT diagnostico
						from tbl_diagnostico
						where fabrica =93 
						and linha = $linha
						and familia = $familia
						and defeito_reclamado = $defeito_reclamado
						and defeito_constatado = $defeito_constatado
						and solucao = $solucao		";
			$resi = pg_query($con,$sqli);
			if(pg_num_rows($resi) == 0){
				$conta ++;
				$sql = " INSERT INTO tbl_diagnostico (
							fabrica,linha,familia,defeito_reclamado,defeito_constatado,solucao
						)values(
							93,$linha,$familia,$defeito_reclamado,$defeito_constatado,$solucao
						)
					";
				$res = pg_query($con,$sql);

			}
			
		}
		echo $conta;
	}

?>




<? include "rodape.php"; ?>

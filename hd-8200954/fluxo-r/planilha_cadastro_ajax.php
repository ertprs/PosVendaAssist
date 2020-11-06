<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "autentica_usuario.php";




if ($acao == "gravar" AND $ajax == "sim") {

	$tecnico = $_POST["tecnico"];

	if(strlen($tecnico)==0) $msg_erro = "Selecione o técnico!";

	if (strlen($msg_erro) == 0) {
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		for ($i = 0 ; $i < $qtde_item ; $i++) {

			$produto   = $_POST["produto_$i"];;

			if(strlen($produto)==0) continue;
			if(strlen($msg_erro)>0)         break;


			$sql =	"UPDATE tbl_produto_rg_item SET
						tecnico = $tecnico
					WHERE produto = $produto
					AND   posto   = $login_posto
					AND   data_devolucao IS NULL
					AND   tecnico        IS NULL;";
			$res = @pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);
			if(strlen($msg_erro)>0) $msg_erro = "$sql $produto";


			/*
			$sql = "SELECT produto_rg
					FROM tbl_produto_rg_item 
					WHERE produto_rg_item = $produto_rg_item";
			$res = @pg_exec($con,$sql);
			$msg_erro .= pg_errormessage($con);

			if(pg_numrows($res)>0){
				$produto_rg = @pg_result($res,0,0);
				if(strlen($msg_erro)==0){
	
					$sql =	"UPDATE tbl_produto_rg_item SET
								tecnico = $tecnico
							WHERE produto_rg_item = $produto_rg_item
							AND   data_devolucao IS NULL
							AND   tecnico        IS NULL;";
					$res = @pg_exec($con,$sql);
					$msg_erro .= pg_errormessage($con);
					if(strlen($msg_erro)>0) $msg_erro = "$sql $produto_rg_item";

				}else $msg_erro_linha = $i;
			}
			*/
		}
	}

	if (strlen($msg_erro) == 0) {
		$res = pg_exec($con,"COMMIT TRANSACTION");
		echo "ok|Gravado com Sucesso|$tecnico";
	}else{
		$res = pg_exec($con,"ROLLBACK TRANSACTION");
		echo "1|$msg_erro|$msg_erro_linha";
	}
	exit;
}
?>
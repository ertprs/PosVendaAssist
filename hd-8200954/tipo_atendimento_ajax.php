<?php
include_once 'dbconfig.php';
include_once 'includes/dbconnect-inc.php';
include_once 'autentica_usuario.php';
include_once 'funcoes.php';

header('Content-Type: text/html; charset=ISO-8859-1');

// HD54668 somente para Colormaq

$referencia = trim($_GET['q']);
if (strlen(trim($referencia))>0) {
	$sql = "SELECT  tbl_produto.referencia,
					tbl_produto.familia,
					tbl_produto.linha
			FROM    tbl_produto
			WHERE   tbl_produto.referencia = '$referencia' AND tbl_produto.fabrica_i = $login_fabrica";
	$res = @pg_exec($con,$sql);
	if (pg_numrows($res)>0) {
		$familia = pg_result($res,0,familia);
		$linha   = pg_result($res,0,linha);
		if ($login_fabrica == 74) {
			$sql2 = "select deslocamento from tbl_linha where linha = $linha and fabrica = $login_fabrica";
			$res2 = pg_query($con, $sql2);
			if (pg_num_rows($res2) > 0) {
				$deslocamento = pg_fetch_result($res2, 0, deslocamento);
				$cond_extra = "";

				if ($deslocamento == 'f') {
					$cond_extra = "AND km_google  is false ";
				}
			}
		}else{
			$sql = "SELECT tbl_posto_familia.paga_deslocamento
						FROM  tbl_posto_familia
						WHERE tbl_posto_familia.posto = $login_posto
						AND   tbl_posto_familia.familia = $familia";
			$res2 = pg_exec ($con,$sql);
			if (pg_numrows($res2) > 0) {
				$paga_deslocamento = pg_result($res2,0,paga_deslocamento);
			} else {
				$paga_deslocamento = 'f';
			}

			if($login_fabrica == 137){
				$cond_extra = " AND km_google IS FALSE ";
			}
		}

		$sql = "SELECT *
				FROM tbl_tipo_atendimento
				WHERE fabrica = $login_fabrica
				AND ativo IS TRUE

				AND tipo_atendimento NOT IN (
					SELECT
						CASE WHEN valor_km > 0
							Then 0
							Else 55
					END as tipo_atendimento
					FROM tbl_posto_fabrica
					WHERE fabrica = $login_fabrica
						AND posto = $login_posto
					)
				$cond_extra
				ORDER BY tipo_atendimento ";
		$res3 = @pg_exec($con,$sql);

		if(pg_numrows($res3)>0){
			echo "<option value=''></option>";
			for ($i=0; $i<pg_numrows($res3); $i++){
				$tipo_atendimento = pg_result($res3,$i,tipo_atendimento);
				$codigo = pg_result($res3,$i,codigo);
				$descricao = pg_result($res3,$i,descricao);
				if(($paga_deslocamento == 'f') AND ($tipo_atendimento==55)){
					continue;
				}else{
					echo "<option value='$tipo_atendimento' $sel>$codigo - $descricao</option>";
				}
			}
		}
	}
}
?>

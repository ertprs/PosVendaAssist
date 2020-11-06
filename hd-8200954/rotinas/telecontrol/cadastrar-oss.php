<?php

error_reporting(E_ALL ^ E_NOTICE);

try {

	include dirname(__FILE__) . '/../dbconfig_pg.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require_once dirname(__FILE__) . '/../funcoes.php';

	define('APP', 'Atualiza Status Pedido');
	define('ENV','producao');

	$vet['fabrica'] = 'Telecontrol';
	$vet['tipo']    = 'atualiza-status';
	$vet['dest']    = ENV == 'testes' ? 'ronald.santos@telecontrol.com.br' : 'helpdesk@telecontrol.com.br';
	$vet['log']     = 1;

	$sql = "SELECT tbl_os.*, (select count(*) from tbl_os_produto join tbl_os_item using(os_produto) where tbl_os_produto.os = tbl_os.os) as qtde_peca
		FROM tbl_os
		JOIN tbl_produto USING(produto)
		WHERE tbl_os.data_digitacao between CURRENT_TIMESTAMP - INTERVAL '1 months' and current_timestamp - interval '2 days' 
		AND excluida is not true
		and fabrica not in (0,10)
		AND     tbl_os.troca_garantia       IS NULL
		AND tbl_os.posto <> 6359 order by random() limit 256
		";
	$res = pg_query($con,$sql);
	if(pg_numrows($res) > 0){
		$resultados =pg_fetch_all($res);
		for($i = 0; $i < pg_numrows($res); $i++){
			$qtde_peca	= pg_result($res,$i,'qtde_peca');
			$msg_erro = "";
			unset($resultados[$i]['os']);
			unset($resultados[$i]['qtde_peca']);
			$sql2 = "SELECT produto FROM tbl_lista_basica WHERE fabrica = 46 order by random() limit 1";
			$res2 = pg_query($con,$sql2);
			$produto = pg_fetch_result($res2,0,0);

			$sql2 = "SELECT defeito_constatado FROM tbl_defeito_constatado WHERE fabrica = 46 order by random() limit 1";
			$res2 = pg_query($con,$sql2);
			$defeito_constatado = pg_fetch_result($res2,0,0);

			$sql2 = "SELECT defeito_reclamado FROM tbl_defeito_reclamado WHERE fabrica = 46 order by random() limit 1";
			$res2 = pg_query($con,$sql2);
			$defeito_reclamado = pg_fetch_result($res2,0,0);

			$sql2 = "SELECT solucao FROM tbl_solucao WHERE fabrica = 46 order by random() limit 1";
			$res2 = pg_query($con,$sql2);
			$solucao = pg_fetch_result($res2,0,0);

			echo pg_last_error($con);
			$sql2 = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = 46 and credenciamento ='CREDENCIADO' order by random() limit 1";
			$res2 = pg_query($con,$sql2);
			$posto = pg_fetch_result($res2,0,0);

			echo pg_last_error($con);
			$resi = @pg_query($con,"BEGIN TRANSACTION");
			$sqli = "insert into tbl_os(";
			foreach($resultados[$i] as $key => $valor){
				$sqli.="$key,";
			}
			$sqli .="os)values("; 
			foreach($resultados[$i] as $key => $valor){
				if($key == 'defeito_reclamado' and !empty($valor)) $valor = $defeito_reclamado ;
				if($key == 'defeito_constatado' and !empty($valor)) $valor = $defeito_constatado ;
				if($key == 'solucao_os' and !empty($valor)) $valor = $solucao ;

				if(empty($valor)) $valor = "null";

				if(preg_match("/\s/",$valor)) {
					$valor= "'$valor'";
				}elseif(strlen($valor) == 1 and is_string($valor)) {
					$valor = "'$valor'"; 
				}elseif(date('Y-m-d', strtotime($valor)) == $valor) {
					$valor = "'$valor'"; 
				}elseif(is_string($valor) and $valor <>'null') {
					$valor = "'$valor'"; 
				}

				if($key == 'produto' and !empty($valor)) $valor = $produto ;
				if($key == 'posto' and !empty($valor)) $valor = $posto ;
				if($key == 'fabrica' and !empty($valor)) $valor = 46;
				$sqli.="{$valor},";
			}
			$sqli .="default) returning os";
			$resi = pg_query($con,$sqli);
			$os = pg_fetch_result($resi,0,0);
			$msg_erro = pg_last_error($con);

			$sqli = "insert into tbl_os_extra(os)values($os) ";
			$resi = pg_query($con,$sqli);

			if($qtde_peca > 0){
				$sqli = "insert into tbl_os_produto(os,produto)values($os,$produto) returning os_produto";
				$resi = pg_query($con,$sqli);
				$os_produto = pg_fetch_result($resi,0,0);

				for($x=0;$x<$qtde_peca;$x++){
					$sql2 = "SELECT peca FROM tbl_lista_basica WHERE fabrica = 46 and produto = $produto order by random() limit 1";
					$res2 = pg_query($con,$sql2);
					$peca = pg_fetch_result($res2,0,0);

					$sql2 = "SELECT defeito FROM tbl_defeito WHERE fabrica = 46  order by random() limit 1";
					$res2 = pg_query($con,$sql2);
					$defeito = pg_fetch_result($res2,0,0);

					$sql2 = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = 46   order by random() limit 1";
					$res2 = pg_query($con,$sql2);
					$servico_realizado = pg_fetch_result($res2,0,0);

					echo pg_last_error($con);
					$sqlo = "insert into tbl_os_item(os_produto,peca, qtde, defeito,servico_realizado) values ($os_produto , $peca, 1,$defeito,  $servico_realizado) ;";
					$reso = pg_query($con,$sqlo);
					$msg_erro = pg_last_error($con);
					echo pg_last_error($con);
					echo pg_last_error($con);
				}
			}
			if (strlen($msg_erro) == 0) {
				$resi = @pg_query($con,"COMMIT TRANSACTION");
			} else {
				$resi = @pg_query($con,"ROLLBACK TRANSACTION");
				echo "erro pg_last_error($con)";
			}

		}
	}

	if (!empty($msg_erro)) {
		$msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
		Log::envia_email($vet, APP, $msg);
	}

} catch (Exception $e) {

    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
    Log::envia_email($vet, APP, $msg);

}

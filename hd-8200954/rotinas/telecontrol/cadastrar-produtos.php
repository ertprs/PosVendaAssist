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
		if(!empty($argv[1])) {
			$fabrica = $argv[1];
			
		}


	$sql = "SELECT tbl_produto.*
			FROM tbl_produto 
			WHERE fabrica_i not in (0,10,$fabrica)
			and mao_de_obra > 0 
			order by random() limit 2 
		";
	$res = pg_query($con,$sql);
	if(pg_numrows($res) > 0){
		$resultados =pg_fetch_all($res);
		for($i = 0; $i < pg_numrows($res); $i++){
			$msg_erro = "";
			unset($resultados[$i]['produto']);
			unset($resultados[$i]['fabrica_i']);
			unset($resultados[$i]['data_input']);
			unset($resultados[$i]['data_atualizacao']);
			unset($resultados[$i]['admin']);
			$resultados[$i]['mao_de_obra_admin'] = 3;
				$produto_in = pg_fetch_result($res,$i,'produto');
			$sql2 = "SELECT linha FROM tbl_linha WHERE fabrica = $fabrica order by random() limit 1";
			$res2 = pg_query($con,$sql2);
			$linha = pg_fetch_result($res2,0,0);

			$sql2 = "SELECT familia FROM tbl_familia WHERE fabrica = $fabrica order by random() limit 1";
			$res2 = pg_query($con,$sql2);
			$familia= pg_fetch_result($res2,0,0);


			echo pg_last_error($con);
			$resi = @pg_query($con,"BEGIN TRANSACTION");
			$sqli = "insert into tbl_produto(";
			foreach($resultados[$i] as $key => $valor){
				$sqli.="$key,";
			}
			$sqli .="produto)values("; 
			foreach($resultados[$i] as $key => $valor){
				if(empty($valor)) $valor = "null";

				if($key == 'linha' and !empty($valor)) $valor = $linha ;
				if($key == 'familia' and !empty($valor)) $valor = $familia ;

				if(preg_match("/\s/",$valor)) {
					$valor= "'$valor'";
				}elseif(strlen($valor) == 1 and is_string($valor)) {
					$valor = "'$valor'"; 
				}elseif(date('Y-m-d', strtotime($valor)) == $valor) {
					$valor = "'$valor'"; 
				}elseif(is_string($valor) and $valor <>'null') {
					$valor = "'$valor'"; 
				}

				$sqli.="{$valor},";
			}
			$sqli .="default) returning produto";
			$resi = pg_query($con,$sqli);
			$produto = pg_fetch_result($resi,0,0);
			$msg_erro = pg_last_error($con);
			$sqli = "SELECT tbl_peca.* FROM tbl_lista_basica join tbl_peca using(peca) where tbl_lista_basica.produto = $produto_in and produto_acabado is not true and ipi > 0 and tbl_peca.ativo  limit 6 ";
			$resi = pg_query($con,$sqli);
		    $qtde_peca = pg_numrows($resi);
			if($qtde_peca == 0 ) {
				echo pg_last_error();
					$resi = @pg_query($con,"ROLLBACK TRANSACTION");
					continue;
					
			}

			$result_peca =pg_fetch_all($resi);
			for($x=0;$x<$qtde_peca;$x++){
					$msg_erro = "";
					unset($result_peca[$x]['peca']);
					unset($result_peca[$x]['admin']);
					unset($result_peca[$x]['data_input']);
					unset($result_peca[$x]['data_atualizacao']);
					unset($result_peca[$x]['preco_anterior']);

		
					$sqli = "insert into tbl_peca(";
					foreach($result_peca[$x] as $key => $valor){
						$sqli.="$key,";
					}
					$sqli .="peca)values("; 
					foreach($result_peca[$x] as $key => $valor){
						if(empty($valor)) $valor = "null";

						if($key == 'fabrica' and !empty($valor)) $valor = $fabrica ;

						if(preg_match("/\s/",$valor)) {
							$valor= "'$valor'";
						}elseif(strlen($valor) == 1 and is_string($valor)) {
							$valor = "'$valor'"; 
						}elseif(date('Y-m-d', strtotime($valor)) == $valor) {
							$valor = "'$valor'"; 
						}elseif(is_string($valor) and $valor <>'null') {
							$valor = "'$valor'"; 
						}

						$sqli.="{$valor},";
					}
					$sqli .="default) returning peca";
					$resi = pg_query($con,$sqli);
					$peca = pg_fetch_result($resi,0,0);
			echo pg_last_error();	
					$msg_erro = pg_last_error($con);
					$sqli = "INSERT INTO tbl_lista_basica(produto, peca, fabrica, qtde)values($produto, $peca, $fabrica, 1)";
					$resi = pg_query($con,$sqli);
					$msg_erro = pg_last_error($con);
			echo pg_last_error();	
			}
			if (strlen($msg_erro) == 0) {
				echo 'ffff';
				$resi = @pg_query($con,"COMMIT TRANSACTION");
			} else {
				echo 'aaaaaa';
				$resi = @pg_query($con,"ROLLBACK TRANSACTION");
				echo "erro pg_last_error($con)";
			}

		}
	}


} catch (Exception $e) {

    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
    Log::envia_email($vet, APP, $msg);

}

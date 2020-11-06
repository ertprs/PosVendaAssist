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

	$sql = "SELECT tbl_faturamento.*
		FROM tbl_faturamento
		WHERE tbl_faturamento.data_input between CURRENT_TIMESTAMP - INTERVAL '1 months' and current_timestamp - interval '2 days' 
		AND cancelada isnull
		and fabrica not in (0,10)
		AND tbl_faturamento.posto <> 6359 order by random() limit 10 
		";
	$res = pg_query($con,$sql);
	if(pg_numrows($res) > 0){
		$resultados =pg_fetch_all($res);
		for($i = 0; $i < pg_numrows($res); $i++){
			unset($resultados[$i]['faturamento']);
			$sql = "SELECT pedido, total FROM tbl_pedido LEFT JOIN tbl_faturamento USING(pedido) WHERE tbl_pedido.fabrica = 131 and tbl_pedido.posto = 6359 and tbl_faturamento.pedido isnull and data between CURRENT_DATE - INTERVAL '2 DAYS' AND CURRENT_TIMESTAMP order by random() limit 1";
			$resi = pg_query($con,$sql);
			$pedido = pg_fetch_result($resi,0,0);
			$total  = pg_fetch_result($resi,0,1);
			$resi = @pg_query($con,"BEGIN TRANSACTION");
			$sqli = "insert into tbl_faturamento(";
			foreach($resultados[$i] as $key => $valor){
				$sqli.="$key,";
			}
			$sqli .="faturamento)values("; 
			foreach($resultados[$i] as $key => $valor){
				if($key == 'emissao' and !empty($valor)) $valor = date('Y-m-d');
				if($key == 'saida' and !empty($valor)) $valor = date('Y-m-d') ;
				if($key == 'pedido' ) $valor =$pedido ;
				if($key == 'total_nota' and !empty($valor)) $valor =$total ;
				if($key == 'posto' and !empty($valor)) $valor = 6359 ;
				if($key == 'fabrica' and !empty($valor)) $valor = 131;

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

				$sqli.="{$valor},";
			}
			$sqli .="default) returning faturamento";
			$resi = pg_query($con,$sqli);
			$faturamento = pg_fetch_result($resi,0,0);
			$msg_erro = pg_last_error($con);

			$cfop = $resultados[$i]['cfop'];
			$sql = "SELECT pedido,pedido_item, peca, preco,qtde FROM tbl_pedido_item WHERE pedido = $pedido";
			$resx = pg_query($con,$sql);

			if(pg_num_rows($resx) > 0){

				for($f=0;$f<pg_num_rows($resx);$f++){
					$itens =pg_fetch_all($resx);
					$sqli = "insert into tbl_faturamento_item(";
					foreach($itens[$f] as $ik => $iv){
						$sqli.="$ik,";
					}
					$sqli.=" faturamento,cfop )values( ";
					foreach($itens[$f] as $ik => $iv){
						$sqli.="$iv,";
					}
					$sqli.=" $faturamento,'$cfop')  ";
					$resi = pg_query($con,$sqli);
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

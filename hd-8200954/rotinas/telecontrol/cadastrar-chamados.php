<?php

error_reporting(E_ALL ^ E_NOTICE);

try {
    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require_once dirname(__FILE__) . '/../funcoes.php';

    define('APP', 'Atualiza Status Pedido');
	define('ENV','producao');

    $vet['fabrica'] = 'Telecontrol';
    $vet['tipo']    = 'atualiza-status';
    $vet['dest']    = ENV == 'testes' ? 'ronald.santos@telecontrol.com.br' : 'helpdesk@telecontrol.com.br';
    $vet['log']     = 1;

    $sql = "SELECT data,status,categoria,resolvido, tbl_hd_chamado_extra.*
	    from tbl_hd_chamado
	    join tbl_hd_chamado_extra using(hd_chamado)
	    where fabrica not in (1,3,10,42)
	    and fabrica_responsavel not in (1,3,10,42)
	    and (tbl_hd_chamado_extra.posto isnull or tbl_hd_chamado_extra.posto <> 6359)
	    and data between current_timestamp - interval '1 month' and current_timestamp - interval '3 days' order by random() limit 128 ;
    ";
    $res = pg_query($con,$sql);
    $resultados = pg_fetch_all($res);
    for($i=0;$i<pg_num_rows($res);$i++){
	$msg_erro = "";
	$hd_chamado_interacao = pg_fetch_result($res,$i,'hd_chamado');
	$sql2  = "SELECT admin FROM tbl_admin WHERE fabrica = 46 order by random() limit 1";
	$res2  = pg_query($con,$sql2);
	$admin = pg_fetch_result($res2,0,0);

	$sql2  = "SELECT admin FROM tbl_admin WHERE fabrica = 46 order by random() limit 1";
	$res2  = pg_query($con,$sql2);
	$atendente = pg_fetch_result($res2,0,0);

	$sql2  = "SELECT produto FROM tbl_produto WHERE fabrica_i = 46 order by random() limit 1";
	$res2  = pg_query($con,$sql2);
	$produto = pg_fetch_result($res2,0,0);

	$sql2 = "SELECT posto FROM tbl_posto_fabrica WHERE fabrica = 46 and credenciamento ='CREDENCIADO' order by random() limit 1";
	$res2 = pg_query($con,$sql2);
	$posto = pg_fetch_result($res2,0,0);

	if($resultados[$i]['status'] == 'Resolvido' and !empty($resultados[$i]['resolvido'])){
		$resolvido = "'{$resultados[$i]['resolvido']}'";
	}else{
		$resolvido = "null";
	}
	$resi = @pg_query($con,"BEGIN TRANSACTION");
	$sqli = "INSERT INTO tbl_hd_chamado(
			titulo,
			status,
			categoria,
			resolvido,
			admin,
			atendente,
			fabrica,
			fabrica_responsavel,
			data
		)values(
			'Atendimento interativo',
			'{$resultados[$i]['status']}',
			'{$resultados[$i]['categoria']}',
			$resolvido,
			$admin,
			$atendente,
			46,
			46,
			'{$resultados[$i]['data']}'
		) returning hd_chamado
		";
	$resi = pg_query($con,$sqli);
	$hd_chamado = pg_fetch_result($resi,0,0);
	$msg_erro = pg_last_error($con);
	unset($resultados[$i]['categoria']);
	unset($resultados[$i]['status']);
	unset($resultados[$i]['hd_chamado']);
	unset($resultados[$i]['data']);
	unset($resultados[$i]['resolvido']);

	$sqli = "insert into tbl_hd_chamado_extra(";
	foreach($resultados[$i] as $key => $valor){
		$sqli.="$key,";
	}
	$sqli .="hd_chamado)values(";
	foreach($resultados[$i] as $key => $valor){
		if($key == 'produto' and !empty($valor)) $valor = $produto ;
		if($key == 'posto' and !empty($valor)) $valor = $posto ;

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
	$sqli .="$hd_chamado)";
	$resi = pg_query($con,$sqli);
	echo pg_last_error($con);
	$msg_erro = pg_last_error($con);
	$sqli = "INSERT INTO tbl_hd_chamado_item(
			hd_chamado,
			data,
			comentario,
			admin,
			interno,
			status_item,
			enviar_email,
			produto,
			serie,
			defeito_reclamado,
			defeito_reclamado_descricao,
			tincaso
		) SELECT
			$hd_chamado,
			data,
			comentario,
			$atendente,
			interno,
			status_item,
			enviar_email,
			produto,
			serie,
			defeito_reclamado,
			defeito_reclamado_descricao,
			tincaso
		FROM tbl_hd_chamado_item 
		WHERE hd_chamado = $hd_chamado_interacao ";
	$resi = pg_query($con,$sqli);
	echo pg_last_error($con);
	$msg_erro = pg_last_error($con);

	if (strlen($msg_erro) == 0) {
		$resi = @pg_query($con,"COMMIT TRANSACTION");
	} else {
		$resi = @pg_query($con,"ROLLBACK TRANSACTION");
		echo "erro pg_last_error($con)";
	}
    }

} catch (Exception $e) {

    $msg = 'Script: '.__FILE__.'<br />Erro na linha ' . $e->getLine() . ':<br />' . $e->getMessage();
    Log::envia_email($vet, APP, $msg);

}

<?php
/**
 *
 * distrib/embarque.php
 *
 * @author  Francisco Ambrozio
 * @version 2012.07.26
 *
 *  HD 920886 - este programa irá executar os embarque_novo.pl e
 *    embarque_novo_faturado.pl alternado entre os dois
 *
 */

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

$fabrica_distrib = 63;

/*
* Cron Class
*/
$phpCron = new PHPCron($fabrica_distrib, __FILE__);
$phpCron->inicio();

/* Fabricas Distrib */
$sql = "SELECT fabrica FROM tbl_fabrica WHERE parametros_adicionais ilike '%telecontrol_distrib%'";
$res = pg_query($con,$sql);
if(pg_num_rows($res) > 0){
	while($data = pg_fetch_object($res)){
		$telecontrol_distrib .= $data->fabrica.",";
	}
}

$telecontrol_distrib = substr($telecontrol_distrib, 0, strlen($telecontrol_distrib) - 1);

if(!empty($argv[1])) {
	$posto = $argv[1];
	$cond = " AND tbl_pedido.posto = $posto ";
}
$sql_embarque_garantia = "
	SELECT distinct os INTO TEMP tmp_embarque
	FROM tbl_os_item
	JOIN tbl_pedido using(pedido)
	JOIN tbl_pedido_item USING(pedido,peca)
	JOIN tbl_os_produto using(os_produto)
	WHERE fabrica = 81
	AND distribuidor =4311
	AND data >'2010-04-30'
	AND fabrica_i = 81
	$cond
	AND tbl_os_item.os_item NOT IN (
		SELECT os_item FROM tbl_embarque_item WHERE tbl_embarque_item.os_item = tbl_os_item.os_item
	)
	AND (tbl_pedido.status_pedido IS NULL OR tbl_pedido.status_pedido IN (1,2,5,7,8,9,10,11,12));

	CREATE INDEX tm_embarque_os ON tmp_embarque(os);

	SELECT os,fabrica FROM tbl_os
	JOIN tmp_embarque USING(os)
	WHERE fabrica = 81
	ORDER BY finalizada desc, data_digitacao";

$sql_embarque_faturado = "
    SELECT pedido
		FROM   tbl_pedido
		WHERE  fabrica = 81
		AND    distribuidor = 4311
		AND    tipo_pedido = 153
		$cond
		AND    posto NOT IN (4311,970,6359,17702)
		AND    (status_pedido IS NULL OR status_pedido IN (1,2,5,7,8,9,10,11,12))
	UNION
		SELECT pedido
		FROM   tbl_pedido
		JOIN	tbl_tipo_pedido USING(tipo_pedido,fabrica)
		WHERE  tbl_pedido.fabrica = 81
		AND    distribuidor = 4311
		$cond
		AND    (pedido_faturado or upper(tbl_tipo_pedido.descricao) = 'FATURADO')
		AND    posto NOT IN (4311,970,6359,17702)
		AND    (status_pedido IS NULL OR status_pedido IN (1,2,5,7,8,9,10,11,12)) 
	ORDER BY pedido
	";

$query_embarque_garantia = pg_query($con, $sql_embarque_garantia);
$query_embarque_faturado = pg_query($con, $sql_embarque_faturado);

$array_embarque_garantia = array();
$array_embarque_faturado = array();

if (pg_num_rows($query_embarque_garantia) > 0) {
	$i = 0;

	while ($fetch = pg_fetch_assoc($query_embarque_garantia)) {
		$os = $fetch['os'];
		$fabrica = $fetch['fabrica'];

		$sql = "SELECT os
					FROM tbl_os
				JOIN tbl_os_produto USING (os) JOIN tbl_os_item USING (os_produto)
				WHERE tbl_os.os = $os
				AND   tbl_os.posto NOT IN (970,6359,17702)";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) == 0) {
			continue;
		}

		# despreza OS canceladas (intervencão do Fabricante no ultimo status)
		$sql = "SELECT status_os
				FROM tbl_os_status
				WHERE tbl_os_status.os = $os
				ORDER BY os_status DESC LIMIT 1";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) > 0) {
			$status_os= pg_fetch_result($res,0,0) ;
			if ($status_os == 62) {
				continue;
			}
		}

		# despreza peças já atendidas, já embarcadas, ou de responsabilidade da GAMA
		$sql = "SELECT tbl_os.posto, tbl_os_item.os_item,
					   tbl_os_item.peca, tbl_os_item.qtde, tbl_os_item.pedido, tbl_os_item.pedido_item
				FROM tbl_os
				JOIN tbl_os_produto USING (os)
				JOIN tbl_os_item USING (os_produto)
				JOIN tbl_pedido ON tbl_os_item.pedido = tbl_pedido.pedido
				JOIN tbl_servico_realizado USING (servico_realizado)
				WHERE tbl_os.os = $os
				AND   tbl_pedido.distribuidor = 4311
				AND   tbl_pedido.data > '2008-07-12'
				AND   tbl_pedido.data > '2010-04-30'
				AND   (tbl_servico_realizado.troca_de_peca IS TRUE OR tbl_servico_realizado.troca_produto IS TRUE)
				AND   tbl_servico_realizado.gera_pedido   IS TRUE
				AND   tbl_os_item.os_item NOT IN (SELECT os_item FROM tbl_embarque_item WHERE tbl_embarque_item.os_item = tbl_os_item.os_item)
				";
		$res = pg_query($con, $sql);
		if (pg_num_rows($res) == 0) {
			continue;
		}

		$array_embarque_garantia[$i] = $fetch['os'];
		$i = $i + 2;
	}
}

if (pg_num_rows($query_embarque_faturado) > 0) {
	$i = 1;

	while ($fetch = pg_fetch_assoc($query_embarque_faturado)) {
		$pedido = $fetch['pedido'];
		$sql = "SELECT pedido_item, tbl_pedido_item.peca, referencia, qtde - qtde_faturada_distribuidor - qtde_cancelada AS qtde
				FROM tbl_pedido_item
				JOIN tbl_peca on tbl_peca.peca = tbl_pedido_item.peca
				WHERE pedido = $pedido
				AND qtde - qtde_faturada_distribuidor - qtde_cancelada > 0;";

		$res = pg_query($con, $sql);
		if (pg_num_rows($res) == 0) {
			continue;
		}

		$sql = "SELECT sum(e.qtde)
				FROM tbl_pedido_item
				JOIN tbl_posto_estoque e USING(peca)
				WHERE pedido = $pedido
				AND   posto = 4311
				AND qtde - qtde_faturada_distribuidor - qtde_cancelada > 0;";

		$res = pg_query($con, $sql);
		if (pg_num_rows($res) > 0) {
			$qtde = pg_fetch_result($res,0,0);
			if($qtde == 0) {
				continue;
			}
		}
		$array_embarque_faturado[$i] = $fetch['pedido'];
		$i = $i + 2;
	}
}

$arr = $array_embarque_garantia + $array_embarque_faturado;
ksort($arr);

if (!empty($arr)) {
	echo `mkdir -p /tmp/telecontrol`;
	echo `echo Começando > /tmp/telecontrol/embarque_novo.txt`;
	echo `echo Começando > /tmp/telecontrol/embarque_novo_faturado.txt`;
	foreach ($arr as $key => $value) {
		if ($key % 2 == 0) {
			echo `/usr/bin/perl /var/www/cgi-bin/distrib/embarque_novo.pl $value`;
		} else {
			echo `/usr/bin/perl /var/www/cgi-bin/distrib/embarque_novo_faturado.pl $value`;
		}
	}
}

/*
* Cron Término
*/
$phpCron->termino();

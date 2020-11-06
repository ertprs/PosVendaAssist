<?php

function verifica_servico_realizado($servico_realizado)
{
    global $login_fabrica, $con;

    $sql = "SELECT * FROM tbl_servico_realizado
            WHERE servico_realizado = {$servico_realizado}
            AND gera_pedido IS NOT TRUE
            AND peca_estoque IS NOT TRUE";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        return false;
    }

    return true;
}

function altera_servico_realizado($pdo, $os_item, $para, $os, $callback = null)
{
    global $login_fabrica, $con;

    if (!in_array($para, array('estoque', 'gera_pedido'))) {
        throw new Exception("Erro ao gravar serviço.");
    }

    $sql = "SELECT * FROM tbl_servico_realizado
            WHERE fabrica = {$login_fabrica}
            AND ativo IS TRUE";

    if ($para == 'estoque') {
        $sql .= ' AND peca_estoque IS TRUE ';
    } else {
        $sql .= ' AND gera_pedido IS TRUE AND troca_de_peca IS TRUE ';
    }

    $qry = $pdo->query($sql);

    if ($qry->rowCount() > 0) {
        $servico_realizado = $qry->fetch();

        $sql = "UPDATE tbl_os_item set servico_realizado = {$servico_realizado['servico_realizado']}
                WHERE os_item = $os_item";
        $upd = $pdo->query($sql);

        if (!$upd) {
            throw new Exception("Erro ao gravar serviço");
        }
    }

    if (!is_null($callback)) {
        return $callback($login_fabrica, $pdo, $os, $os_item, $servico_realizado);
    }
}

function verifica_auditoria_unica($condicao, $os) {
    global $con;

    $sql = "SELECT tbl_auditoria_os.auditoria_status FROM tbl_auditoria_os
            INNER JOIN tbl_auditoria_status ON tbl_auditoria_status.auditoria_status = tbl_auditoria_os.auditoria_status
            WHERE os = {$os}
            AND {$condicao}
            ORDER BY data_input DESC";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) == 0) {
        return true;
    } else {
        return false;
    }
}

function aprovadoAuditoria($condicao, $os) {
    global $con, $login_fabrica;

    $sql = "SELECT auditoria_os FROM tbl_auditoria_os
            INNER JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = {$login_fabrica}
            INNER JOIN tbl_auditoria_status ON tbl_auditoria_os.auditoria_status = tbl_auditoria_status.auditoria_status
            WHERE tbl_auditoria_os.os = {$os}
            AND tbl_auditoria_os.liberada IS NOT NULL
            AND {$condicao}";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        return true;
    }else{
        return false;
    }
}

function buscaAuditoria($condicao) {
    global $con;

    $sql = "SELECT auditoria_status FROM tbl_auditoria_status WHERE $condicao";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {
        return array("resultado" => true, "auditoria" => pg_fetch_result($res, 0, "auditoria_status"));
    }
}

function urlSap($urlFne) {
	global $_serverEnvironment;

	if ($_serverEnvironment == 'development') {
		if($urlFne){
			return "https://empwdq00.empaque.fne";
		}else{
			return "https://fiori.efemsa.com:8443";
			//return "https://200.23.212.38:8443";
		}
	}else{
		return "https://fiori.efemsa.com:8425";
		#return "https://200.23.212.38:8425";
	}
}

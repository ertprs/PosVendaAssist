<?php
$regras["consumidor|cpf"] = array(
    "obrigatorio" => true
); 
$regras["consumidor|celular"]["obrigatorio"] = true;
$regras["consumidor|telefone"]["obrigatorio"] = true;
if (strlen(trim(getValue("consumidor[celular]"))) > 0 OR strlen(trim(getValue("consumidor[telefone]"))) > 0) {
    $regras["consumidor|telefone"]["obrigatorio"] = false;
    $regras["consumidor|celular"]["obrigatorio"] = false;
}


$regras["consumidor|email"] = array(
    "obrigatorio" => true
); 

$regras["os|defeito_reclamado"] = array(
    "obrigatorio" => true
);
$regras["produto|serie"] = array(
    "obrigatorio" => false
);
$regras["os|observacoes"] = array(
    "function" => ["valida_obs_defeito_constatado"]
);

//fora de garantia
if (getValue("os[tipo_atendimento]") == 76964){
    $regras["revenda|nome"]["obrigatorio"]   = false;
    $regras["revenda|cnpj"]["obrigatorio"]   = false;
    $regras["revenda|estado"]["obrigatorio"] = false;
    $regras["revenda|cidade"]["obrigatorio"] = false;
    $regras["os|nota_fiscal"]["obrigatorio"]   = false;
    $regras["os|data_compra"]["obrigatorio"]   = false;
}


if (!verifica_tipo_posto_geral("posto_interno","TRUE",$login_posto)) {
	if ($xfechamentoOs == true) {


		if (isset($_POST['os']) && strlen($_POST['os']) > 0 ){

			$xxos = $_POST['os'];
			
			$sqlTAOS = "SELECT tipo_atendimento FROM tbl_os WHERE os = {$xxos}";
		        $resTAOS = pg_query($con, $sqlTAOS);
			
    			$tipo_atendimento_arr = pg_fetch_result($resTAOS, 0, "tipo_atendimento");
		}

	} else {
    		$tipo_atendimento_arr = getTipoAtendimento(getValue("os[tipo_atendimento]"));
	}
    if ($tipo_atendimento_arr["fora_garantia"] != "t") {

        $auditorias = array(
            "auditoria_os_reincidente_lepono",
            "auditoria_peca_critica",
            "auditoria_km_lepono",
            "auditoria_troca_obrigatoria",
            "auditoria_peca_estoque_lepono",
            "auditoria_peca_garantia"
        );

    }

} else {

    $auditorias = [];

}

function valida_obs_defeito_constatado() {
    global $con, $campos;

    if (!empty($campos["produto"]["defeitos_constatados_multiplos"]) && empty($campos["os"]["observacoes"])) {
        throw new \Exception("Informe a descrição detalhada do defeito");
    }

}

function auditoria_peca_garantia(){
    global $login_fabrica, $campos, $os, $con, $login_admin;

    foreach ($campos["produto_pecas"] as $key => $dadosPecas) {
        if (!empty($dadosPecas["id"]) && !empty($dadosPecas["servico_realizado"])) {

            $servico_realizado = $dadosPecas["servico_realizado"];

            $sql = "SELECT servico_realizado
                    FROM tbl_servico_realizado
                    WHERE servico_realizado = {$servico_realizado}
                    AND gera_pedido IS TRUE";
            $res = pg_query($con, $sql);

            if (verifica_auditoria_unica("tbl_auditoria_os.observacao = 'Auditoria de pedido de peças'", $os) === true && pg_num_rows($res) > 0) {

                $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido) 
                        VALUES ({$os}, 4, 'Auditoria de pedido de peças', true)";
                $res = pg_query($con, $sql);

            }
        }
    }
}

function auditoria_peca_estoque_lepono() {
    global $con, $login_fabrica, $os, $campos;

    foreach ($campos["produto_pecas"] as $key => $dados) {

        $servico_realizado = $dados["servico_realizado"];

        if (!empty($servico_realizado)) {

            $sql = "SELECT servico_realizado
                    FROM tbl_servico_realizado
                    WHERE servico_realizado = {$servico_realizado}
                    AND peca_estoque IS TRUE";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {

                if (verifica_auditoria_unica("tbl_auditoria_os.observacao = 'Troca de peça usando estoque'", $os) === true) {
                    //favor, não trocar para bloqueio_pedido = true, a lepono bloqueia/desbloqueia o pedido na aprovação desta auditoria
                    $sql = "
                        INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                        VALUES ({$os}, 4, 'Troca de peça usando estoque', false);
                    ";
                    pg_query($con,$sql);

                }

            }

        }

    }
    
}

function auditoria_os_reincidente_lepono() {
    global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

    $posto = $campos['posto']['id'];

    $sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND os_reincidente IS NOT TRUE AND cancelada IS NOT TRUE;";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res) > 0){

        $select = "
            SELECT
                tbl_os.os
            FROM tbl_os
            INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            WHERE tbl_os.fabrica = {$login_fabrica}
            AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
            AND tbl_os.excluida IS NOT TRUE
            AND tbl_os.os < {$os}
            AND tbl_os.posto = {$posto}
            AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
            AND tbl_os_produto.produto = {$campos['produto']['id']}
            ORDER BY tbl_os.data_abertura DESC
            LIMIT 1;
        ";

        $resSelect = pg_query($con, $select);

        if (pg_num_rows($resSelect) > 0 && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't'", $os) === true) {
            $os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");


            if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
                $busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

                if($busca['resultado']){
                    $auditoria_status = $busca['auditoria'];
                }

                $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'Auditoria de OS reincidente', true);
                ";

                pg_query($con,$sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new \Exception("Erro ao lançar ordem de serviço #AUD001");
                } else {
                    $os_reincidente_justificativa = true;
                    $os_reincidente = true;
                }
            }
        }
    }
}

function auditoria_km_lepono() {
    global $con, $os, $login_fabrica, $campos;

    $sql = "
        SELECT o.os
        FROM tbl_os o
        JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
        WHERE o.fabrica = {$login_fabrica}
        AND o.os = {$os}
        AND ta.km_google IS TRUE;
    ";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0 && verifica_auditoria_unica("tbl_auditoria_status.km = 't' AND tbl_auditoria_os.observacao ILIKE '%auditoria de KM%'", $os) === true) {
        $busca = buscaAuditoria("tbl_auditoria_status.km = 't'");
        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }

        $qtde_km = $campos["os"]["qtde_km"];
        $qtde_km_anterior = $campos["os"]["qtde_km_hidden"];

        if (!strlen($campos["os"]["qtde_km_hidden"])) {
            $campos["os"]["qtde_km_hidden"] = $campos["os"]["qtde_km"];
        }

        if ($qtde_km > 100) {
                $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'OS em auditoria de KM', false);
                ";
        } elseif ($qtde_km != $campos["os"]["qtde_km_hidden"]) {
            $programa_insert = $_SERVER['PHP_SELF'];
          
            $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'KM alterado manualmente de $qtde_km_anterior para $qtde_km', false);
                ";
        }

        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new \Exception("Erro ao lançar ordem de serviço #AUD012");
        }
    }
}

function getTipoAtendimento($tipo_atendimento) {
    global $con, $login_fabrica;

    if (!empty($tipo_atendimento)) {
        $sql = "SELECT tipo_atendimento as id,
                      UPPER(fn_retira_especiais(descricao)) as descricao,
                      entrega_tecnica, 
                      grupo_atendimento,
                      fora_garantia
                 FROM tbl_tipo_atendimento
                WHERE fabrica = {$login_fabrica}
                AND tipo_atendimento = {$tipo_atendimento}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            return array(
                "id"                => pg_fetch_result($res, 0, "id"),
                "descricao"         => pg_fetch_result($res, 0, "descricao"),
                "entrega_tecnica"   => pg_fetch_result($res, 0, "entrega_tecnica"),
                "grupo_atendimento" => pg_fetch_result($res, 0, "grupo_atendimento"),
                "fora_garantia" => pg_fetch_result($res, 0, "fora_garantia")
            );
        } else {
            throw new \Exception("Tipo de Atendimento inválido");
        }
    } else {
        return false;
    }
}
?>

<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$sql = "
    SELECT
	hd_chamado,
	os,
	array_campos_adicionais
    FROM (
	SELECT DISTINCT
	    TRIM(JSON_FIELD('instalador_id', tbl_hd_chamado_extra.array_campos_adicionais)) AS instalador_id,
	    tbl_hd_chamado_extra.array_campos_adicionais,
	    tbl_os.os,
	    tbl_hd_chamado.hd_chamado
	FROM tbl_hd_chamado
	JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
	JOIN tbl_os ON tbl_hd_chamado.hd_chamado = tbl_os.hd_chamado AND tbl_os.fabrica = 169
	JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
	JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = 169
	JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = 169
	WHERE tbl_hd_chamado.fabrica = 169
	AND tbl_familia.black = 1
	AND tbl_os.data_nf::DATE < (tbl_os.data_abertura - INTERVAL '3 months')::DATE
	AND tbl_os.excluida IS NOT TRUE
    ) x
    WHERE LENGTH(instalador_id) = 0;
";

$res = pg_query($con, $sql);

foreach (pg_fetch_all($res) as $chamados) {

    $array_campos_adicionais = json_decode($chamados['array_campos_adicionais'], true);

    $sql = "SELECT tbl_posto.posto, tbl_posto.nome FROM tbl_os JOIN tbl_posto USING(posto) WHERE fabrica = 169 AND os = {$chamados['os']};";
    $resPst = pg_query($con,$sql);

    $posto = pg_fetch_result($resPst, 0, "posto");
    $nome = utf8_encode(pg_fetch_result($resPst, 0, "nome"));

    $array_campos_adicionais['instalador_nome'] = $nome;
    $array_campos_adicionais['instalador_id'] = $posto;

    $array_campos_adicionais = json_encode($array_campos_adicionais);

    $upd = "UPDATE tbl_hd_chamado_extra SET array_campos_adicionais = '{$array_campos_adicionais}' WHERE hd_chamado = {$chamados['hd_chamado']};";
    pg_query($con, $upd);

}

?>

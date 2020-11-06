<?php
include __DIR__."/../../dbconfig.php";
include __DIR__."/../../includes/dbconnect-inc.php";

$os = $argv[1];
$login_fabrica = 169;

include __DIR__."/../../classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php";

$className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';

//SELECT ARRAY(SELECT os.os FROM (SELECT o.os, (SELECT COUNT(*) FROM tbl_auditoria_os ao WHERE ao.os = o.os AND ao.liberada IS NOT NULL AND ao.paga_mao_obra IS NOT TRUE) AS nao_paga, o.mao_de_obra, o.valores_adicionais, o.qtde_km, o.qtde_km_calculada, oe.extrato, o.consumidor_estado, pf.contato_estado, o.consumidor_cidade, pf.contato_cidade FROM tbl_os o INNER JOIN tbl_os_extra oe ON oe.os = o.os INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = 169 INNER JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto INNER JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento LEFT JOIN tbl_os_campo_extra oce ON oce.os = o.os WHERE o.fabrica = 169 AND (o.qtde_km > 60 OR (o.qtde_km < 60 AND LENGTH(oce.valores_adicionais) > 0)) AND oe.extrato IS NULL AND tp.tipo_posto NOT IN(644) AND o.consumidor_cidade != pf.contato_cidade) os WHERE os.nao_paga = 0);

$array_os = array(47936826, 47849784, 47952739, 47946036, 48132310, 47919644, 47857144, 47955736, 47798377, 47957926, 47859900, 48062363, 48206828, 47849430, 47841807, 48035684, 47847521, 47835059, 47972012, 47977179, 48017484, 48050997, 47992386, 48049326, 47789004, 47938317, 47841672, 47841137, 48027817, 47821718, 48015439, 47849062, 48033404, 48161769, 48075288, 48074022, 48008215, 47931904, 47943766, 47991136, 47979921, 47957807, 47834029, 48080773, 48070188);

echo "\n";

foreach($array_os as $os) {
$classOs   = new $className($login_fabrica, $os, $con);

$calcula_os = true;
$sql = "
        SELECT
        	CASE WHEN COUNT(*) > 0 THEN 't' ELSE 'f' END
        FROM tbl_os_defeito_reclamado_constatado
        JOIN tbl_defeito_constatado USING(defeito_constatado,fabrica)
        WHERE fabrica = {$login_fabrica}
        AND os = {$os}
	AND lista_garantia = 'fora_garantia';
";
$res = pg_query($con,$sql);

if (pg_num_rows($res) > 0) {
        $defeito_fora_garantia = pg_fetch_result($res, 0, 0);

        if ($defeito_fora_garantia == "t") {
        	$calcula_os = false;
	}
}

if ($calcula_os) {
	$classOs->calculaOs($os);
}

$sql = "SELECT o.os, oe.extrato, o.mao_de_obra, o.qtde_km_calculada AS km, o.valores_adicionais FROM tbl_os o INNER JOIN tbl_os_extra oe ON oe.os = o.os WHERE o.os = $os";
$res = pg_query($con, $sql);
$res = (object) pg_fetch_assoc($res);

echo "{$res->os};{$res->extrato};{$res->mao_de_obra};".number_format($res->km, 2, ",", ".").";{$res->valores_adicionais}\n";
}
//SELECT * FROM (SELECT o.os, (SELECT COUNT(*) FROM tbl_auditoria_os ao WHERE ao.os = o.os AND ao.liberada IS NOT NULL AND ao.paga_mao_obra IS NOT TRUE) AS nao_paga, o.mao_de_obra, o.valores_adicionais, o.qtde_km, o.qtde_km_calculada, oe.extrato, o.consumidor_estado, pf.contato_estado, o.consumidor_cidade, pf.contato_cidade FROM tbl_os o INNER JOIN tbl_os_extra oe ON oe.os = o.os INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = 169 INNER JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto INNER JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento WHERE o.fabrica = 169 AND o.qtde_km > 60 AND oe.extrato IS NOT NULL AND tp.tipo_posto NOT IN(644) AND o.consumidor_cidade != pf.contato_cidade) os WHERE os.nao_paga = 0;

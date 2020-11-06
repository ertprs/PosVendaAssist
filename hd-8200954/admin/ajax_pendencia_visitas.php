<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_admin.php';
$hoje = date('Y-m-d');

$sql = "
        SELECT tbl_roteiro.roteiro, 
               tbl_roteiro_posto.roteiro, 
               tbl_roteiro_posto.roteiro,
			   tbl_roteiro_posto.tipo_de_local,
			   tbl_roteiro_posto.data_visita,
               CASE WHEN tbl_roteiro_posto.tipo_de_local = 'PA' THEN
                   tbl_posto.nome
               WHEN tbl_roteiro_posto.tipo_de_local = 'CL' THEN
                   tbl_cliente.nome
               WHEN tbl_roteiro_posto.tipo_de_local = 'RV' THEN       
                   tbl_revenda.nome
               END  AS nome_contato,
               CASE WHEN tbl_roteiro_posto.tipo_de_local = 'PA' THEN
                   tbl_posto_fabrica.codigo_posto
               WHEN tbl_roteiro_posto.tipo_de_local = 'CL' THEN
                   tbl_cliente.codigo_cliente
               WHEN tbl_roteiro_posto.tipo_de_local = 'RV' THEN       
                   tbl_revenda.cnpj 
               END AS codigo_contato
          FROM tbl_roteiro
          JOIN tbl_roteiro_posto USING(roteiro)
			LEFT JOIN tbl_cliente ON tbl_cliente.cpf = tbl_roteiro_posto.codigo 
			LEFT JOIN tbl_revenda ON tbl_revenda.cnpj = tbl_roteiro_posto.codigo 
			LEFT JOIN tbl_posto ON tbl_posto.cnpj = tbl_roteiro_posto.codigo 
			LEFT JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto  AND tbl_posto_fabrica.fabrica = {$login_fabrica}
         WHERE tbl_roteiro_posto.status NOT IN('OK', 'CC')
           AND tbl_roteiro.admin = {$login_admin}
           AND tbl_roteiro.fabrica = {$login_fabrica}
           AND tbl_roteiro_posto.data_visita::date > CURRENT_DATE
      ORDER BY tbl_roteiro_posto.data_visita
";

$res = pg_query($con, $sql);

$rows = pg_num_rows($res);

$retorno = array(
    "qtde"         => $rows,
    "visitas" => array()
);

if ($rows > 0) {
    for ($i = 0; $i < $rows; $i++) {
        $roteiro       = pg_fetch_result($res,$i,roteiro);
        $roteiro_posto = pg_fetch_result($res,$i,roteiro_posto);
        $data_visita = pg_fetch_result($res,$i,data_visita);
        $contato = pg_fetch_result($res,$i,nome_contato);
        $tipo_contato = pg_fetch_result($res,$i,tipo_de_local);
        $codigo_contato = pg_fetch_result($res,$i,codigo_contato);

        $retorno["visitas"][] = array(
            "roteiro"       => $roteiro,
            "roteiro_posto" => $roteiro_posto,
            "data_visita"   => geraDataNormal($data_visita),
            "contato"       => $codigo_contato.' - '.$contato,
            "tipo_contato"	=> getLegendaTipoContato($tipo_contato),
        );
        
    }
}
function geraDataNormal($data) {
    $vetor = explode('-', $data);
    $dataTratada = $vetor[2] . '/' . $vetor[1] . '/' . $vetor[0];
    return $dataTratada;
}
function getLegendaTipoVisita($sigla) {
    $legenda = array("VT" => "Visita Técnica","VC" => "Visita Comercial","VA" => "Visita Administrativa","CM" => "Clínica Makita","FE" => "Feira/Evento","TN" => "Treinamento");
    return $legenda[$sigla];
}
function getLegendaTipoContato($sigla) {
    $arr =  array("CL" => "Cliente","RV" => "Revenda","PA" => "Posto Autorizado");
    return $arr[$sigla];
}
echo json_encode($retorno);

?>

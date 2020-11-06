<?           
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include 'funcoes.php';

$visita = filter_input(INPUT_POST,'os_visita');
$os     = filter_input(INPUT_POST,'os');
$data   = filter_input(INPUT_POST,'data');
$dataVisita   = filter_input(INPUT_POST,'data');
$posto  = filter_input(INPUT_POST,'posto');
$data   = formata_data($data);

if(!empty($visita) and $visita != "null"){

    $sql = "SELECT to_char(data,'DD/MM/YYYY') AS data FROM tbl_os_visita WHERE os_visita = $visita";
    $res = pg_query($con,$sql);
    $data_anterior = pg_fetch_result($res, 0, 'data');

    $sql = "UPDATE tbl_os_visita SET data = '$data' WHERE os_visita = $visita AND os = $os"; 
    $res = pg_exec($con,$sql);
    $msg = "Foi alterada a data de agendamento de $data_anterior para $dataVisita";

}else{

    $sql = "INSERT INTO tbl_os_visita(os,data) VALUES($os,'$data') RETURNING os_visita";
    $res = pg_exec($con,$sql);
    $visita = pg_fetch_result($res, 0, 'os_visita');
    $msg = "Foi informada a nova data de agendamento $dataVisita";
}

$sqlCaso = "select tbl_os.hd_chamado from tbl_os where os = $os";
$resCaso = pg_exec($con,$sqlCaso);

if (pg_num_rows($resCaso)>0) {
	$hd_chamado = pg_result($resCaso,0,0);

	$sql = "INSERT INTO tbl_hd_chamado_item (
                hd_chamado,
                comentario,
                posto,
                enviar_email,
                os,
            ) VALUES (
                $hd_chamado,
                '$msg',
                $posto,
                't',
                $os
            )
    ";
	$res = pg_exec($con,$sql);

	if (!pg_errormessage($con)) {
		
	}
}

echo $visita;
exit;
?>

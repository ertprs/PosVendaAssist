<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include "funcoes.php";
include 'autentica_admin.php';


if ($_GET['gera_excel']=='sim') {

	$cond_1 = '1=1';
	$posto = $_GET['posto'];
	if (strlen($posto)>0) {
		$cond_1 = "tbl_os.posto = $posto ";
	}
	
	$aux_data_inicial = $_GET['data_inicial'];
	$aux_data_final = $_GET['data_final'];


$select ="SELECT DISTINCT tbl_os.os													,
					tbl_os.sua_os															,
					tbl_posto_fabrica.codigo_posto											,
					tbl_posto.nome	as posto_nome											,
					tbl_posto.estado														,
					to_char(tbl_os.data_abertura,'DD/MM/YYYY')         AS abertura			,
					to_char(tbl_pedido.data,'DD/MM/YYYY')              AS data_pedido		,
					tbl_pedido.pedido														,
					date(tbl_pedido.data)-date(tbl_os.data_abertura)            As dias					
			FROM tbl_os
			JOIN tbl_os_produto USING(os)
			JOIN tbl_posto using(posto)
			LEFT JOIN tbl_os_item USING(os_produto)
			LEFT JOIN tbl_pedido on tbl_pedido.pedido=tbl_os_item.pedido and 
			          tbl_pedido.fabrica = tbl_os.fabrica and tbl_pedido.status_pedido <> 14
			JOIN tbl_posto_fabrica on tbl_posto_fabrica.posto = tbl_posto.posto AND
	                     tbl_posto_fabrica.fabrica=tbl_os.fabrica  
			WHERE (tbl_os.data_abertura BETWEEN '$aux_data_inicial' AND '$aux_data_final')   
			AND tbl_os.fabrica=$login_fabrica
			AND $cond_1
			AND (date(tbl_pedido.data)-date(tbl_os.data_abertura)) > 15 ORDER BY tbl_os.os, tbl_posto.nome";

	$export = pg_exec($con, "$select");
    $fields = pg_num_fields($export);

    for ($i = 0; $i < $fields; $i++) {
        $header .= pg_field_name($export, $i) . "\t";
    }

    while($row = pg_fetch_row($export)) {
        $line = '';
        foreach($row as $value) {
            if ((!isset($value)) OR ($value == "")) {
                $value = "\t";
            } else {
                $value = str_replace('"', '""', $value);
                $value = '"' . $value . '"' . "\t";
            }
            $line .= $value;
        }
        $data .= trim($line)."\n";
    }
    $data = str_replace("\r","",$data);
	
	if ($data == "") {
        $data = "\n(0) Records Found!\n";
    }
    else{
		$nome_excel = time();
		$hoje=date("Y_m_j");
        header("Content-type: application/x-msdownload; charset=iso-8859-1");
        header("Content-Disposition: attachment; filename=".$nome_excel.".xls");
        header("Pragma: no-cache");
        header("Expires: 0");
        print "$header\n$data";
    }

}
?>
<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";

if (preg_match("/\/admin\//", $_SERVER["PHP_SELF"])) {
    include 'autentica_admin.php';
    define('APPBACK', '../');
    $areaAdmin = true;    
} else {
    define('APPBACK', '');
    include 'autentica_usuario.php';
}

$posto = $_GET['posto'];

header('Content-Type: text/html; charset=iso-8859-1');

$title = "CONSULTA DADOS DO POSTO NO RECEITA";


if(isset($_GET['parametro'])){

	$parametro = $_GET["parametro"];
	$atendente = (int)$_GET['atendente'];
	$cliente = $_GET['cliente'];
	$cpf = $_GET['cpf'];
	$status = $_GET['status'];
	$providencia = $_GET['providencia'];
	$classificacao_agrupar = $_GET['classificacao_agrupar'];
	$providencia_agrupar = $_GET['providencia_agrupar'];
	$agrupar = $_GET['agrupar'];
	$providencia3   = $_GET['providencia3'];
	$motivo_contato = $_GET['motivo_contato'];

	if (in_array($login_fabrica, [169, 170])) {

		$as_prov     = ",motivo_contato_descricao, hd_providencia_descricao";

    	if (!empty($providencia3)) {
    		$condProv   .= "AND tbl_hd_chamado_extra.hd_providencia = {$providencia3}";
    	}

    	if (!empty($motivo_contato)) {
    		$condMotivo .= "AND tbl_hd_chamado_extra.motivo_contato = {$motivo_contato}";
    	}
    }

if($agrupar == 'c' OR $agrupar == 'n'){

	if(strlen(trim($classificacao_agrupar)) > 0){
		$condAgrupar .= " AND tbl_hd_classificacao.descricao = '$classificacao_agrupar' ";
	}else{
		$condAgrupar .= " AND tbl_hd_classificacao.hd_classificacao is null ";
	}	
}


if($agrupar == 'p' OR $agrupar == 'n'){
	if( strlen(trim($providencia_agrupar)) >0){
		$condAgrupar .= " AND tbl_hd_motivo_ligacao.descricao = '$providencia_agrupar' ";
	}else{
		$condAgrupar .= " AND tbl_hd_motivo_ligacao.hd_motivo_ligacao is null ";
	}
}

	if($parametro == "atrasado"){
		$condParamentro = " AND tbl_hd_chamado.data_providencia < CURRENT_DATE "; 
	}elseif($parametro == "em_dia"){
		$condParamentro = " AND tbl_hd_chamado.data_providencia > CURRENT_DATE "; 
	}elseif($parametro == "hoje"){
		$condParamentro = " AND tbl_hd_chamado.data_providencia = CURRENT_DATE  "; 
	}else{
		$condParamentro = "   "; 
	}

	if(strlen(trim($providencia)) > 0){
		$condProvidencia = " AND tbl_hd_motivo_ligacao.descricao = '$providencia' ";
	}

	if($atendente > 0){
		$condAtendente = "AND tbl_hd_chamado.atendente = $atendente"; 
	}

	if(!empty($cliente)){
		$condCliente .= " AND tbl_hd_chamado_extra.nome ilike '$cliente%' ";
	}

	if(!empty($cpf)){
		$cpf = str_replace("-", "", $cpf);
		$cpf = str_replace(".", "", $cpf);
		$cpf = str_replace("/", "", $cpf);

		$condCPF .= " AND tbl_hd_chamado_extra.cpf = '$cpf' ";
	}

	if(strlen(trim($status))==0){
		$condStatus = " AND tbl_hd_chamado.status NOT IN('Cancelado') ";
	}else{
		$condStatus = " AND tbl_hd_chamado.status IN('$status') ";
	}

	$sql = "SELECT tbl_hd_chamado.hd_chamado,
			to_char(tbl_hd_chamado.data,'DD/MM/YYYY') AS data,
			to_char(tbl_hd_chamado.data_providencia,'DD/MM/YYYY') AS data_providencia,
			tbl_hd_motivo_ligacao.descricao AS providencia,
			tbl_hd_classificacao.descricao AS classificacao,
			upper(tbl_admin.login) AS atendente,
			tbl_motivo_contato.descricao as motivo_contato_descricao,
			tbl_hd_providencia.descricao as descricao_providencia
			FROM tbl_hd_chamado
			JOIN tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
			JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.atendente AND tbl_admin.fabrica = $login_fabrica
			LEFT JOIN tbl_hd_motivo_ligacao ON tbl_hd_motivo_ligacao.hd_motivo_ligacao = tbl_hd_chamado_extra.hd_motivo_ligacao
			LEFT JOIN tbl_hd_classificacao ON tbl_hd_classificacao.hd_classificacao = tbl_hd_chamado.hd_classificacao
			AND tbl_hd_motivo_ligacao.fabrica = $login_fabrica
			LEFT JOIN tbl_hd_providencia ON ( tbl_hd_providencia.hd_providencia = tbl_hd_chamado_extra.hd_providencia)
			AND tbl_hd_providencia.fabrica = {$login_fabrica}
			LEFT JOIN tbl_motivo_contato ON ( tbl_motivo_contato.motivo_contato = tbl_hd_chamado_extra.motivo_contato)
			AND tbl_motivo_contato.fabrica = {$login_fabrica}
			WHERE tbl_hd_chamado.fabrica = $login_fabrica
			$condStatus 
			AND tbl_hd_chamado.data BETWEEN '$data_inicial 00:00:00' and '$data_final 23:59:59'
			$condMotivo
			$condProv
			$condAtendente
			$condCliente
			$condCPF 
			$condProvidencia
			$condParamentro 
			$condAgrupar 
			";
	$res = pg_query($con, $sql);
}

?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title><?=$title?></title>
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="../bootstrap/css/ajuste.css" />
		<script src="../plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="../bootstrap/js/bootstrap.js"></script>
        <script type="text/javascript">            
            $(function(){
                $("#btn_atualizar").click(function(){
                    $("#btn_atualizar").hide();
                    $("#loading_pre_cadastro").show();
                });
            });
        </script>		
	</head>
<body>
	<div class="container">
        <?php if(strlen($msg_erro)>0){ ?>
        <div class="row-fluid">
            <div class="alert alert-danger"><?=$msg_erro?></div>
        </div>
        <?php } if(strlen($ok)>0){ ?>
        <div class="row-fluid">
            <div class="alert alert-success"><?=$ok?></div>
        </div>
        <?php } ?>
        <?php  if(strlen($msg_erro)==0 and strlen($ok)==0){ ?>
		
		<?php if(pg_num_rows($res)>0 ){ ?>
		<div class="row-fluid">
		<table class="table table-striped table-bordered table-hover table-fixed">
            <thead  class="titulo_coluna">
                <tr>
                    <th>Atendimento</th>
                    <th>Data</th>
                    <?php if($moduloProvidencia){?>
	                    <th>Data Providencia</th>
	                    <th>Providencia</th>
	                    <th>Classificação</th>
	                <?php } 
	                 if (in_array($login_fabrica, [169,170])) { ?>
	                 	<th>Providência nv. 3</th>
	                 	<th>Motivo Contato</th>
	                 <?php
	             	 }
	                 ?>
                    <th>Atendente</th>
                </tr>
            </thead>
            <body>       
            	<?php for($i=0;$i<pg_num_rows($res); $i++){ 
            		$hd_chamado = pg_fetch_result($res, $i, hd_chamado);
            		$data = pg_fetch_result($res, $i, data);
            		$atendente = pg_fetch_result($res, $i, atendente);
					
					if($moduloProvidencia){
	            		$data_providencia = pg_fetch_result($res, $i, data_providencia);
	            		$providencia = pg_fetch_result($res, $i, providencia);
	            		$classificacao = pg_fetch_result($res, $i, classificacao);
	            	}

	            	if (in_array($login_fabrica, [169,170])) {
	            		$providencia_descricao = pg_fetch_result($res, $i, descricao_providencia);
	            		$motivo_contato 	   = pg_fetch_result($res, $i, motivo_contato_descricao);
	            	}

            	?>               
                <tr>
                    <td class="tac"><?=$hd_chamado?></td>
                    <td class="tac"><?=$data?></td>
                    <?php if($moduloProvidencia){ ?>
	                    <td class="tac"><?=$data_providencia?></td>
	                    <td class="tac"><?=$providencia?></td>
	                    <td class="tac"><?=$classificacao?></td>
                    <?php }

                    if (in_array($login_fabrica, [169,170])) { ?>
                    	<td class="tac"><?=$providencia_descricao?></td>
                    	<td class="tac"><?=$motivo_contato?></td>
                    <?php
	            	}
                     ?>
                    <td class="tac"><?=$atendente?></td>
                </tr>
                <?php } ?>
            </body>
        </table>
		</div>
        <?php }else { ?>
        <div class="row-fluid">
            <div class="col-md-12">
                <div class="alert">
				    <h4>Nenhum resultado encontrado</h4>
				</div>
            </div>
        </div>
        <?php  } } ?>
	</div>
<?php //endif; ?>
</body>
</html>

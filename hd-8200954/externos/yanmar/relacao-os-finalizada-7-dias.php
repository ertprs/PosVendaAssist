<?php

header('Content-Type: text/html; charset=iso-8859');

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';

$login_fabrica = 148;

if (!function_exists('anti_injection')) {
    function anti_injection($string) {
        $a_limpa = array("'" => "", "%" => "", '"' => "", "\\" => "");
        return strtr(strip_tags(trim($string)), $a_limpa);
    }
}

if(!function_exists('autentica_admin')){

	function autentica_admin($id_admin){

		global $con, $login_fabrica;

		$sql = "SELECT email FROM tbl_admin WHERE fabrica = $login_fabrica AND ativo IS TRUE AND admin = {$id_admin}";
		$qry = pg_query($con, $sql);

		if(pg_num_rows($qry) > 0){
				
			$email = pg_fetch_result($qry, 0, 'email');

			if($email != 'rodrigo_marques@yanmar.com'){
				return false;
			}

			return true;

		}else{
			return false;
		}
	}
}

if(!function_exists('busca_os')){

	function busca_os($data_inicial, $data_final){

		global $con, $login_fabrica;
		
		$sql = " 
		SELECT  tbl_os.os,
				tbl_os.sua_os,
				tbl_os.serie,
				to_char(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
				to_char(tbl_os.finalizada,'DD/MM/YYYY') AS data_fechamento,
				tbl_posto_fabrica.codigo_posto,
				tbl_posto.nome as nome_posto,
				tbl_posto.posto
				FROM tbl_os
				JOIN tbl_posto_fabrica USING(posto,fabrica)
				JOIN tbl_posto ON tbl_posto_fabrica.posto = tbl_posto.posto
				JOIN tbl_tipo_atendimento ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento AND 
											 tbl_tipo_atendimento.fabrica = $login_fabrica
				WHERE tbl_os.fabrica = $login_fabrica
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_os.finalizada IS NOT NULL
				AND tbl_os.finalizada::date BETWEEN '$data_inicial' and '$data_final'
				AND (upper(tbl_tipo_atendimento.descricao) = 'GARANTIA' OR upper(tbl_tipo_atendimento.descricao) LIKE 'PMP%')";

		$qry = pg_query($con, $sql);
		return pg_fetch_all($qry);
	}
}

if(!function_exists('grava_avaliacao')){

	function grava_avaliacao($data){

		global $con, $login_fabrica;

		$os = $data['os'];
		$posto = $data['posto'];
		$avaliacao = $data['avaliacao'];
		$admin = $data['admin'];

		$sql = "INSERT INTO tbl_os_interacao(os,admin,fabrica,posto,comentario,interno,programa) 
			    VALUES('$os', $admin, $login_fabrica,$posto,'$avaliacao',true,'externos/yanmar/relacao-os-finalizada-7-dias.php')";

	    return pg_query($con, $sql);
	}
}

if(isset($_GET['admin'])){

	$id_admin = base64_decode($_GET['admin']);
	$id_admin = anti_injection($id_admin);

	if(!autentica_admin($id_admin)){
		exit;
	}

	$data_inicial = anti_injection($_GET["data_inicio"]);
	$data_final = anti_injection($_GET["data_fim"]);

	if(!empty($data_inicial) && !empty($data_final)){
		$res_os = busca_os($data_inicial, $data_final);
	}
}

if(isset($_POST['gravar_avaliacao'])){

	if(!autentica_admin($id_admin)){
		exit;
	}

	$data = $_POST['data'];
	$data['admin'] = $id_admin;

	$insert = grava_avaliacao($data);

    if(pg_last_error($con)){
    	exit(json_encode(['erro' => true, 'msg' => pg_last_error($con)]));
    }

	exit(json_encode(['erro' => false]));
}

?>

<!doctype html>
<html lang="pt-br">
	<head>
    	<meta charset="utf-8">
    	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
		<link rel="stylesheet" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css">
		<link rel="stylesheet" href="css/relacao-os-finalizada.css">
		<link href="../../imagens/tc_2009.ico" rel="shortcut icon">
		<script src="../js/jquery.min.js"></script>
		<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    	<title>Relação de OS's Fechadas</title>
  	</head>
	<body>
		<div class="container-fluid">
			<div class="row">  	
				<nav class="navbar">
					<a href="https://posvenda.telecontrol.com.br">
						<div class="column-img">
			    			<img src="image/logo_telecontrol.png">
			  			</div>
			  		</a>
				</nav>
			</div>
		</div>
		<div class="container-fluid container-table">
			<div class="row row-header">
		  		<div class="column-img center">
			    	<img src="image/logo_yanmar.jpg">
			  	</div>
			  	<div class="column-title center">
			  		<h4>Relação de OS's Finalizadas Aguardando Avaliação Final da Gerência</h4>
			  	</div>
			</div>

			<? if($res_os) : ?>
			<div class="row">
				<table class="table table-striped table-responsive" id="tbl_os">
			  		<thead>
					    <tr>
				      		<th scope="col">OS</th>
				      		<th scope="col">Série</th>
			     		 	<th scope="col">Abertura</th>
				      		<th scope="col">Fechamento</th>
	   						<th scope="col">Posto</th>
	   						<th scope="col">Avaliação Gerente</th>
	   						<th scope="col">Ações</th>
					    </tr>
				  	</thead>
				  	<tbody>  
		    		<? foreach($res_os as $os) : ?> 
		      			<tr data-os='<?=json_encode($os) ?>'>
					      	<td><a target='_blank' href="https://posvenda.telecontrol.com.br/assist/admin/os_press.php?os=<?=$os['os']?>"><?=$os['os']?></a></td>
					      	<td><?=$os['serie']?></td>
					      	<td><?=$os['data_abertura']?></td>
					      	<td><?=$os['data_fechamento']?></td>
					      	<td><?=$os['nome_posto']?></td>
							<td>
								<input type="text" name="avaliacao" placeholder="Avaliação..." class="form-control"/>
							</td>
							<td>
								<button class="btn btn-primary btn-gravar-avaliacao">Gravar</button>
							</td>
				      	</tr>
			      	<? endforeach; ?>
			  		</tbody>
				</table>
			</div>
			<? else : ?>
				<div class="container-fluid">
					<div class="row" style="border-top: 1px solid #10101040;">
						<div class="alert alert-warning center">Nenhum registro encontrado.</div>
					</div>
				</div>
				
			<? endif; ?>
		</div>
	</body>
</html>

<script type="text/javascript">

function getDadosOs(element){

	var tr = $(element).parent().parent();
	var os = tr.data('os');

	os.avaliacao = tr.find('input[name=avaliacao]').val();

	return os;
}

function gravaAvaliacao(os){

	$.ajax({
		url: window.location,
		type: "POST",
		dataType: 'json',
		data: { gravar_avaliacao : true, data : os},
		success : function(response){

			if(response.erro){
				alert("Ocorreu um erro durante a gravação.")	
			}else{
				alert("Avaliação gravada com sucesso!");
			}
		},
	  	error: function (xhr, ajaxOptions, thrownError) {
	        console.log(xhr.status);
	        console.log(thrownError);
      	}
	});	
}

$(document).ready(function() {

	$(".btn-gravar-avaliacao").click(function(){

		var os = getDadosOs(this);

		if(os.avaliacao != ''){
			gravaAvaliacao(os);
		}else{
			alert("Avaliação não informada, preencha o campo e tente novamente");
		}
	});

    $("#tbl_os").DataTable({
  		ordering: false,
  		"language": {
	    "lengthMenu": "_MENU_ Registros por página",
	    "search": "Procurar:",
      	"info": "Mostrando _START_ de _END_ de _TOTAL_ registros",
     	"paginate": {
	      	"next": "Próxima",
	      	"previous" : "Anterior"
	    }}
	});

});

</script>

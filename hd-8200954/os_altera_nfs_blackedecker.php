<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$amb = $_REQUEST['amb'] ;

if($amb == 'admin'){
	include 'autentica_admin.php';
}else{
	include 'autentica_usuario.php';
}



if($_GET['ajax'] == "sim"){
	$os = $_GET['os'];
	$nota_fiscal = $_GET['nota_fiscal'];
	$data_nf = $_GET['datanf'];	
	$data_nf = str_replace("/", "-", $data_nf);	
	$revenda = $_GET['revenda'];
	
	if(strlen($login_fabrica) > 0){
		$where = "o.fabrica = $login_fabrica and";
	}else{
		$where = "";
	}
	

	$sql = "select o.os, o.nota_fiscal, o.data_nf, o.revenda,p.descricao from tbl_os o
		join tbl_produto p on o.produto = p.produto
		where  ".$where." revenda = $revenda and nota_fiscal = '$nota_fiscal' and os != '".$os."' and data_nf between 
		timestamp '".date('Y-m-d',strtotime($data_nf))." 00:00:00' - interval'90 days' and '".date('Y-m-d 23:59:59',strtotime($data_nf))."' and finalizada is null";

	$res = pg_query($con,$sql);
	if(pg_num_rows($res)>0){
		for($i=0;$i<pg_num_rows($res);$i++){
			$produto_2 = pg_fetch_result($res, $i, descricao);
			$nota_fiscal_2 = pg_fetch_result($res, $i, nota_fiscal);
			$data_nf_2 = date('d/m/Y',strtotime(pg_fetch_result($res, $i, data_nf)));
			$os_2 = pg_fetch_result($res, $i, os);

			if($i%2==0){
				$classelinha = "tr-linha";
			}else{
				$classelinha = "tr-linha-off";
			}

			$tabela .= "<tr class='$classelinha'><td class='td-num-os'><a style='color:#333' href='os_press.php?os=$os_2' target='_blanck'>$os_2</a></td><td class='td-nf'>$nota_fiscal_2</td><td class='td-data'>$data_nf_2</td><td class='td-produto'>$produto_2</td><td class='td-os'><input type='checkbox' class='inp-ck-atualiza' name='ck$i' value='$os_2' /></td></tr>";				
		}
	}else{
		$tabela = "";
	}

	echo $tabela;


	exit;
}


if(count($_POST) > 0){
	$os = $_POST['inp-os'];
	$nota_fiscal = $_POST['inp-nf'];
	$data_nf = $_POST['inp-datanf'];


	$os_atualizar = $_POST['inp-os-atualizar'];

	$array_os_post = get_object_vars(json_decode($os_atualizar));			


	$ind = 0;
	$chaves = array_keys($array_os_post);	
	
	for($i=0;$i<count($chaves);$i++){
		if(substr(trim($chaves[$i]),0,2) == 'ck'){
			$array_os[$ind] = $array_os_post[$chaves[$i]];
			$ind += 1;
		}
	}	

	$sql = "select nota_fiscal, data_nf from tbl_os where os = ".$os;
	$res = pg_query($con,$sql);
	if(pg_num_rows($res)>0){

		$nota_fiscal_antiga = pg_fetch_result($res, 0, nota_fiscal);
		$data_nf_antiga = pg_fetch_result($res, 0, data_nf);

		$obs = 'Dados atualizados pelo admin, data da NF anterior = '.$data_nf_antiga.', Nota Fiscal anterior = '.$nota_fiscal_antiga.'.';

		$sql = "update tbl_os_extra set obs = '$obs' where os = $os";
		$res = pg_query($sql);

		$erro = pg_errormessage($con);
		if(strlen($erro) == 0){
			$sql = "update tbl_os set data_nf = '".$data_nf."', nota_fiscal = '".$nota_fiscal."' where os = $os";			
			$res = pg_query($sql);
			$erro = pg_errormessage($con);
			if(strlen($erro) == 0){
				for($i=0;$i<count($array_os);$i++){					
					$num_os_atual = $array_os[$i];

					$sql = "select nota_fiscal, data_nf from tbl_os where os = ".$os;					
					$res = pg_query($con,$sql);
					if(pg_num_rows($res)>0){
						$nota_fiscal_antiga = pg_fetch_result($res, 0, nota_fiscal);
						$data_nf_antiga = pg_fetch_result($res, 0, data_nf);					
						$obs = 'Dados atualizados pelo admin, data da NF anterior = '.$data_nf_antiga.', Nota Fiscal anterior = '.$nota_fiscal_antiga.'.';

						$sql = "update tbl_os_extra set obs = '$obs' where os = $num_os_atual";						
						$res = pg_query($sql);					
						$erro = pg_errormessage($con);
						if(strlen($erro) == 0){
							$sql = "update tbl_os set data_nf = '".$data_nf."', nota_fiscal = '".$nota_fiscal."' where os = $num_os_atual";														
							$res = pg_query($sql);					
							$erro = pg_errormessage($con);
							if(strlen($erro) > 0){
								echo $erro;
							}
						}else{
							echo $erro;
						}						
					}
				}	
			}else{
				echo $erro;
			}			
		}else{
			echo $erro;
		}
	}else{

	}

	//print_r($array_os);
	echo "<script>	
	parent.Shadowbox.close();
	</script>";	
	exit;
}



$os = $_GET['os'];
if(strlen(trim($os))>0){

	$sql = "select os, nota_fiscal, data_nf, revenda from tbl_os where os = '".$os."'";	
	$res = pg_query($con,$sql);
	if(pg_num_rows($res)>0){
		$result = pg_fetch_row($res);	
		$os = $result[0];
		$nota_fiscal = $result[1];		
		$data_nf_input = date('d/m/Y',strtotime($result[2]));
		
		$data_nf_timestamp = $result[2];
		$revenda = $result[3];
		if(empty($revenda)) $revenda = "null";

		if(strlen($login_fabrica)>0){
			$where = "and p.fabrica_i = $login_fabrica";
			$where2 = "o.fabrica = $login_fabrica and";
		}else{
			$where = "";
			$where2 = "";
		}

		$sql = "select o.os, o.nota_fiscal, o.data_nf, o.revenda,p.descricao from tbl_os o
				join tbl_produto p on o.produto = p.produto ".$where."
				where ".$where2." revenda = $revenda and nota_fiscal = '".$nota_fiscal."' and os != '".$os."' and data_nf between 
				timestamp '$data_nf_timestamp 00:00:00' - interval'90 days' and '$data_nf_timestamp 23:59:59' and finalizada is null";
	    
		

		$res = pg_query($con,$sql);
		if(pg_num_rows($res)>0){
			for($i=0;$i<pg_num_rows($res);$i++){
				$produto_2 = pg_fetch_result($res, $i, descricao);
				$nota_fiscal_2 = pg_fetch_result($res, $i, nota_fiscal);
				$data_nf_2 = date('d/m/Y',strtotime(pg_fetch_result($res, $i, data_nf)));
				$os_2 = pg_fetch_result($res, $i, os);

				if($i%2==0){
					$classelinha = "tr-linha";
				}else{
					$classelinha = "tr-linha-off";
				}

				$tabela .= "<tr class='$classelinha'><td class='td-num-os'><a style='color:#333' href='os_press.php?os=$os_2' target='_blank'>$os_2</a></td><td class='td-nf'>$nota_fiscal_2</td><td class='td-data'>$data_nf_2</td><td class='td-produto'>$produto_2</td><td class='td-os'><input type='checkbox' class='inp-ck-atualiza' name='ck$i' value='$os_2' /></td></tr>";				
			}
		}else{
			$tabela = "";
		}
	}else{
		$os = "";
		$nota_fiscal = "";
		$data_nf = "";
	}	
}
?>

<html>
	<head>
		<meta http-equiv="content-Type"  content="text/html; charset=iso-8859-1">
		<meta http-equiv="Expires"       content="0">
		<meta http-equiv="Pragma"        content="no-cache, public">
		<meta http-equiv="Cache-control" content="no-cache, public, must-revalidate, post-check=0, pre-check=0">

		<style type='text/css'>
			#env-geral{
				float: left;
				width: 785px;	
				height: 500px;		
			}

			#env-geral #env-form{		
				width: 700px;
				height: 130px;
				margin: 5px 0 0 50px;
				background: #e2e2e2;
				border-radius: 10px;
			}

			#env-form .div-titulo-form{
				width: 100%;
				height: 35px;
				margin: 5px 0 0 0;
				background: #ced3f0;
				border-radius: 10px 10px 0 0;
			}

			#env-form .div-titulo-form p{
				float: left;
				width: 100%;
				height: 35px;
				margin: 5px 0 0 10px;
				font-family: Arial;
				font-size: 18px;
				color: #333;		
				text-align: center;
			}

			#env-form .div-input-text-medio{
				float: left;		
				margin: 0 0 0 100px;
			}

			#env-form .div-input-text-medio span{
				float: left;
				width: 240px;
				height: 35px;
				margin: 10px 0 0 10px;
				font-family: Arial;
				font-size: 15px;
				color: #333;
				text-align: center;
			}
			#env-form .div-input-text-medio input{
				float: left;
				width: 150px;
				height: 30px;
				font-family: Arial;
				font-size: 15px;
				color:#333;
				text-align: center;
			}

			#env-geral #env-tabela{
				float: left;
				width: 700px;
				min-height: 280px;
				margin: 5px 0 0 50px;
				background: #DDE3F7;

			}

			#env-tabela #tr-titulo{
				float: left;
				width: 698px;
				height: 40px;		
				background: #ced3f0;
				border-bottom: 1px solid #a1a4b4;
			}

			#tr-titulo #inp-check-all{
				float: left;
				margin: 0 0 0 29px;
			}

			#env-tabela tr th{
				float: left;
				font-family: Arial;
				font-size: 14px;
				font-weight: bold;
				color: #333;
				margin: 5px 0 0 0;
				text-align: center;		
			}

			#env-tabela #th-os{
				width: 100px;
			}

			#env-tabela #th-nf{
				width: 100px;
			}

			#env-tabela #th-data{
				width: 100px;
			}

			#env-tabela #th-produto{
				width: 320px;
			}

			#env-tabela #th-atualizar {
				width: 50px;
			}

			#env-tabela .tr-linha{
				float: left;
				width: 698px;
			}

			#env-tabela .tr-linha-off{
				float: left;
				width: 698px;	
				background: #fff;
			}

			#env-tabela td{
				text-align: center;
				font-family: Arial;
				font-size: 13px;
			}

			#env-tabela .td-num-os{
				width: 100px;
			}

			#env-tabela .td-nf{
				width: 100px;
			}

			#env-tabela .td-data{
				width: 100px;
			}

			#env-tabela .td-produto{
				width: 320px;
			}

			#env-tabela .td-os {
				width: 50px;
			}

			#env-controles{		
				float: left;
				width: 698px;
				height: 35px;
				margin: 0 0 0 50px;
				padding: 10px 0 0 0;
				background: #cdd5f0;
			}

			#env-controles .div-input-button{
				width: 250px;
				margin: 0 auto;
			}

			#env-controles .bt-padrao{
				min-width: 80px;
				height: 25px;		
				font-size: 16px;
				border-radius: 5px;
				cursor: pointer;
			}

			#env-controles .bt-submit{
				border: 1px solid #2c9a26;
				background:  #03a316;		
				color: #fff;
				margin: 0 0 0 60px;		
			}

			#env-controles .bt-submit:hover{
				background:  #79de79;		
				color: #333;	
			}

			#env-controles .bt-submit:active{
				background:  #167816;		
				color: #fff;		
			}

			#env-controles .bt-cancel{
				border: 1px solid #333;
				background:  #e2e2e2;		
				color: #333;	
			}

			#env-controles .bt-cancel:hover{
				background:  #a7a7a7;		
				color: #333;		
			}

			#env-controles .bt-cancel:active{
				background:  #333;		
				color: #fff;			
			}

		</style>
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
		<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick.js"></script>
		<script type="text/javascript" src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
		<link type="text/css" href="plugins/jquery/datepick/telecontrol.datepick.css" rel="stylesheet" />
		<script type="text/javascript" src="js/jquery.maskedinput.js"></script>

		<script type="text/javascript">		
		var dataAnterior;
		var os = <?php echo $os ?>;
		var notafiscal = '<?php echo $nota_fiscal ?>';		
		var revenda = <?php echo $revenda ?>;
		var jsonOsChecked = {};

		$(window).load(function(){			
			$('#data_inicial').datepick({startDate:'01/01/2000',onClose: atualizaLista});
			
			$("#data_inicial").maskedinput("99/99/9999");
		});

		function salvaResultado(){
			dataAnterior = $("#data_inicial").val();			
		}

		function atualizaLista(){			
			$(".tr-linha").remove();
			$(".tr-linha-off").remove();			
					
			res = $.ajax("os_altera_nfs_blackedecker.php",{
				data: 'ajax=sim&amb=admin&datanf='+$("#data_inicial").val()+'&os='+os+'&nota_fiscal='+notafiscal+"&revenda="+revenda,
				complete: retornoAjax});			
		}


		function preSubmit(){
			
			if($("#inp-nf").val() == ''){				
				$("#inp-nf").focus();
				return false;
			}
			if($("#data_inicial").val() == ''){
				$("#data_inicial").focus();
				return false;
			}

			$(".inp-ck-atualiza").each(function(){
				if($(this).is(":checked")){
					var rel = $(this).val().toString();					
               		jsonOsChecked[$(this).attr('name')] = $(this).val();    
				}
			});

			$('#inp-os-atualizar').val(JSON.stringify(jsonOsChecked));
			$('#frm-atualiza').submit();

		}

		function retornoAjax(ret){						
			var html = $("#env-tabela table").html();			
			$("#env-tabela table").html(html+ret.responseText);				
		}

		function closeFrame(){
			parent.Shadowbox.close();			
		}

		function checkAll(){			
			if($("#inp-check-all").prop('checked')){ 
				$(".inp-ck-atualiza").prop('checked',true);
			}else{
				$(".inp-ck-atualiza").prop('checked',false);
			}
		}		
		</script>

	</head>

	

	<body>
		<div id='env-geral'>
			<div id='env-form'>

				<form action="#" id="frm-atualiza" name="frm-atualiza" method="post">

				<div class='div-titulo-form'>
					<p>Dados para atualizar</p>
				</div>
				<div class='div-input-text-medio'>
					<span>Nota Fiscal</span>
					<input type='text'  id='inp-nf' name='inp-nf' value='<?php echo $nota_fiscal ?>' />
					<input type='hidden' name='inp-os' value='<?php echo $os ?>' />
					<input type='hidden' name='amb' value='<?php echo $amb ?>' />
					<input type='hidden' id='inp-os-atualizar' name='inp-os-atualizar' value='' />
				</div>
				<div class='div-input-text-medio'>
					<span>Data da NF</span>
					<input type='text' id='data_inicial' name='inp-datanf' value='<?php echo $data_nf_input ?>'  size='13' onClick="salvaResultado()" />					
				</div>
			</div>				
			<div id='env-tabela'>
				<table>
					<tr id='tr-titulo'>
						<th id='th-os'>OS</th><th id='th-nf'>NF</th><th id='th-data'>Data NF</th><th id='th-produto'>Produto</th><th id='th-atualizar'>Atualizar <input id='inp-check-all' value='ON' onClick='checkAll()' type='checkbox'name='inp-checkall' /></th>
					</tr>
					<?php
						echo $tabela;
					?>
				</table>
			</div>
			<div id='env-controles'>
				<div class='div-input-button'>
					<input type='button' onClick='preSubmit()' class='bt-padrao bt-submit' name='bt-submit' value='Salvar' />
					<input type='button' onClick='closeFrame()' class='bt-padrao bt-cancel' name='bt-cancel' value='Cancelar' />
				</div>

				</form>

			</div>
		</div>
	</body>

</html>

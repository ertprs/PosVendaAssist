<?php

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_usuario.php';
include 'funcoes.php';

$plugins = array(
    "bootstrap3"
);
include("plugin_loader.php");	

?>

<div class="container-fluid">
	<div class="row">
	<hr>
	<h4 style="text-align: center;">Informações gerais</h4>
	<hr>
	<div class="col-sm-6">
	    <label>
	    	<strong>Defeito reclamado</strong>
	    </label>
		<input type="text" class="form-control" value="Produto nao funcionou corretamente e por isso não passou nos testes" disabled>
	</div>
	<div class="col-sm-2">
	    <label>
	    	<strong>Horimetro</strong>
	    </label>
		<input type="text" value="30" disabled class="form-control">
	</div>
	<div class="col-sm-2">
	    <label>
	    	<strong>Pedágio</strong>
	    </label>
		<input type="text" value="30" disabled class="form-control">
	</div>
	<div class="col-sm-2">
	    <label>
	    	<strong>Alimentação</strong>
	    </label>
		<input type="text" value="15" disabled class="form-control">
	</div>
	<hr>
</div>
<div class="row" style="margin-top: 15px">
	<hr>
	<h4 style="text-align: center;">Produto</h4>
	<hr>
	<div style="margin: 0 auto;">
		<div class="col-sm-4">
		    <label>
		    	<strong>Solução</strong>
		    </label>
			<p>Revisão OK</p>
		</div>
		<div class="col-sm-4">
		    <label>
		    	<strong>Defeito constatado</strong>
		    </label>
			<p>NENHUM</p>
		</div>
		<div class="col-sm-4">
		    <label>
		    	<strong>Solução</strong>
		    </label>
			<p>ANÁLISE/ INSPEÇÃO INTERNA MOTOR (YT)</p>
		</div>
	</div>
</div>
<div class="row" style="margin-top: 15px">
	<hr>
	<h4 style="text-align: center;">
		Lista básica 
		<button class="btn btn-success btn-sm"> 
			<i class="glyphicon glyphicon-plus"></i>
		</button>
	</h4>
	<hr>
	<div class="col-sm-3">
		<label>
		    <strong>Referência</strong>
		</label>
		<p>10004212AA-P</p>
		<label>
		    <strong>Descricao</strong>
		</label>
		<p>ABRACADEIRA. EM ACO TESTE PARA NOVO CONTADO DE COMO ISSO ESTÁ QUEBRANDO</p>
		<label>
		    <strong>Serviço realizado</strong>
		</label>
		<select name="" id="" class="form-control">
			<option value="">Troca de Peça(Usando Estoque)</option>
		</select>
		<label>
		    <strong>Quantidade</strong>
		</label>
		<input type="text" class="form-control">
		<button style="float: right; margin-top: 5px" class="btn btn-danger btn-sm">
			<i class="glyphicon glyphicon-minus"></i>
		</button>
	</div>
	<div class="col-sm-3">
		<label>
		    <strong>Referência</strong>
		</label>
		<p>10004212AA-P</p>
		<label>
		    <strong>Descricao</strong>
		</label>
		<p>ABRACADEIRA. EM ACO TESTE PARA NOVO CONTADO DE COMO ISSO ESTÁ QUEBRANDO</p>
		<label>
		    <strong>Serviço realizado</strong>
		</label>
		<select name="" id="" class="form-control">
			<option value="">Troca de Peça(Usando Estoque)</option>
		</select>
		<label>
		    <strong>Quantidade</strong>
		</label>
		<input type="text" class="form-control">
		<button style="float: right; margin-top: 5px" class="btn btn-danger btn-sm">
			<i class="glyphicon glyphicon-minus"></i>
		</button>
	</div>
	<div class="col-sm-3">
		<label>
		    <strong>Referência</strong>
		</label>
		<p>10004212AA-P</p>
		<label>
		    <strong>Descricao</strong>
		</label>
		<p>ABRACADEIRA. EM ACO TESTE PARA NOVO CONTADO DE COMO ISSO ESTÁ QUEBRANDO</p>
		<label>
		    <strong>Serviço realizado</strong>
		</label>
		<select name="" id="" class="form-control">
			<option value="">Troca de Peça(Usando Estoque)</option>
		</select>
		<label>
		    <strong>Quantidade</strong>
		</label>
		<input type="text" class="form-control">
		<button style="float: right; margin-top: 5px" class="btn btn-danger btn-sm">
			<i class="glyphicon glyphicon-minus"></i>
		</button>
	</div>
	<div class="col-sm-3">
		<label>
		    <strong>Referência</strong>
		</label>
		<p>10004212AA-P</p>
		<label>
		    <strong>Descricao</strong>
		</label>
		<p>ABRACADEIRA. EM ACO TESTE PARA NOVO CONTADO DE COMO ISSO ESTÁ QUEBRANDO</p>
		<label>
		    <strong>Serviço realizado</strong>
		</label>
		<select name="" id="" class="form-control">
			<option value="">Troca de Peça(Usando Estoque)</option>
		</select>
		<label>
		    <strong>Quantidade</strong>
		</label>
		<input type="text" class="form-control">
		<button style="float: right; margin-top: 5px" class="btn btn-danger btn-sm">
			<i class="glyphicon glyphicon-minus"></i>
		</button>
	</div>
</div>

<div class="row" style="margin-top: 15px">
	<hr>
	<h4 style="text-align: center;">
		Observação
	</h4>
	<hr>
	<div class="col-sm-12">
		<textarea name="" id="" cols="30" rows="5" class="form-control"></textarea>
	</div>
</div>

<div class="row">
	<hr>
	<h4 style="text-align: center;">
		Anexos
	</h4>
	<hr>
	<div class="col-sm-4">
		<img class="img-responsive" src="http://api2.telecontrol.com.br/tdocs/document/id/cc9eb83d838a6325044424502636f32a8beaa64b46f561b1b834978a88555c55">
	</div>
	<div class="col-sm-4">
		<img class="img-responsive" src="http://api2.telecontrol.com.br/tdocs/document/id/a97de7f390f76f279363a084197fbd118dae74df10e3cf437258d99f8779abf2" >
	</div>
	<div class="col-sm-4">
		<img class="img-responsive" src="http://api2.telecontrol.com.br/tdocs/document/id/365ce31023de4e463794dc35de290de66af5f3ed941147429b51ecc8058c168a" >
	</div>
</div>

<div class="row" style="margin-top: 15px">
	<hr>
	<h4 style="text-align: center;">
		Assinaturas
	</h4>
	<hr>
	<div class="col-sm-6">
		<div style="text-align: center;">
			<img style="width: 50%" src="http://api2.telecontrol.com.br/tdocs/document/id/3c3d9112d297a75dbabefd29c91a8edd87cacdc11b7c1133eb532e778d94e6de" alt="">
			<p>Assinatura</p>
		</div>
	</div>
	<div class="col-sm-6">
		<div style="text-align: center;">
			<img style="width: 50%" src="http://api2.telecontrol.com.br/tdocs/document/id/444f45894c4247ed138e33da4d0d0784c8d942fcc5157186c7d72c25eb44bc54" alt="">
			<p>Assinatura técnico </p>
		</div>
	</div>

</div>

</div>
<br><br><br><br><br>

<button onclick="teste()"> TESTE </button>

<script>

	function teste(){
		var atual = window.parent.Shadowbox.getCurrent();
		console.log(atual);

		window.parent.Shadowbox.open({
			content :   window.location.href,
			player  :   "iframe",
			width   :   1500,
			height  :   600
		});

	}

</script>
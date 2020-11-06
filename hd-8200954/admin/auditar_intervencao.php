<head>
	<title>Auditoria</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@9"></script>

<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
<link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>
<script type="text/javascript" src="plugins/shadowbox/shadowbox.js"></script>
<link type="text/css" href="plugins/shadowbox/shadowbox.css" rel="stylesheet" media="all">
</head>
<div class="container-fluid">
	<div class="row titulo_tabela" colspan="14" style="background-color: #596D9B; color : white;">
		<div class="col-1"></div>
		<div class="col">
			<center><h4>AUDITORIA DA FÁBRICA</h4></center>
		</div>
		<div class="col-1"></div>
	</div>
	<div class="row">
		<div class="col-1"></div>
		<div class="col">
			<p style="color: red; text-align: left;">
				Ao clicar na opção desejada, haverá a Aprovação ou Reprovação em Auditoria.
				Essa opção não poderá ser <strong>revertida</strong>.
			</p>
		</div>
		<div class="col-1"></div>
	</div>
	<div class="row">
		<?php
			include 'dbconfig.php';
			include 'includes/dbconnect-inc.php';
			$os = $_GET['os'];
			$auditoria = $_GET['auditoria'];
			$sql = "SELECT qtde_km from tbl_os 
							left join tbl_auditoria_os on tbl_auditoria_os.os = tbl_os.os 
							where tbl_os.os = $os AND tbl_auditoria_os.auditoria_os = $auditoria AND tbl_auditoria_os.auditoria_status = 2";
							
			$res = pg_query($con, $sql);
			if(pg_num_rows($res) > 0){
				$qtde_km = pg_fetch_result($res, 'qtde_km');
				echo "<div class='col row-fluid' style='margin: 0 auto'>
						<div class='span3'>
							<div class='control-group'>
								<label class='control-label' for='quantidade_de_km'>Quantidade de KM</label>
								<div class='controls controls-row'>
									<div class='span12'>
										<input id='quantidade_de_km' name='quantidade_de_km' class='span6' type='text' value='$qtde_km' style='margin-top: 10px;'>
										<button class='btn btn-primary alerarkm'>Alterar</button>
									</div>
								</div>
							</div>
						</div>
						<div class='span3' style='margin-top: 30px;'>
							<div class='control-group'>
								<div class='controls controls-row'>
									<div class='span12'>
										<button class='btn btn-primary pecas'>Valores das Peças</button>
									</div>
								</div>
								
							</div>
						</div>
					</div>";
			}else{
				echo "<div class='col row-fluid' style='margin: 0 auto'>
						<div class='span3'>
							<div class='control-group'>
								<div class='controls controls-row'>
									<div class='span12'>
										<button class='btn btn-primary pecas'>Valores das Peças</button>
									</div>
								</div>
								
							</div>
						</div>
					</div>";
			}

				


		?>
	</div>
	<div class="row">
		<div class="col btn_auditar_aprovar" data-auditoria="procedente">
			<button class="btn btn-success">Procedente</button>
		</div>
		<div class="col btn_auditar_aprovar" data-auditoria="excepcional">
			<button class="btn btn-success">Excepcional</button>
		</div>
		<div class="col btn_auditar_reprovar" data-auditoria="nao_procedente">
			<button class="btn btn-danger">Não Procedente</button>
		</div>
		<div class="col btn_auditar_reprovar" data-auditoria="falta_informacao">
			<button class="btn btn-danger">Falta Informação</button>
		</div>
	</div>
</div>

<script type="text/javascript">

	$(function () {
		$(document).on("click", ".btn_auditar_reprovar", function () {
		
			var tipo_auditoria = $(this).data('auditoria');
			var os = <?=$_GET['os']?>;
			var auditoria_id = <?=$_GET['auditoria']?>;
			var justificativa = "Reprovado em Auditoria";
			
			Swal.fire({
			  title: 'Tem certeza?',
			  text: "Não será possível reverter essa alteração!",
			  icon: 'warning',
			  showCancelButton: true,
			  confirmButtonColor: '#3085d6',
			  cancelButtonColor: '#d33',
			  cancelButtonText: 'Cancelar',
			  confirmButtonText: 'Sim, reprovar OS!'
			}).then((result) => {

				if (result.value == true) {

					$.ajax({
						type: 'POST',
						url : "relatorio_auditoria_status.php",
						data: { auditar : true, 
							    btn_acao: 'reprovaOS',
							    auditar : true,
							    tipo_auditoria : tipo_auditoria, 
							    auditoria_os : auditoria_id,
							    justificativa : justificativa,
							    os : os },
		            	dataType: "json",
					}).done(function(response) {

		        		if (response['resultado'] == true) { 

						 	Swal.fire({
							  icon: 'success',
							  title: 'Reprovação de Auditoria salva com sucesso',
							  confirmButtonText: 'Ok'
							});

							window.top.location.href = 'os_press.php?os=' + os;
							window.parent.Shadowbox.close();

						} else { 

						 	Swal.fire({
						 	  icon: 'error',
						 	  title: 'Ops!',
						 	  text: 'Ocorreu um erro ao auditar intervenção',
						 	  confirmButtonText: 'Ok'
						 	});	 
		        		}
		        	}).fail(function() {

		        		Swal.fire({
					 	  	icon: 'error',
					 	  	title: 'Ops!',
					 	  	text: 'Ocorreu um erro ao auditar intervenção',
					 	  	confirmButtonText: 'Ok'
					 	});	
					});
				}
			})
        });


		$(document).on("click", ".btn_auditar_aprovar", function () {

			var tipo_auditoria = $(this).data('auditoria');
			var os = <?=$_GET['os']?>;
			var auditoria_id = <?=$_GET['auditoria']?>;
			var justificativa = "Aprovado em Auditoria";

			Swal.fire({
			  title: 'Tem certeza?',
			  text: "Não será possível reverter essa alteração!",
			  icon: 'warning',
			  showCancelButton: true,
			  confirmButtonColor: '#3085d6',
			  cancelButtonColor: '#d33',
			  cancelButtonText: 'Cancelar',
			  confirmButtonText: 'Sim, aprovar OS!'
			}).then((result) => {


				if (result.value == true) {
				
					$.ajax({
						type: 'POST',
						url : "relatorio_auditoria_status.php",
						data: { auditar : true, 
							    btn_acao: 'aprovaOS', 
							    justificativa : justificativa,
							    tipo_auditoria : tipo_auditoria, 
							    auditoria_os : auditoria_id,
							    os : os },
		            	dataType: "json",
					}).done(function(response) {

		        		if (response['resultado'] == true) { 

						 	Swal.fire({
							  icon: 'success',
							  title: 'Auditoria salva com sucesso',
							  confirmButtonText: 'Ok'
							});
				
							window.top.location.href = 'os_press.php?os=' + os;
							window.parent.Shadowbox.close();

						} else { 

						 	Swal.fire({
						 	  icon: 'error',
						 	  title: 'Ops!',
						 	  text: 'Ocorreu um erro ao auditar intervenção',
						 	  confirmButtonText: 'Ok'
						 	});	 
		        		}
		        	}).fail(function() {

					 	Swal.fire({
					 	  icon: 'error',
					 	  title: 'Ops!',
					 	  text: 'Ocorreu um erro ao auditar intervenção',
					 	  confirmButtonText: 'Ok'
					 	});	 
					});

					//window.top.location.href = 'os_press.php?os=' + os;
					//window.parent.Shadowbox.close();
				}

	        });
		});
		
		$(".alerarkm").click(function () {

			var os = <?=$_GET['os']?>;
			var novo_km = $('#quantidade_de_km').val();

			Swal.fire({
				title: 'Tem certeza?',
				text: "Não será possível reverter essa alteração!",
				icon: 'warning',
				showCancelButton: true,
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				cancelButtonText: 'Cancelar',
				confirmButtonText: 'Sim, alterar KM!'
			}).then((result) => {

				if (result.value == true) {

					$.ajax({
						url: 'relatorio_auditoria_status.php',
						type: 'POST',
						data: {
							os: os,
							km: novo_km,
							btn_acao: "inserirKM"
						},
						dataType: "json",
					}).done(function(response) {

						if (response['resultado'] == true) { 

							Swal.fire({
								icon: 'success',
								title: 'Km alterado com sucesso',
								confirmButtonText: 'Ok'
							});

							window.top.location.href = 'os_press.php?os=' + os;
							window.parent.Shadowbox.close();

						} else { 

							Swal.fire({
								icon: 'error',
								title: 'Ops!',
								text: 'Ocorreu um erro ao alterar o km da os',
								confirmButtonText: 'Ok'
							});	 
						}
					}).fail(function() {

						Swal.fire({
							icon: 'error',
							title: 'Ops!',
							text: 'Ocorreu um erro ao auditar intervenção',
							confirmButtonText: 'Ok'
						});	
					});
				}
			})
		});

		$(".pecas").click(function () {

			var os = <?=$_GET['os']?>;

			Shadowbox.init();

			Shadowbox.open({
				content: "editar_valor_pecas.php?os=<?=$os?>",
				player:  "iframe",
				width:  750,
				height: 300
			});
		});
	});

</script>
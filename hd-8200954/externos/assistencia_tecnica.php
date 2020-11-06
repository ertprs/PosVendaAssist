<?php

	include '../admin/dbconfig.php';
	include '../admin/includes/dbconnect-inc.php';
	include '../admin/funcoes.php';
	include '../fn_traducao.php';

	$token = trim($_GET['tk']);
	$token_post = $_POST['token'];
	$cod_fabrica = $_GET['cf'];
	$cod_fabrica = base64_decode(trim($cod_fabrica));

	$nome_fabrica = $_GET['nf'];
	$nome_fabrica = base64_decode(trim($nome_fabrica));

	if ($cod_fabrica == 180) {
		$xxpais = "AR";	//argentina
		$img_mapa = "mapa_argentina.png";
	} elseif ($cod_fabrica == 181) {
		$xxpais = "CO";	//colombia
		$img_mapa = "mapa_colombia.jpg";
	} elseif ($cod_fabrica == 182) {
		$xxpais = "PE";	//peru
		$img_mapa = "mapa_peru.jpg";
	}

	if(!empty($_POST['fabrica'])){
		$sql = "SELECT nome FROM tbl_fabrica WHERE fabrica = ". $_POST['fabrica'];
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0) {
			$cod_fabrica = $_POST['fabrica'];
			$nome_fabrica = pg_fetch_result($res,0,0);
		}
	}

	$token_comp = base64_encode(trim("telecontrolNetworking".$nome_fabrica."assistenciaTecnica".$cod_fabrica));

	if (!empty($token_post)) $token = $token_post;

	// if($token != $token_comp ){
	// 	exit;
	// }

	function maskCep($cep){
		$inicio 	= substr($cep, 0, 2);
		$meio 		= substr($cep, 2, 3);
		$fim 		= substr($cep, 5, strlen($cep));
		$cep 	= $inicio.".".$meio."-".$fim;
		return $cep;
	}

	function maskFone($telefone){
		if(!strstr($telefone, "(")){
			$telefone 	= str_replace("-", "", $telefone);
			$inicio 	= substr($telefone, 0, 2);
			$meio 		= substr($telefone, 2, 4);
			$fim 		= substr($telefone, 6, strlen($telefone));
			$telefone 	= "(".$inicio.") ".$meio."-".$fim;
		}

		return $telefone;
	}

	function retira_acentos($texto){
		$array1 = array( "á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç"
		, "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
		$array2 = array( "a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c"
		, "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
		return str_replace( $array1, $array2, $texto);
	}

	/* Busca Cidades */
	if(isset($_POST['uf']) && isset($_POST['linha'])){

		$uf 		= $_POST['uf'];
		$linha 		= $_POST['linha'];
		$fabrica 	= $_POST['fabrica'];
		$cond = "";

		if (in_array($cod_fabrica, [180,181,182])) {
			$cond = " AND tbl_posto_fabrica.contato_pais='{$xxpais}'";
		}
		$sql = "
		SELECT
			distinct upper(trim(fn_retira_especiais(tbl_posto_fabrica.contato_cidade))) as contato_cidade
		FROM
			tbl_posto_fabrica
		JOIN
			tbl_posto_linha ON tbl_posto_fabrica.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha = $linha
		where
			tbl_posto_fabrica.fabrica = $fabrica
			AND tbl_posto_fabrica.contato_estado = '$uf'
			AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			AND tbl_posto_fabrica.posto <>6359
			{$cond}
			AND tbl_posto_fabrica.divulgar_consumidor IS TRUE ORDER BY 1 ASC";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){
			echo "<option value=''></option>";
			while($data = pg_fetch_object($res)){
				echo "<option value='$data->contato_cidade'>".ucwords(strtolower(retira_acentos($data->contato_cidade)))."</option>";
			}
		}else{
			echo "<option value=''></option>";
		}

		exit;

	}

	/* Busca os Postos Autorizados */
	if(isset($_POST['linha']) && isset($_POST['estado']) && isset($_POST['cidade'])){

		$linha 	= $_POST['linha'];
		$uf 	= $_POST['estado'];
		$cidade = $_POST['cidade'];
		$fabrica 	= $_POST['fabrica'];
		$cond = "";

		if (in_array($cod_fabrica, [180,181,182])) {
			$cond = " AND tbl_posto_fabrica.contato_pais='{$xxpais}'";
		}

		$sql = "
		SELECT
			tbl_posto.posto                                       ,
			tbl_posto.nome                                        ,
			tbl_posto_fabrica.nome_fantasia                               ,
			tbl_posto_fabrica.contato_cep as cep                               		  ,
			tbl_posto_fabrica.contato_fone_comercial AS telefone  ,
			tbl_posto_fabrica.contato_email 		 AS email     ,
			tbl_posto_fabrica.contato_endereco       AS endereco  ,
			tbl_posto_fabrica.contato_numero         AS numero    ,
			tbl_posto_fabrica.contato_cidade      	 AS cidade    ,
			tbl_posto_fabrica.contato_bairro      	 AS bairro
		FROM tbl_posto
		JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto AND tbl_posto_fabrica.fabrica = $fabrica
		JOIN tbl_posto_linha   ON tbl_posto.posto = tbl_posto_linha.posto AND tbl_posto_linha.linha = $linha
		WHERE
			tbl_posto_fabrica.contato_estado = '$uf' 
			AND UPPER(to_ascii(TRIM(tbl_posto_fabrica.contato_cidade), 'LATIN9')) = UPPER(to_ascii('$cidade', 'LATIN9'))
			AND tbl_posto_fabrica.credenciamento = 'CREDENCIADO'
			AND tbl_posto_fabrica.posto <>6359
			{$cond}
			AND tbl_posto_fabrica.divulgar_consumidor IS TRUE
		ORDER BY tbl_posto.cidade";

		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){

			while($data = pg_fetch_object($res)){

				/* Mascara CEP */
				$cep = maskCep($data->cep);

				/* Mascara Telefone */
				$telefone = maskFone($data->telefone);

				if(strlen(trim($data->nome_fantasia)) > 0 && $data->nome_fantasia != "null"){
					$nome_fantasia = "<strong style='font-size: 14px;'>".strtoupper(retira_acentos($data->nome_fantasia))." </strong> <br />";
					$nome = $data->nome."<br />";
				}else{
					$nome_fantasia = "<strong style='font-size: 14px;'>".strtoupper(retira_acentos($data->nome))." </strong> <br />";
					$nome = "";
				}

				echo "
					<div class='row-fluid'>
						<div class='span12'>
							<p style='border-bottom: 1px solid #CCCCCC; padding-bottom: 20px;'>
								<br />
								$nome_fantasia
								$nome
								$data->endereco, $data->numero  &nbsp; / &nbsp; ".traduz('CEP').": $cep <br />
								".traduz('BAIRRO').": $data->bairro &nbsp; / &nbsp; $data->cidade - $uf <br />
								$telefone &nbsp; / &nbsp; ".strtolower($data->email)."
							</p>
						</div>
					</div>
				";

			}
		}else{
			echo "<strong>".traduz('Nenhum Posto Autorizado Encontrado')."!</strong>";
		}

		exit;

	}

	if (isset($_POST['linhaUF'])) {

		$linha = $_POST['linhaUF'];

		$cond = "";

		if (in_array($cod_fabrica, [180,181,182])) {
			$cond = " AND tbl_posto_fabrica.contato_pais='{$xxpais}'";
		}
		$sql = "SELECT DISTINCT tbl_posto_fabrica.contato_estado
				FROM tbl_posto_fabrica
				JOIN tbl_posto_linha USING(posto)
				JOIN tbl_linha USING(linha)
				WHERE tbl_posto_fabrica.fabrica =$cod_fabrica
				{$cond}
				AND tbl_linha.linha = '$linha'
				ORDER BY tbl_posto_fabrica.contato_estado;";

		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
			echo "<option value=''></option>";
			while($data = pg_fetch_object($res)){
				echo "<option value='$data->contato_estado'>".$data->contato_estado."</option>";
			}
		}else{
			echo "<option value=''></option>";
		}

		exit;
	}




?>

<html>

	<head>
		<title><?php echo traduz('Assistência Técnica');?> - <?=$nome_fabrica;?></title>

			<script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>	

			<link rel="stylesheet/less" type="text/css" media="screen,projection" href="cssmap_brazil_v4_4/cssmap-brazil/cssmap-brasil.less" />
			<script src="cssmap_brazil_v4_4/cssmap-brazil/less-1.3.0.min.js"></script>
			<?php
			if ($_GET["xcf"] == "true") {
				$xcf = "-".$_GET["cf"];
			?>
				<script type="text/javascript">
				$(window).load(function () {
					less.modifyVars({'@map_500':'transparent url(\'br-500<?=$xcf?>.png\') no-repeat -1010px 0'});
				});
				</script>
			<?php
			}

			if($cod_fabrica == 11 || $cod_fabrica == 132){
				?>
					<script type="text/javascript">
					$(window).load(function () {
						less.modifyVars({'@map_340':'transparent url(\'br-340.png\') no-repeat -1010px 0'});
					});
					</script>
				<?php
			}

			if($cod_fabrica == 164){
				?>
					<script type="text/javascript">
					$(window).load(function () {
						less.modifyVars({'@map_340':'transparent url(\'br-340-MTIy.png\') no-repeat -1010px 0'});
					});
					</script>
				<?php
			}

			?>

			<link href="../admin/bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet" media="screen" />
			<link href="../admin/bootstrap/css/extra.css" type="text/css" rel="stylesheet" media="screen" />
			<link href="../admin/css/tc_css.css" type="text/css" rel="stylesheet" media="screen" />
			<link href="../admin/css/tooltips.css" type="text/css" rel="stylesheet" />
			<link href="../admin/plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css" type="text/css" rel="stylesheet" media="screen">
			<link href="../admin/bootstrap/css/ajuste.css" type="text/css" rel="stylesheet" media="screen" />

		    <!--[if lt IE 10]>
		  	<link href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-ie.css" rel="stylesheet" type="text/css" media="screen" />
			<link rel='stylesheet' type='text/css' href="bootstrap/css/ajuste_ie.css">
			<![endif]-->

		    <script src="../admin/plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		    <script src="../admin/plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
		    <script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
		    <script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
		    <script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
		    <script src="../admin/plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>

		    <script src="https://raw.github.com/jamietre/ImageMapster/e08cd7ec24ffa9e6cbe628a98e8f14cac226a258/dist/jquery.imagemapster.js"></script>

		    <script type="text/javascript" src="cssmap_brazil_v4_4/jquery.cssmap.js"></script>

		    <?php

		    switch ($cf) {
				case "MTI0": $background_color = "#0075aa"; break;
				case "MTY0": $background_color = "#ffffff"; break;
				default: $background_color     = "#f5f5f5"; break;
		    }

		    $color_font = ($cf == "MTI0") ? "color: #FFF;" : "";

		    ?>

		    <style type="text/css">
		    	.titulo{
		    		background-color: <?=$background_color?>;
		    		<?=$color_font?>
		    		border-bottom: 1px solid #cccccc;
		    	}

		    	.container{
		    		width: 900px;
		    	}
		    </style>

		    <script type="text/javascript">
		    	$('document').ready(function(){

		    		$('#linha').blur(function(){
		    			var id = "linha";
		    			closeMessageError(id);
		    		});

		    		$('#estado').blur(function(){
		    			var id = "estado";
		    			closeMessageError(id);
		    		});

		    		$('#cidade').blur(function(){

		    			var id = "cidade";

		    			if($('#linha').val() == "" && $('#cidade').val() != ""){
		    				id = "linha";
		    				closeMessageError(id);
		    			}

		    			else if($('#estado').val() == "" && $('#cidade').val() != ""){
		    				id = "estado";
		    				closeMessageError(id);
		    			}

		    			else if($('#cidade').val() != ""){
		    				closeMessageError(id);
		    			}

		    		});

		    		/* Busca Postos Autorizados */
		    		$('#btn_acao').click(function(){

		    			if($('#linha').val() == ""){
		    				$('#linha-group').addClass('error');
		    				messageError();
		    				return;
		    			}else{
		    				closeMessageError();
		    			}

		    			if($('#estado').val() == ""){
		    				$('#estado-group').addClass('error');
		    				messageError();
		    				return;
		    			}else{
		    				closeMessageError();
		    			}

		    			if($('#cidade').val() == ""){
		    				$('#cidade-group').addClass('error');
		    				messageError();
		    				return;
		    			}else{
		    				closeMessageError();
		    			}

		    			var linha 		= $('#linha').val();
		    			var estado 		= $('#estado').val();
		    			var cidade 		= $('#cidade').val();
						var fabrica = <?=$cod_fabrica;?>;

		    			$.ajax({
					        url: "<?=$_SERVER['PHP_SELF']?>",
					        type: "POST",
					        dataType: "JSON",
					        data:
					        {
					        	linha 	: linha,
					        	estado  : estado,
								cidade  : cidade,
								fabrica : fabrica,
								token   : '<?=$token?>'
					       	},
					        beforeSend: function() {
					            loading("show");
					        },
					        complete: function(data) {
					            loading("hide");

				    			/* $('#linha').val('');
				    			$('#estado').val('');
				    			$('#cidade').val(''); */

					            data = data.responseText;
					            $('#lista_posto').html(data);
					        }
					    });

		    		});

		    		/* Busca Produtos */
		    		$('#estado').change(function(){

		    			var uf 	= $('#estado').val();
		    			var linha = $('#linha').val();

		    			if(linha != ""){

			    			var fabrica = <?=$cod_fabrica;?>;

					    	$.ajax({
					    		url : '<? echo $_SERVER["PHP_SELF"]; ?>',
					    		type: 'POST',
					    		dataType: "JSON",
					    		data: {
								uf	: uf,
								linha	: linha,
								fabrica	: fabrica,
								token	: '<?=$token?>'
					    		},
					    		complete: function(data){
					    			data = data.responseText;
					    			$('#cidade').html(data);
					    		}
					    	});

		    			}

		    		});

				    $('#map-brazil').cssMap({
				    	'size' : <?php echo (in_array($cod_fabrica, array(132,164))) ? 340 : 500; ?>,
				    	onClick : function(e){
				    		var uf = e[0].id;
				    		$('#estado').val(uf);
				    		$('#estado').change();
				    	},
				    });

				    $('select[name=linha]').change(function () {
		    			var linha = $("#linha").find("option:selected").val();
		    			var fabrica = <?=$cod_fabrica;?>;

		    			$.ajax({
					    		url : '<? echo $_SERVER["PHP_SELF"]; ?>',
					    		type: 'POST',
					    		data: {
									linhaUF	: linha,
									fabrica : fabrica,
									token   : '<?=$token?>'
					    		},
					    		complete: function(data){
					    			data = data.responseText;
					    			console.log(data);
					    			$('#estado').html(data);
					    		}
					    	});
		    			
		    		});
		    	});

		    	/* Loading Imagem */
		    	function loading(e){
		    		if(e == "show"){
		    			$('#loading').html('<img src="imagens/loading.gif" />');
		    		}else{
		    			$('#loading').html('');
		    		}
		    	}

		    	function messageError(){
		    		$('.alert').show();
		    	}

		    	function closeMessageError(e){
		    		$('#'+e+'-group').removeClass('error');
		    		$('.alert').hide();
		    	}
		    </script>

	</head>

	<body>

		<!-- Titulo -->
		<?php
		if ($cod_fabrica != 132) {
		?>
			<div class="container titulo">

				<div class='row-fluid'>
				    <div class='span2'></div>
				    <div class='span8'>
				        <h3 style="text-align: center;"><?php echo traduz('Assistência Técnica');?> - <?=$nome_fabrica;?></h3>
				    </div>
				    <div class='span2'></div>
				</div>

			</div>

			<br />
		<?php
		}
		?>

		<div class="container" style="height: 40px;">
			<div class="alert alert-error" style="width: 400px; margin: 0 auto; display: none;">
				<strong><?php echo traduz('Preencha os campos obrigatórios');?></strong>
		    </div>
		</div>

		<!-- Corpo -->
		<div class="container">

			<div class='row-fluid'>

			    <div class='span7'>
					<?php 
						if (in_array($cod_fabrica, [180,181,182])) {
							
							echo '<img src="cssmap_brazil_v4_4/'.$img_mapa.'">';

						} else {
					?>



			    	<div id="map-brazil">

			    		<ul class="brazil">
							<li id="AC" class="br1"><a href="#acre">Acre</a></li>
							<li id="AL" class="br2"><a href="#alagoas">Alagoas</a></li>
							<li id="AP" class="br3"><a href="#amapa">Amapá</a></li>
							<li id="AM" class="br4"><a href="#amazonas">Amazonas</a></li>
							<li id="BA" class="br5"><a href="#bahia">Bahia</a></li>
							<li id="CE" class="br6"><a href="#ceara">Ceará</a></li>
							<li id="DF" class="br7"><a href="#distrito-federal">Distrito Federal</a></li>
							<li id="ES" class="br8"><a href="#espirito-santo">Espírito Santo</a></li>
							<li id="GO" class="br9"><a href="#goias">Goiás</a></li>
							<li id="MA" class="br10"><a href="#maranhao">Maranhão</a></li>
							<li id="MT" class="br11"><a href="#mato-grosso">Mato Grosso</a></li>
							<li id="MS" class="br12"><a href="#mato-grosso-do-sul">Mato Grosso do Sul</a></li>
							<li id="MG" class="br13"><a href="#minas-gerais">Minas Gerais</a></li>
							<li id="PA" class="br14"><a href="#para">Pará</a></li>
							<li id="PB" class="br15"><a href="#paraiba">Paraíba</a></li>
							<li id="PR" class="br16"><a href="#parana">Paraná</a></li>
							<li id="PE" class="br17"><a href="#pernambuco">Pernambuco</a></li>
							<li id="PI" class="br18"><a href="#piaui">Piauí</a></li>
							<li id="RJ" class="br19"><a href="#rio-de-janeiro">Rio de Janeiro</a></li>
							<li id="RN" class="br20"><a href="#rio-grande-do-norte">Rio Grande do Norte</a></li>
							<li id="RS" class="br21"><a href="#rio-grande-do-sul">Rio Grande do Sul</a></li>
							<li id="RO" class="br22"><a href="#rondonia">Rondônia</a></li>
							<li id="RR" class="br23"><a href="#roraima">Roraima</a></li>
							<li id="SC" class="br24"><a href="#santa-catarina">Santa Catarina</a></li>
							<li id="SP" class="br25"><a href="#sao-paulo">São Paulo</a></li>
							<li id="SE" class="br26"><a href="#sergipe">Sergipe</a></li>
							<li id="TO" class="br27"><a href="#tocantins">Tocantins</a></li>
						</ul>

			    	</div>
					<?php }?>
			    </div>

			    <div class='span5'>

			    	<br />

			    	<strong class="obrigatorio">* <?php echo traduz('Campos obrigatórios');?></strong>

			    	<br /> <br />

			    	<div class="control-group" id="linha-group">
						<label class="control-label" for="linha"><?php echo traduz('Linha');?></label>
						<div class="controls controls-row">
							<h5 class="asteristico">*</h5>
							<select name="linha" id="linha" class="span11">
								<?php
			                        $sql = "SELECT DISTINCT
	                                    tbl_linha.nome,
	                                    tbl_linha.linha
	                                FROM tbl_linha
	                                WHERE tbl_linha.fabrica = $cod_fabrica
	                                ORDER BY tbl_linha.nome";
									$res = pg_query($con,$sql);
									if(pg_num_rows($res) > 1) { echo "<option></option>" ;}

			                        for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			                    ?>
			                            <option value="<?= pg_fetch_result($res,$i,'linha') ?>">
			                            	<?= ucwords(strtolower(pg_fetch_result($res,$i,'nome'))) ?>
			                            </option>
			                    <?php
			                        }
			                    ?>
							</select>
						</div>
					</div>

					<div class="control-group" id="estado-group">
						<label class="control-label" for="linha"><?php echo traduz('Estado');?></label>
						<div class="controls controls-row">
							<h5 class="asteristico">*</h5>
							<select name="estado" id="estado" class="span11">
								<option value=""></option>
								<?php 
									if (in_array($cod_fabrica, [180,181,182])) {
										$sql = "SELECT * FROM tbl_estado_exterior WHERE pais='{$xxpais}' AND visivel IS TRUE ORDER BY nome ASC;";
										$res = pg_query($con,$sql);
										if (pg_num_rows($res) > 0) {
											foreach (pg_fetch_all($res) as $key => $row) {
												echo '<option value="'.$row['estado'].'">'.$row['nome'].'</option>';
											}

										}
									} else {
								?>
									<option value='AC'>Acre</option>
									<option value='AL'>Alagoas</option>
									<option value='AM'>Amazonas</option>
									<option value='AP'>Amapá</option>
									<option value='BA'>Bahia</option>
									<option value='CE'>Ceará</option>
									<option value='DF'>Distrito Federal</option>
									<option value='ES'>Espírito Santo</option>
									<option value='GO'>Goiás</option>
									<option value='MA'>Maranhão</option>
									<option value='MG'>Minas Gerais</option>
									<option value='MS'>Mato Grosso do Sul</option>
									<option value='MT'>Mato Grosso</option>
									<option value='PA'>Pará</option>
									<option value='PB'>Paraíba</option>
									<option value='PE'>Pernambuco</option>
									<option value='PI'>Piauí</option>
									<option value='PR'>Paraná</option>
									<option value='RJ'>Rio de Janeiro</option>
									<option value='RN'>Rio Grande do Norte</option>
									<option value='RO'>Rondônia</option>
									<option value='RR'>Roraima</option>
									<option value='RS'>Rio Grande do Sul</option>
									<option value='SC'>Santa Catarina</option>
									<option value='SE'>Sergipe</option>
									<option value='SP'>São Paulo</option>
									<option value='TO'>Tocantins</option>
								<?php }?>
							</select>
						</div>
					</div>

					<div class="control-group" id="cidade-group">
						<label class="control-label" for="linha"><?php echo traduz('Cidade');?></label>
						<div class="controls controls-row">
							<h5 class="asteristico">*</h5>
							<select name="cidade" id="cidade" class="span11">
								<option value=""></option>
							</select>
						</div>
					</div>

					<button class="btn" id="btn_acao" type="button"><?php echo traduz('Pesquisar');?></button> &nbsp; <span id="loading"></span>

					<?php
					if($cf == "MTI0"){
						echo "<br /> <br />";
						echo "<img src='img/banner_gamma.jpg' />";
					}
					?>

			    </div>

			</div>

		</div>

		<div style="clear: both;"></div>

		<div class="container" id="lista_posto"></div>

	</body>

</html>


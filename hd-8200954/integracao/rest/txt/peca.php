<?php
include_once 'src/config.php';

@session_start();
$_header = $_SESSION['header'];
//echo "<pre>";

 $ws = URI.'pecas';


	@session_start();
	$_header = $_SESSION['header'];
	
	include_once 'src/function/validate.php';
	$error = Array();
	
	// Valida UPLOAD
	if(!empty($_FILES['file']['tmp_name'])){
		$file = $_FILES['file']['name'];
		$layout = $_POST['layout'];

		if(!isFileTexto($file))
			$error['file'][] = "'{$file}' não possui uma extensão válida!";

		$filename = Date('Ymd_His').'.TXT';

		if(count($error) == 0){
			// importa o arquivo

			$path = "file/{$_header['client_code']}/peca/";
			@mkdir($path, 0777, true);
			
			if (!file_exists($path) && !is_writable($path)) {
				$error['file'][] = "Diretório '{$path}' não pode ser lido ou inexistente!";				
			} else {
				if(!move_uploaded_file($_FILES['file']['tmp_name'], $path.$filename))
					$error['file'][] = "Não foi possivel fazer o upload do arquivo '{$file}'";		
				else {
					$arquivo = $path.$filename;
					if(filesize($arquivo) == 0)
						$error['file'][] = "Arquivo '{$file}' está vazio e não pode ser integrado!";	
					
					if(count($error) == 0):
						$file = file_get_contents($arquivo, true);
					
						//Cada linha do arquivo vira um indice do Array
						$file = explode("\n", $file);
						
						//Verfica se a primeira linha tem a mesma quantidade de campos do layout
						$layout = explode(';',$layout);
						$linha_count = count(explode(';',$file[0]));
						$layout_count = count($layout);

						if($linha_count == $layout_count ) :
							$arr = Array();
							foreach ($file as $ln => $column) :
								$col = explode(";", $column);
							
								if(is_array($col)){
									for($i = 0; $i < $layout_count; $i++):
										//if($col[$i])
										$arr[$ln][trim($layout[$i])] = trim($col[$i]);
									endfor;
								}
							
								echo $referencia;
							endforeach;
							
							if(count($arr)):
								$path_integracao = $path.'/integracao/';
								@mkdir($path_integracao, 0777, true);
								
								//Grava o arquivo em formato JSON
								file_put_contents($path_integracao.$filename, json_encode($arr));
								
								//Salva os itens na sessão para gerar a GRID
								$_SESSION['integracao']['peca']['file'] = $filename;
								$_SESSION['integracao']['peca']['path'] = $path_integracao;
								header("Location: {$_SERVER['PHP_SELF']}");
							endif;
							
						else :
							$error['layout'][] = "Layout não contém a quantidade de campos do arquivo";
						endif;
						
					endif;
				}
			}
		}
	}
	
	
	if(isset($_POST['ajax'])){
		$data = json_decode($_POST['data']);

		include '../../class/webservice/curl.php';


		//INTEGRACAO WS
		

		exit();
	}

	include_once 'inc/header.php'; ?>
	
  	<form class="form-horizontal" action=""  enctype="multipart/form-data" method="POST">
	    <fieldset >
	      	<div id="legend" class="">
	        	<legend class="">Integração de Peças</legend>
	      	</div>

	      	<?php msgError($error); ?>

		<div class="control-group">
	      	<label class="control-label">Arquivo Texto</label>
	      	<div class="controls">
	        	<input class="input-file"  name='file' type="file" required />
	        	<span class="help-block">
	        		O Formato do arquivo deve ser 'txt' e como delimitador ';'
	        	</span>
	      	</div>
	    </div>

		<div class="control-group">
	      	<label class="control-label">Layout do Arquivo</label>
	      	<div class="controls">
	        	<textarea class="input-file span6 tags" name='layout' required ><?php echo @$_POST['layout']; ?></textarea>
		        	<span class="help-block">
		        		É a posição de cada item enviado no arquivo texto, o nome das posições deve ser o mesmo que está na documentação
		        		<br />O layout deve ter a mesma quantidade de campos enviado em cada linha do arquivo txt
		        		<br />Use virgula para separar os campos
		        	</span>
		      	</div>
		    </div>

		    <div class="control-group">
		        <div class="controls">
		            <button type="submit" class="btn btn-primary">Importar</button>
		        </div>
		    </div>
	    </fieldset>
  	</form>
  	
  	<?php if(!empty($_SESSION['integracao']['peca'])) :?>
  	<?php 
  		$path = $_SESSION['integracao']['peca']['path'];
  		$file = $_SESSION['integracao']['peca']['file'];
  		
  		$data = json_decode(file_get_contents($path.$file));
  		
  		//retorna todos os indide da primeira linha do Object
  		$header_th = array_keys((Array) $data[0]);
  		//echo "<pre>";  			print_r($data[0]);
  		
  		$grid = Array();
  		$grid[] = "<div>";
	  		$grid[] = "<table class='table table-hover table-bordered table-striped table-condensed'>";
	   			$grid[] = "<thead>";
	 	 			$grid[] = "<tr>";
	 	 				$grid[] = "<th style='width: 20px; text-align: center'><input type='checkbox' name='all' id='checkall' /></th>";
	 	 				foreach ($header_th AS $th)
	  						$grid[] = "<th>{$th}</th>";
	 	 				$grid[] = "<th>Ações</th>";
	  				$grid[] = "</tr>";
	  			$grid[] = "</thead>";
	  			$grid[] = "<tbody class='itens'>";
	  					foreach ($data AS $key => $ln):
		  					$grid[] = "<tr>";
		  						$grid[] = "<td style='text-align: center'>
												<input type='checkbox' name='check[{$key}][]' />
												<input type='hidden' class='data' name='data[{$key}][]' value='".json_encode($ln)."' />
		  								   </td>";
		  						
		  						foreach ($ln AS $value)
		  							$grid[] = "<td>&nbsp;{$value}</td>";
		  						
		  						$grid[] = "<td style=' text-align: center'>
		  										<a href='javascript: void(0);' class='btn btn-client'>
													<span class='icon-circle-arrow-up'></span>
												</a>
										   </td>";
		  					$grid[] = "</tr>";
	  					endforeach;
	  			$grid[] = "</tbody>";
	  		$grid[] = "</table>";
  		$grid[] = "</div>";

  		echo implode("", $grid);
  	?>
  	<?php endif;?>
 
<?php include_once 'inc/footer.php'; ?>
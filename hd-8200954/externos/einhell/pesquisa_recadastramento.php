<?php

include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';
include '../../funcoes.php';
include '../../helpdesk/mlg_funciones.php';
include '../../classes/cep.php';
include '../../class/communicator.class.php';

include 'class/aws/s3_config.php';
include_once S3CLASS;

$login_fabrica = 160;
$dataFimPesquisa = "26/10/2018";

list($da, $ma, $ya) = explode("/", date("d/m/Y"));
list($dp, $mp, $yp) = explode("/", $dataFimPesquisa);

$postomd5 = $_GET["posto"];

$sqlPesquisa = "SELECT pesquisa from tbl_pesquisa where categoria = 'recadastramento' and fabrica = $login_fabrica ";
$resPesquisa = pg_query($con, $sqlPesquisa);
if(pg_num_rows($resPesquisa) > 0){
	$pesquisa = pg_fetch_result($resPesquisa, 0, 'pesquisa');
}else{
	$msg_erro = "Pesquisa não encontrada";
}

$sqlPosto = "select * from tbl_posto_fabrica where md5(posto::text) = '$postomd5' and fabrica = $login_fabrica ";
$resPosto = pg_query($con, $sqlPosto);
if(pg_num_rows($resPosto)>0){
	$id_posto = pg_fetch_result($resPosto, 0, posto);	
}else{
	$msg_erro = "Falha ao encontrar posto. <br>";
}

function retira_acentos( $texto ){
    $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç","º","&","%","$","?","@" );
    $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C","_","_","_","_","_","_" );
    return str_replace( $array1, $array2, $texto );
}


if(isset($_POST['btnacao'])){

	$id_posto = $_POST['posto'];

	$obrigatorios = explode(",", $_POST['obrigatorios']);

	unset($obrigatorios[0]);

	$sqlVerificaResposta = " select * from tbl_resposta where pesquisa = $pesquisa and posto = $posto ";
	$resVerificaResposta = pg_query($con, $sqlVerificaResposta);
	if(pg_num_rows($resVerificaResposta) >0){
		$msg_erro = "Pesquisa já respondida.<br>";
	}
	
	foreach($_POST['resposta'] as $chave => $resposta){
			$dadosChave = explode("_", $chave);
			$txt_resposta = $dadosChave[0];
			$pergunta = trim($dadosChave[2]);

			$key = array_search($pergunta, $obrigatorios) ;

			if($key == ''){
				$key = 9;
			}

			unset($obrigatorios[$key]);
		}

		if(count($obrigatorios)>0){
			$msg_erro .= "Todas as perguntas são obrigatórias. <br>";
		}
	

	if(strlen(trim($msg_erro))==0){

		foreach($_POST['resposta'] as $chave => $resposta){
			$dadosChave = explode("_", $chave);
			$pergunta = $dadosChave[2];
			$txt_resposta = $dadosChave[0];

			if(empty($resposta)){
				continue;
			}

			$sqlInsert = "INSERT INTO tbl_resposta (
                pergunta,
                txt_resposta,
                tipo_resposta_item,
                pesquisa,
                data_input,
                posto
            )VALUES(
                $pergunta,
                '$resposta',
                $txt_resposta,
                $pesquisa,
                current_timestamp,
                $id_posto
            )";

            $resInsert = pg_query($con, $sqlInsert);
            if(strlen(pg_last_error($con))>0){
            	$msg_erro .= "Falha ao gravar pesquisa";
            }
		}

		if(empty($msg_erro)){
			$ok = "Pesquisa respondida com sucesso. ";
		}

	}
}
?>
<!DOCTYPE html />
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=iso-8859-1" />
    <meta name="language" content="pt-br" />

    <!-- jQuery -->
    <script type="text/javascript" src="../callcenter/plugins/jquery-1.11.3.min.js" ></script>

    <!-- Bootstrap -->
    <script type="text/javascript" src="../callcenter/plugins/bootstrap/js/bootstrap.min.js" ></script>
    <link rel="stylesheet" type="text/css" href="../callcenter/plugins/bootstrap/css/bootstrap.min.css" />

    <!-- Plugins Adicionais -->
    <script type="text/javascript" src="../../plugins/jquery.mask.js"></script>
    <script type="text/javascript" src="../../plugins/jquery.alphanumeric.js"></script>
    <script type="text/javascript" src="../../plugins/fancyselect/fancySelect.js"></script>
    <script type="text/javascript" src="../../plugins/jquery.form.js"></script>
    <link rel="stylesheet" type="text/css" href="../../plugins/fancyselect/fancySelect.css" />

    <link rel="stylesheet" type="text/css" href="css/fale_conosco.css" />

    <link href="../institucional/elgin_source/css/externo.css" rel="stylesheet" type="text/css" />
    

    <script type="text/javascript">

    	$( document ).ready(function() {

    		$("input[class^='resposta_sim']").click(function(){
    			$(".resposta_nao").prop("checked", false);
    			$(".resposta_sim").prop("checked", true);

				$(".resposta_por_que_").val('');
				$(".resposta_por_que_").hide();
				$(".resposta_por_que_").prop('disabled', true);
    				
    		});

    		$("input[class^='resposta_nao']").click(function(){
    			$(".resposta_sim").prop("checked", false);
    			$(".resposta_nao").prop("checked", true);

    			$(".resposta_por_que_").show();
    			$(".resposta_por_que_").prop('disabled', false);
    		});

			$(".resposta_outras").click(function(){
				if ($('.resposta_outras').is(':checked')) {
					$(".resposta_quais").show();
					$(".resposta_quais").prop('disabled', false);
				}else{
					$(".resposta_quais").val('');
					$(".resposta_quais").hide();
				}				
			});    		
    	});

    </script>


    <style>
    	#conteudo{
			width:100%;
			font-size:15px;
			color:#6E6F71;
			margin-bottom:10px;
			margin-top:15px;
		}

		#titulo{
			width:              100%;
			font-size:			25px;
			font-weight:		bold;
			color:				#6E6F71;
			margin-top:			20px;
		}

		.perguntas{			
			font-weight: bold;
		}

		.obrigatorios{
			color:red;
		}


    </style>
</head>
<body>
	<div class="container">
		<div class="row">
			<div class="col-md-3"><img src="../../logos/logo_einhell.jpg" style="max-height:65px;max-width:250px;"></div>
		</div>
		
		<?php if(strlen(trim($msg_erro))>0){ ?>
		<br>
		<div class="row">
			<div class="col-md-12 alert alert-danger"><?=$msg_erro?></div>
		</div>
		<?php } ?>

		<?php if(strlen(trim($ok))>0){ ?>
		<br>
		<div class="row">
			<div class="col-md-12 alert alert-success"><?=$ok?></div>
		</div>
		<?php } ?>


		<?php 
			$sql = "SELECT * from tbl_pesquisa where pesquisa = $pesquisa and fabrica = $login_fabrica";
			$resPesquisa = pg_query($con, $sql);
			if(pg_num_rows($resPesquisa)>0){
				$titulo = pg_fetch_result($resPesquisa, 0, descricao);
			}
		?>

		<div class="row"> 
			<div class="col-md-12 museo museo300" id="titulo"><?=$titulo?> </div>
		</div>

		<?php if ( strtotime("$ya-$ma-$da") > strtotime("$yp-$mp-$dp") ) { ?>
			<div class="row">
					<div class="col-md-12 alert alert-danger">Data fora do limite da pesquisa.</div>
				</div>
		<?php  exit; } ?>

		<div class="row">
			<div class="col-md-12 museo museo300" id="conteudo">
				<p>Prezado Assistente Técnico, </p>

				<p>A Einhell Brasil tem trabalhado intensamente no sentido de proporcionar um pós-venda cada dia mais dinâmico e efetivo junto a todos os parceiros de assistência técnica espalhados em todo território nacional.</p>

				<p>
				Com objetivo de fortalecermos nossas relações, inclusive visando projetos futuros de médio prazo, estamos realizando uma pesquisa junto a todas as AT's que se encontram atualmente credenciadas no sistema Telecontrol.
				</p>

				<p>Desta forma, pedimos gentilmente, que nos responda sobre três breves situações:</p>
			</div>
		</div>
		<form class="col-md-12" name="pesquisa" method="POST" action="">
		<?php
			$sqlPerguntaPesquisa = "SELECT * from tbl_pesquisa_pergunta where pesquisa = $pesquisa order by ordem";	
			$resPerguntaPesquisa = pg_query($con, $sqlPerguntaPesquisa);
			for($x =0; $x<pg_num_rows($resPerguntaPesquisa); $x++){
				$pergunta = pg_fetch_result($resPerguntaPesquisa, $x, pergunta);

				$sqlPergunta = "SELECT * from tbl_pergunta where pergunta = $pergunta and fabrica = $login_fabrica and ativo = true";
				$resPergunta = pg_query($con, $sqlPergunta);
				for($i=0; $i<pg_num_rows($resPergunta); $i++){
					$pergunta_descricao = pg_fetch_result($resPergunta, $i, 'descricao');
					$tipo_resposta = pg_fetch_result($resPergunta, $i, 'tipo_resposta');
					$pergunta = pg_fetch_result($resPergunta, $i, 'pergunta');

					$class_obrig = (in_array($pergunta, $obrigatorios))? " obrigatorios ": "" ;

					echo "<div class='row'>
							<div class='col-md-12 perguntas $class_obrig ' >$pergunta_descricao</div>
						</div>";

					$sqlResposta = "SELECT tbl_tipo_resposta.tipo_descricao, tbl_tipo_resposta_item.descricao, tbl_tipo_resposta_item.tipo_resposta_item, tbl_tipo_resposta.tipo_resposta, tbl_tipo_resposta.obrigatorio
									FROM tbl_tipo_resposta_item 
									INNER JOIN tbl_tipo_resposta on tbl_tipo_resposta.tipo_resposta = tbl_tipo_resposta_item.tipo_resposta
									WHERE tbl_tipo_resposta_item.tipo_resposta = $tipo_resposta  order by ordem ";
					$resResposta = pg_query($con, $sqlResposta);

					$tipo_resposta_obrig[0] = 0; 

					for($a=0;$a<pg_num_rows($resResposta); $a++){
						$descricao_resposta 	= pg_fetch_result($resResposta, $a, descricao);
						$tipo_descricao 		= pg_fetch_result($resResposta, $a, tipo_descricao);
						$tipo_resposta_item 	= pg_fetch_result($resResposta, $a, tipo_resposta_item);
						$tipo_resposta 			= pg_fetch_result($resResposta, $a, tipo_resposta);
						$obrigatorio 			= pg_fetch_result($resResposta, $a, obrigatorio);

						if($obrigatorio == 't'){
							$tipo_resposta_obrig[$pergunta] = $pergunta; 
						}
						
						$name = "resposta"."[$tipo_resposta_item"."_$a"."_$pergunta]";			
						$value = "$tipo_resposta_item";

						echo "<div class='row'>";
						 echo "<div class='col-md-12'>";

						if($descricao_resposta == "Por que?" OR $descricao_resposta == "Quais"){
							$lable_descricao_resposta = $descricao_resposta;
							$descricao_resposta = strtolower(retira_acentos($descricao_resposta));
							$descricao_resposta = str_replace(array(' ', "."), "_",$descricao_resposta);

							echo "<lable class='resposta_".$descricao_resposta."' style='display:none;' >$lable_descricao_resposta</lable> 
							<input type='text' class='resposta_".$descricao_resposta."' name='$name' value='' style='display:none;' disabled >
						 ";
						}else{

							$checked = ($_POST['resposta'][$tipo_resposta."_$a"."_".$pergunta])? " checked ": " ";

							echo "<input type='$tipo_descricao' $checked class='resposta_".strtolower(retira_acentos($descricao_resposta))."' name='$name' value='$descricao_resposta'>
						 	<lable>$descricao_resposta</lable>";	
						}						 

						 echo "</div>";
						 echo "</div>";
						
					}
					echo "<br><Br>";
				}
			}
		?>

		<div class="row">
			<div class="col-md-12">Suas respostas são de vital importância para nós. Agradecemos se puder responder até o dia <?=$dataFimPesquisa ?>.</div>
		</div>

		<div class="row">

			<div class="col-md-12"> <center> 
				<input type='hidden' name='posto' value='<?=$id_posto?>'> 
				<input type='hidden' name='obrigatorios' value='<?=implode(",",$tipo_resposta_obrig)?>'> 
				<input type='submit' name='btnacao' class="btn btn-primary" value='Gravar'> 
				</center>
			</div>
		</div>
	</form>


	</div>
	<div class="row"> 
		<div class="col-md-12 museo museo300" id="titulo"> </div>
	</div>

	
	
</body>

</html>
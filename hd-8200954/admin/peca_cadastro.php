<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';
include_once '../helpdesk/mlg_funciones.php';

include_once __DIR__ . '/../class/AuditorLog.php';

include_once __DIR__ . DIRECTORY_SEPARATOR . '../class/json.class.php';
include_once "../class/tdocs.class.php";
$tDocs  = new TDocs($con, $login_fabrica);

$path = getcwd();
$caminho_dir = "imagens_pecas";

if ($login_fabrica == 158) {
    if ($_serverEnvironment == "production") {
        $chave_persys = "12984374000259-7a4e7d2cb15c403b7a33c73ccc4dc4e9";
    }else{
        $chave_persys = "4716427000141-dc3442c4774e4edc44dfcc7bf4d90447";
    }
}

$fabricas_com_imagens_pecas   = array(1,3,5,6,10,11,19,35,45,46,50,51,85,88,91,96,115,116,120,201,124,129,132,138,140,141,144,148,153,156,161,172,174,178,195);

if ($imagemPeca and !in_array($login_fabrica, $fabricas_com_imagens_pecas)) {
	$fabricas_com_imagens_pecas[] = $login_fabrica;
}

$arr_login_fabrica_ativo      = array (11, 14, 20, 24, 30, 35, 40, 42, 43, 72,74,52, 172);
$fabricas_usam_NCM            = isFabrica(1,10,51,81,91,114,119,122,123,125,128,165);

$fabrica_peca_dev_obrigatoria = (isFabrica(1,3,6,15,20,24,35,40,45,46,50,52,59,72,80,81,85,86,90,91,94,98,99,104,114,115,117,120,201,121,122,125,127,129,131,134,138,139,140,141,142,144,145,146,147,151,152,153,154,157,160,162,169,170,171,180,181,182,183) or $replica_einhell);

$fabrica_usa_bloqueada_venda =  (isFabrica(1, 2, 35, 87, 91, 151, 153, 160, 161, 162,163, 164, 165, 166,167,171,173,174,175,176,178,179,183,184,191,200,203) or $replica_einhell);

$label_class_fiscal = isFabrica(1,42) ? traduz('Código de Origem') : traduz('Classificação Fiscal');

if ($login_fabrica<>10) {
	$caminho_dir = $caminho_dir."/".$login_fabrica;
}

// @todo descomentar linahs abaixo
// Arquivo necessario contendo as constantes da Loja Virtual
$servidor = $_SERVER[HTTP_HOST];
if($servidor == 'www.telecontrol.com.br' || $servidor == 'ww2.telecontrol.com.br' || $servidor == 'posvenda.telecontrol.com.br') {
#	include '/var/www/telecontrol/www/loja/bootstrap.php';
#	uses('PecaFoto');
	$caminho_media 	= "/www/assist/www/$caminho_dir/media/";
	$caminho_pequeno 	= "/www/assist/www/$caminho_dir/pequena/";
}else{
	include '../../LojaVirtual/bootstrap.php';
	#uses('PecaFoto');
	$caminho_servidor 	= str_replace('/admin','',$path);
	$caminho_media 	= $caminho_servidor."/".$caminho_dir."/media/";
	$caminho_pequeno 	= $caminho_servidor."/".$caminho_dir."/pequena/";

}

// ! Indice de sufixos das fotos para Loja Virtual Telecontrol
$aIdxFotos = array('pq','g','1','2','3','4');

# HD 48163
$verifica_prio = $_GET["verifica_prio"];
if ($verifica_prio == "true"){
	$prioridade = $_GET["prioridade"];
	if (strlen($prioridade) > 0){
		$sqlPSx = "SELECT posicao_site FROM tbl_peca WHERE fabrica = $login_fabrica
				AND posicao_site IS NOT NULL
				AND posicao_site = $prioridade";
		$resPSx = pg_exec($con,$sqlPSx);

		if(pg_numrows($resPSx) > 0){
			echo traduz("&nbsp;<font color='#FF0000'>Prioridade já existente!</font>");
		}else{
			echo traduz("&nbsp;<font color='#009900'>Prioridade disponível!</font>");
		}
	}
	exit;
}

$ajax           = $_GET['ajax'];
$excluir_peca   = $_GET['excluir_peca'];
$foto_principal = $_GET['foto_principal'];

if(strlen($ajax)>0){
	$imagem         = $_GET['imagem'];
	$peca           = $_GET['peca'];
	$peca_item_foto = $_GET['peca_item_foto'];

		if ($ajax == 'excluir') {

			header("Content-Type: text/html; charset=iso-8859-1");

			if (!file_exists("../$caminho_dir/media/$imagem"))
				die("Imagem ../$caminho_dir/media/$imagem não existe!");

			$deletou = unlink("../$caminho_dir/media/$imagem");
			$deletou = @unlink("../$caminho_dir/pequena/$imagem"); //PDF não tem no pequena

			if ($deletou)
				die(traduz("Imagem excluída com êxito!"));

			die(traduz("Erro ao excluir a imagem!"));

		}

	if ($dh = opendir("../$caminho_dir/media/")) {

		// Fabrica com mais de uma imagem por peça (Telecontrol, p.e., para a Loja Virtual)
		if (file_exists("../$caminho_dir/media/$imagem")) {
			echo"<center><img src=\"../$caminho_dir/media/$imagem\" border='0'></center>";
			if (strlen($peca_item_foto)>0){
				echo "<div><span style='float:left;'>";
				echo traduz("<a href='$PHP_SELF?excluir_peca=sim&peca=$peca&peca_item_foto=$peca_item_foto'>Excluir Foto</a>");
				echo "</span>";
				$sql = "SELECT ordem FROM tbl_peca_item_foto WHERE peca =$peca AND peca_item_foto = $peca_item_foto";
				$res = pg_exec ($con,$sql);
				if (pg_numrows($res) > 0){
					$mostra_def_foto = pg_fetch_result($res, 0, ordem);
				}else{
					$mostra_def_foto = 0;
				}
				if ($mostra_def_foto <> 1){
					echo "<span style='float:right;'>";
					echo traduz("<a href='$PHP_SELF?foto_principal=sim&peca=$peca&peca_item_foto=$peca_item_foto' >Definir como Foto Principal</a>");
					echo "</span>";
				}
				echo "</div>";
			}
		}else{
			echo traduz("<center>Imagem não existe! <a href='javascript:self.parent.tb_remove()'>Fechar Janela</a></center>");
		}

	}
	exit;
}

function download( $path, $fileName = '' ){

    if( $fileName == '' ){
        $fileName = basename( $path );
    }

    header("Content-Type: application/force-download");
    header("Content-type: application/octet-stream;");
	header("Content-Length: " . filesize( $path ) );
	header("Content-disposition: attachment; filename=" . $fileName );
	header("Pragma: no-cache");
	header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
	header("Expires: 0");
	readfile( $path );
	flush();
}

//excel-Item_Revenda
if ($_GET["gerar_excel"] == 'true') {
//if (1==1) {
	$fileName = "item-revenda-etiqueta.txt";
	$file = fopen("/tmp/{$fileName}", "w");

	$sql_r = "SELECT tbl_peca.referencia,tbl_produto.referencia as descricao
				FROM tbl_peca
				JOIN tbl_lista_basica ON tbl_peca.peca = tbl_lista_basica.peca
				JOIN tbl_produto      ON tbl_lista_basica.produto = tbl_produto.produto
			   WHERE JSON_FIELD('item_revenda', tbl_peca.parametros_adicionais) = 't'
				 AND JSON_FIELD('item_revenda', tbl_lista_basica.parametros_adicionais) = 't'
				 AND tbl_peca.fabrica = $login_fabrica
			GROUP BY tbl_peca.referencia,tbl_produto.referencia
			ORDER BY tbl_peca.referencia,tbl_produto.referencia";
	$res_r = pg_query($con,$sql_r);
	$result_lb = pg_fetch_all($res_r);

	$body = '';
	$ref_peca = '*';
    foreach ($result_lb as $key_ex => $value_ex) {
        if ($ref_peca != $value_ex[referencia]) {
        	if ($ref_peca != '*') {
                fwrite($file, ";\r\n");
            }
            $ref_peca = $value_ex[referencia];
            fwrite($file, "R".$value_ex[referencia].";");

            $primeiro_produto = true;
        }
        if ($primeiro_produto == true) {
            fwrite($file, $value_ex[descricao]);
            $primeiro_produto = false;
        }else{
            fwrite($file, "|".$value_ex[descricao]);
        }
    }

	fwrite($file, ';');
	fclose($file);
    if (file_exists("/tmp/{$fileName}")) {
        system("mv /tmp/{$fileName} xls/{$fileName}");
        download("xls/{$fileName}");
	}
	exit;
}

if(isset($_POST['verifica_lista_basica_peca'])){

	$ref 	= $_POST['ref'];
	$peca 	= $_POST['cod'];

	$sql_verifica_lista_basica_peca = "SELECT produto FROM tbl_lista_basica WHERE peca = $peca AND ativo is true";
	$res_verifica_lista_basica_peca = pg_query($con, $sql_verifica_lista_basica_peca);

	echo (pg_num_rows($res_verifica_lista_basica_peca) > 0) ? "ok" : "fail";

	exit;

}

if (isset($_POST['item_revenda_produto_ajax'])) {
	//print_r($_POST);exit;
	$ativa_etiqueta		 	= $_POST['ativa_etiqueta'];
	$lista_basica_produto 	= $_POST['lista_basica_etiqueta'];

	$sql_pa="SELECT parametros_adicionais, lista_basica
				FROM tbl_lista_basica
				WHERE fabrica = $login_fabrica
				AND produto = $lista_basica_produto";
	$res_pa = pg_query($con,$sql_pa);

	if (pg_num_rows($res_pa) > 0) {
		pg_query($con,'BEGIN TRANSACTION');

		for ($i=0; $i < pg_num_rows($res_pa) ; $i++) {
			$pa_lista_basica_id = pg_fetch_result($res_pa, $i, lista_basica);
			$pa_lista_basica = new Json(pg_fetch_result($res_pa, $i, parametros_adicionais));

			if ($ativa_etiqueta == 'true') {
				$pa_lista_basica->item_revenda = 't';
			} else {
				$pa_lista_basica->removeItem('item_revenda');
			}

			$sql_pa_up="UPDATE tbl_lista_basica
				           SET parametros_adicionais = '$pa_lista_basica'
				         WHERE fabrica      = $login_fabrica
				           AND lista_basica = $pa_lista_basica_id";
			$res_pa_up = pg_query($con,$sql_pa_up);
		}

		if (strlen(pg_last_error()) > 0) {
			pg_query($con,'ROLLBACK TRANSACTION');
			die('erro');
		}
		// $sql_pa="UPDATE tbl_lista_basica SET parametros_adicionais = '$pa_lista_basica' WHERE fabrica = $login_fabrica AND lista_basica = $lista_basica_etiqueta;";
		// $res_pa = pg_query($con,$sql_pa);
		// //echo $sql_pa;
		// if (strlen(pg_last_error()) > 0) {
		// 	pg_query($con,'ROLLBACK TRANSACTION');
		// 	echo "erro";
		// }else{
		// 	pg_query($con,'COMMIT TRANSACTION');
		// 	echo "ok";
		// }
	}
	exit;
}

if($excluir_peca == "sim"){
	$peca      = $_GET['peca'];
	$peca_item_foto = $_GET['peca_item_foto'];

	if (strlen($peca_item_foto)>0 AND strlen($peca)>0){

		$res = pg_exec ($con,'BEGIN TRANSACTION');

		$sql = "SELECT caminho,caminho_thumb
				FROM tbl_peca_item_foto
				WHERE peca_item_foto = $peca_item_foto
				AND peca = $peca";
		$res = pg_exec ($con,$sql);

		$caminho       = pg_fetch_result($res,0,caminho);
		$caminho_thumb = pg_fetch_result($res,0,caminho_thumb);

		$sql = "DELETE FROM tbl_peca_item_foto WHERE peca_item_foto = $peca_item_foto AND peca = $peca";
		$res = pg_exec ($con,$sql);


		if (!unlink($caminho)){
			$msg_erro .= traduz('Imagem não pôde ser excluída.');
		}

		if (strlen($msg_erro)==0){
			if (!unlink($caminho_thumb)){
				$msg_erro .= traduz('Imagem não pôde ser excluída.');
			}
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			echo traduz("<center>Imagem excluída com sucesso. Recarregue a página para atualizar. <a href='javascript:self.parent.tb_remove()'>Fechar Janela</a></center>");
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			echo traduz("<center>Imagem não pode ser excluída. ($msg_erro) <a href='javascript:self.parent.tb_remove()'>Fechar Janela</a></center>");
		}
	}else{
		echo traduz("<center>Nenhuma foto selecionada.</center>");
	}
	exit;
}

if ($foto_principal == "sim"){
	# HD 47107

	$peca           = $_GET['peca'];
	$peca_item_foto = $_GET['peca_item_foto'];

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	# Primeiro seta null em todos campos "ordem" relacionado a peça
	$sql = "UPDATE tbl_peca_item_foto SET ordem = null WHERE peca = $peca";
	$res = pg_exec ($con,$sql);
	$msg_erro_peca_foto = pg_errormessage($con);

	# Depois atualiza a escolhida
	$sql = "UPDATE tbl_peca_item_foto SET ordem = 1
		WHERE peca = $peca
		AND peca_item_foto = $peca_item_foto";
	$res = pg_exec ($con,$sql);
	$msg_erro_peca_foto = pg_errormessage($con);

	if (strlen($msg_erro_peca_foto) == 0){
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		echo traduz("<center>Foto definida como principal com sucesso! Recarregue a página para atualizar. <a href='javascript:self.parent.tb_remove()'>Fechar Janela</a></center>");
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		echo traduz("<center>Foto não pôde ser definida como principal! ($msg_erro_peca_foto) <a href='javascript:self.parent.tb_remove()'>Fechar Janela</a></center>");
	}
	exit;
}

function reduz_imagem($img, $max_x, $max_y, $nome_foto) {

	list($original_x, $original_y) = getimagesize($img);	//pega o tamanho da imagem

	// se a largura for maior que altura
	if($original_x > $original_y) {
	   $porcentagem = (100 * $max_x) / $original_x;
	}
	else {
	   $porcentagem = (100 * $max_y) / $original_y;
	}

	$tamanho_x	= $original_x * ($porcentagem / 100);
	$tamanho_y	= $original_y * ($porcentagem / 100);
	$image_p	= imagecreatetruecolor($tamanho_x, $tamanho_y);
	$image		= imagecreatefromjpeg($img);

	imagecopyresampled($image_p, $image, 0, 0, 0, 0, $tamanho_x, $tamanho_y, $original_x, $original_y);
	imagejpeg($image_p, $nome_foto, 65);

}

$altera_posicao = $_GET["altposicao"];
$xposicao = $_GET["posicao"];

if (strlen($_GET["peca"]) > 0) {
	 $peca = trim($_GET["peca"]);
}

if (strlen($_POST["familia_peca"]) > 0) {
	$familia_peca = trim($_POST["familia_peca"]);
}

if (strlen($_POST["peca"]) > 0) {
	$peca = trim($_POST["peca"]);
}

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}

if ($btnacao == "deletar" and strlen($peca) > 0 ) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_peca
			WHERE  tbl_peca.fabrica    = $login_fabrica
			AND    tbl_peca.peca       = $peca;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen ($msg_erro) == 0) {
		if (in_array($login_fabrica, array(151,158))) {
			$sqlFamiliaPeca = "DELETE FROM tbl_peca_familia WHERE fabrica = $login_fabrica AND peca = $peca";
			pg_query($con,$sqlFamiliaPeca);
			$msg_erro = pg_errormessage($con);
		}
	}

	if (strlen($msg_erro) !== 0) {
		###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS
		$peca                         = $_POST["peca"];
		$referencia                   = $_POST["referencia"];
		$descricao                    = $_POST["descricao"];
		$ipi                          = $_POST["ipi"];
		$origem                       = $_POST["origem"];
		$estoque                      = $_POST["estoque"];
		$unidade                      = $_POST["unidade"];
		$peso                         = $_POST["peso"];
		$classificacao_fiscal         = $_POST["classificacao_fiscal"];
		$qtde_disparos 				  = $_POST["qtde_disparos"];
		$multiplo                     = $_POST["multiplo"];
		$garantia_diferenciada        = $_POST["garantia_diferenciada"];
		$devolucao_obrigatoria        = $_POST["devolucao_obrigatoria"];
		if ($login_fabrica == 6) {
			$devolucao_estoque_fabrica= $_POST["devolucao_estoque_fabrica"];
		}
		$item_aparencia               = $_POST["item_aparencia"];
		$acumular_kit                 = $_POST["acumular_kit"];
		$retorna_conserto             = $_POST["retorna_conserto"];
		$intervencao_carteira         = $_POST["intervencao_carteira"];
		$mao_de_obra_diferenciada     = $_POST["mao_de_obra_diferenciada"];
		$bloqueada_garantia           = $_POST["bloqueada_garantia"];

		if(in_array($login_fabrica, [169,170])){
			$peca_original = ($_POST['peca_original'] == 't') ? true : false;
		}

		$remessa_garantia             = $_POST["remessa_garantia"];
		$remessa_garantia_compressor  = $_POST["remessa_garantia_compressor"];
		$acessorio                    = $_POST["acessorio"];
		$aguarda_inspecao             = $_POST["aguarda_inspecao"];
		$peca_critica                 = $_POST["peca_critica"];
		$produto_acabado              = $_POST["produto_acabado"];
		$peca_monitorada              = $_POST["peca_monitorada"];
		$email_peca_monitorada        = $_POST["email_peca_monitorada"];
		$reembolso                    = $_POST["reembolso"];
		$mero_desgaste                = $_POST["mero_desgaste"];
		//$intervencao_fabrica        = $_POST["intervencao_fabrica"];
		$troca_obrigatoria            = $_POST["troca_obrigatoria"];
		$marca                        = $_POST["marca"];
		$informacoes                  = $_POST["informacoes"];
		$preco_compra                 = $_POST["preco_compra"];
		$ativo                        = $_POST["ativo"];
		$uso_interno                  = $_POST["uso_interno"];
		$faturada_manualmente         = $_POST["faturada_manualmente"];
		//hd 47129
		$peca_unica_os                = $_POST["peca_unica_os"];
		$ativo                        = $_POST["ativo"];
		$pre_selecionada              = $_POST["pre_selecionada"];
		$placa                        = $_POST["placa"];#HD 47695
		$peca_pai                     = $_POST["peca_pai"];
		//HD: 2453 - DYNACOM - MAO DE OBRA DIFERENCIA PEÇA, POSTO,ADMIN
		$mo_peca                      = $_POST["mo_peca"];
		$mo_posto                     = $_POST["mo_posto"];
		$mo_admin                     = $_POST["mo_admin"];
		$promocao_site                = $_POST["promocao_site"];
		$at_shop                      = $_POST["at_shop"];
		$multiplo_site                = $_POST["multiplo_site"];
		//$qtde_minima_site           = $_POST["qtde_minima_site"];
		$qtde_max_site                = $_POST["qtde_max_site"];
		$frete_gratis                 = $_POST["frete_gratis"];
		$qtde_minima_estoque          = $_POST["qtde_minima_estoque"];
		$qtde_disponivel_inicial_site = $_POST["qtde_disponivel_inicial_site"]; // HD 18289
		$qtde_disponivel_site         = $_POST["qtde_disponivel_site"];
		$preco_peca                   = $_POST["preco_peca"];
		$preco_anterior               = $_POST["preco_anterior"];
		$data_inicial_liquidacao      = $_POST["data_inicial_liquidacao"];
		$linha                        = $_POST["linha"];
		$bloqueada_venda              = $_POST["bloqueada_venda"];
		$familia                      = $_POST["familia"];
		$ncm                          = $_POST["ncm"]; //MLG 2011-04-13 - Adicionar NCM ao cadastro de peça
		$controla_saldo               = $_POST["controla_saldo"];
        $previsao_entrega             = $_POST['previsao_entrega'];
        $disponibilidade              = $_POST["status_disponibilidade"];

		if (strpos($msg_erro,'"tbl_peca"'))
		$msg_erro = traduz("Esta peça não pode ser excluída");
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}else{
		if (in_array($login_fabrica, array(158))) {
			$key = $chave_persys;
			$ch = curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/recurso/material/codigo/".$_POST["referencia"],
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_HTTPHEADER => array(
					"authorizationv2: $key"
				)
			));

			$response = curl_exec($ch);
			curl_close($ch);
			if (!$response) {
				$msg_erro = traduz("Erro ao tentar deletar da aplicação mobile!");
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			}else{
				$response = json_decode($response, TRUE);
				$campos["statusModel"] = "0";
				$json = json_encode($campos);

				$ch = curl_init();
				curl_setopt_array($ch, array(
					CURLOPT_URL => "http://telecontrol.eprodutiva.com.br/api/recurso/material/".$response['id'],
				  	CURLOPT_RETURNTRANSFER => true,
				  	CURLOPT_CUSTOMREQUEST => "PUT",
				  	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				  	CURLOPT_POSTFIELDS => $json,
				  	CURLOPT_HTTPHEADER => array(
				    	"authorizationv2: $key",
				    	"Content-Type: application/json"
				  	)
				));
				$response = curl_exec($ch);
				curl_close($ch);
				if (!$response) {
					$msg_erro = traduz("Erro ao tentar deletar da aplicação mobile!");
					$res = pg_exec ($con,"ROLLBACK TRANSACTION");
				}else{
					$response = json_decode($response, true);
					if (count($response['error']) == 0) {
						pg_query($con,"COMMIT TRANSACTION");
						header ("Location: $PHP_SELF");
						exit;
					}else{
						$msg_erro = traduz("Erro ao tentar deletar da aplicação mobile!");
						$res = pg_exec ($con,"ROLLBACK TRANSACTION");
					}
				}
			}
		}else{
			pg_query($con,"COMMIT TRANSACTION");
			header ("Location: $PHP_SELF");
			exit;
		}
	}
}

if ($btnacao == "gravar") {

	$cla_fiscal = $_POST["classificacao_fiscal"];
		if (strlen($cla_fiscal) > 10) {
			$msg_erro .= traduz("Classificação Fiscal não pode ser maior que 10 caracteres. <br />");
		}

	if(in_array($login_fabrica, [11,104,172])){
		$qtde_demanda = $_POST["qtde_demanda"];
	}

	//hd-3625122 - fputti
	if (in_array($login_fabrica,array(171,187))) {
		$referencia_fabrica = $_POST["referencia_fabrica"];
	}

	$parametros_adicionais = new Json();


	if(in_array($login_fabrica, [169,170])){
		$parametros_adicionais->peca_original = $peca_original;
	}

	if ($login_fabrica == 123 && !empty($_POST["prev_chegada"])) {
		$prev_chegada_new = date("Y-m-d", strtotime(str_replace("/", "-", $_POST["prev_chegada"])));
		$parametros_adicionais->previsaoEntrega = $prev_chegada_new;
	}

	$arquivo = isset($_FILES["arquivos"]) ? $_FILES["arquivos"] : false;
    //echo "<pre>".print_r($_FILES, 1)."</pre>";exit;
	if (strlen($_POST["ipi"]) > 0) {
		$aux_ipi = "'". trim($_POST["ipi"]) ."'";
	} else {
		if(in_array($login_fabrica,array(6,163,177))){/*hd 3224*/
			$aux_ipi = "'0'" ;
		}else{
			$msg_erro = traduz("Digite o valor do IPI do produto.");
		}
	}

	$aux_ncm = (strlen($_POST["ncm"]) > 0) ? "'$ncm'" : 'NULL'; //MLG 2011-04-13 - Adicionar NCM ao cadastro de peça

	if (strlen($_POST["origem"]) > 0) {
		$aux_origem = "'". trim($_POST["origem"]) ."'";
	} else {
		$msg_erro = traduz("Selecione a origem do produto.");
	}

	if ($login_fabrica <> 20) {
		if (strlen($_POST["descricao"]) > 0) {
			$aux_descricao = "'". trim($_POST["descricao"]) ."'";
		}else{
			$msg_erro = traduz("Digite a descrição da peça.");
		}
	} else {
		if (strlen($_POST['descricao']) > 0) {
			$aux_descricao = $_POST['descricao'];
		} else if (strlen($_POST['descricao_idioma']) > 0) {
			$aux_descricao = $_POST['descricao_idioma'];
		} else {
			$msg_erro =traduz( "Digite uma descrição português ou espanhol para o produto.");
		}
	}

	if (strlen($_POST["referencia"]) > 0) {
		$aux_referencia = "'". substr(trim($_POST["referencia"]),0,20) ."'";
	} else {
		$msg_erro = traduz("Digite a referência da peça ");
	}

	if (strlen($_POST["voltagem"]) > 0) {
		$aux_voltagem = "'". trim($_POST["voltagem"]) ."'";
	} else {
		$aux_voltagem = "null";
	}

	if (strlen($_POST["estoque"]) > 0) {
		$aux_estoque = "'". trim($_POST["estoque"]) ."'";
	} else {
		$aux_estoque= "null";
	}

	if (strlen($_POST["unidade"]) > 0) {
		$aux_unidade = "'". trim($_POST["unidade"]) ."'";
	} else {
		$aux_unidade= "null";
	}

	if (strlen($_POST["peso"]) > 0) {
		$aux_peso = "'". trim($_POST["peso"]) ."'";
	} else {
		if($login_fabrica == 177) {
			$msg_erro .= traduz("Favor digitar o peso da peça");
		}else{
			$aux_peso = "null";
		}
	}

	if($login_fabrica == 101){
		$endereco_estoque = $_POST['endereco_estoque'];
	}

	if (strlen($_POST["marca"]) > 0) {
		$aux_marca = "'". trim($_POST["marca"]) ."'";
	} else {

		if (in_array($login_fabrica, [87])) {
			$msg_erro = "Selecione a empresa da peça";
		} else {
			$aux_marca = "null";
		}

	}

	if (strlen($_POST["informacoes"]) > 0) {
		$aux_informacoes = "'". trim($_POST["informacoes"]) ."'";
	}elseif ($login_fabrica == 151) {
		$aux_informacoes = "'". trim($_POST["informacoes_adicionais"]) ."'";
	} else {
		$aux_informacoes = "null";
	}

	if (strlen($_POST["posicao_site"]) > 0) {
		$aux_posicao_site =  trim($_POST["posicao_site"]) ;
		$aux_posicao_site = str_replace(",",".", $aux_posicao_site);
		$auxx_posicao_site = explode(".", $aux_posicao_site);
		$posicao_site_dig = $auxx_posicao_site[0];
		$aux_posicao_site =  "'".$auxx_posicao_site[0]."'";
	} else {
		$aux_posicao_site = "null";
	}

	if (strlen($_POST["liquidacao"]) > 0) {
		$aux_liquidacao = "'t'";
	} else {
		$aux_liquidacao = "null";
	}

	if (strlen($_POST["preco_peca"]) > 0) $aux_preco_compra = "'". trim($_POST["preco_peca"]) ."'";
	else $aux_preco_compra = "null";

	if (strlen($_POST["qtde_disparos"]) > 0) $aux_qtde_disparos = "'". trim($_POST["qtde_disparos"]) ."'";
	else $aux_qtde_disparos = "null";

	if (strlen($_POST["preco_compra"]) > 0) $aux_preco_compra = "'". trim($_POST["preco_compra"]) ."'";
	else $aux_preco_compra = "null";

	if (strlen($_POST["linha"]) > 0) $aux_linha = "'". trim($_POST["linha"]) ."'";
	else $aux_linha = "null";

	if (strlen($_POST["familia"]) > 0) $aux_familia = "'". trim($_POST["familia"]) ."'";
	else $aux_familia = "null";

	if (strlen($_POST["promocao_site"]) > 0) $aux_promocao_site = "'t'";
	else $aux_promocao_site = "'f'";

	/* 85 */
	if($login_fabrica != 85){
		if (strlen($_POST["at_shop"]) > 0) $aux_at_shop = "'t'";
		else $aux_at_shop = "'f'";
	}

	if (strlen($_POST["multiplo_site"]) > 0) $aux_multiplo_site = $multiplo_site;
	else $aux_multiplo_site = "null";

	/*if (strlen($_POST["qtde_minima_site"]) > 0)		$aux_qtde_minima_site = $qtde_minima_site;
	else							$aux_qtde_minima_site = "null";*/

	if (strlen($_POST["qtde_max_site"]) > 0) $aux_qtde_max_site = $qtde_max_site;
	else $aux_qtde_max_site = "null";

	if (strlen($_POST["qtde_minima_estoque"]) > 0) $aux_qtde_minima_estoque = $qtde_minima_estoque;
	else $aux_qtde_minima_estoque = "null";
	if (strlen($_POST["frete_gratis"]) > 0) $aux_frete_gratis = "'t'";
	else $aux_frete_gratis = "'f'";

	if (strlen($_POST["qtde_disponivel_inicial_site"]) > 0) $aux_qtde_disponivel_inicial_site = $qtde_disponivel_inicial_site;
	else $aux_qtde_disponivel_inicial_site = "null";

	if (strlen($data_inicial_liquidacao) > 0 AND $data_inicial_liquidacao <> "dd/mm/aaaa") $aux_data_inicial_liquidacao = fnc_formata_data_pg($data_inicial_liquidacao);
	else $aux_data_inicial_liquidacao = "null";


	if (strlen($_POST["qtde_disponivel_site"]) > 0) $aux_qtde_disponivel_site = $qtde_disponivel_site;
	else $aux_qtde_disponivel_site = "null";

	#HD 13429
	if (strlen($_POST["preco_anterior"]) > 0) $aux_preco_anterior = $preco_anterior;
	else $aux_preco_anterior = "0";

	if (strlen($_POST["gera_troca_produto"]) > 0) $aux_gera_troca_produto = "TRUE";
	else $aux_gera_troca_produto = "FALSE";

	if(strlen($_POST["numero_serie_peca"]) > 0) $aux_numero_serie_peca = "TRUE";
	else $aux_numero_serie_peca = "FALSE";

	$aux_preco_anterior = str_replace(",",".",$aux_preco_anterior);
	$aux_preco_anterior = number_format($aux_preco_anterior, 2, '.','');

	if (array_key_exists('status_disponibilidade', $_POST)) {
		$disponibilidade = getPost('status_disponibilidade', null);
		$previsaoEntrega = is_date(getPost('previsao_entrega', null));
	}

	if (strlen($_POST["multiplo"]) > 0) {
		$aux_multiplo = "'". trim($_POST["multiplo"]) ."'";
	}else{
		$msg_erro = traduz("Digite o multiplo do produto.");
	}

	/*
	FOI COLOCADA ESSA VALIDAÇÃO NO HD 106887
	ELIANE PEDIU PARA NÃO GRAVAR ZERO NO CAMPO
	FOI COLOCADA ESSA TRATATIVA PORQUE NÃO VALIDA O CAMPO NULL
	*/

	if($aux_multiplo_site=="null"){
		$aux_multiplo_site = 0;
	}

	if($aux_qtde_disponivel_site=="null"){
		$aux_qtde_disponivel_site = 0;
	}

	if($aux_multiplo_site > $aux_qtde_disponivel_site){
		$msg_erro = traduz("Qtde multipla por peça não pode ser maior que a Qtde Disponível.");
	}

	if($login_fabrica==3 || $login_fabrica==85){
		if($aux_multiplo_site==0){
			$aux_multiplo_site = "null";
		}

		if($aux_qtde_disponivel_site==0){
			$aux_qtde_disponivel_site = "null";
		}
	}

	$garantia_diferenciada = $_POST['garantia_diferenciada'];
	$aux_garantia_diferenciada = $garantia_diferenciada;
	if (strlen ($garantia_diferenciada) == 0)
		$aux_garantia_diferenciada = "null";

	$devolucao_obrigatoria = $_POST['devolucao_obrigatoria'];
	$aux_devolucao_obrigatoria = $devolucao_obrigatoria;
	if (strlen ($devolucao_obrigatoria) == 0)
		$aux_devolucao_obrigatoria = "f";

	if ($login_fabrica == 6) {
		$devolucao_estoque_fabrica = $_POST["devolucao_estoque_fabrica"];

		if($devolucao_obrigatoria == 't' and $devolucao_estoque_fabrica == 't'){
			$msg_erro .= traduz("Deve ser selecionado apenas uma opção entre 'Devolução Obrigatória' e 'Devolução Estoque Fábrica' ");
		}
	}

	$aux_kit = $_POST["kit"];
	if(strlen($kit) == 0){
		$aux_kit = "f";
	}

	$item_aparencia = $_POST['item_aparencia'];
	$aux_item_aparencia = $item_aparencia;
	if (strlen ($item_aparencia) == 0)
		$aux_item_aparencia = "f";

	$acumular_kit = $_POST['acumular_kit'];
	$aux_acumular_kit = $acumular_kit;
	if (strlen ($acumular_kit) == 0)
		$aux_acumular_kit = "f";

	$retorna_conserto = $_POST['retorna_conserto'];
	$aux_retorna_conserto = $retorna_conserto;
	if (strlen ($retorna_conserto) == 0)
		$aux_retorna_conserto = "f";

	$intervencao_carteira = $_POST['intervencao_carteira'];
	$aux_intervencao_carteira = $intervencao_carteira;
	if (strlen ($intervencao_carteira) == 0)
		$aux_intervencao_carteira = "f";

	if (strlen($_POST["mao_de_obra_diferenciada"]) > 0) {
		$aux_mao_de_obra_diferenciada = "'". $_POST["mao_de_obra_diferenciada"] ."'";
	}else{
		$aux_mao_de_obra_diferenciada = "null";
	}

	$bloqueada_garantia = $_POST['bloqueada_garantia'];
	$aux_bloqueada_garantia = $bloqueada_garantia;
	if (strlen ($bloqueada_garantia) == 0)
		$aux_bloqueada_garantia = "f";

	$remessa_garantia = $_POST['remessa_garantia'];
	$aux_remessa_garantia = $remessa_garantia;
	if (strlen ($remessa_garantia) == 0)
		$aux_remessa_garantia = "f";

	$remessa_garantia_compressor = $_POST['remessa_garantia_compressor'];
	$aux_remessa_garantia_compressor = $remessa_garantia_compressor;
	if (strlen ($remessa_garantia_compressor) == 0)
		$aux_remessa_garantia_compressor = "f";


	if ($login_fabrica == 30) {
		if ($remessa_garantia_compressor=='t' and $remessa_garantia=='t') {
			$msg_erro .= traduz("Por favor Selecione Remessa em Garantia ou Remessa em Garantia Compressor, a mesma peça não pode ser marcado nas duas opções.");
		}
	}

	$acessorio = $_POST['acessorio'];
	$aux_acessorio = $acessorio;
	if (strlen ($acessorio) == 0)
		$aux_acessorio = "f";

	$aguarda_inspecao = $_POST['aguarda_inspecao'];
	$aux_aguarda_inspecao = $aguarda_inspecao;
	if (strlen ($aguarda_inspecao) == 0)
		$aux_aguarda_inspecao = "f";

	$peca_critica = $_POST['peca_critica'];
	$aux_peca_critica = $peca_critica;
	if (strlen ($peca_critica) == 0)
		$aux_peca_critica = "f";

	if ($login_fabrica == 42) {
		$peca_monitorada = $_POST["peca_monitorada"];
		$email_peca_monitorada = $_POST["email_peca_monitorada"];

		if (!empty($peca_monitorada)) {
			if (empty($email_peca_monitorada)) {
				$msg_erro .= traduz("Informe o E-mail. <br />");
			} else {
				if (!filter_var(trim($email_peca_monitorada),FILTER_VALIDATE_EMAIL)) {
                    $msg_erro .= traduz("O email informado não é válido! <br />");
                }
			}
		}
	}

	$produto_acabado = $_POST['produto_acabado'];
	$aux_produto_acabado = $produto_acabado;
	if (strlen($produto_acabado) == 0)
		$aux_produto_acabado = "f";

	$reembolso = $_POST['reembolso'];
	$aux_reembolso = $reembolso;
	if (strlen($reembolso) == 0)
		$aux_reembolso = "f";


	// HD 1861 IGOR - será utilizada para marcar peças de mero desgaste como por exemplo o carvão.
	$mero_desgaste = $_POST['mero_desgaste'];
	$aux_mero_desgaste = $mero_desgaste;
	if (strlen($mero_desgaste) == 0)
		$aux_mero_desgaste = "f";
	//echo "mero desg: $aux_mero_desgaste - $mero_desgaste";

	//hd 47129
	if (in_array($login_fabrica, array(11,50, 172))) {
		$peca_unica_os = $_POST['peca_unica_os'];
		if (strlen($peca_unica_os) == 0)
			$peca_unica_os = "f";
	}

	$promocao_site = ($aux_promocao_site == "'t'") ? $promocao_site = "t" : "";

	if($login_fabrica == 85){

		$preco = number_format($_POST["preco_peca"], 2, ',', '');

		if($aux_liquidacao != "null" && (strlen($_POST["preco_peca"]) == 0 || $preco == "0,00")){
			$liquidacao = 't';
			$msg_erro .= traduz("Por favor insira o valor do Preço em Liquidação <br />");
		}else{
			$liquidacao = "";
		}

		$aux_at_shop = (strlen($_POST['mostrar_liquidacao_posto']) > 0) ? "'t'" : "'f'";
		$mostrar_liquidacao_posto = (strlen($_POST['mostrar_liquidacao_posto']) > 0) ? "t" : "f";

		if($aux_liquidacao == "null" && $aux_at_shop == "'t'"){
			$msg_erro .= traduz("Por favor insira a opção de Peça em Liquidação e o valor do Preço em Liquidação <br />");
		}

	}

	if ($login_fabrica == 35) {
		$po_peca = $_POST["po_peca"];


		if (strlen($po_peca) == 0) {
			$po_peca = "false";
		}

	}

	if (in_array($login_fabrica, array(184,200))) {
		if ($_POST['ativo'] == 't') {
			$ativo = $_POST['ativo'];
		} else {
			$msg_erro .= "<br> Por favor Selecione o campo ATIVO.";
		}
	}

	if ($login_fabrica == 186) {
		if (strlen($_POST['xendereco']) > 0) {
			if (!is_object($parametros_adicionais) || empty($parametros_adicionais)){
				$parametros_adicionais = new Json($parametros_adicionais);
			}
			$parametros_adicionais->endereco = $_POST['xendereco'];
		}
	}

	//HD 78530
	/**
	 * @since HD 765741
	 */
	if (in_array($login_fabrica, $arr_login_fabrica_ativo) or $login_fabrica > 85 or $login_fabrica == 1) {
		if (!empty($_POST['ativo'])) {
			$ativo = $_POST['ativo'];
		} else {
			$ativo = "f";
		}
		$update_ativo = "ativo = '$ativo', ";
	} else {
		$update_ativo = '';
	}

	if ($login_fabrica == 30) {
		if (!empty($uso_interno)) {
			$uso_interno = "t";
			$ativo = "f";
			$update_ativo = "ativo = '$ativo', ";
		}
		$parametros_adicionais->uso_interno = $uso_interno;
	}

	if ($login_fabrica == 1) {

		$item_revenda = ($_POST["item_revenda"] == "t") ? $_POST["item_revenda"] : "f";
		//echo $item_revenda;exit;
		//if ($item_revenda == 't') {
		//echo "item_revenda == 't' <br>";
		if(strlen($peca) > 0){
			//echo "peca > 0 <br>";
			// Update - alterar nas listas Basicas
			$sql_lb = "SELECT lista_basica, parametros_adicionais FROM tbl_lista_basica WHERE peca = $peca;";
			$res_lb = pg_query($con,$sql_lb);
			//echo nl2br($sql_lb);exit;
			if (pg_num_rows($res_lb) > 0) {
				//echo "pg_num_rows(res_lb) > 0 <br>";

				$info_lb = pg_fetch_all($res_lb);
				pg_query($con,'BEGIN');

				foreach ($info_lb as $key_lb => $value_lb) {
					//echo "foreach res <br>";

					$lista_basica_lb = $value_lb['lista_basica'];
					$parametros_adicionais_lb = new Json($value_lb['parametros_adicionais'], false);

					if ($item_revenda == 't') {

						if (!array_key_exists('item_revenda', $parametros_adicionais_lb)) {
							$parametros_adicionais_lb->item_revenda = $item_revenda;
						}
					}else{
						if (array_key_exists('item_revenda', $parametros_adicionais_lb)) {
							unset($parametros_adicionais_lb->item_revenda);
						}
					}

					$sql_up = "UPDATE tbl_lista_basica
									SET parametros_adicionais = '$parametros_adicionais_lb'
									WHERE lista_basica = $lista_basica_lb
										AND fabrica = $login_fabrica;";
					$res_up = pg_query($con,$sql_up);
					//echo pg_last_error();
					//echo "<br>";
				}
				if (strlen(pg_last_error()) > 0) {
					pg_query($con,'ROLLBACK');
					$msg_erro .= traduz('Erro ao Alterar Item Revenda!<br>');
				}else{
					pg_query($con,'COMMIT');
				}
			}

			$sql_pp = "SELECT parametros_adicionais FROM tbl_peca WHERE peca = $peca;";
			$res_pp = pg_query($con,$sql_pp);

			if (pg_num_rows($res_pp) > 0) {
				$parametros_adicionais = new Json(pg_fetch_result($res_pp, 0, parametros_adicionais), true);
			}
		}
		$parametros_adicionais->item_revenda = $item_revenda;
		// echo "<hr>fim parametros_adicionais_lb";
		// print_r($parametros_adicionais);
		// exit;
	}

	if ($login_fabrica == 3) {
		$serial_lcd   = ($_POST["serial_lcd"] == "t") ? $_POST["serial_lcd"] : "f";
		$upload_fotos = ($_POST["upload_fotos"] == "t") ? $_POST["upload_fotos"] : "f";

		if ($upload_fotos == "t") {
			$qtde_fotos = $_POST["qtde_fotos"];
		}

		$parametros_adicionais->serial_lcd   = $serial_lcd;
		$parametros_adicionais->upload_fotos = $upload_fotos;

		if ($upload_fotos == "t") {
			$parametros_adicionais->qtde_fotos = $qtde_fotos;
		}
	}

	if ($anexo_peca_os) {
		if ($anexo_os == 't') {
			$parametros_adicionais->anexo_os = 't';
			$qaxpos = (int)$_POST['fabrica_qtde_anexo_peca_os'];

			if ($qaxpos > $fabrica_qtde_anexo_peca_os) {
				$msg_erro .= traduz("Quantidade de anexos por peça permitida .");
			}
			if ($qaxpos > 0 and $fabrica_qtde_anexo_peca_os > 0){
				$parametros_adicionais->__set('qtde_anexos',(string) $qaxpos);
			}
		} else {
			$parametros_adicionais->anexo_os = false;
		}
	}
	if (isFabrica(42)) {
			$parametros_adicionais = new Json($parametros_adicionais);
		$parametros_adicionais->add([
			'status' => $disponibilidade,
			'previsaoEntrega' => is_date($previsaoEntrega, 'EUR', 'CDATE') ? : null
		]);
		// pre_echo($parametros_adicionais, 'PAPECA', true);
	}

	if (strlen($faturada_manualmente) == 0) {
		$faturada_manualmente = "f";
	}
	$pre_selecionada = $_POST['pre_selecionada'];
	$aux_pre_selecionada = $pre_selecionada;
	if (strlen($pre_selecionada) == 0) $aux_pre_selecionada = "f";

	$placa = $_POST['placa'];
	$aux_placa = $placa;
	if (strlen($placa) == 0) $aux_placa = "f";

	$peca_pai = $_POST['peca_pai'];
	$aux_peca_pai = $peca_pai;
	if (strlen($peca_pai) == 0) $aux_peca_pai = "f";


	$cobrar_servico = $_POST['cobrar_servico'];
	$aux_cobrar_servico = $cobrar_servico;
	if (strlen ($cobrar_servico) == 0)
		$aux_cobrar_servico = "f";

	//HD: 2453 - DYNACOM - MAO DE OBRA DIFERENCIA PEÇA, POSTO,ADMIN
	//PEÇA
	if (strlen($_POST["mo_peca"]) > 0) {
		$mo_peca = trim($_POST["mo_peca"]);

		$mo_peca = str_replace( ',', '.', $mo_peca);
		$mo_peca = number_format($mo_peca, 2, '.','');

	}else{
		$mo_peca = "null";
	}

	//POSTO
	if (strlen($_POST["mo_posto"]) > 0) {
		$mo_posto = trim($_POST["mo_posto"]);

		$mo_posto = str_replace( '.', '', $mo_posto);
		$mo_posto = str_replace( ',', '.', $mo_posto);
		$mo_posto = number_format($mo_posto, 2, '.','');

	}else{
		$mo_posto = "null";
	}

	//ADMIN
	if (strlen($_POST["mo_admin"]) > 0) {
		$mo_admin = trim($_POST["mo_admin"]);

		$mo_admin = str_replace( '.', '', $mo_admin);
		$mo_admin = str_replace( ',', '.', $mo_admin);
		$mo_admin = number_format($mo_admin, 2, '.','');

	}else{
		$mo_admin = "null";
	}

	$troca_obrigatoria = $_POST['troca_obrigatoria'];
	$aux_troca_obrigatoria = $troca_obrigatoria;
	if (strlen($troca_obrigatoria) == 0)
		$aux_troca_obrigatoria = "f";

	$peca_critica_venda = $_POST['peca_critica_venda'];
	$aux_peca_critica_venda = (strlen($peca_critica_venda) == 0) ? "'f'" : "'".$peca_critica_venda."'";

	$bloqueada_venda = $_POST['bloqueada_venda'];
	$aux_bloqueada_venda = (strlen($bloqueada_venda) == 0) ? "'f'" : "'".$bloqueada_venda."'";

	$controla_saldo          	  = $_POST["controla_saldo"];
	if (strlen($controla_saldo) == 0) $controla_saldo = "f";

	#HD 16207
	if ( !in_array($login_fabrica, array(1,134)) and !empty($aux_referencia)){
		if (strlen($peca) > 0) {
			$sql_referencia = "AND tbl_peca.peca <> $peca ";
		}

		if ($login_fabrica == 87 && !empty($_POST["marca"])) {
			$cond_marca = " AND marca = ".$_POST["marca"];
		}

		$sql = "SELECT peca,referencia,descricao
			FROM tbl_peca
			WHERE fabrica  = $login_fabrica
			AND referencia = $aux_referencia
			$sql_referencia
			$cond_marca";
		$res = @pg_query($con,$sql);
		if (pg_num_rows($res) > 0){
			if ($login_fabrica == 87 && !empty($cond_marca)) {
				$msg_erro .= traduz("Não foi possível atualizar pois existe uma outra peça com a mesma referência e empresa");
			} else {
				$msg_erro .= traduz("Já existe uma peça cadastrada com esta referência.");
			}
		}
	}

	if ( in_array($login_fabrica, array(134)) and !empty($aux_referencia)){
		if (strlen($peca) > 0) {
			$sql_referencia = "AND tbl_peca.peca <> $peca ";
		}
		$sql = "SELECT peca,referencia,descricao
			FROM tbl_peca
			WHERE fabrica  = $login_fabrica
			AND upper(fn_retira_especiais(referencia)) = upper(fn_retira_especiais($aux_referencia))
			$sql_referencia";
		$res = @pg_query($con,$sql);
		if (pg_num_rows($res) > 0){
			$msg_erro .= traduz("Já existe uma peça cadastrada com esta referência.");
		}
	}

	if ($login_fabrica == 10){
		if (strlen($peca) == 0 AND strlen($posicao_site_dig) > 0) {
			# HD - 48163 Altera as posições de prioridade_site.
			#   Em primeiro lugar, se vai inserir uma Prioridade nova
			#    as demais descem um
			$sqlAP = "SELECT posicao_site FROM tbl_peca WHERE fabrica = $login_fabrica
					AND posicao_site IS NOT NULL
					AND posicao_site = $posicao_site_dig";
			$resAP = pg_exec($con,$sqlAP);

			if(pg_numrows($resAP) > 0){
				$sqlAPa = "SELECT posicao_site, peca FROM tbl_peca WHERE fabrica = $login_fabrica
					AND posicao_site IS NOT NULL
					AND posicao_site > $posicao_site_dig
					ORDER BY posicao_site";
				$resAPa = pg_exec($con,$sqlAPa);
					if(pg_numrows($resAPa) > 0){
						for ($j; $j < pg_numrows($resAPa); $j++){
							$peca_atualiza = pg_fetch_result ($resAPa,$j,peca);
							$posicao_site_nova =  pg_fetch_result ($resAPa,$j,posicao_site) + 1;
							$sqlAPx = "UPDATE tbl_peca SET posicao_site = $posicao_site_nova
									WHERE peca = $peca_atualiza AND fabrica = $login_fabrica";
							$resAPx = pg_exec($con,$sqlAPx);
						}
					}
			}
		}

		if (strlen($peca) > 0 && ! empty($posicao_site_dig)) {
			# Segundo caso, alterar uma peça que já tem Prioridade gravada.
			$sqlAP = "SELECT posicao_site FROM tbl_peca WHERE fabrica = $login_fabrica
					AND posicao_site IS NOT NULL
					AND posicao_site = $posicao_site_dig";
			$resAP = pg_exec($con,$sqlAP);

			if(pg_numrows($resAP) > 0){
				$sqlPosPeca = "SELECT posicao_site FROM tbl_peca
						WHERE peca = $peca
						AND fabrica = $login_fabrica";
				$resPosPeca = pg_exec ($con,$sqlPosPeca);

				$posicao_site_peca = pg_fetch_result ($resPosPeca,0,posicao_site);

				if ($posicao_site_peca > $posicao_site_dig){
					# Tira-se a peça de uma Prioridade maior para uma menor
					#   Todas as que estão entre estas duas primeirades
					#    descem uma posição
					$sqlAPb = "SELECT posicao_site, peca FROM tbl_peca WHERE fabrica = $login_fabrica
						AND posicao_site IS NOT NULL
						AND posicao_site < $posicao_site_peca
						AND posicao_site > $posicao_site_dig
						ORDER BY posicao_site";
					$resAPb = pg_exec ($con,$sqlAPb);
					if(pg_numrows($resAPb) > 0){
						for ($j; $j < pg_numrows($resAPb); $j++){
							$peca_atualiza = pg_fetch_result ($resAPb,$j,peca);
							$posicao_site_nova =  pg_fetch_result ($resAPb,$j,posicao_site) + 1;
							$sqlAPx = "UPDATE tbl_peca SET posicao_site = $posicao_site_nova
									WHERE peca = $peca_atualiza AND fabrica = $login_fabrica";
							$resAPx = pg_exec($con,$sqlAPx);
						}
					}
				}else{
					# Aqui o contrário
					$sqlAPb = "SELECT posicao_site, peca FROM tbl_peca WHERE fabrica = $login_fabrica
						AND posicao_site IS NOT NULL
						AND posicao_site > $posicao_site_peca
						AND posicao_site < $posicao_site_dig
						ORDER BY posicao_site";
					$resAPb = pg_exec ($con,$sqlAPb);
					if(pg_numrows($resAPb) > 0){
						for ($j; $j < pg_numrows($resAPb); $j++){
							$peca_atualiza = pg_fetch_result ($resAPb,$j,peca);
							$posicao_site_nova =  pg_fetch_result ($resAPb,$j,posicao_site) - 1;
							$sqlAPx = "UPDATE tbl_peca SET posicao_site = $posicao_site_nova
									WHERE peca = $peca_atualiza AND fabrica = $login_fabrica";
							$resAPx = pg_exec($con,$sqlAPx);
						}
					}
				}
			}
		}
	}

	if (isset($anexo_peca_os)) {
		$anexo_os = $_POST["anexo_os"];

		if (!strlen($anexo_os)) {
			$anexo_os = "f";
		}

		if (!is_object($parametros_adicionais)) {
			$parametros_adicionais = new Json();
		}
		$parametros_adicionais->anexo_os = $anexo_os;
	}

    if (array_key_exists('item_balcao', $_POST)) {
        $parametros_adicionais->item_balcao = $_POST['item_balcao'];
    }

    if (array_key_exists('embalagens', $_POST)) {
        $parametros_adicionais->embalagens = $_POST['embalagens'];
    }

	if (strlen ($msg_erro) == 0){
		if (strlen($peca) == 0) {
			$auditorLog = new AuditorLog('insert');

            $tpAuditor = "insert";
        } else {
            $auditorLog = new AuditorLog();
            $auditorLog->retornaDadosTabela('tbl_peca', array('peca'=>$peca, 'fabrica'=>$login_fabrica));

            $tpAuditor = "update";
        }

		$res = pg_query($con,'BEGIN TRANSACTION');
		$atualiza_peca_icms = false;
		$aux_descricao      = str_replace('\'','',$aux_descricao);
		$descricao_short    = pg_escape_string($aux_descricao);
		$aux_descricao      = pg_escape_string($aux_descricao);

		$etiqueta = $_POST['etiqueta'] !== 't' ? 'f' : 't'; // HD 753391

		if($login_fabrica == 6){
			$parametros_adicionais->devolucao_estoque_fabrica = $devolucao_estoque_fabrica;
		}

        if(in_array($login_fabrica,array(138,157)) && (isset($_REQUEST['parametros_adicionais']['tipo_peca']) || isset($_REQUEST['fabrica_qtde_anexo_peca_os']['qtde_anexos']))) {
			if (!is_object($parametros_adicionais))
				$parametros_adicionais = new Json($parametros_adicionais);

			$parametros_adicionais->tipo_peca   = $_REQUEST['parametros_adicionais']['tipo_peca'];
			$parametros_adicionais->qtde_anexos = $_REQUEST['fabrica_qtde_anexo_peca_os']['qtde_anexos'];
        }

		if(in_array($login_fabrica, [11,104,172])){
			$parametros_adicionais->qtde_demanda = $qtde_demanda;
		}

		if ($login_fabrica == 171){
			if (!is_object($parametros_adicionais) || empty($parametros_adicionais)){
				$parametros_adicionais = new Json($parametros_adicionais);
			}	
			if (isset( $_POST['apresentacao'])) {
		        $parametros_adicionais->apresentacao = $_POST['apresentacao'];
		    }
		    if (isset( $_POST['descricao_detalhada'])) {
		        $parametros_adicionais->descricao_detalhada = $_POST['descricao_detalhada'];
		    }
		    if (isset( $_POST['marca_detalhada'])) {
		        $parametros_adicionais->marca_detalhada = $_POST['marca_detalhada'];
		    }
		    if (isset( $_POST['emb'])) {
		        $parametros_adicionais->emb = $_POST['emb'];
		    }
		    if (isset( $_POST['categoria'])) {
		        $parametros_adicionais->categoria = $_POST['categoria'];
		    }
		    if (isset( $_POST['ncm'])) {
		        $parametros_adicionais->ncm = $_POST['ncm'];
		    }
		    if (isset( $_POST['ii'])) {
		        $parametros_adicionais->ii = $_POST['ii'];
		    }
		    if (isset( $_POST['alt'])) {
		        $parametros_adicionais->alt = $_POST['alt'];
		    }
		    if (isset( $_POST['larg'])) {
		        $parametros_adicionais->larg = $_POST['larg'];
		    }
		    if (isset( $_POST['comp'])) {
		        $parametros_adicionais->comp = $_POST['comp'];
		    }
		    if (isset( $_POST['peso'])) {
		        $parametros_adicionais->peso = $_POST['peso'];
		    }
		    if (isset( $_POST['cod_barra'])) {
		        $parametros_adicionais->cod_barra = $_POST['cod_barra'];
		    }
		    if (isset( $_POST['custo_cip'])) {
		        $parametros_adicionais->custo_cip = $_POST['custo_cip'];
		    }
		}

		if (in_array($login_fabrica, [177])) {
			if (!is_object($parametros_adicionais) || empty($parametros_adicionais)){
				$parametros_adicionais = new Json($parametros_adicionais);
			}	
			
			if (!empty($_POST['lote'])) {
				$lote = $_POST['lote'];
			} else {
				$lote = "f";
			}

			if (!empty($_POST['caneca'])) {
				$caneca = $_POST['caneca'];
			} else {
				$caneca = "f";
			}

			$parametros_adicionais->lote   = $lote;
			$parametros_adicionais->caneca = $caneca;
		}

		if ($login_fabrica == 42) {
			if (!is_object($parametros_adicionais) || empty($parametros_adicionais)){
				$parametros_adicionais = new Json($parametros_adicionais);
			}

			if (!empty($peca_monitorada) && !empty($email_peca_monitorada)) {
				$parametros_adicionais->peca_monitorada = $peca_monitorada;
				$parametros_adicionais->email_peca_monitorada = $email_peca_monitorada;
			}
		}

		if($login_fabrica == 125){
			if (!is_object($parametros_adicionais) || empty($parametros_adicionais)){
				$parametros_adicionais = new Json($parametros_adicionais);
			}

			$parametros_adicionais->kit = $aux_kit;

		}

		if($login_fabrica == 190){

			if (!is_object($parametros_adicionais) || empty($parametros_adicionais)){
				$parametros_adicionais = new Json($parametros_adicionais);
			}

			$parametros_adicionais->consumiveis = $_POST["consumiveis"];

		}

		$op_insert = 0;
		if (strlen($peca) == 0) {
			$op_insert = 1;
			###INSERE NOVO REGISTRO
			$ipi_agregado = "null";
			if ($login_fabrica == 1) $ipi_agregado = 1 + ($ipi /100) ;

			if(in_array($login_fabrica, array(151))){
				$descricao_short = filter_input(INPUT_POST, "descricao", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

				if(strripos($descricao_short,"'") == true){
					$descricao_short = str_replace("'", "''", $descricao_short);
				}
			}

			$sql = "INSERT INTO tbl_peca (
						fabrica,
						referencia,
						descricao,
						descricao_estendida,
						ipi,
						ipi_agregado,
						origem,
						estoque,
						unidade,
						peso,
						classificacao_fiscal,
						multiplo,
						garantia_diferenciada,
						devolucao_obrigatoria,
						item_aparencia,
						acumular_kit,
						linha_peca,
						familia_peca,
						pre_selecionada,
						placa,
						peca_pai,
						retorna_conserto,
						intervencao_carteira,
						promocao_site,
						at_shop,
						multiplo_site,
						qtde_max_site,
						qtde_disponivel_inicial_site,
						qtde_disponivel_site,
						qtde_minima_estoque,
						frete_gratis,
						posicao_site,
						liquidacao,
						gera_troca_produto,
						";
			#HD 101357 acrescentado campo numero_serie_peca

			if(in_array($login_fabrica, array(3, 85,167,175,203))) {
				$sql .= "numero_serie_peca               ,";
			}

			//hd-3625122 - fputti
			if(in_array($login_fabrica, array(171,187))) {
				$sql .= "referencia_fabrica,";
			}

			if ($login_fabrica == 14) {
				$sql .= "mao_de_obra_troca, ";
			}
			//hd 47129
			if (in_array($login_fabrica, array(11,50, 172))) {
				$sql .= "peca_unica_os, ";
			}
			if($login_fabrica == 101){
				$sql .= "localizacao, ";
			}

			// MLG - Mudei. Para não esquecer, caso algum outro parâmetro seja adicionado...
			if (count($parametros_adicionais->data)) {
				$sql .= " parametros_adicionais, ";
			} else if (in_array($login_fabrica, [177])) {
				$sql .= " parametros_adicionais, ";
			}

			if (in_array($login_fabrica, array(11, 172))) {
				$array_fabricas = $_POST["selected_grupos"];
				
				if (empty($array_fabricas)) {
					$aux_fabrica   = $login_fabrica;
					$outra_fabrica = false;
				} else {
					$aux_fabrica = $array_fabricas[0];

					if (count($array_fabricas) <= 1) {
						$outra_fabrica = false;
					} else {
						$outra_fabrica = true;
					}
				}
			}

			$sql .="		bloqueada_garantia,
						remessa_garantia,
						remessa_garantia_compressor,
						acessorio,
						aguarda_inspecao,
						peca_critica,
						marca,
						informacoes,
						preco_compra,
						produto_acabado,
						reembolso,
						mero_desgaste,
						troca_obrigatoria,
						mo_peca,
						mo_posto,
						mo_admin,
						admin,
						preco_anterior,
						data_atualizacao,
						cobrar_servico,
						faturada_manualmente,
						data_inicial_liquidacao,
						peca_critica_venda,
						bloqueada_venda,
						ncm,
						etiqueta,
						reducao,
						controla_saldo
					) VALUES (
						". ((in_array($login_fabrica, array(11, 172)) ? $aux_fabrica : $login_fabrica)) .",
						trim($aux_referencia),
						'{$descricao_short}',
						'{$aux_descricao}',
						fnc_so_numeros($aux_ipi)::integer,
						$ipi_agregado,
						$aux_origem,
						fnc_limpa_moeda($aux_estoque),
						$aux_unidade,
						fnc_limpa_moeda($aux_peso),
						'$classificacao_fiscal',
						'$multiplo',
						$aux_garantia_diferenciada,
						'$aux_devolucao_obrigatoria',
						'$aux_item_aparencia',
						'$aux_acumular_kit',
						$aux_linha,
						$aux_familia,
						'$aux_pre_selecionada',
						'$aux_placa',
						'$aux_peca_pai',
						'$aux_retorna_conserto',
						'$aux_intervencao_carteira',
						".(($login_fabrica == 35) ? $po_peca : $aux_promocao_site)."               ,
						$aux_at_shop,
						$aux_multiplo_site,
						$aux_qtde_max_site,
						$aux_qtde_disponivel_inicial_site,
						$aux_qtde_disponivel_site,
						$aux_qtde_minima_estoque,
						$aux_frete_gratis,
						$aux_posicao_site,
						$aux_liquidacao,
						$aux_gera_troca_produto,";
			#HD 101357 acrescentado campo numero_serie_peca

			if(in_array($login_fabrica, array(3,85,167,175,203))) {
				$sql .= "$aux_numero_serie_peca,";
			}

			//hd-3625122 - fputti
			if(in_array($login_fabrica, array(171,187))) {
				$sql .= "'$referencia_fabrica',";
			}

			if ($login_fabrica == 14) {
				$sql .= "fnc_limpa_moeda($aux_mao_de_obra_diferenciada), ";
			}
			//hd 47129
			if (in_array($login_fabrica, array(11,50, 172))) {
				$sql .= "'$peca_unica_os', ";
			}
			if($login_fabrica == 101){
				$sql .= "'$endereco_estoque', ";
			}

			if (count($parametros_adicionais->data)) {
				$sql .= " '$parametros_adicionais',\n";
			} else if (in_array($login_fabrica, [177])) {
				$sql .= " '$parametros_adicionais',\n";
			}

			$sql .= "	'$aux_bloqueada_garantia',
						'$aux_remessa_garantia',
						'$aux_remessa_garantia_compressor',
						'$aux_acessorio',
						'$aux_aguarda_inspecao',
						'$aux_peca_critica',
						$aux_marca,
						$aux_informacoes,
						$aux_preco_compra,
						'$aux_produto_acabado',
						'$aux_reembolso',
						'$aux_mero_desgaste',
						'$aux_troca_obrigatoria',
						$mo_peca,
						$mo_posto,
						$mo_admin,
						$login_admin,
						$aux_preco_anterior,
						CURRENT_TIMESTAMP,
						'$aux_cobrar_servico',
						'$faturada_manualmente',
						$aux_data_inicial_liquidacao,
						$aux_peca_critica_venda,
						$aux_bloqueada_venda,
						$aux_ncm,
						'$etiqueta',
						$aux_qtde_disparos,
						'$controla_saldo'
					);";
		} else {
			###ALTERA REGISTRO
			$ipi_agregado = "null";
			if ($login_fabrica == 1) $ipi_agregado = 1 + ($ipi /100) ;

			if ($login_fabrica == 3 || $login_fabrica==85){
				$sqlx = "SELECT qtde_disponivel_inicial_site
							FROM tbl_peca
							WHERE tbl_peca.fabrica = $login_fabrica
							AND   tbl_peca.peca    = $peca";
				$resx = pg_exec($con, $sqlx);

				if(pg_numrows($resx)>0){
					$xqtde_disponivel_inicial_site = pg_fetch_result($resx, 0, qtde_disponivel_inicial_site);

					if($aux_qtde_disponivel_site > $xqtde_disponivel_inicial_site){
						$aux_qtde_disponivel_inicial_site = $aux_qtde_disponivel_site;
					}
				}
			}

			if(in_array($login_fabrica, array(151))){
				$descricao_short = filter_input(INPUT_POST, "descricao", FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

				if(strripos($descricao_short,"'") == true){
					$descricao_short = str_replace("'", "''", $descricao_short);
				}
			}

			$sql = "UPDATE tbl_peca SET
						referencia            = TRIM($aux_referencia),
						descricao             = '{$descricao_short}',
						descricao_estendida   = '{$aux_descricao}',
						ipi                   = fnc_so_numeros($aux_ipi)::integer,
						ipi_agregado          = $ipi_agregado,
						origem                = $aux_origem,
						estoque               = fnc_limpa_moeda($aux_estoque),
						unidade               = $aux_unidade,
						peso                  = fnc_limpa_moeda($aux_peso),
						classificacao_fiscal  = '$classificacao_fiscal',
						multiplo              = '$multiplo',
						garantia_diferenciada = $aux_garantia_diferenciada,
						devolucao_obrigatoria = '$aux_devolucao_obrigatoria',
						item_aparencia        = '$aux_item_aparencia',
						acumular_kit          = '$aux_acumular_kit',
						linha_peca            = $aux_linha,
						familia_peca          = $aux_familia,
						retorna_conserto      = '$aux_retorna_conserto',
						intervencao_carteira  = '$aux_intervencao_carteira',
						posicao_site          = $aux_posicao_site,
						liquidacao            = $aux_liquidacao,
						promocao_site         = ".(($login_fabrica == 35) ? $po_peca : $aux_promocao_site)."               ,
						at_shop               = $aux_at_shop,
						multiplo_site         = $aux_multiplo_site,
						qtde_max_site         = $aux_qtde_max_site,
						qtde_disponivel_inicial_site  = $aux_qtde_disponivel_inicial_site,
						qtde_disponivel_site  = $aux_qtde_disponivel_site,
						qtde_minima_estoque   = $aux_qtde_minima_estoque,
						frete_gratis          = $aux_frete_gratis,
						numero_serie_peca     = $aux_numero_serie_peca,
						gera_troca_produto    = $aux_gera_troca_produto,";

    // echo is_object($parametros_adicionais);
    // var_dump($parametros_adicionais);
    // error_reporting(E_ALL);
    // echo "ANTES DO JSONo<br>" . $parametros_adicionais;
			if (count($parametros_adicionais->data)) {
				$sql .= "\n   parametros_adicionais = '$parametros_adicionais',\n";
            
            } else if (in_array($login_fabrica, array(171,177))){
            		if ($parametros_adicionais != '' || !empty($parametros_adicionais)){
            			$sql .= "\n   parametros_adicionais = '$parametros_adicionais',\n";
            		}
            
            } elseif(in_array($login_fabrica, [169,170])){
            	if ($parametros_adicionais != '' || !empty($parametros_adicionais)){
        			$sql .= "\n   parametros_adicionais = '$parametros_adicionais',\n";
        		}
            } else {
                $sql .= ' parametros_adicionais = NULL, ';
            }
            
			//hd-3625122 - fputti
			if(in_array($login_fabrica, array(171,187))) {
				$sql .= "referencia_fabrica='$referencia_fabrica',";
			}

			if ($login_fabrica == 14) {
				$sql .= "	mao_de_obra_troca     = fnc_limpa_moeda($aux_mao_de_obra_diferenciada), ";
			}
			//hd 474129
			if (in_array($login_fabrica,array(11,50,172))) {
				$sql .= "	peca_unica_os     = '$peca_unica_os', ";
			}

			if($login_fabrica == 101){
				$sql .= "localizacao = '$endereco_estoque', ";
			}

			$sql .= " 		$update_ativo
						bloqueada_garantia          = '$aux_bloqueada_garantia',
						remessa_garantia            = '$aux_remessa_garantia',
						remessa_garantia_compressor = '$aux_remessa_garantia_compressor',
						acessorio                   = '$aux_acessorio',
						aguarda_inspecao            = '$aux_aguarda_inspecao',
						peca_critica                = '$aux_peca_critica',
						marca                       = $aux_marca,
						informacoes                 = $aux_informacoes,
						preco_compra                = $aux_preco_compra,
						produto_acabado             = '$aux_produto_acabado',
						pre_selecionada             = '$aux_pre_selecionada',
						placa                       = '$aux_placa',
						peca_pai                    = '$aux_peca_pai',
						reembolso                   = '$aux_reembolso',
						mero_desgaste               = '$aux_mero_desgaste',
						troca_obrigatoria           = '$aux_troca_obrigatoria',
						mo_peca                     = $mo_peca,
						mo_posto                    = $mo_posto,
						mo_admin                    = $mo_admin,
						admin                       = $login_admin,
						preco_anterior              = $aux_preco_anterior,
						data_atualizacao            = CURRENT_TIMESTAMP,
						faturada_manualmente        = '$faturada_manualmente',
						cobrar_servico              = '$aux_cobrar_servico',
						data_inicial_liquidacao     = $aux_data_inicial_liquidacao,
						peca_critica_venda          = $aux_peca_critica_venda,
						bloqueada_venda             = $aux_bloqueada_venda,
						ncm                         = $aux_ncm,
						etiqueta                    = '$etiqueta',
						reducao 					= $aux_qtde_disparos,
						controla_saldo              = '$controla_saldo'
				FROM tbl_fabrica
			";

			if (in_array($login_fabrica, array(11, 172))) {
				$array_fabricas = $_POST["selected_grupos"];

				if (empty($array_fabricas)) {
					$sql .= "
						WHERE tbl_peca.fabrica = $login_fabrica
					    AND tbl_peca.peca = $peca
					";
				} else {
					foreach ($array_fabricas as $select_fabrica) {
						$aux_fabrica = $select_fabrica;
				
						if ($aux_fabrica != $login_fabrica) {
							$aux_sql = "SELECT descricao, referencia FROM tbl_peca WHERE peca = $peca AND fabrica = $login_fabrica";
							$aux_res = pg_query($con, $aux_sql);

							$aux_des = pg_fetch_result($aux_res, 0, 'descricao');
							$aux_ref = pg_fetch_result($aux_res, 0, 'referencia');

							$aux_sql = "SELECT peca FROM tbl_peca WHERE descricao = '$aux_des' AND referencia = '$aux_ref' AND fabrica = $aux_fabrica";
							$aux_res = pg_query($con, $aux_sql);

							if (pg_num_rows($aux_res) <= 0) {
								$echo_fab = ($aux_fabrica == 11 ? "Aulik" : "Pacific");
								$msg_erro .= "A peça '$aux_ref - $aux_des' não está cadastrada para a $echo_fab <br>";
								break;
							} else {
								$outra_peca    = pg_fetch_result($aux_res, 0, 'peca');
								$valor_fabrica = $aux_fabrica;

								$where_peca[]    = $outra_peca;
								$where_fabrica[] = $valor_fabrica;
							}
						} else {
							$where_peca[]    = $peca;
							$where_fabrica[] = $aux_fabrica;
						}
					}
					$sql .= "
						WHERE tbl_peca.fabrica IN (". implode(",", $where_fabrica) .")
						AND tbl_peca.peca IN (". implode(",", $where_peca) .")
					";
				}
			} else {
				$sql .= "
					WHERE tbl_peca.fabrica = $login_fabrica
				    AND tbl_peca.peca = $peca
				";
			}
		}

      	if (empty($msg_erro)) {
      		$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
		}

		/*HD - 4323017*/
		if (in_array($login_fabrica, array(11, 172)) && $outra_fabrica == true && empty($peca)) {
			/*Substituindo a fábrica no insert*/
			$aux_fabrica = $array_fabricas[1];
			$aux_sql     = explode(") VALUES (", $sql);
			$aux_troca   = explode(",", $aux_sql[1]);

			$aux_troca[0] = $aux_fabrica;
			$aux_troca    = implode(",", $aux_troca);
			$aux_sql[1]   = $aux_troca;
			$aux_sql      = implode(") VALUES (", $aux_sql);

			$aux_res = pg_query($con, $aux_sql);
		}

		if (in_array($login_fabrica, array(169,170)) && !empty($peca)) {
			$sql = "
				UPDATE tbl_numero_serie_peca SET
					referencia_peca = tbl_peca.referencia
				FROM tbl_peca
				WHERE tbl_numero_serie_peca.fabrica = {$login_fabrica}
				AND tbl_peca.fabrica = {$login_fabrica}
				AND tbl_numero_serie_peca.peca = {$peca}
				AND tbl_numero_serie_peca.peca = tbl_peca.peca
				AND tbl_peca.referencia != tbl_numero_serie_peca.referencia_peca
			";
			$res = pg_query($con, $sql);
			$msg_erro = pg_errormessage($con);
		}

		if (strlen($peca) == 0) {
			$res = @pg_exec ($con,"SELECT CURRVAL ('seq_peca')");
			$msg_erro .= pg_errormessage($con);
			if(is_resource($res))
				$peca  = pg_fetch_result ($res,0,0);
		}

		if($login_fabrica == 125){
			if(empty($msg_erro) and $aux_kit == 't'){
				$sqlVerKit = "SELECT * FROM tbl_kit_peca where peca = $peca and fabrica = $login_fabrica ";
				$resVerkit = pg_query($con, $sqlVerKit);
				if(pg_num_rows($resVerkit)==0){
					$sqlkit = "INSERT INTO tbl_kit_peca (fabrica, referencia, descricao, peca) values ($login_fabrica, '$referencia', '$descricao', $peca)";
				}else{
					$sqlkit = "UPDATE tbl_kit_peca SET referencia = '$referencia', descricao = '$descricao'  WHERE peca = $peca and fabrica = $login_fabrica ";
				}
				$reskit = pg_query($con, $sqlkit);				
				
			}else{
				//hd=6488205
				$sql = " SELECT kit_peca from tbl_kit_peca where peca = $peca and fabrica = $login_fabrica ";
				$res = pg_query($con, $sql);
				if(pg_num_rows($res)>0){
					$kit_peca = pg_fetch_result($res, 0, 'kit_peca');

					$sqlDelKit = "DELETE from tbl_kit_peca_peca where kit_peca = $kit_peca";
					$resDelKit = pg_query($con, $sqlDelKit);

					$sqlDelKit = "DELETE from tbl_kit_peca where kit_peca = $kit_peca";
					$resDelKit = pg_query($con, $sqlDelKit);
				}				
			}
		}

        if ($login_fabrica == 165) {
            /**
             * HD-3601383 - Verificador de serviços amarrados
             * à peça, dizendo se é uma
             * placa ou não.
             */

             /*
              * 1 - Verifica se a peça tem, ao menos,
              * serviços amarrados à ela, verificando se é peça
              * nova no cadastro.
              */
            $sqlContaServ = "
                SELECT  COUNT(1) AS qtde_servico_peca
                FROM    tbl_peca_servico
                WHERE   peca = $peca
            ";
            $resContaServ = pg_query($con,$sqlContaServ);
            $qtde_servico_peca = pg_fetch_result($resContaServ,0,qtde_servico_peca);

            $sqlAjuste = "
                SELECT  servico_realizado
                FROM    tbl_servico_realizado
                WHERE   fabrica = $login_fabrica
                AND     descricao = 'Ajuste'
            ";
            $resAjuste = pg_query($con,$sqlAjuste);
            $ajuste = pg_fetch_result($resAjuste,0,servico_realizado);

            if ($qtde_servico_peca == 0) {
                /*
                 * - Ao menos, gravar o serviço de
                 * Ajuste para peças novas ou sem
                 * serviço cadastrado
                 */
                $sqlGravaAjuste = "
                INSERT INTO tbl_peca_servico (
                    peca,
                    servico_realizado
                ) VALUES (
                    $peca,
                    $ajuste
                )";
                $resGravaAjuste = pg_query($con,$sqlGravaAjuste);
            }

            /*
             * - Tenta apagar todo serviço vinculado
             * à peça
             */

            $sqlDesvincular = "
                DELETE  FROM tbl_peca_servico
                WHERE   peca = $peca
                AND     servico_realizado <> $ajuste
            ";
            $resDesvincular = pg_query($con,$sqlDesvincular);

            /*
             * - De acordo com a escolha no cadastro,
             * se a peça for placa ou não, selecionará
             * os serviços de acordo com o tipo.
             */

            if ($aux_placa == 'f') {
                $sqlServicos = "
                    INSERT INTO tbl_peca_servico
                    SELECT  $peca as peca,
                            servico_realizado
                    FROM    tbl_servico_realizado
                    WHERE   solucao             IS TRUE
                    AND     troca_produto       IS NOT TRUE
                    AND     servico_realizado   <> $ajuste
                    AND     fabrica             = $login_fabrica
                ";
            } else {
                $sqlServicos = "
                    INSERT INTO tbl_peca_servico
                    SELECT  $peca as peca,
                            servico_realizado
                    FROM    tbl_servico_realizado
                    WHERE   solucao             IS NOT TRUE
                    AND     ativo               IS TRUE
                    AND     fabrica             = $login_fabrica
                ";
            }

            $resServicos = pg_query($con,$sqlServicos);
        }

		if(in_array($login_fabrica, array(30,151,158))){

			if(strlen($familia_peca) > 0){ //hd_chamado=2543280

				if(strlen($peca) > 0){
					$sqlFamiliaPeca = "SELECT peca FROM tbl_peca_familia WHERE peca = ".$peca." AND fabrica = ".$login_fabrica;
					$resFamiliaPeca = pg_query($con,$sqlFamiliaPeca);

					if(pg_fetch_row($resFamiliaPeca) == 0){
						$sqlFamiliaPeca = "";
						$sqlFamiliaPeca = "INSERT INTO tbl_peca_familia (peca, fabrica, familia_peca)

							VALUES (".$peca.",
								".$login_fabrica.",".$familia_peca.")";
					}else{
						$sqlFamiliaPeca = "";
						$sqlFamiliaPeca = "UPDATE tbl_peca_familia SET familia_peca = ".$familia_peca."
							WHERE peca = ".$peca." AND fabrica = ".$login_fabrica;
					}
				}else{

					$sqlFamiliaPeca = "INSERT INTO tbl_peca_familia (peca, fabrica, familia_peca)

						VALUES ((SELECT peca FROM tbl_peca WHERE referencia = '".$referencia."'),
							".$login_fabrica.",".$familia_peca.")";
				}
				pg_query($con,$sqlFamiliaPeca);

			} else if (in_array($login_fabrica, array(151,158))) {
				$msg_erro .= traduz("Selecione a Família da peça");
			}
		}


		if (!$msg_erro and $login_fabrica == 1) {
			$atualiza_peca_icms = true;
			$dirname = dirname(__FILE__);
			$cmd_atualiza = "php $dirname/../rotinas/blackedecker/atualiza-peca-icms.php $peca";
		}

		if (in_array($login_fabrica, array(10, 85, 96)) && strlen($msg_erro) <= 0) {
			$preco_peca = $_POST['preco_peca'];

			if(strlen($preco_peca) == 0){
				$preco_peca = 0;
			}

			/* if($login_fabrica != 85){ */
				if($aux_liquidacao == "null"){
					$preco_peca = 0;
				}

				if($login_fabrica == 85){

					$sql_id_tabela = "SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa is true  AND descricao = 'LOJA VIRTUAL'";
					$res_id_tabela = pg_query($con, $sql_id_tabela);
					$id_tabela = pg_fetch_result($res_id_tabela, 0, 0);

					$tabela = ($aux_liquidacao != "null") ? $id_tabela : 416;
				}else{
					$tabela = 30;
				}

				$aux_preco_peca = str_replace( ',', '.', $preco_peca);
				$sql = ($login_fabrica == 85) ? "SELECT tabela_item from tbl_tabela_item where peca = $peca and preco is not null and tabela = $tabela ORDER BY tabela_item" : "SELECT tabela_item from tbl_tabela_item where peca = $peca and preco is not null and tabela = '30' order by tabela_item";
				//echo nl2br($sql); exit;
				$res = pg_query($con,$sql);
				if (pg_num_rows($res) == 0) {
					$sqlx = "INSERT INTO tbl_tabela_item (
									tabela,
									peca,
									preco
								) VALUES (
									'$tabela',
									$peca,
									fnc_limpa_moeda('$aux_preco_peca')
								);";
					$resx = pg_exec($con,$sqlx);
					$msg_erro = pg_errormessage($con);
				} else {

					$xtabela_item = pg_fetch_result($res,0,tabela_item);

					$sqlx = "UPDATE tbl_tabela_item SET
								preco = fnc_limpa_moeda('$aux_preco_peca')
								where tabela_item = $xtabela_item
								and peca = $peca";
					$resx = pg_exec($con,$sqlx);
					$msg_erro = pg_errormessage($con);
				}
			/* } else{

				if($aux_liquidacao == "null"){
					$preco_peca = 0;
				}
				$preco_peca = str_replace( ',', '.', $preco_peca);
				$sql_lv = "SELECT * FROM tbl_loja_virtual WHERE peca = $peca AND fabrica = $login_fabrica";
				$res_lv = pg_query($con, $sql_lv);
				if(pg_num_rows($res_lv) > 0){
					$sql_lv_update = "UPDATE tbl_loja_virtual SET preco = $preco_peca WHERE peca = $peca AND fabrica = $login_fabrica";
					$res_lv_update = pg_query($con, $sql_lv_update);
				}else{
					$sql_lv_insert = "INSERT INTO tbl_loja_virtual (peca, preco, fabrica) VALUES ($peca, $preco_peca, $login_fabrica)";
					$res_lv_insert = pg_query($con, $sql_lv_insert);
				}
			} */
		}

		//alteração para gustavo da bosch - Raphael Giovanini
		if($login_fabrica == 20){
			if($aux_acessorio == 'f'){
				$sql = "DELETE FROM tbl_lista_basica WHERE produto = 20567 AND peca = $peca";
				$res = @pg_exec ($con,$sql);
				$sql = "SELECT lista_basica FROM tbl_lista_basica WHERE produto = 20568 AND peca = $peca";
				$res = @pg_exec ($con,$sql);
				if (pg_numrows($res) == 0){
					$sql = "INSERT INTO tbl_lista_basica (produto, peca ,qtde ,fabrica       ,ativo)
							VALUES                       (20568  ,$peca , 20 , $login_fabrica,'t'  )";
					$res = @pg_exec ($con,$sql);
				}
			}else{
				$sql = "DELETE FROM tbl_lista_basica WHERE produto = 20568 AND peca = $peca";
				$res = @pg_exec ($con,$sql);
				$sql = "SELECT lista_basica FROM tbl_lista_basica WHERE produto = 20567 AND peca = $peca";
				$res = @pg_exec ($con,$sql);
				if (pg_numrows($res) == 0){
					$sql = "INSERT INTO tbl_lista_basica (produto, peca ,qtde ,fabrica       ,ativo)
							VALUES                       (20567  ,$peca , 20 , $login_fabrica,'t'  )";
					$res = @pg_exec ($con,$sql);
				}
			}


			//--=== TRADUÇÂO DAS PEÇAS ============================================
			$idioma           = $_POST["idioma"];
			$descricao_idioma = $_POST["descricao_idioma"];
			if(strlen($idioma)==2 AND strlen($descricao_idioma)>0 AND strlen($peca)>0){
				$sql = "SELECT * FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma)='ES'";
				$res2 = pg_exec ($con,$sql);

				if (pg_numrows($res2) > 0) {
				$sql = "UPDATE tbl_peca_idioma SET
						descricao = '$descricao_idioma'
					WHERE peca = $peca
					AND idioma = '$idioma'";
				$res = @pg_exec ($con,$sql);
				$msg_erro = pg_errormessage($con);
				}else{
					$sql = "INSERT INTO tbl_peca_idioma
							(
								peca,
								idioma,
								descricao
							)
							VALUES
							(
								$peca,
								'$idioma',
								'$descricao_idioma'
							)";
					$res = @pg_exec ($con,$sql);
					$msg_erro = pg_errormessage($con);
				}
			}
		}
// ! Gravação dos arquivos de imagem da Loja virtual
if ( $login_fabrica == 10 || $lv == 1 || $login_fabrica == 85) {
	foreach ( $aIdxFotos as $fotoSufixo ) {
		// Se o POST do arquivo n existir ou n houver arquivo enviado
		$idx = 'site_imagem_'.$fotoSufixo;
		if ( ! isset($_FILES[$idx]) || empty($_FILES[$idx]['size']) ) { continue; }
		list($tmp,$ext) = explode('.',$_FILES[$idx]['name']);

		$filename = PATH_FOTOS.$peca.'_'.$fotoSufixo.'.'.$ext;

		if (!empty($_FILES[$idx]['tmp_name'])) {
			$_FILES[$idx]['name'] = $peca.'_'.$fotoSufixo.'.'.$ext;
	        $anexoID = $tDocs->uploadFileS3($_FILES[$idx], $peca, false, "lv");
	        if (!$anexoID) {
	            $msg_erro.= traduz('Erro ao anexar');
	        }
	    } else {

			if ( file_exists($filename) ) {
				unlink($filename);
			}
			$ok = @move_uploaded_file($_FILES[$idx]['tmp_name'],$filename);
			if ( $ok ) {
				chmod($filename,0666);
			} else {
				$msg_erro .= traduz("<p>Não foi possível fazer o upload da imagem da <em>Loja Virtual</em></p>");
			}
		}
	}
} // Fim da gravação dos arquivos de imagem da loja
//echo '<pre style="display: block; text-align: left;">',print_r($_POST),print_r($_FILES),'</pre>'; die();
//  HD 97404 - MLG - Adiciono Ga.Ma Italy à lista de fabricantes
//  HD 100696 Adicionado Lorenzetti

	if(in_array($login_fabrica, $fabricas_com_imagens_pecas) OR $lv==1) {


		if (isset($_FILES['arquivos'])){

			$Destino = $caminho_media;
			$DestinoP =  $caminho_pequeno;

			if (!file_exists($Destino)) {
				mkdir($Destino, 0777, true);
			}

			if (!file_exists($DestinoP)) {
				mkdir($DestinoP, 0777, true);
			}

			$Fotos = $_FILES['arquivos'];
			$qtde_de_fotos = $_POST['qtde_de_fotos'];

			for ($i=0; $i<$qtde_de_fotos; $i++){
				 // retorna qndo nw tiver foto
				if (!isset($Fotos['tmp_name'][$i])) {
					continue;
				}

				$Nome    = $Fotos['name'][$i];
				$Tamanho = $Fotos['size'][$i];
				$Tipo    = $Fotos['type'][$i];
				$Tmpname = $Fotos['tmp_name'][$i];
				if (strlen($Nome)==0) continue;

				#$msg_erro = "$Extensao - $Nome 1";

				if(strlen($Nome)>0){

					//if(preg_match('/^image\/(pjpeg|jpeg|png|pdf|gif|jpg)$/', $Tipo)){

					//include '../helpdesk/mlg_funciones.php';

					if(preg_match('/(jpeg|jpg|png)$/i', $Tipo)){


						if (!empty($Tmpname)) {

							$fotoPecas['name']     = $Fotos['name'][$i];
							$fotoPecas['size']     = $Fotos['size'][$i];
							$fotoPecas['type']     = $Fotos['type'][$i];
							$fotoPecas['tmp_name'] = $Fotos['tmp_name'][$i];
		                    $anexoID = $tDocs->uploadFileS3($fotoPecas, $peca, true, "peca");
		                    if (!$anexoID) {
		                        $msg_erro.= traduz('Erro ao anexar');
		                    }
		                } else {

							//echo $Tmpname;
							if(!is_uploaded_file($Tmpname)){
								$msg_erro .= traduz("Não foi possível efetuar o upload.");
								break;
							}

							#$msg_erro .= "$Extensao - 2";

							$tmp = explode(".",$Nome);
							$ext = $tmp[count($tmp)-1];

							if (strlen($Nome)==0){
								$ext = $Nome;
							}

							#$msg_erro .= "$Extensao - 3";

							#inseri um registro
							if ($login_fabrica==10 and strlen($peca) > 0 and strlen($msg_erro) ==0){
								$sql = "INSERT INTO tbl_peca_item_foto
											(descricao, caminho,caminho_thumb, peca)
											VALUES ('$Nome','','',$peca)";
								$res = pg_exec ($con,$sql);
								$msg_erro .= pg_errormessage($con);
								$sql = "SELECT CURRVAL ('tbl_peca_item_foto_peca_item_foto_seq')";
								$res = pg_exec ($con,$sql);
								$msg_erro = pg_errormessage($con);
								$peca_item_foto = pg_fetch_result($res,0,0);
							}

							$ext = strtolower($ext);
							if($login_fabrica == 10){

								$nome_foto  = "$referencia-$peca_item_foto.$ext";
								$nome_foto = str_replace(" ","_",$nome_foto);
								$nome_thumb = "$referencia-$peca_item_foto.$ext";
								$nome_thumb = str_replace(" ","_",$nome_thumb);
							}else{
								$nome_foto  = "$peca.$ext";
								$nome_thumb = "$peca.$ext";

								$ext_maius = strtoupper($ext);
								$nome_foto_verifica  = "$peca.$ext_maius";
								$nome_thumb_ferifica = "$peca.$ext_maius";
							}

							$Caminho_foto  = $Destino . $nome_foto;
							$Caminho_thumb = $DestinoP . $nome_thumb;

							$Caminho_foto_verif  = $Destino . $nome_foto_verifica;
							$Caminho_thumb_verif = $DestinoP . $nome_thumb_ferifica;

							#Atualiza o nome do arquivo na tabela
							if ($login_fabrica==10 AND strlen($peca_item_foto)>0){
								$sql = "UPDATE tbl_peca_item_foto SET caminho = '$Caminho_foto',caminho_thumb = '$Caminho_thumb'
										WHERE peca_item_foto = $peca_item_foto AND peca = $peca";
								$res = pg_exec ($con,$sql);
							}
							$peca_imagem = $_POST['peca_imagem'];
							//echo "Tmpname $Tmpname Caminho_foto $Caminho_foto ext $ext";
							if(strtolower($ext)=="pdf"){
								if (file_exists($Caminho_foto)) { //Imagem anterior!
									if (!unlink($Caminho_foto)) {
										$msg_erro .= traduz("Não foi possível excluir o arquivo $peca".".pdf!<br>\n");
									} else {
										$copiou_arquivo = move_uploaded_file($Tmpname, $Caminho_foto);
									}
								} else {
									$copiou_arquivo = move_uploaded_file($Tmpname, $Caminho_foto);
								}
								if (file_exists($Caminho_foto)) chmod($Caminho_foto, 0666); //Nova imagem!
								#copy($Tmpname, $Caminho_thumb); Não carregar o Thumb para pdf, senão algum lugar nao funcionar
							}else{
								if(file_exists($Caminho_foto_verif) AND $login_fabrica<>10) {
									unlink($Caminho_foto_verif);
									unlink($Caminho_thumb_verif);
								}

								#Apaga a imagem anterior
								if(file_exists($Caminho_foto) AND $login_fabrica<>10){
									unlink($Caminho_foto);
									unlink($Caminho_thumb);
								}

								//HD 739257 - MLG - Apaga a imagem anterior, mesmo se a extensão está em maiúsculo
								if(file_exists(strtoupper($Caminho_foto)) AND $login_fabrica<>10){
									unlink(strtoupper($Caminho_foto));
									unlink(strtoupper($Caminho_thumb));
								}

								reduz_imagem($Tmpname, 400, 300, $Caminho_foto);
								reduz_imagem($Tmpname, 100, 90,  $Caminho_thumb);
								$copiou_arquivo = file_exists($Caminho_foto); // Nova imagem!

								//echo "<BR>teste";
							}
							//if ($testIMG==1) unlink ($Caminho_foto); // HD 397803 - Exclui a imagem recém inserida para testes
							if ($copiou_arquivo and !file_exists($Caminho_foto)) { // HD 397803 - Confere se REALMENTE existe o arquivo "anexado"
								$copiou_arquivo = false;
							}
							if  (!$copiou_arquivo) $msg_erro.= ($ext=='pdf') ? traduz('Documento não anexado.'):traduz('Imagem não anexada.');
						}
					}else{
						$msg_erro .= traduz("O formato do arquivo ( $Nome ) inválidos !<br>São permitidas apenas imagens jpg e png");
					}
				}
			}
		}

	}

#$msg_erro .= "ERRO";

		##Foi mudado o local para sempre que cadastrar a peça já atualizar os defeitos.
		$total_servico_realizado = $_POST['total_servico_realizado'];
		if(strlen($total_servico_realizado)>0 && in_array($login_fabrica,array(6,125)) && strlen($peca)>0){

			$sql = "UPDATE tbl_peca_servico set ativo='f' WHERE peca = $peca";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			for($i=0;$i<$total_servico_realizado;$i++){
				$servico_realizado = $_POST['servico_realizado_'.$i];

				if(strlen($servico_realizado)>0){

					$sql = "SELECT peca from tbl_peca_servico where peca = $peca and servico_realizado = $servico_realizado";
					$res = pg_exec($con,$sql);

					if(pg_numrows($res)>0){
						$sql = "UPDATE tbl_peca_servico set ativo='t' WHERE peca = $peca and servico_realizado = $servico_realizado";
						$res = pg_exec($con,$sql);
						//$msg_erro = "Cadastrado com Sucesso!!";
					}else{
						$sql = "INSERT into tbl_peca_servico (peca,servico_realizado,ativo)values($peca,$servico_realizado,'t')";
						$res = pg_exec($con,$sql);
					//	$msg_erro = "Cadastrado com Sucesso!!";
					}
				}
			}
		}

		$total_defeitos = $_POST['total_defeitos'];
		if(strlen($total_defeitos)>0 and $login_fabrica==6 and strlen($peca)>0){
			$sql = "UPDATE tbl_peca_defeito set ativo='f' WHERE peca = $peca";
			$res = pg_exec($con,$sql);
			$msg_erro = pg_errormessage($con);
			for($i=0;$i<$total_defeitos;$i++){
				$defeito = $_POST['defeito_'.$i];

				if(strlen($defeito)>0){

					$sql = "SELECT peca from tbl_peca_defeito where peca = $peca and defeito = $defeito";
					$res = pg_exec($con,$sql);
					$msg_erro = pg_errormessage($con);
					if(pg_numrows($res)>0){
						$sql = "UPDATE tbl_peca_defeito set ativo='t' WHERE peca = $peca and defeito = $defeito";
						$res = pg_exec($con,$sql);
						$msg_erro = pg_errormessage($con);
						//$msg_erro = "Cadastrado com Sucesso!!";
					}else{
						$sql = "INSERT into tbl_peca_defeito (peca,defeito,ativo)values($peca,$defeito,'t')";
						$res = pg_exec($con,$sql);
						$msg_erro = pg_errormessage($con);
					//	$msg_erro = "Cadastrado com Sucesso!!";
					}
				}
			}
		}
		if(in_array($login_fabrica, array(158))){
			if (isset($ativo) && $ativo == 'f') {
				$campos['statusModel'] = '0';
			}
			if ($op_insert == 1) {
				$request = "POST";
				$url = "http://telecontrol.eprodutiva.com.br/api/recurso/material";
				$campos['codigo'] = trim(str_replace("'", "", $aux_referencia));
				$campos['medida'] = array("id" => "301");
			}else{
				$request = "PUT";
				$url = "http://telecontrol.eprodutiva.com.br/api/recurso/material/codigo/".trim(str_replace("'", "", $aux_referencia));
			}
			$campos['material'] = utf8_encode($aux_descricao);
			$json = json_encode($campos);

			$ch = curl_init();
			curl_setopt_array($ch, array(
				CURLOPT_URL => $url,
			  	CURLOPT_RETURNTRANSFER => true,
			  	CURLOPT_CUSTOMREQUEST => $request,
			  	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  	CURLOPT_POSTFIELDS => $json,
			  	CURLOPT_HTTPHEADER => array(
			    	"authorizationv2: {$chave_persys}",
			    	"Content-Type: application/json"
			  	)
			));
			$response = curl_exec($ch);
			curl_close($ch);

			if (!$response) {
				$msg_erro = traduz("Erro ao tentar incluir/alterar da aplicação mobile!");
			}
			$response = json_decode($response, TRUE);
			if (count($response['error']) && utf8_decode($response["error"]["message"]) != "Informação já cadastrada") {
				$msg_erro = traduz("Erro ao tentar incluir/alterar da aplicação mobile!");
				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			}
		}

			if (strlen ($msg_erro) == 0) {
				###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
				$res = pg_exec ($con,"COMMIT TRANSACTION");

				if (true === $atualiza_peca_icms) {
					$void = exec("$cmd_atualiza");
				}

				if ($tpAuditor == "insert") {
	                $auditorLog->retornaDadosTabela('tbl_peca', array('peca'=>$peca, 'fabrica'=>$login_fabrica))
	                       ->enviarLog('insert', "tbl_peca", $login_fabrica."*".$peca);
	            } else {
	                $auditorLog->retornaDadosTabela()
	                       ->enviarLog('update', "tbl_peca", $login_fabrica."*".$peca);
	            }

				header ("Location: $PHP_SELF?msg=Gravado com Sucesso!");
				exit;
			}else{
				###ABORTA OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E RECARREGA CAMPOS

				$peca                     = $_POST["peca"];
				$referencia               = $_POST["referencia"];
				$descricao                = $_POST["descricao"];
				$ipi                      = $_POST["ipi"];
				$origem                   = $_POST["origem"];
				$estoque                  = $_POST["estoque"];
				$unidade                  = $_POST["unidade"];
				$peso                     = $_POST["peso"];
				$classificacao_fiscal     = $_POST["classificacao_fiscal"];
				$multiplo                 = $_POST["multiplo"];
				$garantia_diferenciada    = $_POST["garantia_diferenciada"];
				$remessa_garantia          = $_POST["remessa_garantia"];
				$remessa_garantia_compressor = $_POST["remessa_garantia_compressor"];
				$devolucao_obrigatoria    = $_POST["devolucao_obrigatoria"];
				$item_aparencia           = $_POST["item_aparencia"];
				$acumular_kit             = $_POST['acumular_kit'];
				$retorna_conserto         = $_POST['retorna_conserto'];
				$intervencao_carteira     = $_POST['intervencao_carteira'];
				$mao_de_obra_diferenciada = $_POST["mao_de_obra_diferenciada"];
				$bloqueada_garantia       = $_POST['bloqueada_garantia'];
				$acessorio                = $_POST['acessorio'];
				$aguarda_inspecao         = $_POST['aguarda_inspecao'];
				$peca_critica             = $_POST["peca_critica"];
				$peca_monitorada          = $_POST["peca_monitorada"];
				$email_peca_monitorada    = $_POST["email_peca_monitorada"];
				$faturada_manualmente     = $_POST["faturada_manualmente"];
				$produto_acabado          = $_POST["produto_acabado"];
				$reembolso                = $_POST["reembolso"];
				$mero_desgaste			  = $_POST["mero_desgaste"];
				//$intervencao_fabrica      = $_POST["intervencao_fabrica"];
				$troca_obrigatoria        = $_POST["troca_obrigatoria"];
				$marca                    = $_POST["marca"];
				$informacoes              = ($login_fabrica == 151) ? $_POST["informacoes_adicionais"] : $_POST["informacoes"];
				$preco_compra             = $_POST["preco_compra"];
				$aux_linha                = $_POST["linha"];
				$aux_familia              = $_POST["familia"];
				$pre_selecionada          = $_POST["pre_selecionada"];
				$placa                    = $_POST["placa"];
				$peca_pai                 = $_POST["peca_pai"];
				$mo_peca                  = $_POST["mo_peca"];
				$mo_posto                 = $_POST["mo_posto"];
				$mo_admin                 = $_POST["mo_admin"];
				$promocao_site            = $_POST["promocao_site"];
				$at_shop                  = $_POST["at_shop"];
				$multiplo_site            = $_POST["multiplo_site"];
				//$qtde_minima_site         = $_POST["qtde_minima_site"];
				$qtde_max_site            = $_POST["qtde_max_site"];
				$qtde_minima_estoque      = $_POST["qtde_minima_estoque"];
				$qtde_disponivel_inicial_site= $_POST["qtde_disponivel_inicial_site"];
				$qtde_disponivel_site     = $_POST["qtde_disponivel_site"];
				$posicao_site             = $_POST["posicao_site"];
				$frete_gratis             = $_POST["frete_gratis"];
				$liquidacao               = $_POST["liquidacao"];
				$gera_troca_produto       = $_POST["gera_troca_produto"];
				$cobra_servico            = $_POST["cobra_servico"];
				$data_inicial_liquidacao  = $_POST["data_inicial_liquidacao"];
				//hd 47129
				$peca_unica_os            = $_POST["peca_unica_os"];
				$bloqueada_venda          = $_POST["bloqueada_venda"];
				$ncm                      = $_POST["ncm"];
				$etiqueta                 = $_POST['etiqueta'];
				$controla_saldo           = $_POST["controla_saldo"];
				$referencia_fabrica       = $_POST["referencia_fabrica"];
				$disponibilidade          = $_POST["status_disponibilidade"];
				$referencia_fabrica       = $_POST["referencia_fabrica"];
				
				if (in_array($login_fabrica, array(171))){
					$apresentacao             = $_POST["apresentacao"];
					$descricao_detalhada      = $_POST["descricao_detalhada"];
					$marca_detalhada          = $_POST["marca_detalhada"];
					$emb                      = $_POST["emb"];
					$categoria                = $_POST["categoria"];
					$ncm                      = $_POST["ncm"];
					$ii                       = $_POST["ii"];
					$alt                      = $_POST["alt"];
					$comp                     = $_POST["comp"];
					$peso                     = $_POST["peso"];
					$cod_barra                = $_POST["cod_barra"];
					$custo_cip                = $_POST["custo_cip"];
					$larg                     = $_POST["larg"];
				}
				$qtde_disparos 			  = $_POST['qtde_disparos']; 

				if (strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_peca_unico\"") > 0)
					$msg_erro = traduz("Esta referência já esta cadastrada e não pode ser duplicada.");

				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			}
		}//fim if msg_erro

		if(strlen($msg_erro)==0){
			$msg = traduz("Gravado com Sucesso!");
		}
}

if ($btnacao == "uploadArq") {

	if (!empty($_FILES["upload"]["name"]) && $login_fabrica == 123) {
		$upload   = $_FILES["upload"];

		if ($upload["error"] > 0) {
	        $msg_erro = traduz("Erro com o Arquivo");
	    }

	    $conteudoArq = file_get_contents($upload["tmp_name"]);
	    $conteudoArq = explode("\n", $conteudoArq);

	    if (empty($conteudoArq)) {
	        $msg_erro .= "<br>".traduz("Arquivo sem conteúdo");
	    }

	    if (empty($msg_erro)) {

			$i = 0 ; 
			$erro_csv = [];
	        foreach ($conteudoArq as $key => $rows) {

	        	if (empty($rows)) {
	        		$i++;
	        		continue;
	        	}

	        	$dadosArq = explode(";", $rows);
	        	if (count($dadosArq) != 2) {
	        		$erro_csv[] = "Erro na linha $i";
	        		$i++;
	        		continue;
	        	}

	        	$dataArq = date("Y-m-d", strtotime(str_replace("/", "-", $dadosArq[1])));
	        	if (strtotime($dataArq) < strtotime(date("Y-m-d"))) {
	        		$erro_csv[] = "Erro de Data na linha $i";
	        		$i++;
	        		continue;
	        	}
				
	            $referenciaArq = trim($dadosArq[0]);

	            $sqlPeca = "SELECT peca FROM tbl_peca WHERE referencia = '$referenciaArq' AND fabrica = $login_fabrica AND ativo LIMIT 1";
	            $resPeca = pg_query($con, $sqlPeca);
	            if (pg_num_rows($resPeca) == 0) {
	            	$erro_csv[] = "Erro na linha $i, Peça $referenciaArq não encontrada";
	        		$i++;
	        		continue;
	            }

	            $pecaId = pg_fetch_result($resPeca, 0, "peca");
	            $previsaoEntrega = ["previsaoEntrega"=>$dataArq];
	            $previsaoEntrega = json_encode($previsaoEntrega);

	            if (!empty($pecaId)) {
	            	$sqlUp = "UPDATE tbl_peca SET parametros_adicionais = coalesce(parametros_adicionais::jsonb, '{}') || '$previsaoEntrega' WHERE fabrica = $login_fabrica AND peca = $pecaId";
	            	$resUp = pg_query($con, $sqlUp);
	            	if (pg_last_error()) {
	            		$erro_csv[] = "Erro ao Salvar a data da linha $i";
	            	}
	            }
	            $i++;
	        }
	    }
	}

	if (count($erro_csv) > 0) {
		$msg_erro = implode("<br>", $erro_csv);
	} else {
		header ("Location: $PHP_SELF?msg=Upload realizado com Sucesso!");
	}
}

###CARREGA REGISTRO
$peca = $_GET ['peca'];

if (strlen($peca) > 0 AND strlen($msg_erro) ==0) {
	$sql = "SELECT tbl_peca.peca                   ,
			tbl_peca.referencia            ,
			tbl_peca.descricao             ,
			tbl_peca.descricao_estendida   ,
			tbl_peca.ipi                   ,
			tbl_peca.origem                ,
			tbl_peca.estoque               ,
			tbl_peca.unidade               ,
			tbl_peca.peso                  ,
			tbl_peca.classificacao_fiscal  ,
			tbl_peca.multiplo              ,
			tbl_peca.garantia_diferenciada ,
			tbl_peca.remessa_garantia      ,
			tbl_peca.remessa_garantia_compressor,
			tbl_peca.devolucao_obrigatoria ,
			tbl_peca.item_aparencia        ,
			tbl_peca.acumular_kit          ,
			tbl_peca.retorna_conserto      ,
			tbl_peca.intervencao_carteira  ,
			tbl_peca.mao_de_obra_troca     ,
			tbl_peca.bloqueada_garantia    ,
			tbl_peca.acessorio             ,
			tbl_peca.aguarda_inspecao      ,
			tbl_peca.peca_critica          ,
			tbl_peca.linha_peca            ,
			tbl_peca.familia_peca          ,
			tbl_peca.marca                 ,
			tbl_peca.preco_compra          ,
			tbl_peca.informacoes           ,
			tbl_peca.produto_acabado       ,
			tbl_peca.reembolso             ,
			tbl_peca.mero_desgaste		   ,
			tbl_peca.mo_peca               ,
			tbl_peca.mo_posto              ,
			tbl_peca.mo_admin              ,
			tbl_peca.ativo                 ,
			tbl_peca.ncm		       ,
			tbl_peca.etiqueta              ,
			tbl_peca.pre_selecionada       ,
			tbl_peca.placa                 ,
			tbl_peca.peca_pai              ,
			tbl_peca.troca_obrigatoria     ,
			tbl_peca.promocao_site         ,
			tbl_peca.at_shop               ,
			tbl_peca.multiplo_site         ,
			tbl_peca.qtde_max_site         ,
			tbl_peca.qtde_disponivel_inicial_site,
			tbl_peca.qtde_disponivel_site  ,
			tbl_peca.qtde_minima_estoque   ,
			tbl_peca.frete_gratis          ,
			tbl_peca.posicao_site          ,
			tbl_peca.liquidacao            ,
			tbl_peca.preco_anterior        ,
			tbl_admin.login                ,
			tbl_peca.gera_troca_produto    ,
			tbl_peca.cobrar_servico        ,
			tbl_peca.faturada_manualmente  ,
			tbl_peca.numero_serie_peca     ,
			tbl_tabela_item.preco          ,
			TO_CHAR(tbl_peca.data_atualizacao, 'DD/MM/YYYY HH24:MI') AS data_atualizacao,
			TO_CHAR(tbl_peca.data_inicial_liquidacao, 'DD/MM/YYYY') AS data_inicial_liquidacao        ,
			tbl_peca.peca_unica_os         ,
			tbl_peca.bloqueada_venda       ,
			tbl_peca.peca_critica_venda    ,
			tbl_peca.controla_saldo        ,
			tbl_peca.referencia_fabrica    ,
			tbl_peca.parametros_adicionais ,
			tbl_peca.reducao AS qtde_disparos,
			tbl_peca.localizacao
		FROM    tbl_peca
		LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_peca.admin
		LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca
		WHERE   tbl_peca.peca = $peca
		AND    tbl_peca.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$peca                        = trim(pg_fetch_result($res,0,'peca'));
		$referencia                  = trim(pg_fetch_result($res,0,'referencia'));
		$descricao                   = trim(pg_fetch_result($res,0,'descricao'));
		$descricao		     		 = mb_detect_encoding($descricao,'UTF-8',true) ? utf8_decode($descricao) : $descricao;
		$descricao_estendida         = trim(pg_fetch_result($res,0,'descricao_estendida'));
		$ipi                         = trim(pg_fetch_result($res,0,'ipi'));
		$origem                      = trim(pg_fetch_result($res,0,'origem'));
		$estoque                     = trim(pg_fetch_result($res,0,'estoque'));
		$unidade                     = trim(pg_fetch_result($res,0,'unidade'));
		$peso                        = trim(pg_fetch_result($res,0,'peso'));
		$classificacao_fiscal        = trim(pg_fetch_result($res,0,'classificacao_fiscal'));
		$multiplo                    = trim(pg_fetch_result($res,0,'multiplo'));
		$garantia_diferenciada       = trim(pg_fetch_result($res,0,'garantia_diferenciada'));
		$remessa_garantia            = trim(pg_fetch_result($res,0,'remessa_garantia'));
		$remessa_garantia_compressor = trim(pg_fetch_result($res,0,'remessa_garantia_compressor'));
		$devolucao_obrigatoria       = trim(pg_fetch_result($res,0,'devolucao_obrigatoria'));
		$item_aparencia              = trim(pg_fetch_result($res,0,'item_aparencia'));
		$acumular_kit                = trim(pg_fetch_result($res,0,'acumular_kit'));
		$retorna_conserto            = trim(pg_fetch_result($res,0,'retorna_conserto'));
		$intervencao_carteira        = trim(pg_fetch_result($res,0,'intervencao_carteira'));
		$mao_de_obra_diferenciada    = trim(pg_fetch_result($res,0,'mao_de_obra_troca'));
		$bloqueada_garantia          = trim(pg_fetch_result($res,0,'bloqueada_garantia'));
		$acessorio                   = trim(pg_fetch_result($res,0,'acessorio'));
		$aguarda_inspecao            = trim(pg_fetch_result($res,0,'aguarda_inspecao'));
		$peca_critica                = trim(pg_fetch_result($res,0,'peca_critica'));
		$produto_acabado             = trim(pg_fetch_result($res,0,'produto_acabado'));
		$reembolso                   = trim(pg_fetch_result($res,0,'reembolso'));
		$mero_desgaste               = trim(pg_fetch_result($res,0,'mero_desgaste'));
		$mo_peca                     = trim(pg_fetch_result($res,0,'mo_peca'));
		$mo_posto                    = trim(pg_fetch_result($res,0,'mo_posto'));
		$mo_admin                    = trim(pg_fetch_result($res,0,'mo_admin'));
		//$intervencao_fabrica       = trim(pg_fetch_result($res,0,'intervencao_fabrica'));
		$troca_obrigatoria           = trim(pg_fetch_result($res,0,'troca_obrigatoria'));
		$marca                       = trim(pg_fetch_result($res,0,'marca'));
		$informacoes                 = trim(pg_fetch_result($res,0,'informacoes'));
		$preco_compra                = trim(pg_fetch_result($res,0,'preco_compra'));
		$linha                       = trim(pg_fetch_result($res,0,'linha_peca'));
		$familia                     = trim(pg_fetch_result($res,0,'familia_peca'));
		$ativo                       = trim(pg_fetch_result($res,0,'ativo'));
		$pre_selecionada             = trim(pg_fetch_result($res,0,'pre_selecionada'));
		$placa                       = trim(pg_fetch_result($res,0,'placa'));
		$peca_pai                    = trim(pg_fetch_result($res,0,'peca_pai'));
		$promocao_site               = trim(pg_fetch_result($res,0,'promocao_site'));

		$qtde_disparos 				 = trim(pg_fetch_result($res,0,'qtde_disparos'));

		if ($login_fabrica == 35) {
			$po_peca = pg_fetch_result($res, 0, "promocao_site");
		}

		//hd-3625122 - fputti
		if (in_array($login_fabrica,array(171,187))) {
			$referencia_fabrica = pg_fetch_result($res, 0, "referencia_fabrica");
		}

		$at_shop                  = trim(pg_fetch_result($res,0,'at_shop'));
		$multiplo_site            = trim(pg_fetch_result($res,0,'multiplo_site'));
		//$qtde_minima_site         = trim(pg_fetch_result($res,0,'qtde_minima_site'));
		$qtde_max_site            = trim(pg_fetch_result($res,0,'qtde_max_site'));
		$qtde_disponivel_inicial_site = trim(pg_fetch_result($res,0,'qtde_disponivel_inicial_site'));
		$qtde_disponivel_site     = trim(pg_fetch_result($res,0,'qtde_disponivel_site'));
		$qtde_minima_estoque      = trim(pg_fetch_result($res,0,'qtde_minima_estoque'));
		$posicao_site             = trim(pg_fetch_result($res,0,'posicao_site'));
		$liquidacao               = trim(pg_fetch_result($res,0,'liquidacao'));
		$admin                    = trim(pg_fetch_result($res,0,'login'));
		$preco_anterior           = trim(pg_fetch_result($res,0,'preco_anterior'));
		$data_atualizacao         = trim(pg_fetch_result($res,0,'data_atualizacao'));
		$gera_troca_produto       = trim(pg_fetch_result($res,0,'gera_troca_produto'));
		$cobrar_servico           = trim(pg_fetch_result($res,0,'cobrar_servico'));
		$faturada_manualmente     = trim(pg_fetch_result($res,0,'faturada_manualmente'));
		# HD 101357
		$numero_serie_peca        = trim(pg_fetch_result($res,0,'numero_serie_peca'));
		$preco_peca               = trim(pg_fetch_result($res,0,'preco'));
		$frete_gratis             = trim(pg_fetch_result($res,0,'frete_gratis'));
		$data_inicial_liquidacao  = trim(pg_fetch_result($res,0,'data_inicial_liquidacao'));

		if ($preco_peca) {
			$preco_peca = number_format($preco_peca,2,',','.');
		}
		//hd 47129
		$peca_unica_os            = trim(pg_fetch_result($res,0,'peca_unica_os'));
		$peca_critica_venda       = trim(pg_fetch_result($res,0,'peca_critica_venda'));
		$bloqueada_venda          = trim(pg_fetch_result($res,0,'bloqueada_venda'));
		$ncm					  = trim(pg_fetch_result($res,0,'ncm'));
		$controla_saldo           = trim(pg_fetch_result($res,0,'controla_saldo'));
		$parametros_adicionais    = trim(pg_fetch_result($res,0,'parametros_adicionais'));
		$etiqueta                 = pg_fetch_result($res,0,'etiqueta');

		if($login_fabrica == 101){
			$endereco_estoque = trim(pg_fetch_result($res,0,'localizacao'));
		}

		if (!empty($parametros_adicionais)) {
			$parametros_adicionais = new Json($parametros_adicionais, false);
			if(in_array($login_fabrica, [11,104,172])){
				$qtde_demanda = $parametros_adicionais->qtde_demanda;
			}
			if ($login_fabrica == 171){
				$apresentacao             = $parametros_adicionais->apresentacao;
				$descricao_detalhada      = $parametros_adicionais->descricao_detalhada;
				$marca_detalhada          = $parametros_adicionais->marca_detalhada;
				$emb                      = $parametros_adicionais->emb;
				$categoria                = $parametros_adicionais->categoria;
				$ncm                      = $parametros_adicionais->ncm;
				$ii                       = $parametros_adicionais->ii;
				$alt                      = $parametros_adicionais->alt;
				$comp                     = $parametros_adicionais->comp;
				$peso                     = $parametros_adicionais->peso;
				$cod_barra                = $parametros_adicionais->cod_barra;
				$custo_cip                = $parametros_adicionais->custo_cip;
				$larg  					  = $parametros_adicionais->larg;
			}
			if (in_array($login_fabrica, [177])) {
				$lote                     = $parametros_adicionais->lote;
				$caneca                   = $parametros_adicionais->caneca;
			}			
		    if (in_array($login_fabrica, [186])) {
				$xendereco = $parametros_adicionais->endereco;
			}

			if ($login_fabrica == 42) {
				$peca_monitorada       = $parametros_adicionais->peca_monitorada;
				$email_peca_monitorada = $parametros_adicionais->email_peca_monitorada;
			}
			if($login_fabrica == 125){
				$aux_kit = $parametros_adicionais->kit;
			}

			if ($login_fabrica == 123) {
				$prev_chegada = date("d/m/Y", strtotime($parametros_adicionais->previsaoEntrega));
				
				$sqlEstoque = "SELECT peca FROM tbl_posto_estoque WHERE peca = {$peca} AND qtde > 0";
				$resEstoque = pg_query($con, $sqlEstoque);
				if (pg_num_rows($resEstoque) > 0) {
					$disponibilidade = "Disponível";
					$classColor = "class_sim";
				} else {
					$disponibilidade = "Indisponível";
					$classColor = "class_nao";
				}
			}

			extract($parametros_adicionais->data, EXTR_PREFIX_INVALID, 'no_var');

			if(!isset($_REQUEST['parametros_adicionais'])){
				$_REQUEST['parametros_adicionais'] = (string)$parametros_adicionais;
			}
		}

		/*85*/
		if ($login_fabrica == 85) {
			$sql_lv = "SELECT * FROM tbl_tabela_item WHERE peca = $peca AND tabela IN (
							SELECT tabela FROM tbl_tabela WHERE fabrica = $login_fabrica AND ativa is true AND descricao = 'LOJA VIRTUAL'
			)";
			$res_lv = pg_query($con, $sql_lv);
			if(pg_num_rows($res_lv) > 0){
				while ($data = pg_fetch_object($res_lv)) {
					$preco_peca = $data->preco;
					if(strlen($preco_peca) > 0){
						$preco_peca = number_format($preco_peca,2,',','.');
					}else{
						$preco_peca = "0,00";
					}
				}
			}else{
				$preco_peca = "0,00";
			}
		}

		if(in_array($login_fabrica, array(30,151,158))){
			$sqlFamiliaPeca = "SELECT * FROM tbl_peca_familia WHERE peca = $peca AND fabrica = $login_fabrica";
			$resFamiliaPeca = pg_query($con, $sqlFamiliaPeca);

			if(pg_num_rows($resFamiliaPeca) > 0){
				$familia_peca = trim(pg_fetch_result($resFamiliaPeca,0,familia_peca));
			}else{
				$familia_peca = 0;
			}
		}
	}
}


$layout_menu = 'cadastro';
$title = traduz('CADASTRO DE PEÇAS');

include 'cabecalho.php';
//include "javascript_calendario.php";

$msg = $_GET['msg'];
?>

<link rel="stylesheet" type="text/css" href="plugins/shadowbox/shadowbox.css" media="all">

<script type="text/javascript" src="plugins/shadowbox/shadowbox.js"></script>
<script type="text/javascript" src="js/jquery.mask.js"></script>
<script type="text/javascript" src="js/jquery.alphanumeric.js"></script>

<?php if ($login_fabrica == 123) { ?>
	<style type="text/css">
		@import "plugins/jquery/datepick/telecontrol.datepick.css";
	</style>
		<script src="plugins/jquery/datepick/jquery.datepick.js"></script>
		<script src="plugins/jquery/datepick/jquery.datepick-pt-BR.js"></script>
<?php } ?>


<script type="text/javascript">
	$(function(){
		Shadowbox.init();
		$("#data_inicial_liquidacao").mask("99/99/9999");

		<?php if ($login_fabrica == 123) { ?>
				$("#prev_chegada").datepick({startDate : "01/01/2000"});
		<?php } ?>

		$("input[name='ipi']").numeric({allow:".,"});
		$("input[name='estoque']").numeric({allow:".,"});
		$("input[name='multiplo']").numeric();
		$("input[name='preco_peca']").numeric({allow:".,"});
		$("input[name='peso']").numeric({allow:".,"});
		$("input[name='garantia_diferenciada']").numeric();
		$("input[name='ncm']").numeric();

		<?php if ($login_fabrica == 183 AND $login_privilegios != "*"){ ?>
			$("input[name='bloqueada_venda']").click(function(){
				return false;
			})
			$("input[name='bloqueada_garantia']").click(function(){
				return false;
			})
		<?php } ?>
		$('img.excluir[id^=peca]').click(function() {
			var peca       = $(this).attr('id').replace(/\D+/g, '');
			var imagem     = $('input:hidden[name=peca_imagem]').val();
			var referencia = $('input:text[name=referencia]').val();

			if (confirm('Excluir a imagem da peça '+referencia+ "?\nATENÇÃO: Se você fez alguma alteração no cadastro da peça, não será salva. Se for o caso, grave primeiro e exclua a imagem após gravar.")) {
				$.get(
					window.location.pathname,
					{
						ajax:     'excluir',
						'imagem': imagem,
						'peca':   peca
					},
					function(data) {
						alert(data);
						if (data.indexOf('com')>2)
							window.location.reload();
					}
				)
			}
		});

		$("input[name=upload_fotos]").change(function () {
			if ($(this).is(":checked")) {
				$("#qtde_fotos").show();
			} else {
				$("#qtde_fotos").hide();
			}
		});

		$("input.item_revenda_produto").change(function (){

			var lista_basica_etiqueta = $(this).next().val();
			var ativa_etiqueta = true;

			if (!$(this).is(":checked")) {
				ativa_etiqueta = false;
			}
			//console.log(ativa_etiqueta);
			$.ajax({
				url: "<?php echo $_SERVER['PHP_SELF']; ?>",
				type: "POST",
				data: {
					item_revenda_produto_ajax : "sim",
					ativa_etiqueta : ativa_etiqueta,
					lista_basica_etiqueta : lista_basica_etiqueta
				},
				complete: function(data){
					data = data.responseText;
					console.log(data);
					if(data == "erro"){
						alert('<?=traduz("Ocorreu um erro na atualização da etiqueta !")?>');
					} else {
						alert('<?=traduz("Etiqueta atualizada com Sucesso!")?>');
					}
				}
			});
		});

<?php
if (in_array($login_fabrica,array(11,172))) {
?>
        $("#cadastro_lb").click(function(e){
            e.preventDefault();

            var peca = $("input[name=referencia]").val();
            var qtde = $("input[name=multiplo]").val();
            Shadowbox.open({
                content:    "peca_lista_basica.php?peca="+peca+"&qtde="+qtde,
                player: "iframe",
                title:      '<?=traduz("Incluir Peça em Lista Básica")?>',
                width:  800,
                height: 500
            });
        });
<?php
}
?>

<?php if (in_array($login_fabrica, array(171))){ ?>
	checkPecaFN();
<?php } ?>

	<?php if ($login_fabrica == 42) { ?>
			$("#peca_monitorada").on("change", function() {
				if ($(this).is(':Checked')) {
					$("#email_peca_monitorada").attr("readonly", false);
				} else {
					$("#email_peca_monitorada").val("");
					$("#email_peca_monitorada").attr("readonly", true);
				}
			});

			if ($("#peca_monitorada").is(':Checked')) {
				$("#email_peca_monitorada").attr("readonly", false);
			} else {
				$("#email_peca_monitorada").attr("readonly", true);
			}
	<?php } ?>

	});

	<?php if (in_array($login_fabrica, array(171))){ ?>
			function checkPecaFN()
			{
				if ($("#ref_fn").val().length > 0){
				 	$("#pre_selecionada_check").prop('checked', false); 
				}else{
					$("#pre_selecionada_check").prop('checked', 'checked'); 
				}
				
				$("#ref_fn").blur(function() {
					if ($("#ref_fn").val().length > 0){
					 	$("#pre_selecionada_check").prop('checked', false); 
					}else{
						$("#pre_selecionada_check").prop('checked', 'checked'); 
					}
				});
			}	
	<?php } ?>

	<? if($login_fabrica == 10) { ?>
		window.onload = function(){
			var oFCKeditor = new FCKeditor( 'informacoes' ) ;
			oFCKeditor.BasePath = "js/fckeditor/" ;
			oFCKeditor.ToolbarSet = 'Peca' ;
			oFCKeditor.ReplaceTextarea() ;
		}
	<? } ?>
	function pesquisaPeca(peca,tipo){
		if ($.trim(peca.value).length > 2){
			Shadowbox.open({
				content:    "peca_pesquisa_nv.php?"+tipo+"="+peca.value,
				player: "iframe",
				title:      '<?=traduz("Peça")?>',
				width:  800,
				height: 500
			});
		}else{
			alert('<?=traduz("Informar toda ou parte da informação para realizar a pesquisa!")?>');
			peca.focus();
		}
	}

	function retorna_dados_peca(peca,referencia,descricao,ipi,origem,estoque,unidade,ativo){
		window.location = "peca_cadastro.php?peca="+peca;
	}

	function gravaDados(name, valor){
		try{
			$("input[name="+name+"]").val(valor);
		} catch(err){
			return false;
		}
	}

	function verificarIntervencao(campo,campo_2){
		$("input[name='"+campo+"']").each( function (){
			if (this.checked){
				$("input[name='"+campo_2+"']").each( function (){
					this.checked = false;
				});
			}
		});
	}
</script>

<script>
	function createRequestObject(){
		var request_;
		var browser = navigator.appName;
		if(browser == "Microsoft Internet Explorer"){
			 request_ = new ActiveXObject("Microsoft.XMLHTTP");
		}else{
			 request_ = new XMLHttpRequest();
		}
		return request_;
	}

	var http3 = new Array();

	function verificaPrioridade(prio){

		var curDateTime = new Date();
		http3[curDateTime] = createRequestObject();
		var campo = document.getElementById('result');

		if (campo==false){
			return;
		}

		if (campo.innerHTML = ""){
			campo.style.display = "none";
		}

		url = "<?php $PHP_SELF; ?>?verifica_prio=true&prioridade="+prio+"&data="+curDateTime;
		http3[curDateTime].open('get',url);
		http3[curDateTime].onreadystatechange = function(){
			if(http3[curDateTime].readyState == 1) {
				campo.innerHTML = traduz(" <font size='1' face='verdana'>&nbsp;Aguarde..</font>");
			}
			if (http3[curDateTime].readyState == 4){
				if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
					var results = http3[curDateTime].responseText;
					campo.innerHTML = results;
				}else {
					alert('<?=traduz("Ocorreu um erro")?>');
				}
			}
		}
		http3[curDateTime].send(null);
	}

	function verificaDescricaoES(es, pt) {
		if (pt.length == 0 && es.length > 0) {
			$("input[name=descricao]").val(es);
		}
	}

	<? if ($login_fabrica == 85) { ?>

		$(document).ready(function(){

			var cont_click = 0;

			$('input[name=mostrar_liquidacao_posto]').change(function(){

				if(cont_click%2 == 0){

					var ref = $('input[name=referencia]').val();
					var cod = '<?=$peca?>';

					$.ajax({
						url: "<?php echo $_SERVER['PHP_SELF']; ?>",
						type: "POST",
						data: {
							verifica_lista_basica_peca : "sim",
							ref : ref,
							cod : cod
						},
						complete: function(data){

							data = data.responseText;
							if(data == "fail"){
								alert('<?=traduz("Para que e peça seja mostrada na Loja Virtual e página inicial do Posto, favor inserir na Lista Básica de um Produto.")?>');
							}

						}
					});

				}

				cont_click++;

			});

		});

	<? } ?>

</script>

<style type='text/css'>
.conteudo {
	font: bold xx-small Verdana, Arial, Helvetica, sans-serif;
	color: #000000;
}
.ac{
	color:#AC9020;
	cursor: help;
	/*border-bottom: 1px dashed #F8F0D6;*/
}
</style>
<style type="text/css">

body {
	margin: 0px;
}

.hidden {
	display: none !important;
}
.titulo {
	font-family: Arial;
	font-size: 7pt;
	text-align: right;
	color: #000000;
	background: #ced7e7;
}
.titulo2 {
	font-family: Arial;
	font-size: 7pt;
	text-align: center;
	color: #000000;
	background: #ced7e7;
}
.inicio {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;
	color: #485989;
}

.conteudo {
	font-family: Arial;
	FONT-SIZE: 8pt;
	font-weight: bold;
	text-align: left;
	background: #F4F7FB;
}

.Div{
	BORDER-RIGHT:     #6699CC 1px solid;
	BORDER-TOP:       #6699CC 1px solid;
	BORDER-LEFT:      #6699CC 1px solid;
	BORDER-BOTTOM:    #6699CC 1px solid;
	FONT:             10pt Arial ;
	COLOR:            #000;
	BACKGROUND-COLOR: #FfFfFF;
}

.frm{
	font-weight: normal;
}

.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}


.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.titulo_tabela_novo{
	background-color:#7092BE;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.sucesso{
	background-color:green;
	font: bold 16px "Arial";
	color:#FFFFFF;
	text-align:center;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.subtitulo{

	background-color: #7092BE;
	font:bold 14px Arial;
	color: #FFFFFF;
}

table tr td p{
	padding-left:20px;
}

.class_sim {
	background-color: #a3eca5;
}

.class_nao {
	background-color: #ffb2b2;
}

</style>
<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
</head>

<body>
<center>
<div id="wrapper">
<form name="frm_peca" method="post" action="<?= $PHP_SELF; ?>" enctype='multipart/form-data'>
<input type="hidden" name="peca" value="<?= $peca ?>">

<!-- formatando as mensagens de erro -->
<? if (strlen($msg_erro) > 0) {
	if (strpos($msg_erro,"ERROR: ") !== false) {
			$erro = traduz("Foi detectado o seguinte erro:<br>");
			$msg_erro = substr($msg_erro, 6);
		}

		// retira CONTEXT:
		if (strpos($msg_erro,"CONTEXT:")) {
			$x = explode('CONTEXT:',$msg_erro);
			$msg_erro = $x[0];
		}
 }

$camporeadonly = "";
if ($login_fabrica == 186 && strlen($peca) > 0) {
	$camporeadonly = "readonly";
}
  ?>
	<br>
	<table width='700' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#D9E2EF'  style="font-size:11px;">
	<? if(strlen($msg_erro)>0){ 
	   		$cols_msg_erro = ($login_fabrica == 42) ? "6" : "5";
	?>
		<tbody><tr class="msg_erro"><td colspan="<?=$cols_msg_erro?>"><? echo $msg_erro;?> </td></tr></tbody>
	<? } ?>
	<? if(strlen($msg)>0){ ?>
		<tr class="sucesso"><td colspan="12"><? echo $msg;?> </td></tr>
	<? } ?>
	<tr><td colspan="100%" class="titulo_tabela"><?=traduz("Cadastro de Peças");?></td></tr>
	<tr>
		<td width="50">&nbsp;</td>
		<?php if ($login_fabrica == 171) {//hd-3625122 - fputti?>
			<td><?=traduz('Referência FN')?></td>
		    <td><?=traduz('Referência Grohe')?></td>
		<?php } else { ?>
			<td><?=traduz('Referência *')?></td>
		<?php }
		if ($login_fabrica == 187) { ?>
			<td><?= traduz("Referência MCASSAB"); ?></td>
		<?php } 
			$cols_origem = (in_array($login_fabrica, array(123,194)) ) ? 1 : 2;
		?>
		<td colspan='<?=$cols_origem?>'><?=traduz('Descrição *')?></td>
		<td><?=traduz('Origem *')?></td>
		<?php if ($login_fabrica == 123) { ?>
				<td><?=traduz('Prev. Chegada')?></td>
		<?php } ?>
	</tr>
	<tr>
		<td width="50">&nbsp;</td>
		<?php if (in_array($login_fabrica,array(171))) {//hd-3625122 - fputti?>
		<td>
			<input id='ref_fn' class='frm' type="text" style="font:11px Arial;font-weight: bold;" name="referencia_fabrica" value="<?php echo $referencia_fabrica;?>" size="10" maxlength="20">
			<a href="javascript: pesquisaPeca (document.frm_peca.referencia_fabrica,'referencia')"><img src="imagens/lupa.png" ></a>
		</td>
		<?php } ?>
		<td><input style="font:11px Arial;font-weight: bold;" class='frm' type="text" name="referencia" <?php echo $camporeadonly;?> value="<? echo $referencia ?>" size="10" <? if ($login_fabrica==20){?> maxlength="10" <? }else{ ?> maxlength="20" <?}?>><a href="javascript: pesquisaPeca (document.frm_peca.referencia,'referencia')"><img src="imagens/lupa.png" ></a></td>

		<?php
			$descricao = ( $login_fabrica == 10 && ! empty($descricao_estendida) ) ? $descricao_estendida : $descricao ;
			$descricao = stripcslashes($descricao);
			$descricao = htmlspecialchars($descricao);
		?>
		<?php if (in_array($login_fabrica,array(187))) {?>
		<td>
			<input id='ref_fn' class='frm' type="text" style="font:11px Arial;font-weight: bold;" name="referencia_fabrica" value="<?php echo $referencia_fabrica;?>" size="10" maxlength="20">
		</td>
		<?php } ?>
		<td colspan="2"><input style="font:11px Arial;font-weight: bold;" class='frm' type="text" name="descricao" value="<?=$descricao?>" size="<?php echo (in_array($login_fabrica, array(94)))?'58':'30'; ?>" maxlength="<?php echo (in_array($login_fabrica, array(1, 10)))?'150':'50'; ?>"><a href="javascript: pesquisaPeca (document.frm_peca.descricao,'descricao')"><img src="imagens/lupa.png" ></a></td>
		<td ><select name='origem' size='1' class='frm' style="font:11px Arial;font-weight: bold;">
		<? if($login_fabrica == 91){?>
			<option style="font:11px Arial;font-weight: bold;" value='NAC' <? if ($origem == 'NAC' OR $origem == 1) echo " selected " ?> > <?=traduz('Fabricação')?> </option>
			<option style="font:11px Arial;font-weight: bold;" value='IMP' <? if ($origem == 'IMP' OR $origem == 2) echo " selected " ?> > <?=traduz('Terceiro Importado')?> </option>
			<option style="font:11px Arial;font-weight: bold;" value='TER' <? if ($origem == 'TER') echo " selected " ?> > <?=traduz('Terceiro Nacional')?></option></td>
		<? }else{ ?>
			<option style="font:11px Arial;font-weight: bold;" value='NAC' <? if ($origem == 'NAC' OR $origem == 1) echo " selected " ?> > <?=traduz('Fabricação')?> </option>
			<option style="font:11px Arial;font-weight: bold;" value='IMP' <? if ($origem == 'IMP' OR $origem == 2) echo " selected " ?> > <?=traduz('Importado')?> </option>
			<option style="font:11px Arial;font-weight: bold;" value='TER' <? if ($origem == 'TER') echo " selected " ?> > <?=traduz('Terceiros')?> </option>
			<?php if($login_fabrica == 1){ ?>
					<option style="font:11px Arial;font-weight: bold;" value='FAB/SUB' <? if ($origem == 'FAB/SUB') echo " selected " ?> > <?=traduz('Fabricação/Subsidiado')?> </option>
					<option style="font:11px Arial;font-weight: bold;" value='IMP/SUB' <? if ($origem == 'IMP/SUB') echo " selected " ?> > <?=traduz('Importado/Subsidiado')?> </option>
					<option style="font:11px Arial;font-weight: bold;" value='TER/SUB' <? if ($origem == 'TER/SUB') echo " selected " ?> > <?=traduz('Terceiros/Subsidiado')?> </option>
					<option style="font:11px Arial;font-weight: bold;" value='FAB/SA' <? if ($origem == 'FAB/SA') echo " selected " ?> > <?=traduz('Fabricação/Semi acabado')?> </option>
					<option style="font:11px Arial;font-weight: bold;" value='IMP/SA' <? if ($origem == 'IMP/SA') echo " selected " ?> > <?=traduz('Importado/Semi acabado')?> </option>
			<?php }
		} ?>
			</select>
			<?php if ($login_fabrica == 123) { ?>
					<td><input style="font:11px Arial;font-weight: bold;" class='frm' type="text" name="prev_chegada" id="prev_chegada" value="<? echo $prev_chegada ?>" size="10" maxlength="10"></td>
			<?php } ?>
		</td>
		
	</tr>
	<?php
		$colspanEstoque = (!in_array($login_fabrica, array(186,194))) ? "colspan='2'" : "";
	?> 
	<tr>
		<td width="50">&nbsp;</td>
		<td>IPI <?php echo ($login_fabrica!=163)?"*":""; ?></td>
		<td <?=$colspanEstoque?>><?=traduz('Estoque');?></td>
		<?php if ($login_fabrica == 186){?>
		<td ><?=traduz('Endereço');?></td>
		<?php }?>
		<?php if ($login_fabrica == 194){ ?>
		<td ><?=traduz('Estoque Previsto');?></td>
		<?php } ?>
		<td><?=traduz('Unidade')?></td>
		<?php if ($login_fabrica == 123) { ?>
				<td><?=traduz('Disponibilidade')?></td>
		<?php } ?>
	</tr>
	<tr>
		<td width="50">&nbsp;</td>
		<td><input style="font:11px Arial;font-weight: bold;" class='frm' type="text" name="ipi" value="<? echo $ipi ?>" size="10" maxlength="20"></td>
		<td <?=$colspanEstoque?>><input style="font:11px Arial;font-weight: bold;" class='frm' type="text" name="estoque" value="<? echo $estoque ?>" size="10" maxlength="20"></td>
		<?php if ($login_fabrica == 186){?>
		<td ><input style="font:11px Arial;font-weight: bold;" class='frm' type="text" name="xendereco" value="<?php echo $parametros_adicionais->endereco;?>" size="10"></td>
		<?php }?>
		<?php 
			if ($login_fabrica == 194){ 
				if (!empty($estoque_previsto)){
					$estoque_previsto = mostra_data($estoque_previsto);
				}
		?>
		<td><input disabled="true" style="font:11px Arial;font-weight: bold;" class='frm' type="text" name="estoque_previsto" value="<? echo $estoque_previsto ?>" size="10" maxlength="10"></td>
		<?php 
			} 
		?>
		<td><input style="font:11px Arial;font-weight: bold;" class='frm' type="text" name="unidade" value="<? echo $unidade ?>" size="10" maxlength="10"></td>
		<?php if ($login_fabrica == 123) { ?>
				<td><input style="font:11px Arial;font-weight: bold;" class='frm <?=$classColor?>' disabled type="text" name="disponibilidade" value="<? echo $disponibilidade ?>" size="10" maxlength="15"></td>
		<?php } ?>
	</tr>

	<?php 
		if ($login_fabrica == 175){
			$colspan_classificacao = "";
		}else if ($login_fabrica == 171){
			$colspan_classificacao = "1";
		}else{
			$colspan_classificacao = "2";
		}
	?>
	<tr>
		<td width="50">&nbsp;</td>
		<td><?=traduz('Peso Kg')?></td>
		<?if ($fabricas_usam_NCM or $telecontrol_distrib) { ?>
			<td><?=$label_class_fiscal?></td>
			<td><?=traduz('NCM')?></td>
		<?} elseif (isFabrica(42)) {?>
			<td><?=$label_class_fiscal?></td>
			<td><?=traduz('Prev. Chegada')?></td>
            <td title="Disponibilidade"><?=traduz('Status Disp.')?></td>
		<?} else {
			?>
			<td colspan='<?=$colspan_class;?>'><?=$label_class_fiscal?></td>
		<?}?>
		<?php if ($login_fabrica == 175){ ?>
			<td><?=traduz('Qtde de disparos')?></td>
		<?php } ?>
		<?php 
			$obrigatorio = "";
			if (in_array($login_fabrica, array(184,200))){ 
				$obrigatorio = "*";
			} 
		?>
		<td><?=traduz('Múltiplo').$obrigatorio?></td>
	</tr>

	<tr>
		<td width="50">&nbsp;</td>
		<td><input style="font:11px Arial;font-weight: bold;" class='frm' type="text" name="peso" value="<? echo $peso ?>" size="10" maxlength="20"></td>
		<?if ($fabricas_usam_NCM or $telecontrol_distrib) { ?>
		<td><input style="font:11px Arial;font-weight: bold;" class='frm' type="text" name="classificacao_fiscal" value="<? echo $classificacao_fiscal ?>" size="10" maxlength="10"></td>
		<td>
			<input style="font:11px Arial;font-weight: bold;width:100px;" class='frm' type="text" name="ncm" value="<?=$ncm?>" size="15" maxlength="16">
		</td>
		<?} else {?>
		<td><input style="font:11px Arial;font-weight: bold;" class='frm' type="text" name="classificacao_fiscal" value="<? echo $classificacao_fiscal ?>" size="10" maxlength="10"></td>
		<?}?>
<?php
            $colspanMultiplo = 3;
            if (isFabrica(42)):
                $colspanMultiplo = 1;
 ?>
        <td align="left">
            <input class="frm" type="text" name="previsao_entrega" size='14' value="<?=($parametros_adicionais->previsaoEntrega)?>">
        </td>
        <td align="left">
<?php
	    $parametros_adicionais->status[0] = (strlen($parametros_adicionais->status[0]) == 0) ? "D" : $parametros_adicionais->status[0];
        echo array2select(
            'status_disponibilidade', 'status_disponibilidade',
            ['D' => traduz('Disponível'), 'I' => traduz('Indisponível')],
            ($_POST['status_disponibilidade'] ? : $parametros_adicionais->status[0]),
            " class='frm'", ' ', true);
?>
        </td>
        <?php endif; ?>

        <?php if ($login_fabrica == 175){ ?>
            <td  colspan="" align="left"><input style="font:11px Arial;font-weight: bold;" class='frm' type="text" name="qtde_disparos" value="<?=$qtde_disparos?>" size="10" maxlength="20"></td>
	    <?php } ?>
        <td  colspan="<?=$colspanMultiplo?>" align="left"><input style="font:11px Arial;font-weight: bold;" class='frm' type="text" name="multiplo" value="<? if (strlen($multiplo) == 0){echo "1";}else{echo "$multiplo";}  ?>" size="10" maxlength="20"></td>
	</tr>
	<?php if(in_array($login_fabrica, [11,104,172])){ ?>
		<tr>
			<td width="50">&nbsp;</td>
			<td><?=traduz('Qtde Demanda')?></td>
		</tr>
		<tr>
			<td width="50">&nbsp;</td>
			<td>
				<input style="font:11px Arial;font-weight: bold;" class='frm'  type="text" name="qtde_demanda" value="<?=$qtde_demanda?>" size="6">
			</td>
		</tr>
	<?php } 

	if (in_array($login_fabrica, [87])) { ?>

		<tr>
			<td width="50">&nbsp;</td>
			<td>Empresa *</td>
		</tr>
		<tr>
			<td width="50">&nbsp;</td>
			<td>
				<select name="marca" class="frm" style="width: 200px;">
					<option value="">Selecione a empresa da peça</option>
					<?php
					$sqlEmpresaPeca = "SELECT marca, codigo_marca, descricao 
									   FROM tbl_marca 
									   JOIN tbl_empresa using(empresa) 
									   WHERE tbl_marca.fabrica = {$login_fabrica}
									   AND tbl_marca.ativo";
					$resEmpresaPeca = pg_query($con, $sqlEmpresaPeca);

					while ($dadosEmpresa = pg_fetch_object($resEmpresaPeca)) {

						$selected = ($marca == $dadosEmpresa->marca) ? "selected" : "";

						?>

						<option value="<?= $dadosEmpresa->marca ?>" <?= $selected ?>>
							<?= $dadosEmpresa->codigo_marca ?> - <?= $dadosEmpresa->descricao ?>
						</option>

					<?php
					} ?>
				</select>
			</td>
		</tr>

	<?php
	}
	?>

	<?php
	if (in_array($login_fabrica, array(11, 172))) { /*HD - 4323017*/ 
		$auxiliar = array(11, 172);
		$json_options_select_fabricas = json_encode($auxiliar);
	?>
		
		<script>
			$(function() {
				var multiselect = {
				    j: null,
				    k: null,
				    options_selected: '<?=$json_options_select_fabricas?>',
				    init: function(j, options_selected) {
				        this.j = $(j);

				        $(this.j).find("option").addClass("option-selectable");

				        var k = this.j.clone();

				        this.k = k;

				        $(this.k).attr({ id: "multiselect-selected_grupos" });
				        $(this.k).attr({ name: "selected_grupos[]" });
				        $(this.k).find("option").remove();
				        $(this.j).parent().after($("<span></span>", { css: { "text-align": "left", display: "inline-block" }, html: "Selecionados:<br />" }).append(k));
				        $(this.j).attr({ id: "multiselect-selectable_grupos", name: "multiselect-selectable_grupos" });

				        if (multiselect.options_selected != undefined && multiselect.options_selected != '' && multiselect.options_selected != null) {
				            multiselect.options_selected = JSON.parse(multiselect.options_selected);

				            $(this.j).find("option").each(function(){
				                if ($.inArray($(this).val(), multiselect.options_selected) != -1) {
				                    var option_clone = $(this).clone();
				                    $(option_clone).prop({ selected: true }).addClass("option-selectable");
				                    $(multiselect.k).append(option_clone);
				                    $(this).remove();
				                }
				            });
				        }

				        this.trigger();
				    },
				    trigger: function() {
				        $(document).delegate("#multiselect-selectable_grupos option.option-selectable", "click", function() {
				            var o = $(this).clone();
				            $(o).prop({ selected: true });
				            $(multiselect.k).append(o);
				            $(this).remove();
				        });

				        $(document).delegate("#multiselect-selected_grupos option.option-selectable", "click", function() {
				            var o = $(this).clone();
				            $(o).prop({ selected: false });
				            $(multiselect.j).append(o);
				            $(this).remove();
				            $("#multiselect-selected_atend").find("option").prop({ selected: true });
				        });
				    }
				}

				multiselect.init("#select_fabricas");
			});
		</script>

		<?php
		$select_fabricas = array();
		switch ($login_fabrica) {
			case 11:
				$select_fabricas[0]["option"] 	 = 11;
				$select_fabricas[0]["descricao"] = "Aulik";

				$select_fabricas[1]["option"] 	 = 172; 
				$select_fabricas[1]["descricao"] = "Pacific";
			break;

			case 172: 
				$select_fabricas[0]["option"] 	 = 172; 
				$select_fabricas[0]["descricao"] = "Pacific";

				$select_fabricas[1]["option"] 	 = 11;
				$select_fabricas[1]["descricao"] = "Aulik";
			break;
			
			default:
				unset($select_fabricas);
			break;
		}
		
		if (!empty($select_fabricas)) { ?>
			<tr>
				<td width="50">&nbsp;</td>
				<td><?=traduz('Fábrica')?></td>
			</tr>
			<tr>
				<td width="50">&nbsp;</td>
				<td>
					<select multiple="multiple" id="select_fabricas" name="select_fabricas[]">
						<?php
							foreach ($select_fabricas as $key => $select) {
								?> <option value="<?=$select['option'];?>"> <?=$select['descricao'];?> </option> <?
							}
						?>
					</select>
				</td>
			</tr>
		<?php }
	}

	if ($login_fabrica == 1) {?>
	<tr>
		<td width="50">&nbsp;</td>
		<td><?=traduz('Informações')?></td>
	</tr>
	<tr>
		<td bgcolor='#D9E2EF' style='font-size:10px' align='left'  nowrap>&nbsp;</td>
		<td align='left'>
		<?php
		$sql_inf = "SELECT informacoes FROM tbl_peca WHERE peca = {$peca} AND fabrica = {$login_fabrica};";
		$res_inf = pg_query($con,$sql_inf);
		if(pg_num_rows($res_inf) > 0){
			$info_peca = pg_fetch_result($res_inf, 0, informacoes);
		}
		?>
		<input style="font:11px Arial;font-weight: bold;" class='frm' type="text" name="classificacao_fiscal" value="<? echo $info_peca ?>" size="10" maxlength="10" readonly >
		</td>
	</tr>
	<?
	}

	if(in_array($login_fabrica, array(30,151,158))) { ?>
	<tr>
		<td width="50">&nbsp;</td>
		<td><?=traduz('Família')?></td>
	</tr>
	<tr>
		<td bgcolor='#D9E2EF' style='font-size:10px' align='left'  nowrap>&nbsp;</td>
		<td align='left'>
		<?
		$sql =	"SELECT * FROM tbl_familia_peca WHERE fabrica = $login_fabrica AND ativo IS TRUE ORDER BY descricao;";
		$res = pg_exec($con,$sql);
		echo "<select name='familia_peca' size='1' class='frm' style='width:200px;'>";
		if (pg_numrows($res) > 0) {
			#echo "<select name='familia_peca' size='1' class='frm' style='width:200px;'>";
			echo "<option value=''>".traduz("ESCOLHA")."</option>";
			for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
				$aux_familia = trim(pg_result($res,$i,familia_peca));
				$aux_nome  = trim(pg_result($res,$i,descricao));
				echo "<option value='$aux_familia'";
				if ($familia_peca == $aux_familia) echo " selected";
				echo ">$aux_nome</option>";
			}
			#echo "</select>";
		}
			echo "</select>";
		?>
		</td>
	</tr>
	<? }
	if($login_fabrica == 101){ ?>
		<tr>
			<td width="50">&nbsp;</td>
			<td><?=traduz('Endereço/Localização no Depósito da Fábrica')?></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="50">&nbsp;</td>
			<td><input style="font:11px Arial;font-weight: bold;" class='frm' type="text" name="endereco_estoque" value="<? echo $endereco_estoque ?>" size="10" maxlength="6"></td>
			<td >&nbsp;</td>
			<td >&nbsp;</td>
		</tr>
	<? }
	if ($login_fabrica == 51) { ?>
		<tr>
			<td width="50">&nbsp;</td>
			<td><?=traduz('Qtde Max. Faturado')?></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="50">&nbsp;</td>
			<td><input style="font:11px Arial;font-weight: bold;" class='frm' type="text" name="qtde_max_site" value="<? echo $qtde_max_site ?>" size="10" maxlength="20"></td>
			<td >&nbsp;</td>
			<td >&nbsp;</td>
		</tr>
	<? }
	if ($login_fabrica == 20) { ?>
		<tr>
			<td width="50">&nbsp;</td>
			<td width='100' height='15'><?=traduz('Família')?>&nbsp;</td>
			<td >&nbsp;</td>
			<td >&nbsp;</td>
		</tr>
		<tr>
			<td >&nbsp;</td>
			<td class='conteudo'>
			<?
			##### INÍCIO FAMÍLIA #####
			$sql = "SELECT  *
					FROM    tbl_familia
					WHERE   tbl_familia.fabrica = $login_fabrica
					ORDER BY tbl_familia.descricao;";
			$res = pg_exec ($con,$sql);

			if (pg_numrows($res) > 0) {
				echo "<select class='frm' style='width: 200px;' name='familia'>\n";
				echo "<option value=''>".traduz("ESCOLHA")."</option>\n";

				for ($x = 0 ; $x < pg_numrows($res) ; $x++){
					$aux_familia = trim(pg_fetch_result($res,$x,familia));
					$aux_descricao  = trim(pg_fetch_result($res,$x,descricao));

					echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
				}
				echo "</select>\n";
			}
			##### FIM FAMÍLIA #####
			?>

			</td>
			<td >&nbsp;</td>
			<td >&nbsp;</td>
		</tr>
	<? }

    // Código somente para Bosch Security, pois eles não terão integração e neste caso
    // irão cadastrar as peças manualmente - HD 397744
	if ($login_fabrica == 96) { ?>
		<tr>
			<td width="50">&nbsp;</td>
			<td><?=traduz('Preço')?></td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td width="50">&nbsp;</td>
			<td><input style="font:11px Arial;font-weight: bold;" class='frm' type="text" name="preco_peca" value="<? echo $preco_peca; ?>" size="10" maxlength="20"></td>
			<td >&nbsp;</td>
			<td >&nbsp;</td>
		</tr>
	<? }

	// Grohe 
	if ($login_fabrica == 171){ ?>
		<tr class='show_ref'>
			<td width="50">&nbsp;</td>
            <td><?=traduz('Apresentação')?></td>
            <td><?=traduz('Descrição Detalhada')?></td>
            <td><?=traduz('Marca Detalhada')?></td>
            <td><?=traduz('Emb. 01')?></td>
        </tr>
        <tr class='show_ref'>
        	<td width="50">&nbsp;</td>
            <td> <input type='text' class='frm' name='apresentacao' id='apresentacao' style="font:11px Arial;font-weight: bold;" size="10" value='<?= $apresentacao; ?>'> </td>
            <td> <input type='text' class='frm' name='descricao_detalhada' id='descricao_detalhada' style="font:11px Arial;font-weight: bold;" size="10" value="<?= $descricao_detalhada; ?>"></td>
            <td> <input type='text' class='frm' name='marca_detalhada' id='marca_detalhada' style="font:11px Arial;font-weight: bold;" size="10" value="<?= $marca_detalhada; ?>"></td>
            <td> <input type='text' class='frm' name='emb' id='emb' style="font:11px Arial;font-weight: bold;" size="10" value='<?= $emb; ?>'> </td>
        </tr>
        <tr class='show_ref'>
        	<td width="50">&nbsp;</td>
            <td><?=traduz('Categoria')?></td>
            <td><?=traduz('NCM')?></td>
            <td><?=traduz('II%')?></td>
            <td><?=traduz('ALT (CM)')?></td>
            <td><?=traduz('LARG (CM)')?></td>
        </tr>
        <tr class='show_ref'>  
        	<td width="50">&nbsp;</td>
            <td> <input type='text' class='frm' name='categoria' id='categoria' style="font:11px Arial;font-weight: bold;" size="10" value='<?= $categoria; ?>'> </td>
            <td> <input type='text' class='frm' name='ncm' id='ncm' style="font:11px Arial;font-weight: bold;" size="10" value='<?= $ncm; ?>'> </td>
            <td> <input type='text' class='frm' name='ii' id='ii' style="font:11px Arial;font-weight: bold;" size="10" value='<?= $ii; ?>'> </td>  
        	<td> <input type='text' class='frm' name='alt' id='alt' style="font:11px Arial;font-weight: bold;" size="10" value='<?= $alt; ?>'> </td> 
        	<td> <input type='text' class='frm' name='larg' id='larg' style="font:11px Arial;font-weight: bold;" size="10" value='<?= $larg; ?>'> </td> 
        </tr>
        <tr class='show_ref'>
        	<td width="50">&nbsp;</td>
            <td><?=traduz('COMP (CM)')?></td>
            <td><?=traduz('PESO (KG)')?></td>
            <td><?=traduz('Cod. Barras 01')?></td>
            <td><?=traduz('Custo Cip. Porto')?></td>
        </tr><tr class='show_ref'>
        	<td width="50">&nbsp;</td>
        	<td> <input type='text' class='frm' name='comp' id='comp' style="font:11px Arial;font-weight: bold;" size="10" value='<?= $comp; ?>'> </td> 
        	<td> <input type='text' class='frm' name='peso' id='peso' style="font:11px Arial;font-weight: bold;" size="10" value='<?= $peso; ?>'> </td> 
        	<td> <input type='text' class='frm' name='cod_barra' id='cod_barra' style="font:11px Arial;font-weight: bold;" size="10" value='<?= $cod_barra; ?>'> </td>
        	<td> <input type='text' class='frm' name='custo_cip' id='custo_cip' style="font:11px Arial;font-weight: bold;" size="10" value='<?= $custo_cip; ?>'> </td>
        </tr>
<?php } ?>


<?
//and $ip= "201.92.127.116"
if ($login_fabrica == 2) {
	echo "
	<tr>
		<td width='50'>&nbsp;</td>
		<td>".traduz('Mão de Obra Peça')."></td>
		<td>".traduz('Mão de Obra Posto')."</td>
		<td>".traduz('Mão de Obra Admin')."</td>
	</tr>

	<tr>
		<td width='50'>&nbsp;</td>
		<td>
			<input class='frm' type='text' name='mo_peca'  value='$mo_peca'  size='10' maxlength='20'>
		</td>
		<td>
			<input class='frm' type='text' name='mo_posto' value='$mo_posto' size='10' maxlength='20'>
		</td>
		<td colspan='4' align='center'>
			<input class='frm' type='text' name='mo_admin' value='$mo_admin' size='10' maxlength='20'></td>
	</tr>";

}
?>
  </tbody>
</table>
<?
if($login_fabrica == 20 and !empty($peca)){
	echo "<table width='700'align='center'><tr><td><div class='Div'>";
	$sql = "SELECT * FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma)='ES'";
	$res2 = pg_query($con,$sql);

	if (pg_num_rows($res2) > 0) {
		for ( $i = 0 ; $i < pg_num_rows($res2) ; $i++ ) {
		$peca                     = trim(pg_fetch_result($res2,$i,peca));
		$idioma                   = trim(pg_fetch_result($res2,$i,idioma));
		$descricao_idioma         = trim(pg_fetch_result($res2,$i,descricao));
		if($idioma == 'ES')echo traduz("Espanhol<br>");
		else               echo traduz("Inglês<br>");
		echo "Descrição: <input  type='text' class='frm' name='descricao_idioma' value='$descricao_idioma' size='30' maxlength='50' onblur='verificaDescricaoES($(this).val(), $(\"input[name=descricao]\").val())'><br><br>";
		echo "<input type='hidden' name='idioma' value='$idioma'>";
		}


	}else{
		echo traduz("Não existe descrição para essa peça em outro idioma, preencha o campo abaixo para inserir uma.<br>");
		echo traduz("Espanhol<br>");
		echo traduz("Descrição: <input  type='text' class='frm' name='descricao_idioma' value='$descricao_idioma' size='30' maxlength='50' onblur='verificaDescricaoES($(this).val(), $(\"input[name=descricao]\").val())' ><br><br>");
		echo "<input type='hidden' name='idioma' value='ES'>";
	}

	echo "</div></td></tr></table>";
}
if ($login_fabrica==21){

?>


<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
	<tr>
		<TD class='inicio' colspan='4'height='15' colspan='4'>&nbsp;<?=traduz('INFORMAÇÕES PARA VENDA')?>&nbsp;</TD>
	</tr>
	<TR>
		<td class='titulo'width='100' height='15'><?=traduz('PREÇO DE COMPRA')?>&nbsp;</td>
		<td class='conteudo' width='100' height='15'><input class='frm' type='text' name='preco_compra' value='<? echo $preco_compra; ?>'></td>
		<td class='titulo' width='100' height='15'><?=traduz('MARCA')?>&nbsp;</td>
		<td class='conteudo' width='100' height='15'><select name="marca" size="1" class="frm">
					<option ></option>
<?


$sql = "SELECT marca,nome
		FROM tbl_marca
		WHERE fabrica=$login_fabrica
		ORDER BY nome";

		$res = pg_exec ($con,$sql) ;
#echo $sql;
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			echo "<option ";
			if ($marca == pg_fetch_result ($res,$i,marca) ) echo " selected ";
					echo " value='" . pg_fetch_result ($res,$i,marca) . "'>" ;
					echo pg_fetch_result ($res,$i,nome) ;
					echo "</option>";
				}
?>
				</select>

		</td>
	</tr>
	<tr>
		<td class='titulo'><?=traduz('CATEGORIA')?></td>
		<td class='conteudo'>
		<?
		##### INÍCIO LINHA #####
		$sql = "SELECT  *
				FROM    tbl_linha
				WHERE   tbl_linha.fabrica = $login_fabrica
				ORDER BY tbl_linha.nome;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<select class='frm' style='width: 200px;' name='linha'>\n";
			echo "<option value=''>ESCOLHA</option>\n";

			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_linha = trim(pg_fetch_result($res,$x,linha));
				$aux_nome  = trim(pg_fetch_result($res,$x,nome));

				echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>\n";
			}
			echo "</select>\n";
		}
		##### FIM LINHA #####
		?>
		</td>
		<td class='titulo'><?=traduz('SUBCATEGORIA')?></td>
		<td class='conteudo'>
		<?
		##### INÍCIO FAMÍLIA #####
		$sql = "SELECT  *
				FROM    tbl_familia
				WHERE   tbl_familia.fabrica = $login_fabrica
				ORDER BY tbl_familia.descricao;";
		$res = pg_exec ($con,$sql);

		if (pg_numrows($res) > 0) {
			echo "<select class='frm' style='width: 200px;' name='familia'>\n";
			echo "<option value=''>".traduz("ESCOLHA")."</option>\n";

			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_familia = trim(pg_fetch_result($res,$x,familia));
				$aux_descricao  = trim(pg_fetch_result($res,$x,descricao));

				echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
			}
			echo "</select>\n";
		}
		##### FIM FAMÍLIA #####
		?>

		</td>
	</tr>
	<tr>
		<td class='titulo' width='100' height='15'><?=traduz('INFORMAÇÕES')?>&nbsp;</td>
		<td class='conteudo' width='100' height='15' colspan='3'><TEXTAREA NAME="informacoes" ROWS="10" COLS="70"><? echo ($informacoes)?></TEXTAREA>
		<?=traduz('Arquivo')?> <input type='file' name='arquivo' size='50' class='frm'>
		</td>
	</tr>

<?
}

$outras_info_colspan = '2';

if (in_array($login_fabrica,array(11,172))) {
    $outras_info_colspan = '3';
}

$st = "style='font-size:11px;'";
$mostraUpload = false;

if (count($_GET) == 0 && $login_fabrica == 123) {
	$st = "style='font-size:11px; width: 150px;'";
	$outras_info_colspan = '3';
	$mostraUpload = true;
}
?>

<table width='700px' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#D9E2EF' style="font:11px Arial">
	<tr><td>&nbsp;</td></tr>
    <tr class="subtitulo"><td align="center" colspan="<?=$outras_info_colspan ?>"><?=traduz('Outras Informações')?></td></tr>
	<tr>
		<td valign="top" width="350">
			<table style="font-size:11px;">
				<tr>
					<td align='right' width="100">
						<input class='frm' type="text" name="garantia_diferenciada" value="<? echo $garantia_diferenciada ?>" size="1" maxlength="3">
					</td>
					<td align='left'><?=($login_fabrica == 1) ? traduz("Garantia da peça") : traduz("Garantia Diferenciada") ?> (meses)</td>
				<tr>

				<?php
				if ( ($fabrica_peca_dev_obrigatoria || isset($usaLGR)) && !in_array($login_fabrica, [193])) {
				?>
				<tr>
					<td align='right'>
						<input type='checkbox' <? if ($devolucao_obrigatoria == 't' ) echo " checked " ?> name='devolucao_obrigatoria' value='t'>
					</td>
					<td align='left'><?=traduz('Devolução Obrigatória')?></td>
				</tr>
    			<?
    			}

    			if($login_fabrica == 6){
    			?>
				<tr>
					<td align='right'>
						<input type='checkbox' <? if ($devolucao_estoque_fabrica == 't' ) echo " checked " ?> name='devolucao_estoque_fabrica' value='t'>
					</td>
					<td align='left'><?=traduz('Devolução Estoque Fábrica')?></td>
				</tr>
				<?
				}
				?>

				<?php if ($login_fabrica == 171): ?>
				<tr>
                    <td align='right'>
                        <input type='checkbox' <?php if ($pre_selecionada == 't' ) echo " checked " ?> name='pre_selecionada' id='pre_selecionada_check' value='t'>
                    </td>
                    <td align='left'><?=traduz('Peça não cadastrada na FN')?></td>
                </tr>
				<?php endif ?>

				<tr>
					<td align='right'>
						<acronym title='<?=traduz("Esta informação trabalha em conjunto com a informação ITEM APARÊNCIA no cadastro de postos. Deixando setado como SIM, ela é bloqueada para ser lançada em Ordem de Serviço, e setando o posto autorizadao para ITEM APARÊNCIA, as Ordens de Serviço de Revenda irão permitir a digitação de Peças que sejam Itens de Aparência.")?>'>
						<input type='checkbox' <? if ($item_aparencia == 't' ) echo " checked " ?> name='item_aparencia' value='t'>
						</acronym>
					</td>
					<td align='left'><acronym title='<?=traduz("Esta informação trabalha em conjunto com a informação ITEM APARÊNCIA no cadastro de postos. Deixando setado como SIM, ela é bloqueada para ser lançada em Ordem de Serviço, e setando o posto autorizadao para ITEM APARÊNCIA, as Ordens de Serviço de Revenda irão permitir a digitação de Peças que sejam Itens de Aparência.")?>'><?=traduz('Item de Aparência')?></acronym></td>
				</tr>
				<?php if($login_fabrica == 125){ ?>
					<tr>
						<td align='right'>
							<input type="checkbox" name="kit" value="t" <?php if($aux_kit == "t"){echo " checked ";} ?>>
						</td>
						<td>KIT</td>
					</tr>
				<?php } ?>
				<? if(in_array($login_fabrica,array(6,85,80,81,52,59))) {?>
				<tr>
					<td align='right'>
						<input type='checkbox' <? if ($acumular_kit == 't' ) echo " checked " ?> name='acumular_kit' value='t'>
					</td>
					<td align='left'><?=traduz('Acumula para Kit')?></td>
				</tr>

				<?php }
				if (in_array($login_fabrica, [3,169,170])) { /* HD 35521 */
					if ($login_fabrica == 3) { ?>
						<tr>
							<td align='right'>
								<input type='checkbox' <? if ($retorna_conserto == 't' AND $intervencao_carteira != 't') echo " checked "?> name='retorna_conserto' value='t' onClick="javascript:verificarIntervencao('retorna_conserto','intervencao_carteira')">
							</td>
							<td align='left'><?=traduz('Peça sob Intervenção (<b>SAP<b>)')?></td>
						</tr>

						<tr>
							<td align='right'>
								<input type='checkbox' <? if ($intervencao_carteira == 't' ) echo " checked " ?> name='intervencao_carteira' value='t' onClick="javascript:verificarIntervencao('intervencao_carteira','retorna_conserto')">
							</td>
							<td align='left'><?=traduz('Peça sob Intervenção (<b>Carteira</b>)')?></td>
						</tr>
					<?php } else { ?>
						<tr>
							<td align='right'>
								<input type='checkbox' <?= ($intervencao_carteira == 't') ? "checked" : ""; ?> name='intervencao_carteira' value='t' />
							</td>
							<td align='left'><?=traduz('S- LCP (<b>SAP<b>)')?></td>
						</tr>
					<?php }
				}
				if(in_array($login_fabrica,array(85,45,46,99,88,11,35,127,80,20,96,59,24,6,40,172))) { ?>
				<tr>
					<td align='right'>
						<input type='checkbox' <? if ($retorna_conserto == 't' ) echo " checked " ?> name='retorna_conserto' value='t'>
					</td>
					<td align='left'>
						<?
						echo ($login_fabrica==2 OR $login_fabrica==11 or $login_fabrica == 172) ? traduz("Peça sob Intervenção") : traduz("Retorna para Conserto");
						?>
					</td>
				</tr>
				<? } ?>
				<?if ( in_array($login_fabrica,array(14,20)) ) {?>
				<tr>
					<td align='right'>
						<input class='frm' type="text" name="mao_de_obra_diferenciada" value="<? echo $mao_de_obra_diferenciada ?>" size="5" maxlength="10">
					</td>

					<td align='left'><?=traduz('Mão-de-obra Diferenciada (TROCA)')?></td>
				</tr>
				<?}?>

				<tr>
					<td align='right'>
						<input type='checkbox' <? if ($bloqueada_garantia == 't' ) echo " checked " ?> name='bloqueada_garantia' value='t'>
					</td>
					<td align='left'>
						<?if ($login_fabrica==3 OR $login_fabrica==11 or $login_fabrica == 172) {?>
							<?=traduz('Bloqueada para Garantia')?>
							</acronym>
						<?}else{?>
							<?=traduz('Bloqueada para Garantia')?>
						<?}?>
						<acronym class='ac' title='<?=traduz("Se marcada,não irá deixar lançar a peça na OS")?>'><img src='imagens/help.png'></acronym></p>
					</td>
				</tr>
				<?php if(in_array($login_fabrica, [169,170])){?>
				<tr>
					<td align='right'>
						<input type='checkbox' <? if ($peca_original == 't' ) echo " checked " ?> name='peca_original' value='t'> 
					</td>
					<td align='left'>
						Peça Original
						<acronym class='ac' title="Somente peça marcada como original estará disponível para venda."><img src='imagens/help.png'></acronym></p>
					</td>
				</tr>
				<?php } ?>

				<?php if ($login_fabrica == 42) { ?>
					<tr>
						<td align='right'>
							<input id ='peca_monitorada' type='checkbox' <? if ($peca_monitorada == 't' ) echo " checked " ?> name='peca_monitorada' value='t'>
						</td>
						<td align='left'>
							<?=traduz('Peça Monitorada (Aviso)')?>
						</td>
					</tr>
					<tr style="position: absolute;" >
						<td>
							<input id ='email_peca_monitorada' class='frm' style=" margin-left: 44%;" type="text" name="email_peca_monitorada" value="<? echo $email_peca_monitorada ?>" size="30" maxlength="80" placeholder="E-mail" >
						</td>
					</tr>
				<?php
					}

                    if (isset($anexo_peca_os)) {
                    ?>
                        <tr>
                            <td align='right'>
                            <input type='checkbox' <? if ($anexo_os == 't' ) echo " checked " ?> id='anexo_os' name='anexo_os' value='t'<?
                            if ($fabrica_qtde_anexo_peca_os) {
                                echo " onchange='$(\"#tr_qtde_anexo\").slideToggle();$(\"#qtde_fotos\").attr(\"disabled\", !(this.checked))'";
                            } ?>>
                            </td>
                            <td align='left'>
                                <label for="anexo_os">
                                    <?=traduz('Anexo na Ordem de Serviço')?>
                                </label>
                            </td>
                        </tr>
                    <?php
                        if ($fabrica_qtde_anexo_peca_os): ?>
                            <tr id="tr_qtde_anexo" <?=($anexo_os != 't')?' style="display:none"':''?>>
                                <td></td>
                                <td>
                                    <label for="qtde_fotos"><?=traduz('Máx. Imagens por Peça na OS')?>: </label>
                                    <input id="qtde_fotos" type="number" min="1" style="width:2.5em;text-align:right" max="<?=$fabrica_qtde_anexo_peca_os?>"
                                     onchange="this.setAttribute('title', 'Até '+this.value+((this.value==1)?' imagem':' imagens'));" required="true"
                                        title="Até <?=$fabrica_qtde_anexo_peca_os?> imagens" name="fabrica_qtde_anexo_peca_os" value="<?=$parametros_adicionais->qtde_anexos?>" />
                                </td>
                            </tr>
                        <?php endif;
                    }

                    if ($login_fabrica == 30) {
                    ?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($remessa_garantia == 't' ) echo " checked " ?> name='remessa_garantia' value='t'>
                        </td>
                        <td><?=traduz('Remessa em Garantia')?></td>

                    </tr>


                    <?php

                    }

					if($fabrica_usa_bloqueada_venda) {

                    ?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? echo ($bloqueada_venda == 't' ) ? " checked " : "";?> name='bloqueada_venda' id="bloqueada_venda" value='t'>
                        </td>
                        <td align='left'><?=traduz('Bloqueada para Venda')?></td>
                    </tr>
                    <?
                    }
                    if ($login_fabrica == 1) {?>
                    	<tr>
	                        <td align='right'>
	                            <input type='checkbox' <? if ($item_revenda == 't' ) echo " checked "; ?> name='item_revenda' value='t'>
	                        </td>
	                        <td align='left'><?=traduz('Item de Revenda')?></td>
	                    </tr>
                    <?php
                    }

                    if ($login_fabrica == 35) {
                    ?>
                        <tr>
                            <td align='right'>
                                <input type='checkbox' <? echo ($po_peca == 't' ) ? " checked " : "";?> name='po_peca' value='true'>
                            </td>
                            <td align='left'><?=traduz('Obrigatório PO-Peça')?></td>
                        </tr>
                    <?php
                    }
                    ?>

                    <? if ($login_fabrica == 74) { ?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($controla_saldo == 't' ) echo " checked " ?> name='controla_saldo' value='t'>
                        </td>
                        <td align='left'><?=traduz('Controla Saldo')?></td>
                    </tr>
                    <? } ?>

                    <?php if ($login_fabrica == 151) { /*HD - 6198307*/ ?>
                    	<tr>
	                        <td width="50">&nbsp;</td>
	                        <td>
	                        	<label for="informacoes_adicionais"><?=traduz('Informações Adicionais')?></label>
	                        </td>
	                    </tr>
	                    <tr>
	                        <td width="50">&nbsp;</td>
	                        <td>
	                        	<textarea name="informacoes_adicionais" id="informacoes_adicionais" class="frm" rows="6"><?=$informacoes;?></textarea>
	                        </td>
	                    </tr>
                    <?php } ?>

                </table>
            </td>

            <td valign="top">
                <table <?=$st?>>
                    <? if(!in_array($login_fabrica,array(117,121))) { ?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($acessorio == 't' ) echo " checked " ?> name='acessorio' value='t'>
                        </td>
                        <td align='left'><?php echo ($login_fabrica == 153)? traduz("Componente") : traduz("Acessório") ?></td>
                    </tr>
                    <?
                    }

                    if (in_array($login_fabrica, array(6,129,98,81,15,72,42,35,127,80,90,134,96,117,142,149,151,94,157,161,176))) {
                    ?>

                        <td align='right'>
                            <input type='checkbox' <? if ($aguarda_inspecao == 't' ) echo " checked " ?> name='aguarda_inspecao' value='t'>
                        </td>
                        <td align='left'><?=traduz('Aguarda Inspeção')?></td>
                    </tr>
                    <?php
                    }
                    ?>

                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($peca_critica == 't' ) echo " checked " ?> name='peca_critica' id="peca_critica" value='t'>
                        </td>
                        <td align='left'>
<?php
                            if (in_array($login_fabrica,array(11,172))) {
?>
                            <acronym title='<?=traduz("Se marcada,a OS entrará em Intervenção de Suprimentos")?>'><?=traduz('Peça Crítica')?></acronym>
<?php
                            } else {
                                echo ($login_fabrica==35) ? traduz('Peça Crítica Garantia') : traduz('Peça Crítica');
                            }
?>
                        </td>
                    </tr>

                    <?if ($login_fabrica == 35) {?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? echo ($peca_critica_venda == 't' ) ? " checked " : "";?> name='peca_critica_venda' value='t'>
                        </td>
                        <td align='left'><?=traduz('Peça Crítica Venda')?></td>
                    </tr>
                    <?} ?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($produto_acabado == 't' ) echo " checked " ?> name='produto_acabado' value='t'>
                        </td>
                        <td align='left'><?=traduz('Produto Acabado')?></td>
                    </tr>
                    <?php if (in_array($login_fabrica, [177])) { ?>
	                        <tr>
		                        <td align='right'>
		                            <input type='checkbox' <? if ($lote == 't' ) echo " checked " ?> name='lote' value='t'>
		                        </td>
		                        <td align='left'>Lote</td>
		                    </tr>
		                    <tr>
		                        <td align='right'>
		                            <input type='checkbox' <? if ($caneca == 't' ) echo " checked " ?> name='caneca' value='t'>
		                        </td>
		                        <td align='left'><?=traduz('Caneca')?></td>
		                    </tr>
                    <?php } ?>
                    <?
                     if (in_array($login_fabrica,array(6,45,46,1,72,88,91,90,80,101,20,96))) { ?>

                    <tr>
                        <td align='right'><input type='checkbox' <? if ($mero_desgaste == 't' ) echo " checked " ?> name='mero_desgaste' value='t'></td>
                        <td align='left'><?=traduz('Mero Desgaste')?></td>
                    </tr>
                    <?php
			   }
			   // arr_login_fabrica_ativo da Black é separado
                     if ($login_fabrica == 1) { ?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($ativo == 't' ) echo " checked "; ?> name='ativo' value='t'>
                        </td>
                        <td align='left'><font color='#880000'><?=traduz('ATIVO')?></font></td>
                    </tr>
                    <?php }

                    if ($login_fabrica == 7) { ?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($cobrar_servico == 't' ) echo " checked " ?> name='cobrar_servico' value='t'>
                        </td>
                        <td align='left'><?=traduz('Cobra Serviço')?></td>
                    </tr>
<?php
                    }
                    if (in_array($login_fabrica,array(11,172))) {
?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($reembolso == 't' ) echo " checked " ?> name='reembolso' value='t'>
                        </td>
                        <td align='left'><?=traduz('Reembolso')?></td>
                    </tr>
<?php
                    }
                    if (in_array($login_fabrica,array(11,50,172))) {
                        if (in_array($login_fabrica,array(11,172))) {
                            echo '</table></td>';
                            echo '<td valign="top"><table>';
                        }
?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($peca_unica_os == 't' ) echo " checked "; ?> name='peca_unica_os' value='t'>
                        </td>
                        <?php
                        if ($login_fabrica == 50) {
                        ?>
                            <td align='left'><?=traduz('Peça única na OS')?></td>
                        <?php
                        } else {
                        ?>
                            <td align='left'><nobr><?=traduz('Peça crítica')?></nobr><br><nobr><?=traduz('única na OS')?></nobr></td>
                        <?php
                        }
                        ?>
                    </tr>
<?php
                    }

                    if (in_array($login_fabrica,array(11,172))) {
?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <?php if ($item_balcao == 't' ) echo " checked " ?> name='item_balcao' value='t'>
                        </td>
                        <td align='left'><?=traduz('Item Balcão/Revenda')?></td>
                    </tr>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($embalagens == 't' ) echo " checked " ?> name='embalagens' value='t'>
                        </td>
                        <td align='left'><?=traduz('Embalagens/Calços')?></td>
                    </tr>
<?php
                    }
                    if ($login_fabrica == 3 OR $login_fabrica == 51) {
?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($troca_obrigatoria == 't' ) echo " checked " ?> name='troca_obrigatoria' value='t'>
                        </td>
                        <td align='left'><?=traduz('Troca Obrigatória')?></td>
                    </tr>
                    <? } ?>

                    <? if ($login_fabrica == 45) { ?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($gera_troca_produto == 't' ) echo " checked " ?> name='gera_troca_produto' value='t'>
                        </td>
                        <td align='left'><?=traduz('Gerar Troca de Produto')?></td>
                    </tr>
                    <? } ?>

                    <?php
                    //o campo da BlackDecker está em outro lugar por causa da Table criada.
                    if (in_array($login_fabrica,$arr_login_fabrica_ativo) or $login_fabrica > 85) { ?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($ativo == 't' ) echo " checked "; ?> name='ativo' value='t'>
                        </td>
                        <td align='left'><font color='#880000'><?= (($login_fabrica == 30) ? traduz('Status da Rede') : traduz('ATIVO')).$obrigatorio; ?></font></td>
                    </tr>
                    <? } ?>
                    <?php if (in_array($login_fabrica,array(91))) { ?>
                        <tr>
                         <td align='right'>
                             <input type='checkbox' <? if ($etiqueta == 't' ) echo " checked " ?> name='etiqueta' value='t'>
                         </td>
                         <td align='left'><?=traduz('Necessita Etiqueta')?></td>
                        </tr>
                    <? } ?>
                    <?php if (in_array($login_fabrica,array(190))) { ?>
                        <tr>
                         <td align='right'>
                             <input type='checkbox' <?php if ($consumiveis == 't' ) echo " checked " ?> name='consumiveis' value='t'>
                         </td>
                         <td align='left'>Consumíveis</td>
                        </tr>
                    <? } ?>
                    <?php if (in_array($login_fabrica,array(30))) { ?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($uso_interno == 't' ) echo " checked "; ?> name='uso_interno' value='t'>
                        </td>
                        <td align='left'><font color='#880000'><?=traduz('Status uso Interno')?></font></td>
                    </tr>
                    <? } ?>

                    <?php // HD 706867
                        $sql = "SELECT fabrica FROM tbl_fabrica WHERE fabrica = $login_fabrica AND fatura_manualmente";
                        $res = pg_query($con,$sql);
                        if( pg_num_rows($res) ) {
                            $fabrica_fatura_manualmente = true;
                        }
                    ?>

                    <? if ($fabrica_fatura_manualmente === true && in_array($login_fabrica,array(43))) { ?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($faturada_manualmente == 't' ) echo " checked "; ?> name='faturada_manualmente' value='t'>
                        </td>
                        <td align='left'><?=traduz('Faturar Manualmente')?></font></td>
                    </tr>
                    <? } ?>

                    <? if (in_array($login_fabrica, array(7,165))) { #HD 47695 ?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($placa == 't' ) echo " checked " ?> name='placa' value='t'>
                        </td>
                        <td align='left'><?=traduz('Placa')?></td>
                    </tr>
                    <? }
                    if(in_array($login_fabrica,array(6,14,45,90,72,47,24,169,170))) {?>

                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($pre_selecionada == 't' ) echo " checked " ?> name='pre_selecionada' value='t'>
                        </td>
                        <td align='left'><?=(in_array($login_fabrica, array(169,170))) ? traduz("Peça Faltante") : traduz("Pre - Selecionada") ?></td>
                        <? #HD 101357 acrecentei o campo numero de serie obrigatorio?>
                    </tr>

                    <?php
                    }
                    if (in_array($login_fabrica, array(30,169,170))) {
                    ?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($remessa_garantia_compressor == 't' ) echo " checked " ?> name='remessa_garantia_compressor' value='t'>
                        </td>
                        <td><?= ($login_fabrica == 30) ? traduz('Remessa em Garantia Compressor') : traduz('Compressor'); ?></td>
                    </tr>
                    <?php
                    }
                    ?>

                    <?php
	                    if( in_array($login_fabrica, array(167,175,203))){//HD-3428297
	                    	if ($login_fabrica == 175){
	                    		$label_serie = traduz("Número de Série");
	                    	}else{
	                    		$label_serie = traduz("Série da Peça");
	                    	}
	                ?>
                		<tr>
                            <td align='right'>
                            <input type='checkbox' <? if ($numero_serie_peca == 't' ) echo " checked " ?> name='numero_serie_peca' value='t' >
                            </td>
                            <td align='left'><?=$label_serie?></td>
                        </tr>
	                <?php
	                    }
                    ?>

                    <? if($login_fabrica==3){?>
                        <tr>
                            <td align='right'>
                            <input type='checkbox' <? if ($numero_serie_peca == 't' ) echo " checked " ?> name='numero_serie_peca' value='t' >
                            </td>
                            <td align='left'><?=traduz('Nº Série Obrigatório (Informática)')?></td>
                        </tr>
                        <tr>
                            <td align='right'>
                            <input type='checkbox' <? if ($upload_fotos == 't' ) echo " checked " ?> name='upload_fotos' value='t' >
                            </td>
                            <td align='left'><?=traduz('Upload Fotos')?></td>
                        </tr>
                        <tr id="qtde_fotos" style="display: <?=(($upload_fotos == 't') ? 'table-row' : 'none')?>;">
                            <td>&nbsp;</td>
                            <td align='left'>
                                Qtde Fotos
                                <select name="qtde_fotos" style="height: 20px; font-size: 12px;">
                                    <option <?=($qtde_fotos == 1) ? "SELECTED" : ""?> >1</option>
                                    <option <?=($qtde_fotos == 2) ? "SELECTED" : ""?> >2</option>
                                    <option <?=($qtde_fotos == 3) ? "SELECTED" : ""?> >3</option>
                                    <option <?=($qtde_fotos == 4) ? "SELECTED" : ""?> >4</option>
                                    <option <?=($qtde_fotos == 5) ? "SELECTED" : ""?> >5</option>
                                    <option <?=($qtde_fotos == 6) ? "SELECTED" : ""?> >6</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td align='right'>
                            <input type='checkbox' <? if ($serial_lcd == 't' ) echo " checked " ?> name='serial_lcd' value='t' >
                            </td>
                            <td align='left'><?=traduz('Serial LCD Obrigatório')?></td>
                        </tr>
                    <?}?>

                    <? if ($login_fabrica == 5) { ?>
                    <tr>
                        <td align='right'>
                            <input type='checkbox' <? if ($peca_pai == 't' ) echo " checked " ?> name='peca_pai' value='t'>
                        </td>
                        <td align='left'><?=traduz('Peça Pai')?></td>
                    </tr>
<?php
                    }
                    if (in_array($login_fabrica,array(138))) {
?>
                    <tr>
                        <td align="right">
                            <input
                                type="checkbox"
                                name="parametros_adicionais[tipo_peca]"
                                value="compressor"
                                <?php echo ($_REQUEST['parametros_adicionais']['tipo_peca']=='compressor' or $parametros_adicionais->tipo_peca=='compressor') ?'checked="checked"':'' ?>
                            />
                        </td>
                        <td align="left">
                            <?=traduz('Compressor')?>
                        </td>
                    </tr>
                    <tr>
                        <td align="right">
                            <input
                                type="checkbox"
                                name="parametros_adicionais[tipo_peca]"
                                value="serpentina"
                                <?php echo ($_REQUEST['parametros_adicionais']['tipo_peca']=='serpentina' or $parametros_adicionais->tipo_peca=='serpentina') ?'checked="checked"':'' ?>
                            />
                        </td>
                        <td align="left">
                            <?=traduz('Serpentina')?>
                        </td>
                    </tr>
                    <script type="text/javascript">
                        $(function(){
                            $(document).on('change',"input[type=checkbox][name]:not([name*='[]']):checked",function(){
                                var value = $(this).val();
                                var name = $(this).attr('name');
                                $("input[type=checkbox][name='"+name+"'][value!='"+value+"']").removeAttr('checked');
                            });
                        });
                    </script>
<?php
                    }
?>
                </table>
            </td>
            <?php if ($mostraUpload) { ?>
	            <td>
	            	<table>
	            		<tr>
	            			<td style="font-size: 12px;">
								Previsão de Chegada da Peça
							</td>
	            		</tr>
	            		<tr><td>&nbsp;</td></tr>
	            		<tr>
	            			<td style="font-size: 10px;">
	            				<b>Layout do arquivo - Referência;Data Previsão<br />Ex: xxxx;dd/mm/aaaa<b>
	            			</td>
	            		</tr>
	            		<tr><td>&nbsp;</td></tr>
	            		<tr>
	            			<td>
								<input type="file" name="upload" id="upload">
							</td>
	            		</tr>
	            		<tr><td>&nbsp;</td></tr>
	            		<tr>
	            			<td>
	            				<input type="button"  value='<?=traduz("Upload")?>' onclick="javascript: if (document.frm_peca.btnacao.value == '' ) { document.frm_peca.btnacao.value='uploadArq' ; document.frm_peca.submit() } else { alert ('<?=traduz("Aguarde submissão")?>') } return false;" ALT='<?=traduz("Upload Arquivo")?>' border='0' >
	            			</td>
	            		</tr>
	            	</table>
	            </td>
	        <?php } ?>
        </tr>


        <tr><td>&nbsp;</td></tr>
    <?
        #113180
        if($login_fabrica==20){
    ?>
    <tr bgcolor='#D9E2EF' style='font-size:12px'>
        <td colspan='13' align='center'><?=traduz('Comentários')?></td>
    </tr>
    <tr>
        <td align='center' colspan='13'><TEXTAREA NAME="informacoes" ROWS="4" COLS="90" class='frm' id ='informacoes' class='frm'><? echo ($informacoes)?></TEXTAREA>
    </tr>
<?php
        }
        if (in_array($login_fabrica,array(11,172)) && !empty($peca)) {
?>
        <tr>
            <td align="center" colspan="3">
                <button id="cadastro_lb" name="cadastro_lb"><?=traduz('Modelo de Produtos')?></button>
            </td>
        </tr>
<?php
        }
?>
    </table>
    <?

    if(in_array($login_fabrica,array(6,125))){
        if($login_fabrica==6){
            if(strlen($peca)>0){
                $sql = "select defeito from tbl_peca_defeito where peca=$peca and ativo='t'";
                $res = pg_exec($con,$sql);
                if(pg_numrows($res)>0){
                    for($x=0;$x<pg_numrows($res);$x++){
                        $defeito_peca[] = pg_fetch_result($res,$x,defeito);
                    }
                }else{
                $defeito_peca[]=0;
                }

            }else{
                $defeito_peca[]=0;
            }
    ?>
    <table width='700' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>
    <tr  bgcolor='#D9E2EF' >
        <td align='center' colspan='8'><?=traduz('Defeitos na peça')?></td>
    </tr>
    <tr  bgcolor='#FFFFFF'>
    <?
            $sql = "SELECT 	tbl_defeito.defeito    ,
                            tbl_defeito.descricao
                    FROM  tbl_defeito
                    WHERE fabrica = $login_fabrica
                    AND   tbl_defeito.ativo IS TRUE
                    ORDER BY tbl_defeito.descricao";
            $res = pg_exec($con,$sql);



            if(pg_numrows($res)>0){

                for($x=0;$x<pg_numrows($res);$x++){
                    $xdefeito   = pg_fetch_result($res,$x,defeito);
                    $xdescricao = pg_fetch_result($res,$x,descricao);
                    if(($x % 4)==0){ echo "</tr><tr>";}

                    echo "<td align='left'>&nbsp;</td><td align='left'><input type='checkbox' name='defeito_$x' value='$xdefeito'"; if(in_array($xdefeito,$defeito_peca)){ echo "checked";} echo "> $xdescricao</td>";
                }
            echo "<input type='hidden' name='total_defeitos' value='$x'>";
            }

    ?>
        <td align='left'>
    </tr>
    </table>
    <p>
    <?
        }
        if(strlen($peca)>0){

            $sql = "SELECT servico_realizado
                    FROM  tbl_peca_servico
                    WHERE peca=$peca
                    AND   ativo='t'";
            $res = pg_exec($con,$sql);
            if(pg_numrows($res)>0){
                for($x=0;$x<pg_numrows($res);$x++){
                    $servico_realizado_peca[] = pg_fetch_result($res,$x,servico_realizado);
                }
            }else{
            $servico_realizado_peca[]=0;
            }

        }else{
            $servico_realizado_peca[]=0;
        }
    ?>
    <table width='700' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>
    <tr  bgcolor='#D9E2EF' >
        <td align='center' colspan='8'><?=traduz('Serviços na peça')?></td>
    </tr>
    <tr  bgcolor='#FFFFFF'>
    <?
        $sql = "SELECT 	tbl_servico_realizado.servico_realizado     ,
                        tbl_servico_realizado.descricao
                FROM  tbl_servico_realizado
                WHERE fabrica = $login_fabrica
                AND   tbl_servico_realizado.ativo   IS TRUE
                and   tbl_servico_realizado.solucao IS NOT TRUE
                ORDER BY tbl_servico_realizado.descricao";
        $res = pg_exec($con,$sql);

        if(pg_numrows($res)>0){

            for($x=0;$x<pg_numrows($res);$x++){
                $xservico_realizado   = pg_fetch_result($res,$x,servico_realizado);
                $xdescricao = pg_fetch_result($res,$x,descricao);
                if(($x % 4)==0){ echo "</tr><tr>";}

                echo "<td align='left'>&nbsp;</td><td align='left'><input type='checkbox' name='servico_realizado_$x' value='$xservico_realizado'"; if(in_array($xservico_realizado,$servico_realizado_peca)){ echo "checked";} echo "> $xdescricao</td>";
            }
        echo "<input type='hidden' name='total_servico_realizado' value='$x'>";
        }
    ?>
    </tr>
    </table><P>
    <?
    }
    ?>

    <!-- INICIO DA TABELA  DE IMAGEM -->
    <?if(strlen($peca)>0 and in_array($login_fabrica, $fabricas_com_imagens_pecas)) {?>
        <table width='700px' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>
        <? if(strlen($peca) > 0) { ?>
        <tr bgcolor='#D9E2EF' >
        <td align='center' colspan='2' class='subtitulo'><?=traduz('Imagem da peça')?> - <?echo $referencia ;?></td>
        </tr>
        <? } ?>
        <tr class='Conteudo' bgcolor='#FFFFFF' >
        <td align="center">
        <?
        $num_fotos = 0 ;
        if(strlen($peca) > 0) {

			$xpecas = $tDocs->getDocumentsByRef($peca, "peca");
            if (!empty($xpecas->attachListInfo)) {

				$a = 1;
				foreach ($xpecas->attachListInfo as $kFoto => $vFoto) {
				    $fotoPeca = $vFoto["link"];
				    if ($a == 1){break;}
				}
				echo "<a href='$fotoPeca' target='_blank' title='Imagem Ampliada' class='thickbox'><img src='$fotoPeca' border='0' width='180'></a>";
            } else {




	            if ($login_fabrica == 10 ){
	                $sql = "SELECT  peca_item_foto,caminho,caminho_thumb,descricao, ordem
	                        FROM tbl_peca_item_foto
	                        WHERE peca = $peca";
	                $res = pg_exec ($con,$sql) ;
	                $num_fotos = pg_num_rows($res);
	                if ($num_fotos > 0){
	                    echo "<TABLE><TR>";
	                    for ($i=0; $i<$num_fotos; $i++){
	                        $caminho        = trim(pg_fetch_result($res,$i,caminho));
	                        $caminho_thum   = trim(pg_fetch_result($res,$i,caminho_thumb));
	                        $foto_descricao = trim(pg_fetch_result($res,$i,descricao));
	                        $foto_id        = trim(pg_fetch_result($res,$i,peca_item_foto));
	                        $ordem          = pg_fetch_result($res,$i,ordem);

	                        $caminho      = str_replace("/www/assist/www/$caminho_dir/media/",'',$caminho);
	                        $caminho_thum = str_replace("/www/assist/www/$caminho_dir/pequena/",'',$caminho_thum);

	                        echo "<TD";

	                        if ($ordem == 1){
	                            echo " style='border: 4px solid navy; color: navy;'";
	                        }

	                        echo ">";

	            ?>
	                        <a href="<? echo "$PHP_SELF?ajax=true&imagem=$caminho&peca=$peca&peca_item_foto=$foto_id"; ?>&keepThis=true&TB_iframe=true&height=340&width=420" title="Imagem Ampliada" class="thickbox"><img src='<?="../$caminho_dir/pequena/$caminho_thum?bypass=" . md5(mt_rand(100,999))?>' border='0'></a>

	            <?
	                        echo "</TD>";
	                        //echo " <div class='contenedorfoto'><a href='$caminho' title='$foto_descricao' class='thickbox' rel='gallery-plants'><img src='$caminho_thum' alt='$desc_foto' /><br /></a><a href=\"javascript:if (confirm('Deseja excluir esta foto?')) window.location='?peca=$peca&excluir_foto=$foto_id'\"><img src='imagens/cancel.png' width='12px' alt='Excluir foto' style='margin-right:0;float:right;align:right' /></a></div>";
	                    }
	                    echo "</TR></TABLE>";
	                }
	            }

	            if ($num_fotos==0){
	                $contador=0;
	                if ($dh = opendir("../".$caminho_dir."/media/")) { #estamos pesquisando na media pq pdf não grava no diretorio pequeno

	                    while (false !== ($filename = readdir($dh))) {
	                        if($contador == 1) break;
	                        $xpeca = $peca.'.';

	                        if (strpos($filename,$peca) !== false){
	                            $po = strlen($xpeca);

	                            if(substr($filename, 0,$po)==$xpeca){
	                                $contador++;

	                                if (strpos(strtolower($filename),"pdf") == true) {
	                                    echo "<a href='../imagens_pecas/$login_fabrica/media/$peca.pdf' target='_blank'><img src='/assist/imagens/icone_pdf.jpg' border='0' width='22' align='absmiddle'></a>";
	                                }else{?>
	                                    <a href="<? echo "$PHP_SELF?ajax=true&imagem=$filename"; ?>&keepThis=true&TB_iframe=true&height=340&width=420" title='<?=traduz("Imagem Ampliada")?>' class="thickbox">
	                                    <img src='../<?echo $caminho_dir;?>/pequena/<?echo $filename; ?>' border='0'>
	                                    <input type='hidden' name='peca_imagem' value='<?echo $filename; ?>'>
	                                    </a>
	                                <? }
	                                echo "<img src='../imagens/excluir_loja.gif' class='excluir' id='peca$peca' style='cursor:pointer' alt='".traduz("Excluir Imagem")."' title='".traduz("Excluir imagem")."' />";
	                            }
	                        }
	                    }

	                    if($contador == 0){
	                        if ($dh = opendir("../".$caminho_dir."/media/")) { #estamos pesquisando na media pq pdf não grava no diretorio pequeno
	                            $Xreferencia = str_replace(" ", "_",$referencia);
	                            while (false !== ($filename = readdir($dh))) {
	                                if($contador == 1) break;
	                                if ( empty($Xreferencia) ) { continue; }
	                                if (strpos($filename,$Xreferencia) !== false){

	                                    //$peca_referencia = ntval($peca_referencia);
	                                    $po = strlen($Xreferencia);
	                                    if(substr($filename, 0,$po)==$Xreferencia){
	                                        $contador++;
	                                    ?>
	                                        <a href="<? echo "$PHP_SELF?ajax=true&imagem=$filename"; ?>&keepThis=true&TB_iframe=true&height=340&width=420" title="Imagem Ampliada" class="thickbox">
	                                        <img src='../<?echo $caminho_dir;?>/pequena/<?echo $filename; ?>' border='0'>
	                                        <input type='hidden' name='peca_imagem' value='<?echo $filename; ?>'>
	                                        <img src='../imagens/excluir_loja.gif' class='excluir' id='peca<?=$peca?>' style='cursor:pointer' alt='<?=traduz('Excluir Imagem')?>' title='<?=traduz('Excluir imagem')?>' />
	                                        </a>
	                        <?			}
	                                }
	                            }
	                        }
	                    }
	                }
	            }
        	}
        }

    ?>

            </td>
            <td bgcolor="#D9E2EF" align='left' style="padding:15px 0px;">
            <p style='font-size:11px; padding-left:170px;'>
            <?php

            $qtde_imagens = 1;

            if ($login_fabrica==10){
                if ($num_fotos>0){
                    $qtde_imagens = 5 - $num_fotos;
                }else{
                    $qtde_imagens = 5;
                }
                if ($qtde_imagens < 0){
                    $qtde_imagens = 0;
                }
            }
            if(strlen($peca) > 0 or $login_fabrica == 10){
                echo "<input type='hidden' name='qtde_de_fotos' value='$qtde_imagens'>";

                echo traduz('Selecione a imagem para a peça ').$referencia.' <input type="file" size="35" value="'.traduz('Procurar foto').'" name="arquivos[]" class="multi {accept:\'pdf|jpg|jpeg\', max:\''.$qtde_imagens.'\', STRING: {remove:\''.traduz('Remover').'\',selected:\''.traduz('Selecionado').': '.$file.'\',denied:\''.traduz('Tipo de arquivo inválido').': '.$ext.'!\'}}" />';

                if ($login_fabrica<>10){
                    if ($login_fabrica == 6){

                        echo traduz('<br /> * Formatos aceitos: PDF, jpg e JPEG <br /> * Se uma imagem estiver cadastrada ela será sobreposta');

                    }else{

                        echo traduz('<br />* Se uma imagem estiver cadastrada ela será sobreposta');

                    }
                }else{
                    echo traduz('* Quantidade máxima de fotos: ').$qtde_imagens;
                }
            }
            ?>
            </p>
            </td>
            </table>
        <?
         if (in_array($login_fabrica, array(1,  10, 35, 85))) { # retirar fabrica 3, HD 3394908
        ?>
            <table style='background-color: #D9E2EF ' align='center' width='700' border='0' style="font:11px Arial;padding:0;margin:0;">

            <tr>
            <td bgcolor='#D9E2EF' align='left' >
                <p style="font:11px Arial;font-weight: bold;"><?=traduz('Loja Virtual')?> <acronym class='ac' title='<?=traduz("Clique aqui para inserir na loja virtual")?>'><img src='imagens/help.png'></acronym></p>
            </td>
            <td align='left'>
                <input type="checkbox" value="ok" name="promocao_site" <?if($promocao_site == "t") echo "CHECKED";?>>
            <?
            if ($login_fabrica == 10 OR $login_fabrica == 3) {
                //echo "Clique aqui para inserir na loja virtual.";
            }
            ?>
            </td>
            </tr>

            <? if($login_fabrica==3){ //HD 102203 ?>
                <tr >
                <td bgcolor='#D9E2EF'  align='center' colspan='2'>
                &nbsp;<?=traduz('AT Shop')?>:
                <input type="checkbox" value="ok" name="at_shop" <?if($at_shop == "t") echo "CHECKED";?>>
                    <?=traduz('Clique aqui para inserir na AT Shop.')?>
                </td>
                </tr>
            <? } ?>

    <!-- 	<tr >
            <td bgcolor='#D9E2EF'  width='130' height='15'>Qtde mínimo por pedido&nbsp;</td>
            <td ><input NAME="qtde_minima_site" size='10' maxlength='5' value='<?=$qtde_minima_site?>' class='frm'>
            </td>
        </tr>
     -->

        <?if($login_fabrica == 10) {?>
        <tr style="font: Arial 11px;">
            <td bgcolor='#D9E2EF'><p style="font:11px Arial;font-weight: bold;"><?=traduz('Linha')?><acronym class='ac' title='<?=traduz("Linha")?>'><img src='imagens/help.png'></acronym></p></td>
            <td>
                <?
                $sql = "
                    SELECT  *
                    FROM    tbl_linha
                    WHERE   tbl_linha.fabrica = $login_fabrica
                    AND     ativo IS TRUE
                    ORDER BY tbl_linha.nome;";
                $res = pg_exec ($con,$sql);

                if (pg_numrows($res) > 0) {
                    echo "<select name='linha' class='frm' style='width:200px;'>\n";
                    echo "<option value=''>ESCOLHA</option>\n";
                    for ($x = 0 ; $x < pg_numrows($res) ; $x++){
                        $aux_linha = trim(pg_fetch_result($res,$x,linha));
                        $aux_nome  = trim(pg_fetch_result($res,$x,nome));

                        echo "<option value='$aux_linha'";
                        if ($linha == $aux_linha){
                            echo " SELECTED ";
                            $mostraMsgLinha = traduz("<br> da LINHA $aux_nome");
                        }
                        echo ">$aux_nome</option>\n";
                    }
                    echo "</select>\n&nbsp;";
                }

                ?>
            </td>
        </tr>
        <tr>
            <td bgcolor='#D9E2EF' align='left'  nowrap><p style="font:11px Arial;font-weight: bold;"><?=traduz('Família')?>&nbsp;<acronym class='ac' title='<?=traduz("Família")?>'><img src='imagens/help.png'></acronym></p></td>
            <td align='left'>
            <?

            $sql =	"SELECT *
                    FROM tbl_familia
                    WHERE fabrica = $login_fabrica
                    AND   ativo IS TRUE
                    ORDER BY descricao;";
            $res = pg_exec($con,$sql);
            if (pg_numrows($res) > 0) {
                echo "<select name='familia' size='1' class='frm' style='width:200px;'>";
                echo "<option value=''>ESCOLHA</option>";
                for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
                    $aux_familia = trim(pg_fetch_result($res,$i,familia));
                    $aux_nome  = trim(pg_fetch_result($res,$i,descricao));
                    echo "<option value='$aux_familia'";
                    if ($familia == $aux_familia) echo " selected";
                    echo ">$aux_nome</option>";
                }
                echo "</select>";
            }
            ?>
            </td>
        </tr>
        <tr>
        <td bgcolor='#D9E2EF' align='left'  nowrap><p style="font:11px Arial;font-weight: bold;"><?=traduz('Marca')?>&nbsp;<acronym class='ac' title='<?=traduz("Marca")?>'><img src='imagens/help.png'></acronym></p></td>
        <td align='left'><select name="marca" size="1" class="frm" style='width:200px;'>
                        <option ></option>
    <?

            $sql = "SELECT marca,nome
                    FROM tbl_marca
                    WHERE fabrica=$login_fabrica
                    ORDER BY nome";

            $res = pg_exec ($con,$sql) ;

            for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
                echo "<option ";
                if ($marca == pg_fetch_result ($res,$i,marca) ) echo " selected ";
                        echo " value='" . pg_fetch_result ($res,$i,marca) . "'>" ;
                        echo pg_fetch_result ($res,$i,nome) ;
                        echo "</option>";
            }
    ?>
                    </select>
            </td>
            </tr>
        <? } ?>
        <tr>
            <td bgcolor='#D9E2EF'  align='left'  nowrap><p style="font:11px Arial;font-weight: bold;"><?=traduz('Qtde máxima por posto')?>&nbsp;<acronym class='ac' title='<?=traduz("Quantidade máxima que pode ser pedido desta peça")?>'><img src='imagens/help.png'></acronym></p></td>
            <td align='left'><input NAME="qtde_max_site" size="20" maxlength='5' value='<?=$qtde_max_site?>' class='frm'>
            </td>
        </tr>
        <tr>
            <td bgcolor='#D9E2EF'  align='left' ><p style="font:11px Arial;font-weight: bold;"><?=traduz('Qtde multipla por peça')?>&nbsp;<acronym class='ac' title='<?=traduz("Quantidade de venda por múltiplo. Ex: 3,6,9...")?>'><img src='imagens/help.png'></acronym></p></td>
            <td align='left'><input NAME="multiplo_site" size='20' maxlength='5' value='<?=$multiplo_site?>' class='frm'>
            </td>
        </tr>
        <?if($login_fabrica!=3 && $login_fabrica!=85){?>
        <tr>
            <td bgcolor='#D9E2EF'  align='left' ><p style="font:11px Arial;font-weight: bold;"><?=traduz('Qtde Inicial Disponível')?>&nbsp;<acronym class='ac' title='<?=traduz("Quantidade incial disponivel para venda desta peça")?>'><img src='imagens/help.png'></acronym></p></td>
            <td align='left'><input NAME="qtde_disponivel_inicial_site" size='20' maxlength='5' value='<?=$qtde_disponivel_inicial_site?>' class='frm'>
            </td>
        </tr>
        <tr>
            <td bgcolor='#D9E2EF'  align='left' ><p style="font:11px Arial;font-weight: bold;"><?=traduz('Data Inicial')?> &nbsp;<acronym class='ac' title='<?=traduz("Data incial em que a peça foi colocada em liquidação")?>'><img src='imagens/help.png'></acronym></p></td>
            <td align='left'>
                <input type="text" name="data_inicial_liquidacao" id="data_inicial_liquidacao" size="20" maxlength="10" value="<? if (strlen($data_inicial_liquidacao) > 0) echo substr($data_inicial_liquidacao,0,10); else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
            </td>
        </tr>
        <?}?>
        <tr>
            <td bgcolor='#D9E2EF'  align='left' ><p style="font:11px Arial;font-weight: bold;"><?=traduz('Qtde Disponível')?>&nbsp;<acronym class='ac' title='<?=traduz("Quantidade disponivel para venda desta peça")?>'><img src='imagens/help.png'></acronym></p></td>
            <td align='left'><input NAME="qtde_disponivel_site" size='20' maxlength='5' value='<?=$qtde_disponivel_site?>' class='frm'>
            </td>
        </tr>
        <? if($login_fabrica == 10) { ?>
        <tr>
            <td bgcolor='#D9E2EF'  align='left' ><p style="font:11px Arial;font-weight: bold;"><?=traduz('Qtde Mínima no estoque')?>&nbsp;<acronym class='ac' title='<?traduz("Quantidade mínima no estoque")?>'><img src='imagens/help.png'></acronym></p></td>
            <td align='left'><input NAME="qtde_minima_estoque" size='20' maxlength='5' value='<?=$qtde_minima_estoque?>' class='frm'>
            </td>
        </tr>
        <? } ?>
        <tr>
            <td bgcolor='#D9E2EF'  align='left' ><p style="font:11px Arial;font-weight: bold;"><?=traduz('Prioridade no Site')?>&nbsp;<acronym class='ac' title='<?=traduz("Prioridade de exibição da peça na Loja Virtual. (1,2,3...1000)")?>'><img src='imagens/help.png'></acronym></p></td>
            <td align='left'><input NAME="posicao_site" size='20' maxlength='3' value='<?=$posicao_site?>' class='frm'
            <?php
             if ($login_fabrica == 10 OR $login_fabrica == 85){
                echo " onchange='verificaPrioridade(this.value)'>";
                echo "<span id='result'>&nbsp;</span></td>";
            }else{ ?>
                ></td>
            <?php }	?>
        </tr>

        <?php
        if($login_fabrica == 85){
            ?>
            <script>
                $(document).ready(function(){
                    $('input[name=liquidacao]').click(function(){
                        $('#box_preco').toggle();
                    });
                });
            </script>
            <?php
        }
        ?>

        <tr>
            <td bgcolor='#D9E2EF'  align='left' ><p style="font:11px Arial;font-weight: bold;"><?=traduz('Peça em Liquidação')?>&nbsp;<acronym class='ac' title='<?=traduz("Peça será exibida no link de LIQUIDAÇÃO")?>'><img src='imagens/help.png'></acronym></p></td>
            <td align='left'> <input type="checkbox" value="ok" name="liquidacao" <?if($liquidacao == "t") echo "CHECKED";?>>
            </td>
        </tr>

        <?php
        if($login_fabrica == 85){
            ?>
            <tr id="box_preco" <? echo ($liquidacao == "t") ? "" : "style='display: none';";?>>
                <td bgcolor='#D9E2EF'  align='left' ><p style="font:11px Arial;font-weight: bold;"><?=traduz('Preço em Liquidação')?></p></td>
                <td><input style="font:11px Arial;font-weight: bold;" class='frm' type="text" name="preco_peca" value="<? echo $preco_peca; ?>" size="10" maxlength="20"></td>
            </tr>
            <?php
        }
        ?>

        <?PHP if ($login_fabrica == 10) {?>
        <tr>
            <td bgcolor='#D9E2EF'  align='left' ><p style="font:11px Arial;font-weight: bold;"><?=traduz('Não cobrar frete')?>&nbsp;<acronym class='ac' title='<?=traduz("Não cobrará o frete desta peça")?>'><img src='imagens/help.png'></acronym></p></td>
            <td align='left'> <input type="checkbox" value="ok" name="frete_gratis" <?if($frete_gratis == "t") echo "CHECKED";?>>
            </td>
        </tr>
        <tr>
            <td bgcolor='#D9E2EF'  align='left'><p style="font:11px Arial;font-weight: bold;"><?=traduz('Preço da Peça')?>&nbsp;<acronym class='ac' title='<?=traduz("Cadastra o preço da peça")?>'><img src='imagens/help.png'></acronym></p></td>
            <td align='left'><input NAME="preco_peca" size='20' maxlength='10' value='<?=$preco_peca?>' class='frm'>
        </tr>
        <?PHP }?>
        <tr>
            <td bgcolor='#D9E2EF'  align='left' ><p style="font:11px Arial;font-weight: bold;"><?=traduz('Preço anterior')?>&nbsp;<acronym class='ac' title='<?=traduz("Preço será mostrado abaixo da peça como preço anterior")?>'><img src='imagens/help.png'></acronym></p></td>
            <td align='left'><input NAME="preco_anterior" size='20' maxlength='10' value='<?=$preco_anterior?>' class='frm'>
            </td>
        </tr>
            <?php
        if($login_fabrica == 85){
            ?>
            <tr>
                <td bgcolor='#D9E2EF'  align='left' ><p style="font:11px Arial;font-weight: bold;"><?=traduz('Mostrar liquidação para o Posto')?></p></td>
                <td><input type="checkbox" value="ok" name="mostrar_liquidacao_posto" <?if($mostrar_liquidacao_posto == "t" || $at_shop == "t") echo "CHECKED";?>></td>
            </tr>
            <?php
        }
        ?>
        <?php
            if($login_fabrica != 3 && $login_fabrica !=  85 && $login_fabrica != 35){
                // ! Cadastro de fotos para loja virtual
                if($login_fabrica == 85){
                    $aIdxFotos = array('g','1','2','3','4'); //(Ver declaracao no momento no inicio do arquivo)
                }else{
                    $aIdxFotos = array('p','g','1','2','3','4'); //(Ver declaracao no momento no inicio do arquivo)
                }

                $aFotoLabel= $aFotoDescr = array();
                $aFoto     = array();
                $aFotoLabel['pq'] = traduz('Imagem Pequena');
                $aFotoLabel['g'] = traduz('Imagem Grande');
                $aFotoDescr['p'] = traduz('Imagem pequena do produto, é exibida na listagem de produtos, carrinho, mais produtos, etc ...');
                $aFotoDescr['g'] = traduz('Imagem do detalhe do produto. Irá aparecer na página de detalhes do produto, como primeira image selecionada');

                foreach ( $aIdxFotos as $_i ) {
                    $aFoto[$_i] = null;
                }
            }else{
                $aIdxFotos = array();
            }
        ?>
        <?php foreach ($aIdxFotos as $_i):

			$xxpecas = $tDocs->getDocumentsByRef($peca, "peca","lv");
            if (!empty($xxpecas->attachListInfo)) {
            	$name = "";
				$link_foto = "";
				foreach ($xxpecas->attachListInfo as $key => $fotos) {
					list($name, $ext) = explode(".", $fotos["filename"]);

					if ($name == $peca."_".$_i) {
						$link_foto[$_i] = $fotos["link"];
					}
				}
            }

        ?>
        <tr>
            <td bgcolor="#D9E2EF" style="font-size: 10px" align="left">
                <p style="font:11px Arial;font-weight: bold;">
                <?php echo ( isset($aFotoLabel[$_i]) ) ? $aFotoLabel[$_i] : traduz('Imagem Adicional ').$_i ; ?>
                <?php if($_i == 1 and $login_fabrica == 85){ ?>
                    <acronym class="ac" title="<?php echo ( isset($aFotoDescr[$_i]) ) ? $aFotoDescr[$_i] : 'Imagem adicional, exibida também como miniatura principal do produto' ; ?>"><img src='imagens/help.png'></acronym></p>
                <?php }else{ ?>
                    <acronym class="ac" title="<?php echo ( isset($aFotoDescr[$_i]) ) ? $aFotoDescr[$_i] : 'Imagem adicional, exibida também nos detalhes do produto' ; ?>"><img src='imagens/help.png'></acronym></p>
                <?php } ?>
            </td>
            <td align="left">
                <input type="file" name="site_imagem_<?php echo $_i; ?>" id="site_imagem_<?php echo $_i; ?>" value="" />
                <?php
                	if (!empty($link_foto[$_i])) {
                		echo "<a href=\"$link_foto[$_i]\" target=\"_blank\">[Ver imagem]</a>";

                	} else {

                		echo ( ! empty($aFoto[$_i]) ) ? "<a href=\"peca_cadastro_foto_lv.php?peca={$peca}&sufixo={$_i}\" target=\"_blank\">[Ver imagem]</a>" : '' ;
                	}

                ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <tr class="titulo_tabela_novo">
            <td colspan="2"><?=traduz('Informações Referentes à Loja virtual')?></td>
        </tr>
        <tr>
            <td align='center' colspan='2'><textarea name="informacoes" rows="10" cols="109" class='frm' id ='informacoes' ><? echo ($informacoes)?></textarea>
            </td>
        </tr>

            </table>
        <?
        }
    }

    ?>

    <td align='center' class='table_line1' bgcolor='<? echo $cor;?>'>


    <!-- ---------------------------------- Botoes de Acao ---------------------- -->
    <div id="wrapper" style="background-color:#D9E2EF; width:700px;">
        <br>
        <input type='hidden' name='btnacao' value=''>
        <input type="button"  value='<?=traduz("Gravar")?>' onclick="javascript: if (document.frm_peca.btnacao.value == '' ) { document.frm_peca.btnacao.value='gravar' ; document.frm_peca.submit() } else { alert ('<?=traduz("Aguarde submissão")?>') } return false;" ALT='<?=traduz("Gravar formulário")?>' border='0' >
        <input type="button" value='<?=traduz("Apagar peça")?>' onclick="javascript: if (document.frm_peca.btnacao.value == '' ) { document.frm_peca.btnacao.value='deletar' ; document.frm_peca.submit() } else { alert ('<?=traduz("Aguarde submissão")?>') } return false;" ALT='<?=traduz("Apagar produto")?>' border='0' >
        <input type="button" value='<?=traduz("Limpar")?>' onclick="javascript: window.location = '<? echo $PHP_SELF; ?>'; return false;" ALT='<?=traduz("Limpar campos")?>' border='0' >
        <br><br>
    </div>
    <br>
    <P>
    <? if (strlen($peca) > 0) { ?>
    <? if (strlen($admin) > 0 and strlen($data_atualizacao) > 0) { ?>
    <div>
        <strong><?=traduz('ÚLTIMA ATUALIZAÇÃO')?>: </strong><?echo $admin ." - ". $data_atualizacao;?>
         - <a rel='shadowbox' href='relatorio_log_alteracao_new.php?parametro=tbl_peca&id=<?php echo $peca; ?>' name="btnAuditorLog"><?=traduz('Visualizar Log Auditor')?></a>
    </div>
    <? } ?>
    <? } ?>
    </P>
    <p>

    <?


    if (strlen($peca) > 0 AND $ip == '201.0.9.216') {
        echo "<a href='preco_cadastro_peca.php?peca=$peca'>".traduz("CLIQUE AQUI PARA LISTAR A TABELA DE PREÇOS")."</a>\n<br>\n<br>\n";
    }

    if ($login_fabrica == 20){
        echo "<br>";
        echo "<a href='peca_tipo_fleg.php?peca=$peca'>".traduz("GERAÇÃO DE LISTAGEM")."</a>\n<br>\n<br>\n";
    }

    if (strlen($peca) > 0) {
        // HD 3360

    	if (in_array($login_fabrica, array(169,170))){
    		$sql = "SELECT DISTINCT
                            tbl_produto.referencia,
                            tbl_produto.descricao,
                            tbl_produto.produto
                    FROM tbl_numero_serie_peca
                    JOIN tbl_numero_serie ON tbl_numero_serie.numero_serie = tbl_numero_serie_peca.numero_serie AND tbl_numero_serie.fabrica = {$login_fabrica}
                    JOIN tbl_produto ON tbl_produto.produto = tbl_numero_serie.produto AND tbl_produto.fabrica_i = {$login_fabrica}
                    JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
                    WHERE tbl_numero_serie_peca.peca = {$peca}
                    AND tbl_numero_serie_peca.fabrica = {$login_fabrica}
                    AND tbl_produto.ativo IS TRUE
                    ORDER BY tbl_produto.referencia ";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) == 0){
            	$sql = "SELECT  DISTINCT
		                        tbl_produto.referencia,
		                        tbl_produto.descricao,
		                        tbl_lista_basica.parametros_adicionais,
		                        tbl_produto.produto
		                FROM    tbl_produto
		                JOIN    tbl_lista_basica USING (produto)
		                JOIN    tbl_peca         USING (peca)
		                JOIN    tbl_linha        USING (linha)
		                WHERE   tbl_linha.fabrica     = $login_fabrica
		                AND     tbl_lista_basica.peca = $peca
		                ORDER BY tbl_produto.referencia;";
		        $res = pg_query($con, $sql);
            }
    	}else{
    		$sql = "SELECT  DISTINCT
	                        tbl_produto.referencia,
	                        tbl_produto.descricao,
	                        tbl_lista_basica.parametros_adicionais,
	                        tbl_produto.produto
	                FROM    tbl_produto
	                JOIN    tbl_lista_basica USING (produto)
	                JOIN    tbl_peca         USING (peca)
	                JOIN    tbl_linha        USING (linha)
	                WHERE   tbl_linha.fabrica     = $login_fabrica
	                AND     tbl_lista_basica.peca = $peca
	                ORDER BY tbl_produto.referencia;";
	        $res = pg_exec ($con,$sql);
    	}

        if (pg_numrows($res) > 0) {
            if ($login_fabrica == 1 AND $item_revenda == 't') {
                $coluna = "colspan='3'";
            }else{
                $coluna = "colspan='2'";
            }
            echo "<table width='700' align='center' border='0' class='tabela' cellpadding='2' cellspacing='1' >";
            echo "<tr class='titulo_tabela'>";

            echo "<td $coluna>";
            echo traduz("Equipamentos que Possuem esta Peça");
            echo "</td>";

            echo "</tr>";
            echo "<tr class='titulo_coluna'>";

            echo "<td>";
            echo traduz("Referência");
            echo "</td>";

            echo "<td>";
            echo traduz("Descrição");
            echo "</td>";

            if ($login_fabrica == 1 AND $item_revenda == 't') {
                echo "<td>";
                echo traduz("Etiqueta");
                echo "</td>";
            }

            echo "</tr>";

            for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
                $ref = trim(pg_fetch_result($res,$i,referencia));
                $des = trim(pg_fetch_result($res,$i,descricao));
                $param_add = pg_fetch_result($res,$i,parametros_adicionais);
                $lista_basica_add = pg_fetch_result($res,$i,produto);
                if($i % 2 == 0) $cor = "#F7F5F0"; else $cor = "#F1F4FA";
                echo "<tr bgcolor='$cor'>";

                echo "<td align='left'>";
                echo "$ref";
                echo "</td>";

                echo "<td align='left'>";
                echo "$des";
                echo "</td>";

                if ($login_fabrica == 1 AND $item_revenda == 't') {

                    $param_add = new Json($param_add, false);
                    if ($param_add->item_revenda == 't') {
                        $che = "<input type='checkbox' value='t' class='item_revenda_produto' name='item_revenda_produto_$i' checked=''> <input type='hidden' value='$lista_basica_add' class='lista_basica_add' name='lista_basica_add_$i'> ";
                    }else{
                        $che = "<input type='checkbox' value='t' class='item_revenda_produto' name='item_revenda_produto_$i'> <input type='hidden' value='$lista_basica_add' class='lista_basica_add' name='lista_basica_add_$i'>";
                    }
                    echo "<td align='center'>";
                    echo $che;
                    echo "</td>";
                }

                echo "</tr>";
            }

            echo "</table>";
            echo "<p>";
        }
    }
    ?>


    </form>
    </div>
    <!-- inicio relatorio -->

    <?
    $listartudo = $_GET['listartudo'];
    $ativo      = $_GET['ativo'];
    if(strlen($ativo) >0){
        $sql_ativo="AND tbl_peca.ativo='$ativo'";
    }
    if ($listartudo == 1){
        echo '<br />';
        if ($login_fabrica == 20) {
		$join_sql = " LEFT JOIN tbl_familia ON tbl_peca.familia_peca = tbl_familia.familia ";
        	$select_sql = " tbl_familia.descricao as desc_familia, ";
        }else{
        	$join_sql = "";
        	$select_sql = "";
        }

        if(in_array($login_fabrica, array(104,140))) {

        	$sqlproduto = "(SELECT array_to_string(array_agg(tbl_produto.referencia),'<br>')
		 				 FROM tbl_lista_basica 
		 				 JOIN tbl_produto USING(produto)
		 				 WHERE tbl_lista_basica.fabrica = $login_fabrica
		 				 AND tbl_lista_basica.peca = tbl_peca.peca) AS produtos,";
         }

         $sql = "SELECT tbl_peca.peca,
        				$sqlproduto
                        tbl_peca.referencia,
                        tbl_peca.descricao ,
                        tbl_peca.descricao_estendida ,
                        tbl_peca.acessorio,
                        tbl_peca.ativo,
                        tbl_peca.origem,
                        tbl_peca.ipi,
                        tbl_peca.unidade,
                        tbl_peca.peso,
                        tbl_peca.classificacao_fiscal,
                        tbl_peca.multiplo,
                        tbl_peca.bloqueada_garantia,
                        tbl_peca.peca_critica,
						tbl_peca.localizacao,
						tbl_peca.referencia_fabrica,
						tbl_peca.parametros_adicionais,
						tbl_peca.devolucao_obrigatoria,
						tbl_peca.produto_acabado,						
						tbl_peca.item_aparencia,						
						$select_sql
                        tbl_peca.estoque
                FROM    tbl_peca
                	$join_sql
                WHERE   tbl_peca.fabrica = $login_fabrica
                $sql_ativo
                ORDER BY    tbl_peca.descricao;"; 
                //die(nl2br($sql));
        $res = pg_exec ($con,$sql);

        if($login_fabrica ==14 OR $login_fabrica ==43 or $login_fabrica == 35){
            echo "<div id='wrapper'>";
            echo "<table width='700px' align='center' border='0' cellpadding='1' cellspacing='0'>";
            echo "<tr>";
            echo "<td align='left' width='150'><a href='$PHP_SELF?listartudo=1'>".traduz("TODOS")."</a></td>";
            echo "<td  align='center' width='150'><a href='$PHP_SELF?listartudo=1&ativo=t'>".traduz("ATIVO")."</a></td>";
            echo "<td align='right' width='150'><a href='$PHP_SELF?listartudo=1&ativo=f'>".traduz("INATIVO")."</a></td>";
            echo "</tr>";
            echo "</table>";
            echo "</div>";
            echo "<br>";
        }
        if(pg_numrows($res) >0) {

            if($login_fabrica == 45){

                $fileName = "relatorio_pecas-{$login_fabrica}-{$data}.xls";
                echo "<p id='id_download2'><a href='xls/$fileName'><img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>&nbsp;&nbsp;&nbsp;".traduz("Download Excel")."</a></p><br />";
                $data = date("d-m-Y-H:i");
                $head = "<table>
                         <tr>
                            <td>".traduz("Referência")."</td>
                            <td>".traduz("Descrição")."</td>
                            <td>".traduz("Origem")."</td>
                            <td>".traduz("IPI")."</td>
                            <td>".traduz("Estoque")."</td>
                            <td>".traduz("Unidade")."</td>
                            <td>".traduz("Peso Kg")."</td>
                            <td>".traduz("Classificação Fiscal")."</td>
                            <td>".traduz("Múltiplo")."</td>
                            <td>".traduz("Bloqueado para garantia")."</td>
                            <td>".traduz("Peça critica")."</td>
                         </tr>";
	    }

	    if(in_array($login_fabrica,array(104,140))){
		$fileName = "relatorio_pecas-{$login_fabrica}-{$data}.csv";
		echo "<p id='id_download2'><a href='xls/$fileName'><img src='imagens/excel.png' height='20px' width='20px' align='absmiddle'>&nbsp;&nbsp;&nbsp;".traduz("Download CSV")."</a></p><br />";

			if($login_fabrica == 140){
				$head = "Referência;Descrição;Status;Devolução Obrigatória;Item de Aparência;Bloqueada Garantia;Acessórios;Peça Crítica;Produto Acabado\n";
			} else {
				$head = "Referência;Descrição;Produto;Qtde Demanda;Status;Devolução Obrigatória;Bloqueada Garantia;Acessórios;Peça Crítica;Produto Acabado\n";
			}
	    }
	    	if(in_array($login_fabrica, array(104,140))){
				echo "</table>\n<div id='wrapTable' style='width:1200px;padding-left:16px;height:70%;overflow-x:hidden;overflow-y:auto;min-height:200px;margin-bottom:1em;'>\n";
	    	} else {
            	echo "</table>\n<div id='wrapTable' style='width:716px;padding-left:16px;height:70%;overflow-x:hidden;overflow-y:auto;min-height:200px;margin-bottom:1em;'>\n";
            }
                    flush();
                   	echo "<table width='700px' align='center' border='0' cellpadding='2' cellspacing='1' class='tabela'>";                   

                    echo "<tr class='titulo_coluna' >";

                    echo "<td align='left' width='150'>".traduz("Referência")."</td>";
                    echo ($login_fabrica == 101) ? "<td align='left'>".traduz("Endereçamento")."</td>" : "";
                    echo "<td align='left'>".traduz("Descrição")."</td>";

                    echo ($login_fabrica == 104) ? "<td align='left'>".traduz("Produtos")."</td>" : "";
                    
                    echo ( in_array($login_fabrica,array(20)) ) ? "<td align='left'>".traduz("Família")."</td>" : "";

                    //hd-3625122 - fputti
                    echo (in_array($login_fabrica,array(171))) ? "<td align='center'>".traduz("Referência Fábrica")."</td>" : "";

                    if($login_fabrica == 104){
                    	echo "<td>".traduz("Qtde Demanda")."</td>";
                    }

                    echo (in_array($login_fabrica, $arr_login_fabrica_ativo) or $login_fabrica > 86) ? "<td align='left'>Status</td>" : "";
                    echo isFabrica(104,140) ? '<td align="center">'.traduz('Devolução Obrigatória').'</td><td align="center">'.traduz('Item de Aparência').'</td><td align="center">'.traduz('Bloqueada Garantia').'</td><td align="center">'.traduz('Acessórios').'</td><td align="center">'.traduz('Peça Crítica').'</td><td align="center">'.traduz('Produto Acabado').'</td>' : '';
                    echo isFabrica(42) ? '<td align="center">'.traduz("Disponibilidade").'</td>' : '';
                    echo isFabrica(42) ? '<td align="center">'.traduz("Previsão Chegada").'</td>' : '';
                    echo ($login_fabrica == 20) ? "<td align='left'>".traduz("Tipo")."</td>" : "";

                    echo "</tr>";

            $contadorRES = pg_num_rows($res);
            for ($i = 0 ; $i < $contadorRES; $i++) {            	
                $parametros_adicionais = new Json(pg_fetch_result($res, $i, "parametros_adicionais"), false);
                $produtoref 		  =  pg_fetch_result($res,$i,"produtos");
                $produtorefExcel      =  str_replace("<br>", ",",$produtoref);
                $produtorefExcel      =  str_replace(" ", "", $produtorefExcel);
 
                if (in_array($login_fabrica, [11,104,172])) {
                    $qtde_demanda = $parametros_adicionais->qtde_demanda;
                }

                if (isFabrica(42)) {
                    $disponibilidade = $parametros_adicionais->status;
		    $disponibilidade = ($disponibilidade == "I") ? "Indisponível" : "Disponível";
                    $previsaoEntrega = $parametros_adicionais->previsaoEntrega
                        ? is_date($parametros_adicionais->previsaoEntrega, 'ISO', 'EUR')
                        : '&ndash;';
                }

                if (in_array($login_fabrica,$arr_login_fabrica_ativo) or $login_fabrica > 86 or in_array($login_fabrica,array(1,20)) ) {
                    $ativo = trim(pg_fetch_result($res, $i, 'ativo'));
                    $ativo = ($ativo=='t') ? "<img src='imagens/status_verde.gif'> Ativo" : "<img src='imagens/status_vermelho.gif'> Inativo";
                }

                $cor = ($i%2 == 0) ? "#F7F5F0" : "#F1F4FA";

                echo "<tr bgcolor='$cor'>";
                echo "<td align='left'>";
                echo pg_fetch_result ($res,$i,referencia);
                echo "</td>";

                if($login_fabrica == 101) {
                    echo "<td align='left'>";
                    echo pg_fetch_result($res,$i,"localizacao");
                    echo "</td>";
                }

                echo "<td align='left' nowrap>";
                echo "<a href='$PHP_SELF?peca=" . pg_fetch_result ($res,$i,peca) . "'>";
                if($login_fabrica == 10 or $login_fabrica == 82) {
                    echo pg_fetch_result ($res,$i,descricao_estendida);
                }else{
                	$descricao = pg_fetch_result($res, $i, "descricao");
                	$encoding = mb_detect_encoding($descricao);
                	
                	if (strtoupper($encoding) == "UTF-8") {
                		$result_descricao = iconv("UTF-8", "ISO-8859-1", $descricao);
                		if (!$result_descricao) {
                			echo $descricao;
                		} else {
                			echo $result_descricao;
                		}
                	} else {
                		echo $descricao;
                	}
                }
                echo "</a>";
                echo "</td>";
                if (in_array($login_fabrica,array(20)) ) {

                    echo "<td nowrap align='left'>".pg_fetch_result ($res,$i,desc_familia)."</td>";
                }

                //hd-3625122 - fputti
                if (in_array($login_fabrica,array(171,187))) {
                    echo "<td nowrap align='center'>".pg_fetch_result ($res, $i, referencia_fabrica)."</td>";
                }

                if(in_array($login_fabrica,array(104))) {
                	echo "<td>$produtoref</td>";
                	echo "<td>$qtde_demanda</td>";
                }

                if(in_array($login_fabrica, [11,172])){
                	
                    echo "<td>$qtde_demanda</td>";
                }


                if (in_array($login_fabrica,$arr_login_fabrica_ativo) or $login_fabrica > 86 or in_array($login_fabrica,array(1,20)) ) {
                    echo "<td nowrap align='left'>$ativo</td>";
                }

                if(in_array($login_fabrica,array(104,140))) {
                	echo "<td align='center'>$devolucao_obrigatoria</td>";                	
                	echo "<td align='center'>$item_aparencia</td>";	                	
                	echo "<td align='center'>$bloqueada_garantia</td>";
                	echo "<td align='center'>$acessorio</td>";
                	echo "<td align='center'>$peca_critica</td>";
                	echo "<td align='center'>$produto_acabado</td>";
                }

                if (isFabrica(42)) {
                    echo "<td>$disponibilidade</td>";
                    echo "<td>$previsaoEntrega</td>";
                }

                if($login_fabrica == 20){
                    $acessorio = pg_fetch_result ($res,$i,acessorio);
                    echo "<td align='left'>";
                    echo ($acessorio=='t') ? traduz("Acessório") : (($acessorio=='f') ? traduz("Peça") : " - ");
                    echo "</td>";
                }
                echo "</tr>";

                if(in_array($login_fabrica,array(45,104,140))){
                    $referencia           = pg_fetch_result($res,$i,"referencia");
                    $descricao            = pg_fetch_result($res,$i,"descricao");
                    $origem               = pg_fetch_result($res,$i,"origem");
                    $ipi                  = pg_fetch_result($res,$i,"ipi");
                    $estoque              = pg_fetch_result($res,$i,"estoque");
                    $unidade              = pg_fetch_result($res,$i,"unidade");
                    $peso                 = pg_fetch_result($res,$i,"peso");
                    $classificacao_fiscal = pg_fetch_result($res,$i,"classificacao_fiscal");
                    $multiplo             = pg_fetch_result($res,$i,"multiplo");
                    $bloqueada_garantia   = (pg_fetch_result($res,$i,"bloqueada_garantia") == "t") ? traduz("Sim") : traduz("Não");
                    if(in_array($login_fabrica, array(104,140))){
                    	$devolucao_obrigatoria   = (pg_fetch_result($res,$i,"devolucao_obrigatoria") == "t") ? traduz("Sim") : traduz("Não");
                    	$item_aparencia 		 = (pg_fetch_result($res,$i,"item_aparencia") == "t") ? traduz("Sim") : traduz("Não");
                    	$peca_critica 			 = (pg_fetch_result($res,$i,"peca_critica") == "t") ? traduz("Sim") : traduz("Não");
                    	$produto_acabado 		 = (pg_fetch_result($res,$i,"produto_acabado") == "t") ? traduz("Sim") : traduz("Não");
                    	$acessorio  			 = (pg_fetch_result($res,$i,"acessorio") == "t") ? traduz("Sim") : traduz("Não");
                    }
		    		$peca_critica         = (pg_fetch_result($res,$i,"peca_critica") == "t") ? traduz("Sim") : traduz("Não");
		   		$status		      = (pg_fetch_result($res,$i,"ativo") == "t") ? traduz("Ativo") : traduz("Inativo");
		   		$peca                 =  pg_fetch_result($res,$i,"peca");

				    if($login_fabrica == 45){
		                    $body .= "<tr>
		                                <td align='left'>$referencia</td>
		                                <td align='left'>$descricao</td>
		                                <td align='center'>$origem</td>
		                                <td align='center'>$ipi</td>
		                                <td align='center'>$estoque</td>
		                                <td align='center'>$unidade</td>
		                                <td align='center'>$peso</td>
		                                <td align='center'>$classificacao_fiscal</td>
		                                <td align='center'>$multiplo</td>
		                                <td align='center'>$bloqueada_garantia</td>
		                                <td align='center'>$peca_critica</td>
					     </tr>";
				    }else{
				    	if (in_array($login_fabrica, array(104))){
				    		$body .= "$referencia;$descricao;$produtorefExcel;$qtde_demanda;$status;$devolucao_obrigatoria;$bloqueada_garantia;$acessorio;$peca_critica;$produto_acabado\n";
				    	}else if($login_fabrica == 140) {
				    		$body .= "$referencia;$descricao;$status;$devolucao_obrigatoria;$item_aparencia;$bloqueada_garantia;$acessorio;$peca_critica;$produto_acabado\n";
				    	} else {
				    		$body .= "$referencia;$descricao;$qtde_demanda;$status\n";				    	
				    	}
				    }
                }
            }
            echo "</table>\n</div>\n";
            echo '<br>Total de registros <b>', $i, '</b><br><br>';

            if(in_array($login_fabrica,array(45,104,140))){

				if($login_fabrica == 45){
					$body .= "</table>";
				}

                $file = fopen("/tmp/{$fileName}", "w");

                
                fwrite($file, $head . $body);

                fclose($file);

                if (file_exists("/tmp/{$fileName}")) {
                    system("mv /tmp/{$fileName} xls/{$fileName}");
                }
	        }
        }else {
            echo "<table width='700px' align='center' border='0' cellpadding='1' cellspacing='0'>";
            echo "<tr>";
            echo "<td nowrap>".traduz("NENHUM RESULTADO ENCONTRADO.")."</td>";
            echo "</tr>";
            echo "</table>";
        }
    }


    ?>

    <!-- fim relatorio -->

    <?php
    if ($login_fabrica<>20){?>
    <div id="wrapper" style="width:700px;">
        <input type="button" onClick="window.open('peca_consulta.php')" value='<?=traduz("Consultar Peças por Tipo")?>' >
        <!-- a href='peca_devolucao_obrigatoria_consulta.php' target='_blank'>CLIQUE AQUI PARA LISTAR TODAS AS PEÇAS COM DEVOLUÇÃO OBRIGATÓRIA</a -->

    <?php
	}
    if ($login_fabrica<>11 and $login_fabrica <> 172 and !$listartudo){ // HD 56689?>

        <input type="button" onClick="location.href='<?echo $PHP_SELF;?>?listartudo=1'" value='<?=traduz("Listar todas as Peças")?>'>

    <?}
    if ($login_fabrica == 1) {?>
    	<br>
    	<br>
        <table width='600' border='0' cellspacing='2' cellpadding='2' align='center'>
        	<tr>
        		<td align='center' style='border: 0; font: bold 14px \"Arial\";'>
        		<a href="peca_cadastro.php?gerar_excel=true" target="_blank" style="text-decoration: none; "><img src="imagens/	document-txt-blue-new.png" height="20px" width="20px" align="absmiddle">&nbsp;&nbsp;<?=traduz('Gerar Arquivo Peças Item de Revenda')?></a>
        		</td>
        </tr>
        </table>
    <?php
    }
    ?>
    <p>
<?php 
	if($login_fabrica == 91){?>
	<script type="text/javascript">
        $(function(){
            $("input[name=peca_critica]").click(function() {
                marcarBloqueadaVendaWanke();
            });
        });

        function marcarBloqueadaVendaWanke() {
          var critica = $("input[name=peca_critica]:checked").val();
          if (critica == "t") {
            document.getElementById("bloqueada_venda").checked = true;
          }
        }

        $(document).ready(function() {
            marcarBloqueadaVendaWanke();
        });

    </script>
<?php }
?>
    <? include "rodape.php"; ?>

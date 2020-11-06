<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$caminho_dir = "imagens_pecas";
if($login_fabrica<>10){
	$caminho_dir = $caminho_dir."/".$login_fabrica;

}

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
			echo "&nbsp;<font color='#FF0000'>Prioridade já existente!</font>";
		}else{
			echo "&nbsp;<font color='#009900'>Prioridade disponível!</font>";
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
	if ($dh = opendir("../$caminho_dir/media/")) {

		if (file_exists("../$caminho_dir/media/$imagem")) {
			echo"<center><img src=\"../$caminho_dir/media/$imagem\" border='0'></center>";
			if (strlen($peca_item_foto)>0){
				echo "<div><span style='float:left;'>";
				echo "<a href='$PHP_SELF?excluir_peca=sim&peca=$peca&peca_item_foto=$peca_item_foto'>Excluir Foto</a>";
				echo "</span>";
				$sql = "SELECT ordem FROM tbl_peca_item_foto WHERE peca =$peca AND peca_item_foto = $peca_item_foto";
				$res = pg_exec ($con,$sql);
				if (pg_numrows($res) > 0){
					$mostra_def_foto = pg_result($res, 0, ordem);
				}else{
					$mostra_def_foto = 0;
				}
				if ($mostra_def_foto <> 1){
					echo "<span style='float:right;'>";
					echo "<a href='$PHP_SELF?foto_principal=sim&peca=$peca&peca_item_foto=$peca_item_foto' >Definir como Foto Principal</a>";
					echo "</span>";
				}
				echo "</div>";
			}
		}else{
			echo"<center>Imagem não existe! <a href='javascript:self.parent.tb_remove()'>Fechar Janela</a></center>";
		}

	}
	exit;
}

if($excluir_peca == "sim"){
	$peca      = $_GET['peca'];
	$peca_item_foto = $_GET['peca_item_foto'];

	if (strlen($peca_item_foto)>0 AND strlen($peca)>0){

		$res = pg_exec ($con,"BEGIN TRANSACTION");

		$sql = "SELECT caminho,caminho_thumb
				FROM tbl_peca_item_foto
				WHERE peca_item_foto = $peca_item_foto
				AND peca = $peca";
		$res = pg_exec ($con,$sql);

		$caminho       = pg_result($res,0,caminho);
		$caminho_thumb = pg_result($res,0,caminho_thumb);

		$sql = "DELETE FROM tbl_peca_item_foto WHERE peca_item_foto = $peca_item_foto AND peca = $peca";
		$res = pg_exec ($con,$sql);


		if (!unlink($caminho)){
			$msg_erro .= "Imagem não pode ser excluída.";
		}

		if (strlen($msg_erro)==0){
			if (!unlink($caminho_thumb)){
				$msg_erro .= "Imagem não pode ser excluída.";
			}
		}

		if (strlen ($msg_erro) == 0) {
			$res = pg_exec ($con,"COMMIT TRANSACTION");
			echo "<center>Imagem excluída com sucesso. Recarregue a página para atualizar. <a href='javascript:self.parent.tb_remove()'>Fechar Janela</a></center>";
		}else{
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			echo "<center>Imagem não pode ser excluída. ($msg_erro) <a href='javascript:self.parent.tb_remove()'>Fechar Janela</a></center>";
		}
	}else{
		echo"<center>Nenhuma foto selecionada.</center>";
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
		echo "<center>Foto definida como principal com sucesso! Recarregue a página para atualizar. <a href='javascript:self.parent.tb_remove()'>Fechar Janela</a></center>";
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
		echo "<center>Foto não pôde ser definida como principal! ($msg_erro_peca_foto) <a href='javascript:self.parent.tb_remove()'>Fechar Janela</a></center>";
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

if (strlen($_POST["peca"]) > 0) {
	$peca = trim($_POST["peca"]);
}

if (strlen($_POST["btnacao"]) > 0) {
	$btnacao = trim($_POST["btnacao"]);
}

if ($btnacao == "deletar" and strlen($peca) > 0 ) {
	$res = pg_exec ($con,"BEGIN TRANSACTION");

	$sql = "DELETE FROM tbl_peca
			WHERE  tbl_fabrica.fabrica = tbl_peca.fabrica
			AND    tbl_peca.fabrica    = $login_fabrica
			AND    tbl_peca.peca       = $peca;";
	$res = @pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);

	if (strlen ($msg_erro) == 0) {

		$res = pg_exec ($con,"COMMIT TRANSACTION");

		header ("Location: $PHP_SELF");
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
		$devolucao_obrigatoria    = $_POST["devolucao_obrigatoria"];
		$item_aparencia           = $_POST["item_aparencia"];
		$acumular_kit             = $_POST["acumular_kit"];
		$retorna_conserto         = $_POST["retorna_conserto"];
		$intervencao_carteira     = $_POST["intervencao_carteira"];
		$mao_de_obra_diferenciada = $_POST["mao_de_obra_diferenciada"];
		$bloqueada_garantia       = $_POST["bloqueada_garantia"];
		$acessorio                = $_POST["acessorio"];
		$aguarda_inspecao         = $_POST["aguarda_inspecao"];
		$peca_critica             = $_POST["peca_critica"];
		$produto_acabado          = $_POST["produto_acabado"];
		$reembolso                = $_POST["reembolso"];
		$mero_desgaste			  = $_POST["mero_desgaste"];
		//$intervencao_fabrica      = $_POST["intervencao_fabrica"];
		$troca_obrigatoria        = $_POST["troca_obrigatoria"];
		$marca                    = $_POST["marca"];
		$informacoes              = $_POST["informacoes"];
		$preco_compra             = $_POST["preco_compra"];
		$ativo                    = $_POST["ativo"];
		$faturada_manualmente     = $_POST["faturada_manualmente"];
		//hd 47129
		$peca_unica_os            = $_POST["peca_unica_os"];
		$ativo                    = $_POST["ativo"];
		$pre_selecionada          = $_POST["pre_selecionada"];
		$placa                    = $_POST["placa"];#HD 47695
		//HD: 2453 - DYNACOM - MAO DE OBRA DIFERENCIA PEÇA, POSTO,ADMIN
		$mo_peca                  = $_POST["mo_peca"];
		$mo_posto                 = $_POST["mo_posto"];
		$mo_admin                 = $_POST["mo_admin"];
		$promocao_site            = $_POST["promocao_site"];
		$at_shop                  = $_POST["at_shop"];
		$multiplo_site            = $_POST["multiplo_site"];
		//$qtde_minima_site       = $_POST["qtde_minima_site"];
		$qtde_max_site            = $_POST["qtde_max_site"];
		$frete_gratis             = $_POST["frete_gratis"];
		$qtde_minima_estoque        = $_POST["qtde_minima_estoque"];
		$qtde_disponivel_inicial_site = $_POST["qtde_disponivel_inicial_site"]; // HD 18289
		$qtde_disponivel_site         = $_POST["qtde_disponivel_site"];
		$preco_peca                   = $_POST["preco_peca"];
		$preco_anterior               = $_POST["preco_anterior"];
		$data_inicial_liquidacao      = $_POST["data_inicial_liquidacao"];
		$linha                    = $_POST["linha"];
		$familia                  = $_POST["familia"];

		if(strpos($msg_erro,'"tbl_peca"'))
		$msg_erro = "Esta peça não pode ser excluida";
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


if ($btnacao == "gravar") {
	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : false;

	if (strlen($_POST["ipi"]) > 0) {
		$aux_ipi = "'". trim($_POST["ipi"]) ."'";
	}else{
		if($login_fabrica==6){/*hd 3224*/
			$aux_ipi = 0 ;
		}else{
			$msg_erro = "Digite o valor do IPI do produto.";
		}
	}

	if (strlen($_POST["origem"]) > 0) {
		$aux_origem = "'". trim($_POST["origem"]) ."'";
	}else{
		$msg_erro = "Selecione a origem do produto.";
	}

	if (strlen($_POST["descricao"]) > 0) {
		$aux_descricao = "'". trim($_POST["descricao"]) ."'";
	}else{
		$msg_erro = "Digite a descrição do produto.";
	}

	if (strlen($_POST["referencia"]) > 0) {
		$aux_referencia = "'". trim($_POST["referencia"]) ."'";
	}else{
		$msg_erro = "Digite a referência do produto.";
	}

	if (strlen($_POST["voltagem"]) > 0) {
		$aux_voltagem = "'". trim($_POST["voltagem"]) ."'";
	}else{
		$aux_voltagem = "null";
	}

	if (strlen($_POST["estoque"]) > 0) {
		$aux_estoque = "'". trim($_POST["estoque"]) ."'";
	}else{
		$aux_estoque= "null";
	}

	if (strlen($_POST["unidade"]) > 0) {
		$aux_unidade = "'". trim($_POST["unidade"]) ."'";
	}else{
		$aux_unidade= "null";
	}

	if (strlen($_POST["peso"]) > 0) {
		$aux_peso = "'". trim($_POST["peso"]) ."'";
	}else{
		$aux_peso = "null";
	}


	if (strlen($_POST["marca"]) > 0) {
		$aux_marca = "'". trim($_POST["marca"]) ."'";
	}else{
		$aux_marca = "null";
	}

	if (strlen($_POST["informacoes"]) > 0) {
		$aux_informacoes = "'". trim($_POST["informacoes"]) ."'";
	}else{
		$aux_informacoes = "null";
	}

	if (strlen($_POST["posicao_site"]) > 0) {
		$aux_posicao_site =  trim($_POST["posicao_site"]) ;
		$aux_posicao_site = str_replace(",",".", $aux_posicao_site);
		$auxx_posicao_site = explode(".", $aux_posicao_site);
		$posicao_site_dig = $auxx_posicao_site[0];
		$aux_posicao_site =  "'".$auxx_posicao_site[0]."'";
	}else{
		$aux_posicao_site = "null";
	}

	if (strlen($_POST["liquidacao"]) > 0) {
		$aux_liquidacao = "'t'";
	}else{
		$aux_liquidacao = "null";
	}

	if (strlen($_POST["preco_peca"]) > 0)                 $aux_preco_compra = "'". trim($_POST["preco_peca"]) ."'";
	else                                                    $aux_preco_compra = "null";

	if (strlen($_POST["preco_compra"]) > 0)                 $aux_preco_compra = "'". trim($_POST["preco_compra"]) ."'";
	else                                                    $aux_preco_compra = "null";

	if (strlen($_POST["linha"]) > 0)                        $aux_linha = "'". trim($_POST["linha"]) ."'";
	else                                                    $aux_linha = "null";

	if (strlen($_POST["familia"]) > 0)                      $aux_familia = "'". trim($_POST["familia"]) ."'";
	else                                                    $aux_familia = "null";

	if (strlen($_POST["promocao_site"]) > 0)                $aux_promocao_site = "'t'";
	else                                                    $aux_promocao_site = "'f'";

	if (strlen($_POST["at_shop"]) > 0)                      $aux_at_shop = "'t'";
	else                                                    $aux_at_shop = "'f'";

	if (strlen($_POST["multiplo_site"]) > 0)                $aux_multiplo_site = $multiplo_site;
	else                                                    $aux_multiplo_site = "0";

	/*if (strlen($_POST["qtde_minima_site"]) > 0)		$aux_qtde_minima_site = $qtde_minima_site;
	else							$aux_qtde_minima_site = "null";*/

	if (strlen($_POST["qtde_max_site"]) > 0)                $aux_qtde_max_site = $qtde_max_site;
	else                                                    $aux_qtde_max_site = "null";

	if (strlen($_POST["qtde_minima_estoque"]) > 0)                $aux_qtde_minima_estoque = $qtde_minima_estoque;
	else                                                    $aux_qtde_minima_estoque = "null";
	if (strlen($_POST["frete_gratis"]) > 0)                $aux_frete_gratis = "'t'";
	else                                                    $aux_frete_gratis = "'f'";


	if (strlen($_POST["qtde_disponivel_inicial_site"]) > 0)         $aux_qtde_disponivel_inicial_site = $qtde_disponivel_inicial_site;
	else                                                    $aux_qtde_disponivel_inicial_site = "null";

	if (strlen($data_inicial_liquidacao) > 0 AND $data_inicial_liquidacao <> "dd/mm/aaaa") $aux_data_inicial_liquidacao = fnc_formata_data_pg($data_inicial_liquidacao);
	else                                                    $aux_data_inicial_liquidacao = "null";


	if (strlen($_POST["qtde_disponivel_site"]) > 0)         $aux_qtde_disponivel_site = $qtde_disponivel_site;
	else                                                    $aux_qtde_disponivel_site = "0";

	#HD 13429
	if (strlen($_POST["preco_anterior"]) > 0)               $aux_preco_anterior = $preco_anterior;
	else                                                    $aux_preco_anterior = "0";

	if (strlen($_POST["gera_troca_produto"]) > 0)           $aux_gera_troca_produto = "TRUE";
	else                                                    $aux_gera_troca_produto = "FALSE";


	$aux_preco_anterior = str_replace(",",".",$aux_preco_anterior);
	$aux_preco_anterior = number_format($aux_preco_anterior, 2, '.','');

	if (strlen($_POST["multiplo"]) > 0) {
		$aux_multiplo = "'". trim($_POST["multiplo"]) ."'";

	}else{
		$msg_erro = "Digite o multiplo do produto.";
	}

	if($aux_multiplo_site > $aux_qtde_disponivel_site){
		$msg_erro = "Qtde multipla por peça não pode ser maior que a Qtde Disponível.";
	}

	$garantia_diferenciada = $_POST['garantia_diferenciada'];
	$aux_garantia_diferenciada = $garantia_diferenciada;
	if (strlen ($garantia_diferenciada) == 0)
		$aux_garantia_diferenciada = "null";

	$devolucao_obrigatoria = $_POST['devolucao_obrigatoria'];
	$aux_devolucao_obrigatoria = $devolucao_obrigatoria;
	if (strlen ($devolucao_obrigatoria) == 0)
		$aux_devolucao_obrigatoria = "f";

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
	if($login_fabrica == 11){
	$peca_unica_os = $_POST['peca_unica_os'];

		if (strlen($peca_unica_os) == 0)
			$peca_unica_os = "f";
	}

	//HD 78530
	if($login_fabrica==24 or $login_fabrica==11 or $login_fabrica == 14 or $login_fabrica == 43){
	$ativo = $_POST['ativo'];

		if (strlen($ativo) == 0)
			$ativo = "f";
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

	#HD 16207
	if ($login_fabrica<>1){
		if (strlen($peca) > 0) {
			$sql_referencia = " AND tbl_peca.peca <> $peca ";
		}
		$sql = "SELECT peca,referencia,descricao
				FROM tbl_peca
				WHERE fabrica  = $login_fabrica
				AND referencia =$aux_referencia
				$sql_referencia
				";
		$res = @pg_exec ($con,$sql);
		if (pg_numrows($res) > 0){
			$msg_erro .= "Já existe uma peça cadastrada com esta referência.";
		}
	}

	if ($login_fabrica == 10){
		if (strlen($peca) == 0) {
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
							$peca_atualiza = pg_result ($resAPa,$j,peca);
							$posicao_site_nova =  pg_result ($resAPa,$j,posicao_site) + 1;
							$sqlAPx = "UPDATE tbl_peca SET posicao_site = $posicao_site_nova
									WHERE peca = $peca_atualiza AND fabrica = $login_fabrica";
							$resAPx = pg_exec($con,$sqlAPx);
						}
					}
			}
		}

		if (strlen($peca) > 0) {
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

				$posicao_site_peca = pg_result ($resPosPeca,0,posicao_site);

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
							$peca_atualiza = pg_result ($resAPb,$j,peca);
							$posicao_site_nova =  pg_result ($resAPb,$j,posicao_site) + 1;
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
							$peca_atualiza = pg_result ($resAPb,$j,peca);
							$posicao_site_nova =  pg_result ($resAPb,$j,posicao_site) - 1;
							$sqlAPx = "UPDATE tbl_peca SET posicao_site = $posicao_site_nova
									WHERE peca = $peca_atualiza AND fabrica = $login_fabrica";
							$resAPx = pg_exec($con,$sqlAPx);
						}
					}
				}
			}
		}
	}

	if (strlen ($msg_erro) == 0){
		$res = pg_exec ($con,"BEGIN TRANSACTION");

		if (strlen($peca) == 0) {
			###INSERE NOVO REGISTRO
			$ipi_agregado = "null";
			if ($login_fabrica == 1) $ipi_agregado = 1 + ($ipi /100) ;

			$sql = "INSERT INTO tbl_peca (
						fabrica                          ,
						referencia                       ,
						descricao                        ,
						ipi                              ,
						ipi_agregado					 ,
						origem                           ,
						estoque                          ,
						unidade                          ,
						peso                             ,
						classificacao_fiscal             ,
						multiplo                         ,
						garantia_diferenciada            ,
						devolucao_obrigatoria            ,
						item_aparencia                   ,
						acumular_kit                     ,
						linha_peca                       ,
						familia_peca                     ,
						pre_selecionada                  ,
						placa                            ,
						retorna_conserto                 ,
						intervencao_carteira             ,
						promocao_site                    ,
						at_shop                          ,
						multiplo_site                    ,
						qtde_max_site                    ,
						qtde_disponivel_inicial_site     ,
						qtde_disponivel_site             ,
						qtde_minima_estoque              ,
						frete_gratis                     ,
						posicao_site	                 ,
						liquidacao                       ,
						gera_troca_produto               ,
						";
			if ($login_fabrica == 14) {
				$sql .= "mao_de_obra_troca, ";
			}
			//hd 47129
			if ($login_fabrica == 11) {
				$sql .= "peca_unica_os, ";
			}
			$sql .="	bloqueada_garantia               ,
						acessorio                        ,
						aguarda_inspecao                 ,
						peca_critica                     ,
						marca                            ,
						informacoes                      ,
						preco_compra                     ,
						produto_acabado                  ,
						reembolso                        ,
						mero_desgaste                    ,
						troca_obrigatoria                ,
						mo_peca                          ,
						mo_posto                         ,
						mo_admin                         ,
						admin                            ,
						preco_anterior                   ,
						data_atualizacao                 ,
						cobrar_servico                   ,
						faturada_manualmente             ,
						data_inicial_liquidacao
					) VALUES (
						$login_fabrica                   ,
						trim($aux_referencia)            ,
						trim($aux_descricao)             ,
						fnc_so_numeros($aux_ipi)::integer,
						$ipi_agregado                    ,
						$aux_origem                      ,
						fnc_limpa_moeda($aux_estoque)    ,
						$aux_unidade                     ,
						fnc_limpa_moeda($aux_peso)       ,
						'$classificacao_fiscal'          ,
						'$multiplo'                      ,
						$aux_garantia_diferenciada       ,
						'$aux_devolucao_obrigatoria'     ,
						'$aux_item_aparencia'            ,
						'$aux_acumular_kit'              ,
						$aux_linha                       ,
						$aux_familia                     ,
						'$aux_pre_selecionada'           ,
						'$aux_placa'                     ,
						'$aux_retorna_conserto'          ,
						'$aux_intervencao_carteira'      ,
						$aux_promocao_site               ,
						$aux_at_shop                     ,
						$aux_multiplo_site               ,
						$aux_qtde_max_site               ,
						$aux_qtde_disponivel_inicial_site,
						$aux_qtde_disponivel_site        ,
						$aux_qtde_minima_estoque         ,
						$aux_frete_gratis                ,
						$aux_posicao_site                ,
						$aux_liquidacao                  ,
						$aux_gera_troca_produto          ,";
			if ($login_fabrica == 14) {
				$sql .= "fnc_limpa_moeda($aux_mao_de_obra_diferenciada), ";
			}
			//hd 47129
			if ($login_fabrica == 11) {
				$sql .= "'$peca_unica_os', ";
			}
			$sql .= "	'$aux_bloqueada_garantia'        ,
						'$aux_acessorio'                 ,
						'$aux_aguarda_inspecao'          ,
						'$aux_peca_critica'              ,
						$aux_marca                       ,
						$aux_informacoes                 ,
						$aux_preco_compra                ,
						'$aux_produto_acabado'           ,
						'$aux_reembolso'                 ,
						'$aux_mero_desgaste'             ,
						'$aux_troca_obrigatoria'         ,
						$mo_peca                         ,
						$mo_posto                        ,
						$mo_admin                        ,
						$login_admin                     ,
						$aux_preco_anterior              ,
						current_timestamp                ,
						'$aux_cobrar_servico'            ,
						'$faturada_manualmente'          ,
						$aux_data_inicial_liquidacao
					);";
		}else{

			###ALTERA REGISTRO
			$ipi_agregado = "null";
			if ($login_fabrica == 1) $ipi_agregado = 1 + ($ipi /100) ;

			if ($login_fabrica == 3){
				$sqlx = "SELECT qtde_disponivel_inicial_site
							FROM tbl_peca
							WHERE tbl_peca.fabrica = $login_fabrica
							AND   tbl_peca.peca    = $peca";
				$resx = pg_exec($con, $sqlx);

				if(pg_numrows($resx)>0){
					$xqtde_disponivel_inicial_site = pg_result($resx, 0, qtde_disponivel_inicial_site);

					if($aux_qtde_disponivel_site > $xqtde_disponivel_inicial_site){
						$aux_qtde_disponivel_inicial_site = $aux_qtde_disponivel_site;
					}
				}
			}

			$sql = "UPDATE tbl_peca SET
							referencia            = trim($aux_referencia)            ,
							descricao             = trim($aux_descricao)             ,
							ipi                   = fnc_so_numeros($aux_ipi)::integer,
							ipi_agregado          = $ipi_agregado                    ,
							origem                = $aux_origem                      ,
							estoque               = fnc_limpa_moeda($aux_estoque)    ,
							unidade               = $aux_unidade                     ,
							peso                  = $aux_peso                        ,
							classificacao_fiscal  = '$classificacao_fiscal'          ,
							multiplo              = '$multiplo'                      ,
							garantia_diferenciada = $aux_garantia_diferenciada       ,
							devolucao_obrigatoria = '$aux_devolucao_obrigatoria'     ,
							item_aparencia        = '$aux_item_aparencia'            ,
							acumular_kit          = '$aux_acumular_kit'              ,
							linha_peca            = $aux_linha                       ,
							familia_peca          = $aux_familia                     ,
							retorna_conserto      = '$aux_retorna_conserto'          ,
							intervencao_carteira  = '$aux_intervencao_carteira'      ,
							posicao_site          = $aux_posicao_site                ,
							liquidacao            = $aux_liquidacao                  ,
							promocao_site         = $aux_promocao_site               ,
							at_shop               = $aux_at_shop                     ,
							multiplo_site         = $aux_multiplo_site               ,
							qtde_max_site         = $aux_qtde_max_site               ,
							qtde_disponivel_inicial_site  = $aux_qtde_disponivel_inicial_site,
							qtde_disponivel_site  = $aux_qtde_disponivel_site        ,
							qtde_minima_estoque   = $aux_qtde_minima_estoque         ,
							frete_gratis          = $aux_frete_gratis                ,
							gera_troca_produto    = $aux_gera_troca_produto         ,";
			if ($login_fabrica == 14) {
				$sql .= "	mao_de_obra_troca     = fnc_limpa_moeda($aux_mao_de_obra_diferenciada), ";
			}
			//hd 474129
			if ($login_fabrica == 11) {
				$sql .= "	peca_unica_os     = '$peca_unica_os', ";
			}
			if ($login_fabrica == 24 or $login_fabrica == 11 or $login_fabrica == 14 or $login_fabrica == 43) {
				$sql .= "	ativo     = '$ativo', ";
			}
			$sql .= "		bloqueada_garantia    = '$aux_bloqueada_garantia'        ,
							acessorio             = '$aux_acessorio'                 ,
							aguarda_inspecao      = '$aux_aguarda_inspecao'          ,
							peca_critica          = '$aux_peca_critica'              ,
							marca                 = $aux_marca                       ,
							informacoes           = $aux_informacoes                 ,
							preco_compra          = $aux_preco_compra                ,
							produto_acabado       = '$aux_produto_acabado'           ,
							pre_selecionada       = '$aux_pre_selecionada'           ,
							placa                 = '$aux_placa'                     ,
							reembolso             = '$aux_reembolso'                 ,
							mero_desgaste         = '$aux_mero_desgaste'             ,
							troca_obrigatoria     = '$aux_troca_obrigatoria'         ,
							mo_peca               = $mo_peca                         ,
							mo_posto              = $mo_posto                        ,
							mo_admin              = $mo_admin                        ,
							admin                 = $login_admin                     ,
							preco_anterior        = $aux_preco_anterior              ,
							data_atualizacao      = current_timestamp                ,
							faturada_manualmente  = '$faturada_manualmente'          ,
							cobrar_servico         = '$aux_cobrar_servico'           ,
							data_inicial_liquidacao = $aux_data_inicial_liquidacao
					WHERE  tbl_peca.fabrica       = tbl_fabrica.fabrica
					AND    tbl_peca.fabrica       = $login_fabrica
					AND    tbl_peca.peca          = $peca;";

		}
	//echo $sql;
//	echo nl2br($sql);
		$res = @pg_exec ($con,$sql);

		$msg_erro = pg_errormessage($con);
		if (strlen($peca) == 0) {
			$res = @pg_exec ($con,"SELECT CURRVAL ('seq_peca')");
			$peca  = pg_result ($res,0,0);
		}
		//alteração para gustavo da bosch - Raphael Giovanini

		if ($login_fabrica == 10) {
			$preco_peca = $_POST['preco_peca'];
			$aux_preco_peca = str_replace( ',', '.', $preco_peca);
			if(strlen($preco_peca) == 0){
				$preco_peca=0;
			}
			$sql = "SELECT tabela_item from tbl_tabela_item where peca = '$peca' and preco is not null and tabela = '30' order by tabela_item";
			$res = pg_exec ($con,$sql);
			if (pg_numrows($res) == 0) {
				$sqlx = "INSERT INTO tbl_tabela_item (
							tabela                       ,
							peca                         ,
							preco
							) VALUES (
							'30'                         ,
							$peca                        ,
							fnc_limpa_moeda('$aux_preco_peca')
							);";
				$resx = pg_exec($con,$sqlx);

			} else {

				$xtabela_item = pg_result($res,0,tabela_item);

				$sqlx = "UPDATE tbl_tabela_item SET
							preco        =        fnc_limpa_moeda('$aux_preco_peca')
							where tabela_item    =     $xtabela_item
							and peca             =     $peca";
				$resx = pg_exec($con,$sqlx);

			}
		}


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

		if(1==2){
		//inclusao de arquivos
			$arquivo                = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;
			if (strlen ($msg_erro) == 0) {
				$config["tamanho"] = 2048000; // Tamanho máximo do arquivo (em bytes)

				if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "none"){

					// Verifica o mime-type do arquivo
					if (!preg_match("/\/(pdf|msword|pjpeg|jpeg|png|gif|bmp|vnd.ms-excel|richtext|plain)$/", $arquivo["type"])){
						$msg_erro = "Arquivo em formato inválido!";
					} else { // Verifica tamanho do arquivo
						if ($arquivo["size"] > $config["tamanho"])
							$msg_erro = "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.";
					}
					if (strlen($msg_erro) == 0) {
						// Pega extensão do arquivo
						preg_match("/\.(pdf|doc|gif|bmp|png|jpg|jpeg|rtf|xls|txt){1}$/i", $arquivo["name"], $ext);
						$aux_extensao = "'".$ext[1]."'";

						$nome_sem_espaco = implode("", explode(" ",$arquivo["name"]));

						// Gera um nome único para a imagem
						$nome_anexo = "/www/akacia/www/loja/produtos/" . $peca."-".strtolower ($nome_sem_espaco);
	//echo "<br>Nome do anexo: $nome_anexo <BR>";exit;
	//echo "$nome_sem_espaco";
						// Faz o upload da imagem
						if (strlen($msg_erro) == 0) {
							if (copy($arquivo["tmp_name"], $nome_anexo)) {
							}else{
								$msg_erro = "Arquivo não foi enviado!!!";
							}
						}//fim do upload da imagem
					}//fim da verificação de erro
				}//fim da verificação de existencia no apache
			}//fim de todo o upload
		}

	/*upload arquivo takashi*/

//  HD 97404 - MLG - Adiciono Ga.Ma Italy à lista de fabricantes
//  HD 100696 Adicionado Lorenzetti
	if(in_array($login_fabrica, array(1, 3, 5, 6, 10, 11, 19, 35, 45, 46, 50, 51)) OR $lv==1) {

		if (isset($_FILES['arquivos'])){
			$Destino = "/www/assist/www/$caminho_dir/media/";
			$DestinoP = "/www/assist/www/$caminho_dir/pequena/";
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
					if(preg_match('/^image\/(pjpeg|jpeg|png|gif|jpg)$/', $Tipo)){


						if(!is_uploaded_file($Tmpname)){
							$msg_erro .= "Não foi possível efetuar o upload.";
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
							$peca_item_foto = pg_result($res,0,0);
						}

						if($login_fabrica == 10){
							$nome_foto  = "$referencia-$peca_item_foto.$ext";
							$nome_foto = str_replace(" ","_",$nome_foto);
							$nome_thumb = "$referencia-$peca_item_foto.$ext";
							$nome_thumb = str_replace(" ","_",$nome_thumb);
						}else{
							$nome_foto  = "$peca.$ext";
							$nome_thumb = "$peca.$ext";
						}

#$msg_erro .= " $nome_foto - $nome_thumb -";

						$Caminho_foto  = $Destino . $nome_foto;
						$Caminho_thumb = $DestinoP . $nome_thumb;

						#Atualiza o nome do arquivo na tabela
						if ($login_fabrica==10 AND strlen($peca_item_foto)>0){
							$sql = "UPDATE tbl_peca_item_foto SET caminho = '$Caminho_foto',caminho_thumb = '$Caminho_thumb'
									WHERE peca_item_foto = $peca_item_foto AND peca = $peca";
							$res = pg_exec ($con,$sql);
						}

						$peca_imagem = $_POST['peca_imagem'];

						#Apaga a imagem anterior
						if(strlen($peca_imagem)>0 AND $login_fabrica<>10){
							unlink($Destino.$peca_imagem);
							unlink($DestinoP.$peca_imagem);
						}
						reduz_imagem($Tmpname, 400, 300, $Caminho_foto);
						reduz_imagem($Tmpname, 100, 90,  $Caminho_thumb);
					}else{
						$msg_erro .= "O formato da foto $Nome não é permitido!<br>";
					}
				}
			}
		}
	}

#$msg_erro .= "ERRO";

		##Foi mudado o local para sempre que cadastrar a peça já atualizar os defeitos.
		$total_servico_realizado = $_POST['total_servico_realizado'];
		if(strlen($total_servico_realizado)>0 and $login_fabrica==6 and strlen($peca)>0){

			$sql = "UPDATE tbl_peca_servico set ativo='f' WHERE peca = $peca";
			$res = pg_exec($con,$sql);
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
			for($i=0;$i<$total_defeitos;$i++){
				$defeito = $_POST['defeito_'.$i];

				if(strlen($defeito)>0){

					$sql = "SELECT peca from tbl_peca_defeito where peca = $peca and defeito = $defeito";
					$res = pg_exec($con,$sql);

					if(pg_numrows($res)>0){
						$sql = "UPDATE tbl_peca_defeito set ativo='t' WHERE peca = $peca and defeito = $defeito";
						$res = pg_exec($con,$sql);
						//$msg_erro = "Cadastrado com Sucesso!!";
					}else{
						$sql = "INSERT into tbl_peca_defeito (peca,defeito,ativo)values($peca,$defeito,'t')";
						$res = pg_exec($con,$sql);
					//	$msg_erro = "Cadastrado com Sucesso!!";
					}
				}
			}
		}



			if (strlen ($msg_erro) == 0) {
				###CONCLUI OPERAÇÃO DE INCLUSÃO/EXLUSÃO/ALTERAÇÃO E SUBMETE
				$res = pg_exec ($con,"COMMIT TRANSACTION");

				header ("Location: $PHP_SELF");
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
				$faturada_manualmente     = $_POST["faturada_manualmente"];
				$produto_acabado          = $_POST["produto_acabado"];
				$reembolso                = $_POST["reembolso"];
				$mero_desgaste			  = $_POST["mero_desgaste"];
				//$intervencao_fabrica      = $_POST["intervencao_fabrica"];
				$troca_obrigatoria        = $_POST["troca_obrigatoria"];
				$marca                    = $_POST["marca"];
				$informacoes              = $_POST["informacoes"];
				$preco_compra             = $_POST["preco_compra"];
				$aux_linha                = $_POST["linha"];
				$aux_familia              = $_POST["familia"];
				$pre_selecionada          = $_POST["pre_selecionada"];
				$placa                    = $_POST["placa"];
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


				if (strpos ($msg_erro,"duplicate key violates unique constraint \"tbl_peca_unico\"") > 0)
					$msg_erro = "Esta referência já esta cadastrada e não pode ser duplicada.";

				$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			}
		}//fim if msg_erro
}


###CARREGA REGISTRO
$peca = $_GET ['peca'];

if (strlen($peca) > 0) {
	$sql = "SELECT tbl_peca.peca                   ,
					tbl_peca.referencia            ,
					tbl_peca.descricao             ,
					tbl_peca.ipi                   ,
					tbl_peca.origem                ,
					tbl_peca.estoque               ,
					tbl_peca.unidade               ,
					tbl_peca.peso                  ,
					tbl_peca.classificacao_fiscal  ,
					tbl_peca.multiplo              ,
					tbl_peca.garantia_diferenciada ,
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
					tbl_peca.pre_selecionada       ,
					tbl_peca.placa                 ,
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
					tbl_tabela_item.preco          ,
					to_char(tbl_peca.data_atualizacao, 'DD/MM/YYYY HH24:MI') AS data_atualizacao,
					to_char(tbl_peca.data_inicial_liquidacao, 'DD/MM/YYYY') AS data_inicial_liquidacao,
					tbl_peca.peca_unica_os
			FROM    tbl_peca
			LEFT JOIN tbl_admin ON tbl_admin.admin = tbl_peca.admin
			LEFT JOIN tbl_tabela_item ON tbl_tabela_item.peca = tbl_peca.peca
			WHERE   tbl_peca.peca = $peca
			AND    tbl_peca.fabrica = $login_fabrica";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		$peca                     = trim(pg_result($res,0,peca));
		$referencia               = trim(pg_result($res,0,referencia));
		$descricao                = trim(pg_result($res,0,descricao));
		$ipi                      = trim(pg_result($res,0,ipi));
		$origem                   = trim(pg_result($res,0,origem));
		$estoque                  = trim(pg_result($res,0,estoque));
		$unidade                  = trim(pg_result($res,0,unidade));
		$peso                     = trim(pg_result($res,0,peso));
		$classificacao_fiscal     = trim(pg_result($res,0,classificacao_fiscal));
		$multiplo                 = trim(pg_result($res,0,multiplo));
		$garantia_diferenciada    = trim(pg_result($res,0,garantia_diferenciada));
		$devolucao_obrigatoria    = trim(pg_result($res,0,devolucao_obrigatoria));
		$item_aparencia           = trim(pg_result($res,0,item_aparencia));
		$acumular_kit             = trim(pg_result($res,0,acumular_kit));
		$retorna_conserto         = trim(pg_result($res,0,retorna_conserto));
		$intervencao_carteira     = trim(pg_result($res,0,intervencao_carteira));
		$mao_de_obra_diferenciada = trim(pg_result($res,0,mao_de_obra_troca));
		$bloqueada_garantia       = trim(pg_result($res,0,bloqueada_garantia));
		$acessorio                = trim(pg_result($res,0,acessorio));
		$aguarda_inspecao         = trim(pg_result($res,0,aguarda_inspecao));
		$peca_critica             = trim(pg_result($res,0,peca_critica));
		$produto_acabado          = trim(pg_result($res,0,produto_acabado));
		$reembolso                = trim(pg_result($res,0,reembolso));
		$mero_desgaste			  = trim(pg_result($res,0,mero_desgaste));
		$mo_peca                  = trim(pg_result($res,0,mo_peca));
		$mo_posto                 = trim(pg_result($res,0,mo_posto));
		$mo_admin                 = trim(pg_result($res,0,mo_admin));
		//$intervencao_fabrica          = trim(pg_result($res,0,intervencao_fabrica));
		$troca_obrigatoria        = trim(pg_result($res,0,troca_obrigatoria));
		$marca                    = trim(pg_result($res,0,marca));
		$informacoes              = trim(pg_result($res,0,informacoes));
		$preco_compra             = trim(pg_result($res,0,preco_compra));
		$linha                    = trim(pg_result($res,0,linha_peca));
		$familia                  = trim(pg_result($res,0,familia_peca));
		$ativo                    = trim(pg_result($res,0,ativo));
		$pre_selecionada          = trim(pg_result($res,0,pre_selecionada));
		$placa                    = trim(pg_result($res,0,placa));
		$promocao_site            = trim(pg_result($res,0,promocao_site));
		$at_shop                  = trim(pg_result($res,0,at_shop));
		$multiplo_site            = trim(pg_result($res,0,multiplo_site));
		//$qtde_minima_site         = trim(pg_result($res,0,qtde_minima_site));
		$qtde_max_site            = trim(pg_result($res,0,qtde_max_site));
		$qtde_disponivel_inicial_site = trim(pg_result($res,0,qtde_disponivel_inicial_site));
		$qtde_disponivel_site     = trim(pg_result($res,0,qtde_disponivel_site));
		$qtde_minima_estoque      = trim(pg_result($res,0,qtde_minima_estoque));
		$posicao_site             = trim(pg_result($res,0,posicao_site));
		$liquidacao               = trim(pg_result($res,0,liquidacao));
		$admin                    = trim(pg_result($res,0,login));
		$preco_anterior           = trim(pg_result($res,0,preco_anterior));
		$data_atualizacao         = trim(pg_result($res,0,data_atualizacao));
		$gera_troca_produto       = trim(pg_result($res,0,gera_troca_produto));
		$cobrar_servico           = trim(pg_result($res,0,cobrar_servico));
		$faturada_manualmente     = trim(pg_result($res,0,faturada_manualmente));
		$preco_peca               = trim(pg_result($res,0,preco));
		$frete_gratis             = trim(pg_result($res,0,frete_gratis));
		$data_inicial_liquidacao  = trim(pg_result($res,0,data_inicial_liquidacao));
		$preco_peca = str_replace( '.', ',', $preco_peca);

		//hd 47129
		$peca_unica_os  = trim(pg_result($res,0,peca_unica_os));
	}
}


$layout_menu = "cadastro";
$title = "Cadastramento de Peças";
include 'cabecalho.php';

?>

<script type="text/javascript" src="js/jquery-latest.pack.js"></script>

<script src="js/jquery.MultiFile.js" type="text/javascript" language="javascript"></script>
<script src="js/jquery.MetaData.js" type="text/javascript" language="javascript"></script>
<script type="text/javascript" src="js/thickbox.js"></script>
<script type="text/javascript" src="js/fckeditor/fckeditor.js"></script>

<? include "javascript_calendario.php"; ?>

<script language="JavaScript">
	$(function()
	{
		$("#data_inicial_liquidacao").maskedinput("99/99/9999");
	});

<? if($login_fabrica == 10) { ?>
window.onload = function(){
	var oFCKeditor = new FCKeditor( 'informacoes' ) ;
	oFCKeditor.BasePath = "js/fckeditor/" ;
	oFCKeditor.ToolbarSet = 'Peca' ;
	oFCKeditor.ReplaceTextarea() ;
}
<? } ?>
	function fnc_pesquisa_peca (campo, campo2, tipo) {
		if (tipo == "referencia" ) {
			var xcampo = campo;
		}

		if (tipo == "descricao" ) {
			var xcampo = campo2;
		}

		if (xcampo.value != "") {
			var url = "";
			url = "peca_pesquisa.php?forma=reload&campo=" + xcampo.value + "&tipo=" + tipo ;
			janela = window.open(url, "janela", "toolbar=no, location=no, status=yes, scrollbars=yes, directories=no, width=500, height=400, top=0, left=0");
			janela.retorno = "<? echo $PHP_SELF ?>";
			janela.referencia= campo;
			janela.descricao= campo2;
			janela.focus();
		}
	}

	function verificarIntervencao(campo,campo_2){
		$("input[@name='"+campo+"']").each( function (){
			if (this.checked){
				$("input[@name='"+campo_2+"']").each( function (){
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
				campo.innerHTML = " <font size='1' face='verdana'>&nbsp;Aguarde..</font>";
			}
			if (http3[curDateTime].readyState == 4){
				if (http3[curDateTime].status == 200 || http3[curDateTime].status == 304){
					var results = http3[curDateTime].responseText;
					campo.innerHTML = results;
				}else {
					alert('Ocorreu um erro');
				}
			}
		}
		http3[curDateTime].send(null);
	}
</script>

<link rel="stylesheet" href="js/thickbox.css" type="text/css" media="screen" />
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
.Tabela{
/*	border:1px solid #d2e4fc; */
/*	background-color:#485989;*/
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
</style>
</head>

<body>
<center>
<div id="wrapper">
<form name="frm_peca" method="post" action="<? $PHP_SELF ?>" enctype='multipart/form-data'>
<input type="hidden" name="peca" value="<? echo $peca ?>">

<!-- formatando as mensagens de erro -->
<?
if (strlen($msg_erro) > 0) {
	if (strpos($msg_erro,"ERROR: ") !== false) {
			$erro = "Foi detectado o seguinte erro:<br>";
			$msg_erro = substr($msg_erro, 6);
		}

		// retira CONTEXT:
		if (strpos($msg_erro,"CONTEXT:")) {
			$x = explode('CONTEXT:',$msg_erro);
			$msg_erro = $x[0];
		}

?>

<div class='error'>
	<? echo $msg_erro; ?>
</div>

<? } ?>

	<table width='700' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>
	<tr>
		<td bgcolor='#D9E2EF'><b>Referência</b> (*)</td>
		<td bgcolor='#D9E2EF'><b>Descrição</b> (*)</td>
		<td bgcolor='#D9E2EF'><b>Origem</b> (*)</td>
	</tr>
	<tr>
		<td bgcolor='#FfFfFF'><input class='frm' type="text" name="referencia" value="<? echo $referencia ?>" size="15" maxlength="20"><a href="javascript: fnc_pesquisa_peca (document.frm_peca.referencia,document.frm_peca.descricao,'referencia')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a></td>
		<td bgcolor='#FfFfFF'><input class='frm' type="text" name="descricao" value="<? echo $descricao ?>" size="30" maxlength="50"><a href="javascript: fnc_pesquisa_peca (document.frm_peca.referencia,document.frm_peca.descricao,'descricao')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a></td>
		<td  bgcolor='#FfFfFF'><select name='origem' size='1' class='frm'>
		<option value='NAC' <? if ($origem == 'NAC' OR $origem == 1) echo " selected " ?> > Fabricação </option>
		<option value='IMP' <? if ($origem == 'IMP' OR $origem == 2) echo " selected " ?> > Importado </option>
		<option value='TER' <? if ($origem == 'TER') echo " selected " ?> > Terceiros </option></td>
	</tr>

	<tr>
		<td bgcolor='#D9E2EF'><b>IPI</b> (*)</td>
		<td bgcolor='#D9E2EF'><b>Estoque</b></td>
		<td bgcolor='#D9E2EF' ><b>Unidade</b></td>
	</tr>
	<tr>
		<td bgcolor='#FfFfFF'><input class='frm' type="text" name="ipi" value="<? echo $ipi ?>" size="10" maxlength="20"></td>
		<td bgcolor='#FfFfFF'><input class='frm' type="text" name="estoque" value="<? echo $estoque ?>" size="10" maxlength="20"></td>
		<td bgcolor='#ffffff'><input class='frm' type="text" name="unidade" value="<? echo $unidade ?>" size="10" maxlength="10"></td>
	</tr>


	<tr>
		<td bgcolor='#D9E2EF'><b>Peso Kg</b></td>
		<td bgcolor='#D9E2EF'><b>Classificação Fiscal</b></td>
		<td bgcolor='#D9E2EF'><b>Multiplo</b></td>
	</tr>
	<tr>
		<td bgcolor='#FfFfFF'><input class='frm' type="text" name="peso" value="<? echo $peso ?>" size="10" maxlength="20"></td>
		<td bgcolor='#FfFfFF'><input class='frm' type="text" name="classificacao_fiscal" value="<? echo $classificacao_fiscal ?>" size="10" maxlength="20"></td>
		<td bgcolor='#FfFfFF' colspan="4" align="center"><input class='frm' type="text" name="multiplo" value="<? if (strlen($multiplo) == 0){echo "1";}else{echo "$multiplo";}  ?>" size="10" maxlength="20"></td>
	</tr>

	<? if($login_fabrica==51){?>
		<tr>
			<td bgcolor='#D9E2EF'><b>Qtde Max. Faturado</b></td>
			<td bgcolor='#D9E2EF'><b>&nbsp;</b></td>
			<td bgcolor='#D9E2EF'><b>&nbsp;</b></td>
		</tr>
		<tr>
			<td bgcolor='#FfFfFF'><input class='frm' type="text" name="qtde_max_site" value="<? echo $qtde_max_site ?>" size="10" maxlength="20"></td>
			<td bgcolor='#FfFfFF'>&nbsp;</td>
			<td bgcolor='#FfFfFF'>&nbsp;</td>
		</tr>
	<?}?>


<?
//and $ip= "201.92.127.116"
if($login_fabrica ==2 ){
	echo "
	<tr>
		<td bgcolor='#D9E2EF'><b>Mão de Obra Peça</b></td>
		<td bgcolor='#D9E2EF'><b>Mão de Obra Posto</b></td>
		<td bgcolor='#D9E2EF'><b>Mão de Obra Admin</b></td>
	</tr>

	<tr>
		<td bgcolor='#FfFfFF'>
			<input class='frm' type='text' name='mo_peca'  value='$mo_peca'  size='10' maxlength='20'>
		</td>
		<td bgcolor='#FfFfFF'>
			<input class='frm' type='text' name='mo_posto' value='$mo_posto' size='10' maxlength='20'>
		</td>
		<td bgcolor='#FfFfFF' colspan='4' align='center'>
			<input class='frm' type='text' name='mo_admin' value='$mo_admin' size='10' maxlength='20'></td>
	</tr>";

}
?>

</table>
<?
if($login_fabrica == 20 AND  strlen($peca)>0){
	echo "<table width='700'align='center'><tr><td><div class='Div'>";
	$sql = "SELECT * FROM tbl_peca_idioma WHERE peca = $peca AND upper(idioma)='ES'";
	$res2 = pg_exec ($con,$sql);

	if (pg_numrows($res2) > 0) {
		for ( $i = 0 ; $i < pg_numrows($res2) ; $i++ ) {
		$peca                     = trim(pg_result($res2,$i,peca));
		$idioma                   = trim(pg_result($res2,$i,idioma));
		$descricao_idioma         = trim(pg_result($res2,$i,descricao));
		if($idioma == 'ES')echo "<b>Espanhol</b><br>";
		else               echo "<b>Inglês</b><br>";
		echo "Descrição: <input  type='text' class='frm' name='descricao_idioma' value='$descricao_idioma' size='30' maxlength='50'><br><br>";
		echo "<input type='hidden' name='idioma' value='$idioma'>";
		}


	}else{
		echo "Não existe descrição para essa peça em outro idioma, preencha o campo abaixo para inserir uma.<br>";
		echo "<b>Espanhol</b><br>";
		echo "Descrição: <input  type='text' class='frm' name='descricao_idioma' value='$descricao_idioma' size='30' maxlength='50'><br><br>";
		echo "<input type='hidden' name='idioma' value='ES'>";
	}

	echo "</div></td></tr></table>";
}
if ($login_fabrica==21){

?>


<table width='700' border="0" cellspacing="1" cellpadding="0" class='Tabela' align='center'>
	<tr>
		<TD class='inicio' colspan='4'height='15' colspan='4'>&nbsp;INFORMAÇÕES PARA VENDA&nbsp;</TD>
	</tr>
	<TR>
		<td class='titulo'width='100' height='15'>PREÇO DE COMPRA&nbsp;</td>
		<td class='conteudo' width='100' height='15'><input class='frm' type='text' name='preco_compra' value='<? echo $preco_compra; ?>'></td>
		<td class='titulo' width='100' height='15'>MARCA&nbsp;</td>
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
			if ($marca == pg_result ($res,$i,marca) ) echo " selected ";
					echo " value='" . pg_result ($res,$i,marca) . "'>" ;
					echo pg_result ($res,$i,nome) ;
					echo "</option>";
				}
?>
				</select>

		</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td class='titulo'>CATEGORIA</td>
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
				$aux_linha = trim(pg_result($res,$x,linha));
				$aux_nome  = trim(pg_result($res,$x,nome));

				echo "<option value='$aux_linha'"; if ($linha == $aux_linha) echo " SELECTED "; echo ">$aux_nome</option>\n";
			}
			echo "</select>\n";
		}
		##### FIM LINHA #####
		?>
		</td>
		<td class='titulo'>SUBCATEGORIA</td>
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
			echo "<option value=''>ESCOLHA</option>\n";

			for ($x = 0 ; $x < pg_numrows($res) ; $x++){
				$aux_familia = trim(pg_result($res,$x,familia));
				$aux_descricao  = trim(pg_result($res,$x,descricao));

				echo "<option value='$aux_familia'"; if ($familia == $aux_familia) echo " SELECTED "; echo ">$aux_descricao</option>\n";
			}
			echo "</select>\n";
		}
		##### FIM FAMÍLIA #####
		?>

		</td>
	</tr>
	<tr>
		<td class='titulo' width='100' height='15'>INFORMAÇÕES&nbsp;</td>
		<td class='conteudo' width='100' height='15' colspan='3'><TEXTAREA NAME="informacoes" ROWS="10" COLS="70"><? echo ($informacoes)?></TEXTAREA>
		Arquivo <input type='file' name='arquivo' size='50' class='frm'>
		</td>
	</tr>

<?
}
?>






<!--

<table width = '700' align='center' border='0' cellpadding='1' cellspacing='1'>
	<tr align='left'>
		<td align='right' bgcolor='#D9E2EF'><b>Referência</b> (*)</td>
		<td bgcolor='#F0F7FF'><input class='frm' type="text" name="referencia" value="<?// echo $referencia ?>" size="15" maxlength="20"><a href="javascript: fnc_pesquisa_peca (document.frm_peca.referencia,document.frm_peca.descricao,'referencia')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a></td>
		<td align='right' bgcolor='#D9E2EF'><b>Descrição</b> (*)</td>
		<td bgcolor='#F0F7FF'><input class='frm' type="text" name="descricao" value="<? //echo $descricao ?>" size="30" maxlength="50"><a href="javascript: fnc_pesquisa_peca (document.frm_peca.referencia,document.frm_peca.descricao,'descricao')"><IMG SRC="imagens_admin/btn_buscar5.gif" ></a></td>
	</tr>
	<tr align='left'>
		<td align='right' bgcolor='#D9E2EF'><b>Origem</b> (*)</td>
		<td bgcolor='#F0F7FF'><select name='origem' size='1' class='frm'>
			<option value='NAC' <?// if ($origem == 'NAC' OR $origem == 1) echo " selected " ?> > Fabricação </option>
			<option value='IMP' <?// if ($origem == 'IMP' OR $origem == 2) echo " selected " ?> > Importado </option>
			<option value='TER' <?// if ($origem == 'TER') echo " selected " ?> > Terceiros </option>

	</tr>
	<tr align='left'>
		<td align='right' bgcolor='#D9E2EF'><b>Estoque</b></td>
		<td bgcolor='#F0F7FF'><input class='frm' type="text" name="estoque" value="<? //echo $estoque ?>" size="10" maxlength="20">
		<td   bgcolor='#D9E2EF' align='right'><b>Unidade</b></td>
		<td bgcolor='#F0F7FF'><input class='frm' type="text" name="unidade" value="<?// echo $unidade ?>" size="10" maxlength="10"></td>
	</tr>
	<tr align='left'>
		<td align='right'  bgcolor='#D9E2EF'><b>Peso</b></td>
		<td bgcolor='#F0F7FF'><input class='frm' type="text" name="peso" value="<?// echo $peso ?>" size="10" maxlength="20"></td>
		<td align='right'  bgcolor='#D9E2EF'><b>Classificação Fiscal</b></td>
		<td bgcolor='#F0F7FF'><input class='frm' type="text" name="classificacao_fiscal" value="<? //echo //$classificacao_fiscal ?>" size="10" maxlength="20"></td>
	</tr>
	<tr>
	<td align='right'  bgcolor='#D9E2EF'><b>Multiplo</b></td>
	<td bgcolor='#F0F7FF' colspan="4" align="left"><input class='frm' type="text" name="multiplo" value="<?// echo $multiplo ?>" size="10" maxlength="20"></td>
	</tr>
</table>
-->


<table width='700' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>
<tr  bgcolor='#D9E2EF' style='font-size:10px'>
	<td align='center'><b>Garantia Diferenciada (meses)</b></td>
	<td align='center'><b>Devolução Obrigatória</b></td>
	<td align='center'><b>Item de Aparência</b></td>
	<td align='center'><b>Acumula para Kit</b></td>
	<td align='center'><b>
		<?if($login_fabrica==2 OR $login_fabrica==3 OR $login_fabrica==11){
			echo "Peça sob Intervenção";
		}else {
			echo"Retorna para Conserto";
		}
		?></b>
	</td>
	<?if ($login_fabrica == 14 OR $login_fabrica == 20) {?>
	<td align='center'><b>Mão-de-obra Diferenciada (TROCA)</b></td>
	<?}?>
	<td align='center'>
		<?if ($login_fabrica==3 OR $login_fabrica==11) {?>
			<acronym title="Se marcada,a OS's entrará em Intervenção">
			<b>Bloqueada para Garantia</b>
			</acronym>
		<?}else{?>
			<b>Bloqueada para Garantia</b>
		<?}?>

	</td>
	<td align='center'><b>Acess.</b></td>
	<td align='center'><b>Aguarda Inspeção</b></td>
	<td align='center'><b>
		<? if ($login_fabrica == 11) {
			echo '<acronym title="Se marcada,a OS entrará em Intervenção de Suprimentos">Peça Crítica</acronym>';
		 }else{
			echo 'Peça Crítica';
		}?>
		</b>
	</td>
	<td align='center'><b>Produto Acabado</b></td>
	<? if ($login_fabrica !=7 ) { ?>
	<td align='center'><b>Mero Desgaste</b></td>
	<?}?>
	<? if ($login_fabrica ==7 ) { ?>
	<td align='center'><b>Cobra Serviço</b></td>
	<?}?>
	<? if ($login_fabrica == 11) { ?>
		<td align='center'><b>Reembolso</b></td>
		<td align='center'><b><nobr>Peça crítica</nobr><br><nobr>única na OS</nobr></b></td>
	<? } ?>
	<? if ($login_fabrica == 3 OR $login_fabrica == 51) { ?>
		<td align='center'><b>Troca Obrigatória</b></td>
	<? } ?>
	<? if ($login_fabrica == 45) { ?>
		<td align='center'><b>Gerar Troca de Produto</b></td>
	<? } ?>
	<? if ($login_fabrica == 24 or $login_fabrica == 11 or $login_fabrica == 14 or $login_fabrica == 43) { ?>
		<td align='center'><b><font color='#880000'>ATIVO</font></b></td>
	<? } ?>


	<? if ($login_fabrica == 43) { ?>
		<td align='center'><b>Faturar Manualmente</font></b></td>
	<? } ?>



	<? if ($login_fabrica == 7 ){ ?>
		<td align='center'><b>Placa</b></td>
	<? } ?>
	<td align='center'><b>Pre - Selec.</b></td>
</tr>


<tr  bgcolor='#FFFFFF'>
	<td align='center'>
		<input class='frm' type="text" name="garantia_diferenciada" value="<? echo $garantia_diferenciada ?>" size="3" maxlength="3">
	</td>

	<td align='center'>
		<input type='checkbox' <? if ($devolucao_obrigatoria == 't' ) echo " checked " ?> name='devolucao_obrigatoria' value='t'>
	</td>

	<td align='center'>
		<input type='checkbox' <? if ($item_aparencia == 't' ) echo " checked " ?> name='item_aparencia' value='t'>
	</td>

	<td align='center'>
		<input type='checkbox' <? if ($acumular_kit == 't' ) echo " checked " ?> name='acumular_kit' value='t'>
	</td>

	<td align='center'>
		<?if ($login_fabrica==3){ /* HD 35521 */ ?>
			<div align='left'>
				<input type='checkbox' <? if ($retorna_conserto == 't' AND $intervencao_carteira != 't') echo " checked "?> name='retorna_conserto' value='t' onClick="javascript:verificarIntervencao('retorna_conserto','intervencao_carteira')"> SAP <br>
				<input type='checkbox' <? if ($intervencao_carteira == 't' ) echo " checked " ?> name='intervencao_carteira' value='t' onClick="javascript:verificarIntervencao('intervencao_carteira','retorna_conserto')"> Carteira
			</div>
		<?}else{?>
			<input type='checkbox' <? if ($retorna_conserto == 't' ) echo " checked " ?> name='retorna_conserto' value='t'>
		<?}?>
	</td>

	<?if ($login_fabrica == 14 OR $login_fabrica == 20) {?>
	<td align='center'>
		<input class='frm' type="text" name="mao_de_obra_diferenciada" value="<? echo $mao_de_obra_diferenciada ?>" size="5" maxlength="10">
	</td>

	<?}?>

	<td align='center'>
		<input type='checkbox' <? if ($bloqueada_garantia == 't' ) echo " checked " ?> name='bloqueada_garantia' value='t'>
	</td>

	<td align='center'>
		<input type='checkbox' <? if ($acessorio == 't' ) echo " checked " ?> name='acessorio' value='t'>
	</td>

	<td align='center'>
		<input type='checkbox' <? if ($aguarda_inspecao == 't' ) echo " checked " ?> name='aguarda_inspecao' value='t'>
	</td>

	<td align='center'>
		<input type='checkbox' <? if ($peca_critica == 't' ) echo " checked " ?> name='peca_critica' value='t'>
	</td>

	<td align='center'>
		<input type='checkbox' <? if ($produto_acabado == 't' ) echo " checked " ?> name='produto_acabado' value='t'>
	</td>

	<? if ($login_fabrica != 7) { ?>
	<td align='center'>
		<input type='checkbox' <? if ($mero_desgaste == 't' ) echo " checked " ?> name='mero_desgaste' value='t'>
	</td>
	<?}?>

	<? if ($login_fabrica == 7) { ?>
	<td align='center'>
		<input type='checkbox' <? if ($cobrar_servico == 't' ) echo " checked " ?> name='cobrar_servico' value='t'>
	</td>
	<?}?>

	<? if ($login_fabrica == 11) { ?>
		<td align='center'>
			<input type='checkbox' <? if ($reembolso == 't' ) echo " checked " ?> name='reembolso' value='t'>
		</td>
	<? } ?>

	<? if ($login_fabrica == 3 OR $login_fabrica == 51) { ?>
		<td align='center'>
			<input type='checkbox' <? if ($troca_obrigatoria == 't' ) echo " checked " ?> name='troca_obrigatoria' value='t'>
		</td>
	<? } ?>
	<? if ($login_fabrica == 45) { ?>
		<td align='center'>
			<input type='checkbox' <? if ($gera_troca_produto == 't' ) echo " checked " ?> name='gera_troca_produto' value='t'>
		</td>
	<? } ?>

	<?//hd 47129?>
	<? if ($login_fabrica == 11) { ?>
		<td align='center'>
			<input type='checkbox' <? if ($peca_unica_os == 't' ) echo " checked "; ?> name='peca_unica_os' value='t'>
		</td>
	<? } ?>

	<? if ($login_fabrica == 24 or $login_fabrica == 11 or $login_fabrica == 14 or $login_fabrica == 43) { ?>
		<td align='center'>
			<input type='checkbox' <? if ($ativo == 't' ) echo " checked "; ?> name='ativo' value='t'>
		</td>
	<? } ?>

	<? if ($login_fabrica == 43) { ?>
		<td align='center'>
			<input type='checkbox' <? if ($faturada_manualmente == 't' ) echo " checked "; ?> name='faturada_manualmente' value='t'>
		</td>
	<? } ?>


	<? if ($login_fabrica == 7) { #HD 47695 ?>
	<td align='center'>
		<input type='checkbox' <? if ($placa == 't' ) echo " checked " ?> name='placa' value='t'>
	</td>
	<?}?>
	<td align='center'>
		<input type='checkbox' <? if ($pre_selecionada == 't' ) echo " checked " ?> name='pre_selecionada' value='t'>
	</td>
</tr>


</table>
<?
if($login_fabrica==6){
if(strlen($peca)>0){

	$sql = "select defeito from tbl_peca_defeito where peca=$peca and ativo='t'";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		for($x=0;$x<pg_numrows($res);$x++){
			$defeito_peca[] = pg_result($res,$x,defeito);
		}
	}else{
	$defeito_peca[]=0;
	}

}else{
	$defeito_peca[]=0;
}
?>
<table width='700' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>
<tr  bgcolor='#D9E2EF' style='font-size:10px'>
	<td align='center' colspan='8'><b>Defeitos na peça</b></td>
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
			$xdefeito   = pg_result($res,$x,defeito);
			$xdescricao = pg_result($res,$x,descricao);
			if(($x % 4)==0){ echo "</tr><tr>";}

			echo "<td align='center'>&nbsp;</td><td align='left'><input type='checkbox' name='defeito_$x' value='$xdefeito'"; if(in_array($xdefeito,$defeito_peca)){ echo "checked";} echo "> $xdescricao</td>";
		}
	echo "<input type='hidden' name='total_defeitos' value='$x'>";
	}

?>
	<td align='center'>
</tr>
</table>
<p>
<?
if(strlen($peca)>0){

	$sql = "SELECT servico_realizado
			FROM  tbl_peca_servico
			WHERE peca=$peca
			AND   ativo='t'";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		for($x=0;$x<pg_numrows($res);$x++){
			$servico_realizado_peca[] = pg_result($res,$x,servico_realizado);
		}
	}else{
	$servico_realizado_peca[]=0;
	}

}else{
	$servico_realizado_peca[]=0;
}
?>
<table width='700' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>
<tr  bgcolor='#D9E2EF' style='font-size:10px'>
	<td align='center' colspan='8'><b>Serviços na peça</b></td>
</tr>
<tr  bgcolor='#FFFFFF'>
<?
	$sql = "SELECT 	tbl_servico_realizado.servico_realizado     ,
					tbl_servico_realizado.descricao
			FROM  tbl_servico_realizado
			WHERE fabrica = $login_fabrica
			AND   tbl_servico_realizado.ativo IS TRUE
			and   tbl_servico_realizado.solucao is not true
			ORDER BY tbl_servico_realizado.descricao";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){

		for($x=0;$x<pg_numrows($res);$x++){
			$xservico_realizado   = pg_result($res,$x,servico_realizado);
			$xdescricao = pg_result($res,$x,descricao);
			if(($x % 4)==0){ echo "</tr><tr>";}

			echo "<td align='center'>&nbsp;</td><td align='left'><input type='checkbox' name='servico_realizado_$x' value='$xservico_realizado'"; if(in_array($xservico_realizado,$servico_realizado_peca)){ echo "checked";} echo "> $xdescricao</td>";
		}
	echo "<input type='hidden' name='total_servico_realizado' value='$x'>";
	}

?>
	<td align='center'>
</tr>
</table><P>
<? } ?>





<!-- INICIO DA TABELA  DE IMAGEM -->
<?if((strlen($peca)>0 and ($login_fabrica==3 or $login_fabrica==35 or $login_fabrica == 45 or $login_fabrica==1 or $login_fabrica==5 OR $login_fabrica==11 OR $login_fabrica==51 OR $login_fabrica==50 OR $login_fabrica==46 OR $login_fabrica ==6 OR $login_fabrica ==19)) OR $login_fabrica == 10  ){?>
	<table width='700' align='center' border='0' cellpadding='2' cellspacing='1'  bgcolor='#F0F7FF'>
	<? if(strlen($peca) > 0) { ?>
	<tr  bgcolor='#D9E2EF' style='font-size:10px'>
	<td align='center' colspan='2'><b>Imagem da peça - <?echo $referencia ;?></b></td>
	</tr>
	<? } ?>
	<tr class='Conteudo' bgcolor='#FFFFFF' >
	<td align='center'>
	<?
	$num_fotos = 0 ;
	if(strlen($peca) > 0) {
		if ($login_fabrica == 10 ){
			$sql = "SELECT  peca_item_foto,caminho,caminho_thumb,descricao, ordem
					FROM tbl_peca_item_foto
					WHERE peca = $peca";
			$res = pg_exec ($con,$sql) ;
			$num_fotos = pg_num_rows($res);
			if ($num_fotos > 0){
				echo "<TABLE><TR>";
				for ($i=0; $i<$num_fotos; $i++){
					$caminho        = trim(pg_result($res,$i,caminho));
					$caminho_thum   = trim(pg_result($res,$i,caminho_thumb));
					$foto_descricao = trim(pg_result($res,$i,descricao));
					$foto_id        = trim(pg_result($res,$i,peca_item_foto));
					$ordem          = pg_result($res,$i,ordem);

					$caminho      = str_replace("/www/assist/www/$caminho_dir/media/",'',$caminho);
					$caminho_thum = str_replace("/www/assist/www/$caminho_dir/pequena/",'',$caminho_thum);

					echo "<TD";

					if ($ordem == 1){
						echo " style='border: 4px solid navy; color: navy;'";
					}

					echo ">";

		?>
					<a href="<? echo "$PHP_SELF?ajax=true&imagem=$caminho&peca=$peca&peca_item_foto=$foto_id"; ?>&keepThis=true&TB_iframe=true&height=340&width=420" title="Imagem Ampliada" class="thickbox"><img src='../<?echo $caminho_dir;?>/pequena/<? echo $caminho_thum; ?>' border='0'></a>

		<?
					echo "</TD>";
					//echo " <div class='contenedorfoto'><a href='$caminho' title='$foto_descricao' class='thickbox' rel='gallery-plants'><img src='$caminho_thum' alt='$desc_foto' /><br /></a><a href=\"javascript:if (confirm('Deseja excluir esta foto?')) window.location='?peca=$peca&excluir_foto=$foto_id'\"><img src='imagens/cancel.png' width='12px' alt='Excluir foto' style='margin-right:0;float:right;align:right' /></a></div>";
				}
				echo "</TR></TABLE>";
			}
		}

		if ($num_fotos==0){
			$contador=0;
			if ($dh = opendir("../".$caminho_dir."/pequena/")) {
				while (false !== ($filename = readdir($dh))) {
					if($contador == 1) break;
					$xpeca = $peca.'.';
					if (strpos($filename,$peca) !== false){
						$po = strlen($xpeca);
						if(substr($filename, 0,$po)==$xpeca){
							$contador++;
						?>
							<a href="<? echo "$PHP_SELF?ajax=true&imagem=$filename"; ?>&keepThis=true&TB_iframe=true&height=340&width=420" title="Imagem Ampliada" class="thickbox">
							<img src='../<?echo $caminho_dir;?>/pequena/<?echo $filename; ?>' border='0'>
							<input type='hidden' name='peca_imagem' value='<?echo $filename; ?>'>
							</a>
			<?			}
					}
				}

				if($contador == 0){
					if ($dh = opendir("../".$caminho_dir."/pequena/")) {
						$Xreferencia = str_replace(" ", "_",$referencia);
						while (false !== ($filename = readdir($dh))) {
							if($contador == 1) break;
							if (strpos($filename,$Xreferencia) !== false){

								//$peca_referencia = ntval($peca_referencia);
								$po = strlen($Xreferencia);
								if(substr($filename, 0,$po)==$Xreferencia){
									$contador++;
								?>
									<a href="<? echo "$PHP_SELF?ajax=true&imagem=$filename"; ?>&keepThis=true&TB_iframe=true&height=340&width=420" title="Imagem Ampliada" class="thickbox">
									<img src='../<?echo $caminho_dir;?>/pequena/<?echo $filename; ?>' border='0'>
									<input type='hidden' name='peca_imagem' value='<?echo $filename; ?>'>
									</a>
					<?			}
							}
						}
					}
				}
			}
		}
	}

?>

		</td>
		<td align='center'>
		<p style='font-size:10px'>
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
		if(strlen($peca) > 0 or $login_fabrica ==10){
			echo "<input type='hidden' name='qtde_de_fotos' value='$qtde_imagens'>";

			echo '<B>Selecione a imagem:</B> <input type="file" value="Procurar foto" name="arquivos[]" class="multi {accept:\'jpg|gif|png\', max:'.$qtde_imagens.', STRING: {remove:\'Remover\',selected:\'Selecionado: $file\',denied:\'Tipo de arquivo inválido: $ext!\'}}" />';

			if ($login_fabrica<>10){
				echo '* Caso a peça tenha uma imagem cadastrada, <BR>a imagem será sobreposta';
			}else{
				echo '* Quantidade máxima de fotos: '.$qtde_imagens;
			}
		}
		?>
		</p>
		</td>
		</table>
		<BR>
	<?
	 if($login_fabrica==3 OR $login_fabrica==10 or $login_fabrica==1 or $login_fabrica==35){
	?>
		<table style=' border:#485989 1px solid; background-color: #F0F7FF ' align='center' width='700' border='0'>
		<tr >
		<td bgcolor='#D9E2EF' style='font-size:10px' align='center' colspan='2'>
		<B>Loja Virtual:</B>
		<input type="checkbox" value="ok" name="promocao_site" <?if($promocao_site == "t") echo "CHECKED";?>>
		<?PHP
		if ($login_fabrica == 10 OR $login_fabrica == 3) {
			echo "<b>Clique aqui para inserir na loja virtual.</b>";
		}
		?>
		</td>
		</tr>

		<? if($login_fabrica==3){ //HD 102203 ?>
			<tr >
			<td bgcolor='#D9E2EF' style='font-size:10px' align='center' colspan='2'>
			&nbsp;<B>AT Shop:</B>
			<input type="checkbox" value="ok" name="at_shop" <?if($at_shop == "t") echo "CHECKED";?>>
				<b>Clique aqui para inserir na AT Shop.</b>
			</td>
			</tr>
		<? } ?>

<!-- 	<tr >
		<td bgcolor='#D9E2EF' style='font-size:10px' width='130' height='15'>Qtde mínimo por pedido&nbsp;</td>
		<td ><input NAME="qtde_minima_site" size='10' maxlength='5' value='<?=$qtde_minima_site?>' class='frm'>
		</td>
	</tr>
 -->
	<?if($login_fabrica == 10) {?>
	<tr>
		<td bgcolor='#D9E2EF' style='font-size:10px' align='left' nowrap>Linha&nbsp;<acronym class='ac' title='Linha'>[?]</acronym></td>
		<td align='left'>
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
					$aux_linha = trim(pg_result($res,$x,linha));
					$aux_nome  = trim(pg_result($res,$x,nome));

					echo "<option value='$aux_linha'";
					if ($linha == $aux_linha){
						echo " SELECTED ";
						$mostraMsgLinha = "<br> da LINHA $aux_nome";
					}
					echo ">$aux_nome</option>\n";
				}
				echo "</select>\n&nbsp;";
			}
			?>
		</td>
	</tr>
	<tr>
		<td bgcolor='#D9E2EF' style='font-size:10px' align='left'  nowrap>Família&nbsp;<acronym class='ac' title='Família'>[?]</acronym></td>
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
				$aux_familia = trim(pg_result($res,$i,familia));
				$aux_nome  = trim(pg_result($res,$i,descricao));
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
	<td bgcolor='#D9E2EF' style='font-size:10px' align='left'  nowrap>Marca&nbsp;<acronym class='ac' title='Marca'>[?]</acronym></td>
	<td align='left'><select name="marca" size="1" class="frm">
					<option ></option>
<?


		$sql = "SELECT marca,nome
				FROM tbl_marca
				WHERE fabrica=$login_fabrica
				ORDER BY nome";

		$res = pg_exec ($con,$sql) ;

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
			echo "<option ";
			if ($marca == pg_result ($res,$i,marca) ) echo " selected ";
					echo " value='" . pg_result ($res,$i,marca) . "'>" ;
					echo pg_result ($res,$i,nome) ;
					echo "</option>";
		}
?>
				</select>
		</td>
		</tr>
	<?}?>
	<tr>
		<td bgcolor='#D9E2EF' style='font-size:10px' align='left'  nowrap>Qtde máxima por posto&nbsp;<acronym class='ac' title='Quantidade máxima que pode ser pedido desta peça'>[?]</acronym></td>
		<td align='left'><input NAME="qtde_max_site" size='10' maxlength='5' value='<?=$qtde_max_site?>' class='frm'>
		</td>
	</tr>
	<tr>
		<td bgcolor='#D9E2EF' style='font-size:10px' align='left' >Qtde multipla por peça&nbsp;<acronym class='ac' title='Quantidade de venda por múltiplo. Ex: 3,6,9...'>[?]</acronym></td>
		<td align='left'><input NAME="multiplo_site" size='10' maxlength='5' value='<?=$multiplo_site?>' class='frm'>
		</td>
	</tr>
	<?if($login_fabrica==3){?>
	<tr>
		<td bgcolor='#D9E2EF' style='font-size:10px' align='left' >Qtde Inicial Disponível&nbsp;<acronym class='ac' title='Quantidade incial disponivel para venda desta peça'>[?]</acronym></td>
		<td align='left'><input NAME="qtde_disponivel_inicial_site" size='10' maxlength='5' value='<?=$qtde_disponivel_inicial_site?>' class='frm'>
		</td>
	</tr>
	<tr>
		<td bgcolor='#D9E2EF' style='font-size:10px' align='left' >Data Inicial &nbsp;<acronym class='ac' title='Data incial em que a peça foi colocada em liquidação'>[?]</acronym></td>
		<td align='left'>
			<input type="text" name="data_inicial_liquidacao" id="data_inicial_liquidacao" size="10" maxlength="10" value="<? if (strlen($data_inicial_liquidacao) > 0) echo substr($data_inicial_liquidacao,0,10); else echo "dd/mm/aaaa"; ?>" onClick="javascript: if (this.value == 'dd/mm/aaaa') this.value='';" class="frm">
		</td>
	</tr>
	<?}?>
	<tr>
		<td bgcolor='#D9E2EF' style='font-size:10px' align='left' >Qtde Disponível&nbsp;<acronym class='ac' title='Quantidade disponivel para venda desta peça'>[?]</acronym></td>
		<td align='left'><input NAME="qtde_disponivel_site" size='10' maxlength='5' value='<?=$qtde_disponivel_site?>' class='frm'>
		</td>
	</tr>
	<? if($login_fabrica == 10) { ?>
	<tr>
		<td bgcolor='#D9E2EF' style='font-size:10px' align='left' >Qtde Mínima no estoque&nbsp;<acronym class='ac' title='Quantidade mínima no estoque'>[?]</acronym></td>
		<td align='left'><input NAME="qtde_minima_estoque" size='10' maxlength='5' value='<?=$qtde_minima_estoque?>' class='frm'>
		</td>
	</tr>
	<? } ?>
	<tr>
		<td bgcolor='#D9E2EF' style='font-size:10px' align='left' >Prioridade no Site&nbsp;<acronym class='ac' title='Prioridade de exibição da peça na Loja Virtual. (1,2,3...1000)'>[?]</acronym></td>
		<td align='left'><input NAME="posicao_site" size='3' maxlength='3' value='<?=$posicao_site?>' class='frm'
		<?php
		 if ($login_fabrica == 10){
			echo " onchange='verificaPrioridade(this.value)'>";
			echo "<span id='result'>&nbsp;</span></td>";
		}else{ ?>
			></td>
		<?php }	?>
	</tr>
	<tr>
		<td bgcolor='#D9E2EF' style='font-size:10px' align='left' >Peça em Liquidação&nbsp;<acronym class='ac' title='Peça será exibida no link de LIQUIDAÇÃO'>[?]</acronym></td>
		<td align='left'> <input type="checkbox" value="ok" name="liquidacao" <?if($liquidacao == "t") echo "CHECKED";?>>
		</td>
	</tr>
	<?PHP if ($login_fabrica == 10) {?>
	<tr>
		<td bgcolor='#D9E2EF' style='font-size:10px' align='left' >Não cobrar frete&nbsp;<acronym class='ac' title='Não cobrará o frete desta peça'>[?]</acronym></td>
		<td align='left'> <input type="checkbox" value="ok" name="frete_gratis" <?if($frete_gratis == "t") echo "CHECKED";?>>
		</td>
	</tr>
	<tr>
		<td bgcolor='#D9E2EF' style='font-size:10px' align='left'> Preço da Peça&nbsp;<acronym class='ac' title='Cadastra o preço da peça'>[?]</acronym></td>
		<td align='left'><input NAME="preco_peca" size='10' maxlength='10' value='<?=$preco_peca?>' class='frm'>
	</tr>
	<?PHP }?>
	<tr>
		<td bgcolor='#D9E2EF' style='font-size:10px' align='left' >Preço anterior&nbsp;<acronym class='ac' title='Preço será mostrado abaixo da peça como preço anterior'>[?]</acronym></td>
		<td align='left'><input NAME="preco_anterior" size='10' maxlength='10' value='<?=$preco_anterior?>' class='frm'>
		</td>
	</tr>
	<tr>
		<td bgcolor='#D9E2EF' style='font-size:10px' valign='top' align='left' >
		Informações&nbsp;<acronym class='ac' title='Neste campo pode ser digitadas informações gerais sobre essa peça ou sobre a promção da mesma'>[?]</acronym></td>
		<td align='left'><TEXTAREA NAME="informacoes" ROWS="30" COLS="60" class='frm' id ='informacoes' ><? echo ($informacoes)?></TEXTAREA>

		</td>
	</tr>
	<tr>
		<td bgcolor='#D9E2EF' style='font-size:10px' colspan='2' align='center'><font color='#990000'><b>Esta area é referente a loja virtual</b></font></td>
	</tr>
		</table><p></p>&nbsp;
	<?
	}
}

					?>
<td align='center' class='table_line1' bgcolor='<? echo $cor;?>'>


<!-- ---------------------------------- Botoes de Acao ---------------------- -->
<div id="wrapper">
	<input type='hidden' name='btnacao' value=''>
	<IMG SRC="imagens_admin/btn_gravar.gif" ONCLICK="javascript: if (document.frm_peca.btnacao.value == '' ) { document.frm_peca.btnacao.value='gravar' ; document.frm_peca.submit() } else { alert ('Aguarde submissão') }" ALT="Gravar formulário" border='0' style="cursor:pointer;">
	<IMG SRC="imagens_admin/btn_apagar.gif" ONCLICK="javascript: if (document.frm_peca.btnacao.value == '' ) { document.frm_peca.btnacao.value='deletar' ; document.frm_peca.submit() } else { alert ('Aguarde submissão') }" ALT="Apagar produto" border='0' style="cursor:pointer;">
	<IMG SRC="imagens_admin/btn_limpar.gif" ONCLICK="javascript: if (document.frm_peca.btnacao.value == '' ) { document.frm_peca.btnacao.value='reset' ; window.location='<? echo $PHP_SELF ?>' } else { alert ('Aguarde submissão') }" ALT="Limpar campos" border='0' style="cursor:pointer;">
</div>

<P>
<? if (strlen($peca) > 0) { ?>
<? if (strlen($admin) > 0 and strlen($data_atualizacao) > 0) { ?>
<div id="wrapper">
	ÚLTIMA ATUALIZAÇÃO
</div>

<div id="wrapper">
	<?echo $admin ." - ". $data_atualizacao;?>
</div>
<? } ?>
<? } ?>
</P>
<p>
<div id="wrapper">
			<h3>
				Para pesquisar uma peça, informe parte da descrição ou da referência.
				<br>
				Os campos com esta marcação (*) não poder ser nulos.
			</h3>
</div>



<?
if (strlen($peca) > 0 AND $ip == '201.0.9.216') {
	echo "<a href='preco_cadastro_peca.php?peca=$peca'>CLIQUE AQUI PARA LISTAR A TABELA DE PREÇOS</a>\n<br>\n<br>\n";
}

if (strlen($peca) > 0) {
	// HD 3360


	$sql = "SELECT  DISTINCT
					tbl_produto.referencia,
					tbl_produto.descricao
			FROM    tbl_produto
			JOIN    tbl_lista_basica USING (produto)
			JOIN    tbl_peca         USING (peca)
			JOIN    tbl_linha        USING (linha)
			WHERE   tbl_linha.fabrica     = $login_fabrica
			AND     tbl_lista_basica.peca = $peca
			ORDER BY tbl_produto.referencia;";
	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='450' align='center' border='1' class='conteudo' cellpadding='2' cellspacing='0'>";
		echo "<tr bgcolor='#D9E2EF'>";

		echo "<td align='center' colspan='2'>";
		echo "<b>EQUIPAMENTOS QUE POSSUEM ESTA PEÇA</b>";
		echo "</td>";

		echo "</tr>";
		echo "<tr bgcolor='#D9E2EF'>";

		echo "<td align='center'>";
		echo "<b>REFERÊNCIA</b>";
		echo "</td>";

		echo "<td align='center'>";
		echo "<b>DESCRIÇÃO</b>";
		echo "</td>";

		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$ref = trim(pg_result($res,$i,referencia));
			$des = trim(pg_result($res,$i,descricao));

			echo "<tr bgcolor='#FFFFFF'>";

			echo "<td align='center'>";
			echo "$ref";
			echo "</td>";

			echo "<td align='left'>";
			echo "$des";
			echo "</td>";

			echo "</tr>";
		}

		echo "</table>";
		echo "<p>";
	}
}
?>

<?if ($login_fabrica<>20){?>
<div id="wrapper">
	<a href='peca_consulta.php' target='_blank'>CLIQUE AQUI PARA CONSULTAR TODAS AS PEÇAS, FILTRANDO DE ACORDO COM O TIPO (EX.: DEVOLUÇÃO OBRIGATÓRIA)</a>
	<!-- a href='peca_devolucao_obrigatoria_consulta.php' target='_blank'>CLIQUE AQUI PARA LISTAR TODAS AS PEÇAS COM DEVOLUÇÃO OBRIGATÓRIA</a -->
</div>
<?}?>
<?if ($login_fabrica<>11){ // HD 56689?>
<div id="wrapper">
	<a href='<?echo $PHP_SELF;?>?listartudo=1'>CLIQUE AQUI PARA LISTAR TODAS AS PEÇAS CADASTRADAS</a>
</div>
<?}?>
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
	$sql = "SELECT  tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao ,
					tbl_peca.acessorio,
					tbl_peca.ativo
			FROM    tbl_peca
			WHERE   tbl_peca.fabrica = $login_fabrica
			$sql_ativo
			ORDER BY    tbl_peca.descricao;";
	$res = pg_exec ($con,$sql);

	if($login_fabrica ==14 OR $login_fabrica ==43){
		echo "<div id='wrapper'>";
		echo "<table width='450' align='center' border='0' cellpadding='1' cellspacing='0'>";
		echo "<tr>";
		echo "<td align='left' width='150'><a href='$PHP_SELF?listartudo=1'>TODOS</a></td>";
		echo "<td  align='center' width='150'><a href='$PHP_SELF?listartudo=1&ativo=t'>ATIVO</a></td>";
		echo "<td align='right' width='150'><a href='$PHP_SELF?listartudo=1&ativo=f'>INATIVO</a></td>";
		echo "</tr>";
		echo "</table>";
		echo "</div>";
		echo "<br>";
	}
	if(pg_numrows($res) >0) {
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			if ($i % 20 == 0) {
				if ($i > 0) echo "</table>";
				flush();
				echo "<table width='450' align='center' border='1' class='conteudo' cellpadding='2' cellspacing='0'>";
				echo "<tr bgcolor='#D9E2EF'>";

				echo "<td align='center' width='150'>";
				echo "<b>Referência</b>";
				echo "</td>";

				echo "<td align='center'>";
				echo "<b>Descrição</b>";
				echo "</td>";

				if($login_fabrica == 14){
					echo "<td align='center'>";
					echo "<b>Status</b>";
					echo "</td>";
				}


				if($login_fabrica == 20 ){
					echo "<td align='center'>";
					echo "<b>Tipo</b>";
					echo "</td>";
				}

				echo "</tr>";

			}
			if($login_fabrica == 14 OR $login_fabrica == 43){ // HD 29842
				$ativo         = trim(@pg_result($res,$i,ativo));
				if($ativo=='t') $ativo = "<img src='imagens/status_verde.gif'> Ativo";
				else            $ativo = "<img src='imagens/status_vermelho.gif'> Inativo";
			}

			echo "<tr>";

			echo "<td align='left'>";
			echo pg_result ($res,$i,referencia);
			echo "</td>";

			echo "<td align='left'>";
			echo "<a href='$PHP_SELF?peca=" . pg_result ($res,$i,peca) . "'>";
			echo pg_result ($res,$i,descricao);
			echo "</a>";
			echo "</td>";

			if($login_fabrica == 14 OR $login_fabrica == 43){ // HD 29842

				echo "<td nowrap align='left'>$ativo</td>";
			}
			if($login_fabrica == 20){
			$acessorio = pg_result ($res,$i,acessorio);
			echo "<td align='left'>";
			if($acessorio=='t')     echo "Acessório";
			elseif($acessorio=='f') echo "Peça";
			else                    echo " - ";
			echo "</td>";
			}
			echo "</tr>";
		}
		echo "</table>";
	}else {
		echo "<table width='450' align='center' border='0' cellpadding='1' cellspacing='0'>";
		echo "<tr>";
		echo "<td nowrap>NENHUM RESULTADO ENCONTRADO.</td>";
		echo "</tr>";
		echo "</table>";
	}
}
?>

<!-- fim relatorio -->

<p>



<? include "rodape.php"; ?>

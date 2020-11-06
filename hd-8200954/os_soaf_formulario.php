<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$res = pg_query($con,"SELECT TO_CHAR(current_date,'DD/MM/YYYY')");
$data_abertura_soaf = pg_result($res,0,0);
$msg_erro = array();

//arrays para definição de exibição dos campos, o conteúdo dos arrays provém do campo "tbl_tipo_soaf.descricao"
//os campos que não estiverem presentes, é porque será padrão para todos
$usa_garantia_dana_jacto 		= array('DANA');
$usa_produto_serie      		= array('EATON','DANA','PARKER');
$usa_lote               		= array('EATON');
$usa_detalhe_soaf        		= array('CUMMINS','EATON');
$usa_familia             		= array('PARKER');
$usa_familia_especifico  		= array('CUMMINS');
$usa_aplicacao           		= array('DANA');
$usa_produto_serie_especifico 	= array('EATON','DANA','CUMMINS');
$usa_rastreabilidade_item 		= array('PARKER');

//Recebe OS e SOAF
$os_soaf   = ($_GET['os'])        ? trim($_GET['os']) : trim($_POST['os_soaf']) ;
$tipo_soaf = ($_GET['tipo_soaf']) ? trim($_GET['tipo_soaf']) : trim($_POST['tipo_soaf']);
$soaf      = ($_GET['soaf'])      ? trim($_GET['soaf'])      : trim($_POST['soaf']);

//SE A PESSOA TENTAR ENTRAR NA TELA SEM NENHUM PARAMETRO, IRÁ RETORNAR A PARA 'os_soaf.php'
if (empty($os_soaf) and empty($tipo_soaf) and empty($soaf)){
	header('Location: os_soaf.php');
}

//SQL PARA RECEBER OS DADOS DA OS NECESSÁRIOS NO FORMULÁRIO POREM NÃO SERÃO GRAVADOS EM NENHUM CANTO
//INICIO {
$sql_os = "

	SELECT  tbl_produto.descricao,
			tbl_produto.referencia,
			tbl_familia.descricao,
			tbl_os.serie,
			TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') as data_abertura,
			tbl_os.consumidor_nome,
			tbl_os.consumidor_endereco,
			tbl_os.consumidor_numero,
			tbl_os.consumidor_complemento,
			tbl_os.consumidor_cidade,
			tbl_os.consumidor_estado
			
	FROM tbl_os 
	JOIN tbl_produto on (tbl_os.produto = tbl_produto.produto) 
	JOIN tbl_familia on (tbl_produto.familia = tbl_familia.familia and tbl_familia.fabrica = $login_fabrica)
	WHERE tbl_os.fabrica=$login_fabrica 
	and tbl_os.os=$os_soaf 

";
$res_os = pg_query($con,$sql_os);
$produto_descricao      = pg_result($res_os,0,0);
$produto_referencia     = pg_result($res_os,0,1);
$familia_produto        = pg_result($res_os,0,2);
$serie_produto          = pg_result($res_os,0,3);
$data_abertura          = pg_result($res_os,0,4);
$condumidor_nome        = pg_result($res_os,0,5);
$condumidor_endereco    = pg_result($res_os,0,6);
$condumidor_numero      = pg_result($res_os,0,7);
$condumidor_complemento = pg_result($res_os,0,8);
$consumidor_cidade      = pg_result($res_os,0,9);
$consumidor_estado      = pg_result($res_os,0,10);

//FIM }
 
//SQL PARA VERIFICAR A DESCRICAO DO TIPO DE SOAF CADASTRADO PARA O FORMULARIO
//INICIO {
$sql = "
	SELECT tbl_tipo_soaf.descricao
	from tbl_tipo_soaf 
	where tipo_soaf = $tipo_soaf
";

$res = pg_query($con,$sql);

$tipo_soaf_descricao = (pg_num_rows($res)>0) ? strtoupper(trim(pg_result($res,0,'descricao'))) : "";
//FIM }

//VERIFICAÇÕES E ALTERAÇÃO NO LABEL DA DESCRICAO DE CADA INPUT DE ACORDO COM O TIPO DE SOAF E O BLOQUEIO
//INICIO {

//tbl_os.serie -> série do produto cadastrado no cadastro da OS
if (in_array($tipo_soaf_descricao,$usa_produto_serie)){

	$desc_numero_serie = ($tipo_soaf_descricao == 'EATON')  ? "Número de Série da Maquina" : "$desc_numero_serie" ;
	$desc_numero_serie = ($tipo_soaf_descricao == 'DANA')   ? "Número de Série da Maquina" : "$desc_numero_serie" ;
	$desc_numero_serie = ($tipo_soaf_descricao == 'PARKER') ? "Número de Série do Produto" : "$desc_numero_serie" ;
	$mostra_serie = 't';
	
}

//tbl_soaf.numero_serie_especifico
if (in_array($tipo_soaf_descricao,$usa_produto_serie_especifico)){
	
	$desc_numero_serie_especifico = ($tipo_soaf_descricao == 'EATON')  ? "Número de Série do Cambio" : "$desc_numero_serie_especifico" ;
	$desc_numero_serie_especifico = ($tipo_soaf_descricao == 'DANA')   ? "Número de Série DANA" : "$desc_numero_serie_especifico" ;
	$desc_numero_serie_especifico = ($tipo_soaf_descricao == 'CUMMINS') ? "Número de Série do Motor" : "$desc_numero_serie_especifico" ;
	$mostra_serie_especifico = 't';
	
}


if (in_array($tipo_soaf_descricao,$usa_familia_especifico)){

	$desc_familia = ($tipo_soaf_descricao == 'CUMMINS')  ? "Família do Motor" : "$desc_familia" ;
	$mostra_familia='t';
	
}elseif (in_array($tipo_soaf_descricao,$usa_familia)){

	$desc_familia = "Família do Produto" ;
	$mostra_familia='t';
	
}

//FIM }

$btn_acao = $_POST['btn_acao'];

if ($btn_acao == "cadastrar"){
	
	//VALIDAÇÃO DOS CAMPOS DE DATA
	$data_abertura_soaf		= trim($_POST['data_abertura_soaf']);	
	$data_inicio_garantia 	= trim($_POST['data_inicio_garantia']);
	$data_falha 			= trim($_POST['data_falha']);
	$data_montagem_cliente 	= trim($_POST['data_montagem_cliente']);
	$data_entrega_tecnica   = trim($_POST['data_entrega_tecnica']);
	$data_reclamacao 		= trim($_POST['data_reclamacao']);

	if (strlen($data_abertura_soaf) == 0){
		$msg_erro[] = "Informe a data de abertura" ;
	}
	if (strlen($data_inicio_garantia) == 0) {
		$msg_erro[] = "Informe a data de início da garantia";
	}
	if (strlen($data_falha) == 0){
		$msg_erro[] = "Informe a data da falha";
	}
	if (strlen($data_montagem_cliente) == 0){
		$msg_erro[] = "Informe a data de montagem no cliente";
	}
	if (strlen($data_entrega_tecnica) == 0){
		$msg_erro[] = "Informe data da entrega técnica";
	}
	if (strlen($data_reclamacao) == 0){
		$msg_erro[] = "Informe data da reclamação";
	}
	
	if(!$msg_erro){
		list($das, $mas, $yas) = explode("/", $data_abertura_soaf);
		if(!checkdate($mas,$das,$yas)) 
			$msg_erro[] = "Data de Abertura do SOAF Inválida";
	
		list($dig, $mig, $yig) = explode("/", $data_inicio_garantia);
		if(!checkdate($mig,$dig,$yig)) 
			$msg_erro[] = "Data de Inicio da Garantia Inválida";
	
		list($ddf, $mdf, $ydf) = explode("/", $data_falha);
		if(!checkdate($mdf,$ddf,$ydf)) 
			$msg_erro[] = "Data da Falha Inválida";
	
		list($dmc, $mmc, $ymc) = explode("/", $data_montagem_cliente);
		if(!checkdate($mmc,$dmc,$ymc)) 
			$msg_erro[] = "Data da Montagem do Cliente Inválida";
	
		list($det, $met, $yet) = explode("/", $data_entrega_tecnica);
		if(!checkdate($met,$det,$yet)) 
			$msg_erro[] = "Data da Entrega Tecnica Inválida";

		list($ddr, $mdr, $ydr) = explode("/", $data_reclamacao);
		if(!checkdate($mdr,$ddr,$ydr)) 
			$msg_erro[] = "Data da Reclamação Inválida";
	}

	if(!$msg_erro){
			$x_data_abertura_soaf = $yas.'-'.$mas.'-'.$das;
			$x_data_inicio_garantia = $yig.'-'.$mig.'-'.$dig;
			$x_data_falha = $ydf.'-'.$mdf.'-'.$ddf;
			$x_data_montagem_cliente = $ymc.'-'.$mmc.'-'.$dmc;
			$x_data_entrega_tecnica = $yet.'-'.$met.'-'.$det;
			$x_data_reclamacao = $ydr.'-'.$mdr.'-'.$ddr;
	}
	
	//FIM VALIDAÇÃO DE DATA
	if (in_array($tipo_soaf_descricao,$usa_produto_serie_especifico)){
		
		$numero_serie_especifico = trim($_POST['produto_serie_especifico']);
		if (strlen($numero_serie_especifico) == 0){
			$msg_erro[] = "Informe o $desc_numero_serie_especifico";
		}
		
		if (!$msg_erro){
		
			$ins_serie_especifico_field = "numero_serie_especifico ";
			$ins_serie_especifico_value = "= ' $numero_serie_especifico',";
		
		}
		
	}
	
	//RECEBE DADOS PARA OS TIPOS DE SOAF QUE USAM OS CAMPOS: garantia_dana - garantia_jacto
	if (in_array($tipo_soaf_descricao,$usa_garantia_dana_jacto)){
		
		$garantia_dana  = trim($_POST['garantia_dana']);
		$garantia_jacto = trim($_POST['garantia_jacto']);
		
		if (strlen($garantia_dana) == 0){
			$msg_erro[] = "Informe o número da GARANTIA DANA";
		}
		if (strlen($garantia_jacto) == 0){
			$msg_erro[] = "Informe o número da GARANTIA JACTO";
		}
		
		if (!$msg_erro){
			$ins_garantia_dana_field = "garantia_dana ";
			$ins_garantia_dana_value = "' = $garantia_dana',";
			
			$ins_garantia_jacto_field = "garantia_jacto ";
			$ins_garantia_jacto_value = " = '$garantia_jacto',";
		}
	}
	
	//RECEBE DADOS PARA OS TIPOS DE SOAF QUE USAM OS CAMPOS: produto_familia_especifico
	if (in_array($tipo_soaf_descricao,$usa_familia_especifico)){
		$produto_familia_especifico	   = trim($_POST['produto_familia_especifico']);
		if (strlen($produto_familia_especifico) == 0){
			$msg_erro[] = "Informe a $desc_familia";
		}
		
		if (!$msg_erro){
			$ins_familia_especifico_field = "produto_familia_especifico ";
			$ins_familia_especifico_value = " = '$produto_familia_especifico',";
		}
		
	}elseif (in_array($tipo_soaf_descricao,$usa_familia)){
		$produto_familia	   = trim($_POST['produto_familia']);
		if (strlen($produto_familia) == 0){
			$msg_erro[] = "Informe a $desc_familia";
		}
			
	}
	
	//RECEBE DADOS PARA OS TIPOS DE SOAF QUE USAM OS CAMPOS: aplicacao
	if (in_array($tipo_soaf_descricao,$usa_aplicacao)){
		
		$aplicacao = trim($_POST['aplicacao']);
		
		if (strlen($aplicacao)==0){
			$msg_erro[] = "Informe a aplicação do produto";
		}
		
		if (!$msg_erro){
			$ins_aplicacao_field = "aplicacao ";
			$ins_aplicacao_value = " = '$aplicacao',";
		}
		
	}	
	
	if (in_array($tipo_soaf_descricao,$usa_lote)){
		
		$produto_lote = trim($_POST['produto_lote']);
		
		if (strlen($produto_lote) == 0){
			$msg_erro[] = "Informe o Lote do Produto";
		}
		
		if (!$msg_erro){
			$ins_lote_field = "lote ";
			$ins_lote_value = " = '$produto_lote',";
		}
		
	}
	
	$produto_horas_trabalhadas = trim($_POST['produto_horas_trabalhadas']);
	
	if (strlen($produto_horas_trabalhadas) == 0){
		$msg_erro[] = "Informe as Horas Trabalhadas";
	}
	
	$falha_reclamacao          = trim($_POST['falha_reclamacao']);
	$falha_causa               = trim($_POST['falha_causa']);
	$falha_correcao            = trim($_POST['falha_correcao']);
	
	if (strlen($falha_reclamacao) == 0){
		$msg_erro[] = "Informe a descrição da falha";
	}
	
	if (strlen($falha_causa) == 0){
		$msg_erro[] = "Informe a causa da falha";
	}
	
	if (strlen($falha_correcao) == 0){
		$msg_erro[] = "Informe a correção da falha";
	}
	
	$total_itens			   = trim($_POST['total_itens']);
		
	if (strlen($total_itens) == 0){
		$msg_erro[] = "Informe o valor do total dos itens";
	}

	if (in_array($tipo_soaf_descricao,$usa_detalhe_soaf)){

		$mao_de_obra_horas         = trim($_POST['mao_de_obra_horas']);
		$mao_de_obra_valor         = trim($_POST['mao_de_obra_valor']);
		$mao_de_obra_total 		   = trim($_POST['mao_de_obra_total']);
		$viagem_horas			   = trim($_POST['viagem_horas']);
		$viagem_km                 = trim($_POST['viagem_km']);
		$viagem_mao_de_obra        = trim($_POST['viagem_mao_de_obra']);
		$viagem_valor_km           = trim($_POST['viagem_valor_km']);
		$viagem_outros             = trim($_POST['viagem_outros']);
		$viagem_total              = trim($_POST['viagem_total']);
		$total_mao_de_obra 		   = trim($_POST['total_mao_de_obra']);
		$total_viagem			   = trim($_POST['total_viagem']);
		$total_pecas			   = trim($_POST['total_pecas']);
		
		if ($mao_de_obra_total != $total_mao_de_obra){
			$msg_erro[] = "Valor dos totais de mão de obra são diferentes";
		}
		if ($viagem_total != $total_viagem){
			$msg_erro[] = "Valor dos totais da viagem são diferentes";
		}
		if ($total_itens != $total_pecas){
			$msg_erro[] = "Valor dos totais das peças são diferentes";
		}
		
		//VALIDAÇÃO DOS VALORES DE MÃO DE OBRA
		if (strlen($mao_de_obra_horas) == 0){
			$mao_de_obra_horas = 0;
		}
		if (strlen($mao_de_obra_valor) == 0){
			$mao_de_obra_valor = 0;
		}
		if (strlen($mao_de_obra_total) == 0){
			$mao_de_obra_total = 0;
		}
		
		//VALIDAÇÃO DOS VALORES DOS CUSTOS DA VIAGEM
		if (strlen($viagem_horas) == 0){
			$viagem_horas = 0;
		}
		if (strlen($viagem_km) == 0){
			$viagem_km = 0;
		}
		if(strlen($viagem_mao_de_obra) == 0){
			$viagem_mao_de_obra = 0;
		}
		if (strlen($viagem_valor_km) == 0){
			$viagem_valor_km = 0;
		}
		if (strlen($viagem_total) == 0){
			$viagem_total = 0;
		}
		
		if (strlen($viagem_outros) == 0){
			$viagem_outros = 0;
		}
		
		//VALIDAÇÃO DOS VALORES TOTAIS
		if (strlen($total_mao_de_obra) == 0) {
			$total_mao_de_obra = 0;
		}
		if (strlen($total_viagem) == 0){
			$total_viagem = 0;
		}
		if (strlen($total_pecas) == 0){
			$total_pecas = 0;
		}
		
		if (!$msg_erro){
			
			$ins_mao_de_obra_horas_field = "mao_de_obra_horas ";
			$ins_mao_de_obra_horas_value = " = $mao_de_obra_horas ,";
			
			$ins_mao_de_obra_valor_field = "mao_de_obra_valor ";
			$ins_mao_de_obra_valor_value = " = $mao_de_obra_valor ,";
						
			$ins_viagem_horas_field = "viagem_horas ";
			$ins_viagem_horas_value = " = $viagem_horas ,";
			
			$ins_viagem_km_field = "viagem_km_percorrido ";
			$ins_viagem_km_value = " = $viagem_km ,";
			
			$ins_viagem_mao_de_obra_field = "viagem_valor_mao_de_obra ";
			$ins_viagem_mao_de_obra_value = " = $viagem_mao_de_obra ,";
			
			$ins_viagem_valor_km_field = "viagem_valor_por_km ";
			$ins_viagem_valor_km_value = " = $viagem_valor_km ,";
			
			$ins_viagem_outros_field = "viagem_outros_gastos ";
			$ins_viagem_outros_value = " = $viagem_outros ,";
			
			$ins_total_mao_de_obra_field = "total_soaf_mao_de_obra ";
			$ins_total_mao_de_obra_value = " = $total_mao_de_obra ,";
			
			$ins_total_viagem_field = "total_soaf_viagem ";
			$ins_total_viagem_value = " = $total_viagem ,";
			
			$ins_total_pecas_field = "total_soaf_pecas ";
			$ins_total_pecas_value = " = $total_pecas ,";
			
		}
		
	}else{
		
		$total_itens			   = trim($_POST['total_itens']);
		
		if (strlen($total_itens) == 0){
			$msg_erro[] = "Informe o valor do total dos itens";
		}
		
		$ins_total_pecas_field = "total_soaf_pecas";
		$ins_total_pecas_value = " = $total_itens ,";
		
	}
	
	$total_soaf	= trim($_POST['total_soaf']);
	$obs_laudo  = trim($_POST['obs_laudo']);
	
	if (strlen($total_soaf) == 0){
		$msg_erro[] =  "Informe o valor do total do SOAF";
	}
	if (strlen($obs_laudo) == 0){
		$msg_erro[] = "Informe a observação do laudo";
	}

	if (!$msg_erro){
		
		$res = pg_query($con,'BEGIN TRANSACTION');
		
		$sql = "
			UPDATE tbl_soaf SET
				data_abertura = '$x_data_abertura_soaf',
				data_inicio_garantia = '$x_data_inicio_garantia',
				data_falha 						= '$x_data_falha',
				data_reclamacao 				= '$x_data_reclamacao',
				data_montagem_cliente 			= '$x_data_montagem_cliente',
				data_entrega_tecnica 			= '$x_data_entrega_tecnica',
				status							= 'Em Aprovação',
				horas 							= '$produto_horas_trabalhadas',
				falha_reclamacao 				= '$falha_reclamacao',
				falha_causa 					= '$falha_causa',
				falha_correcao 					= '$falha_correcao',
				observacao 						= '$obs_laudo',
				$ins_aplicacao_field  			$ins_aplicacao_value
				$ins_garantia_dana_field 		$ins_garantia_dana_value
				$ins_garantia_jacto_field  		$ins_garantia_jacto_value
				$ins_serie_especifico_field  	$ins_serie_especifico_value
				$ins_familia_especifico_field  	$ins_familia_especifico_value
				$ins_lote_field  				$ins_lote_value
				$ins_mao_de_obra_horas_field  	$ins_mao_de_obra_horas_value
				$ins_mao_de_obra_valor_field  	$ins_mao_de_obra_valor_value
				$ins_viagem_horas_field  		$ins_viagem_horas_value
				$ins_viagem_km_field  			$ins_viagem_km_value
				$ins_viagem_mao_de_obra_field  	$ins_viagem_mao_de_obra_value
				$ins_viagem_valor_km_field  	$ins_viagem_valor_km_value
				$ins_viagem_outros_field  		$ins_viagem_outros_value
				$ins_total_mao_de_obra_field  	$ins_total_mao_de_obra_value
				$ins_total_viagem_field  		$ins_total_viagem_value
				$ins_total_pecas_field  		$ins_total_pecas_value
				total_soaf_total 				= $total_soaf
				
			WHERE tbl_soaf.soaf=$soaf 
			AND   tbl_soaf.tipo_soaf=$tipo_soaf
		";
		$res = pg_query($con,$sql);
		
		if (pg_last_error($con) != ""){
			$msg_erro[] = pg_last_error($con);
		}
		
	}
	
	$qtde_itens = $_POST['qtde_itens'];
	
	//AQUI COMEÇA OS ITENS
	if (!$msg_erro){
		$n = 1;
		for ($i=0;$i<$qtde_itens;$i++){
			
			$item_referencia 	  = trim($_POST['item_'.$n.'_referencia']);
			if (strlen($item_referencia)==0){
				$msg_erro[] = "Informe a referência do item $n";
			}		
			
			$item_descricao 	  = trim($_POST['item_'.$n.'_descricao']);
			if (strlen($item_descricao)==0){
				$msg_erro[] = "Informe a descrição do item $n";
			}
			
			$item_qtde		 	  = trim($_POST['item_'.$n.'_qtde']);
			if (strlen($item_qtde)==0){
				$msg_erro[] = "Informe a quantidade do item $n";
			}		
			
			if (in_array($tipo_soaf_descricao,$usa_rastreabilidade_item)){
				$item_rastreabilidade = trim($_POST['item_'.$n.'_rastreabilidade']);
				if ($item_rastreabilidade){
					$ins_rastreabilidade_field = 'rastreabilidade,';
					$ins_rastreabilidade_value = "'$item_rastreabilidade' ,";
				}else{
					$ins_rastreabilidade_field = '';
					$ins_rastreabilidade_value = "";
				}
			}
			
			$item_causador = trim($_POST['item_'.$n.'_causador']);
			$item_causador = ($item_causador == 't') ? 'true' : 'false';
					
			$item_valor_unitario  = trim($_POST['item_'.$n.'_valor_unitario']);
			if (strlen($item_valor_unitario)==0){
				$msg_erro[] = "Informe o valor unitário do item $n";
			}
			
			$item_valor_total = trim($_POST['item_'.$n.'_total']);
			if (strlen($item_valor_total)==0){
				$msg_erro[] = "Informe o valor total do item $n";
			}
			
			if (!$msg_erro){
				
				//INSERE OS ITENS
				$sql = "
					INSERT INTO tbl_soaf_item (
						soaf,
						qtde,
						$ins_rastreabilidade_field
						causadora,
						preco_unitario,
						referencia,
						descricao,
						item_valor_total
					)values(
						$soaf,
						$item_qtde,
						$ins_rastreabilidade_value
						$item_causador,
						$item_valor_unitario,
						'$item_referencia',
						'$item_descricao',
						$item_valor_total
					)
				";
				
				$res = pg_query($con,$sql);
				if (pg_last_error($con) != ""){
					$msg_erro[] = pg_last_error($con);
				}
				
			}else{
				break;
			}
			$n++;
		}
		
	}
	
	if($msg_erro) {
		
		$msg_erro = implode('<br>', array_filter($msg_erro));
		$res = pg_query($con,'ROLLBACK TRANSACTION');
		
	}else{
	
		//ANEXAR ARQUIVOS INICIO
		$target_path  = "uploads-soaf/";
		if (!is_dir($target_path)){
			system("mkdir -m 777 uploads-soaf");
		}
		if ( !system("cd $target_path && mkdir -m 777 $soaf") ){
			$allowedExtensions = array('jpeg','png','pdf', 'doc', 'docx', 'xls', 'xlsx', 'PDF' );
			$complete_target_path = $target_path."$soaf/";
			if (basename($_FILES['uploadedfile']['name'])){
				
				$base_name1     = basename($_FILES['uploadedfile']['name']);
				$file_path1    = $complete_target_path . $base_name1; 
				$extension1    = basename($_FILES['uploadedfile']['type']);
				if ( in_array($extension1,$allowedExtensions) ){
					$valid_extension = 't';
				}else{
					$valid_extension = 'f';
				}
				
				if( $_FILES['uploadedfile']['size'] <= $_POST['MAX_FILE_SIZE'] and $valid_extension = 't' and !$msg_erro ) {
					move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $file_path1);
				} else {
					$msg_erro[] = "O arquivo '$base_name1' não pode ser enviado, tente novamene";
				}
				
			}
			
			if (basename($_FILES['uploadedfile2']['name'])){

				$base_name2    = basename($_FILES['uploadedfile2']['name']);
				$file_path2    = $complete_target_path . $base_name2; 
				$extension2    = basename($_FILES['uploadedfile2']['type']);
				if ( in_array($extension2,$allowedExtensions)){
					$valid_extension = 't';
				}else{
					$valid_extension = 'f';
				}

				if( $_FILES['uploadedfile2']['size'] <= $_POST['MAX_FILE_SIZE'] and $valid_extension = 't' and !$msg_erro ) {
					move_uploaded_file($_FILES['uploadedfile2']['tmp_name'], $file_path2);
				} else {
					$msg_erro[] = "O arquivo '$base_name2' não pode ser enviado, tente novamene";
				}

			}
			
			if (basename($_FILES['uploadedfile3']['name'])){

				$base_name3    = basename($_FILES['uploadedfile3']['name']);
				$file_path3    = $complete_target_path . $base_name3; 				
				$extension3    = basename($_FILES['uploadedfile3']['type']);
				if ( in_array($extension3,$allowedExtensions)){
					$valid_extension = 't';
				}else{
					$valid_extension = 'f';
				}
				
				if( $_FILES['uploadedfile3']['size'] <= $_POST['MAX_FILE_SIZE'] and $valid_extension == 't' and !$msg_erro ) {
					move_uploaded_file($_FILES['uploadedfile3']['tmp_name'], $file_path3);
				} else {
					$msg_erro[] = "O arquivo '$base_name3' não pode ser enviado, tente novamene";
				}
				
			}
		
		}
		//ANEXAR FIM
		
		if (!$msg_erro){
			$res = pg_query($con,'COMMIT TRANSACTION');
			header ("Location: os_soaf.php?ok=ok&os=$os_soaf&soaf=$soaf");
		}
		
	}
	
}

$title = "Formulário de SOAF";
include "cabecalho.php";

?>
<!DOCTYPE HTML>
<html>
<head>
	<meta charset="ISO 8859-1">
	<style type="text/css">
		
		.msg_erro{
			background-color:#FF0000;
			font: bold 14px "Arial";
			color:#FFFFFF;
			text-align:center;
		}
		
		.formulario{
			background-color:#D9E2EF;
			font:11px Arial;
			text-align:left;
		}
		
		.sucesso{
			background-color:#008000;
			font: bold 14px "Arial";
			color:#FFFFFF;
			text-align:center;
		}
		
		.titulo_tabela{
			background-color:#596d9b;
			font: bold 14px "Arial" !important;
			color:#FFFFFF;
			text-align:center;
		}
		
		.titulo_coluna{
			background-color:#596d9b;
			font: bold 11px "Arial";
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
			font:bold 11px Arial;
			color: #FFFFFF;
		}
		
		.money{
			text-align:right;
		}
		
		.km{
			text-align:right;
		}
			
	</style>
	<script type="text/javascript" src="js/jquery.js"></script>
	<script type="text/javascript" src="js/jquery.maskedinput.js"></script>
	<script type="text/javascript" src="admin/js/jquery.alphanumeric.js"></script>
	<?php include "javascript_calendario.php";?>
	<script type="text/javascript" src="admin/js/jquery.maskmoney.js"></script>
	
	 <script type="text/javascript" language="javascript">
		$(function(){
			
			$( "#data_inicio_garantia" ).datePicker({startDate : "01/01/2000"});
			$( "#data_inicio_garantia" ).maskedinput("99/99/9999");
			
			$( "#data_aprovacao" ).datePicker({startDate : "01/01/2000"});
			$( "#data_aprovacao" ).maskedinput("99/99/9999");
			
			$( "#data_reprovacao" ).datePicker({startDate : "01/01/2000"});
			$( "#data_reprovacao" ).maskedinput("99/99/9999");
			
			$( "#data_montagem_cliente" ).datePicker({startDate : "01/01/2000"});
			$( "#data_montagem_cliente" ).maskedinput("99/99/9999");
			
			$( "#data_entrega_tecnica" ).datePicker({startDate : "01/01/2000"});
			$( "#data_entrega_tecnica" ).maskedinput("99/99/9999");
			
			$( "#data_reclamacao" ).datePicker({startDate : "01/01/2000"});
			$( "#data_reclamacao" ).maskedinput("99/99/9999");
			
			$(".money").maskMoney({symbol:"", decimal:".", thousands:'', precision:2, maxlength: 10});			
			$(".km").maskMoney({symbol:"", decimal:".", thousands:'', precision:3, maxlength: 10});			
			
		});
		
		
		function removeTableRow(botao){
			$(botao).parent().parent().remove();
			
			qtde_itens =  $('#qtde_itens').val();
			qtde_itens--;
			$('#qtde_itens').val(qtde_itens);
		}
		
		
		//FUNCAO PARA ADICIONAR NOVA LINHA NOS ITENS DO SOAF
		function adicionaLinha(linha){
			
			
			qtde_itens =  $('#qtde_itens').val();
			tbl = document.getElementById("table_itens");
	 		qtde_itens ++;
			var novaLinha = tbl.insertRow(-1);
			
			var novaCelula;
			if(qtde_itens%2==0) cl = "#F7F5F0";
			else cl = "#F1F4FA";
			
			cell = 0;
			
			novaCelula = novaLinha.insertCell(cell);
			novaCelula.align = "center";
			novaCelula.style.backgroundColor = cl
			novaCelula.innerHTML = qtde_itens;
			
			cell ++;
			
			novaCelula = novaLinha.insertCell(cell);
			novaCelula.align = "center";
			novaCelula.style.backgroundColor = cl
			novaCelula.innerHTML = "<input type='text' class='frm' size='15' name='item_"+qtde_itens+"_referencia' id='item_"+qtde_itens+"_referencia' />";
			
			cell ++;
			novaCelula = novaLinha.insertCell(cell);
			novaCelula.align = "center";
			novaCelula.style.backgroundColor = cl;
			novaCelula.innerHTML = "<input type='text' class='frm' id='item_"+qtde_itens+"_descricao' name='item_"+qtde_itens+"_descricao' size='40' />";
			
			cell++;
			novaCelula = novaLinha.insertCell(cell);
			novaCelula.align = "center";
			novaCelula.style.backgroundColor = cl;
			novaCelula.innerHTML = "<input type='text' class='frm' id='item_"+qtde_itens+"_qtde' name='item_"+qtde_itens+"_qtde' size='5' />";
			
			<?if (in_array($tipo_soaf_descricao,$usa_rastreabilidade_item)){?>
			cell++;
			novaCelula = novaLinha.insertCell(cell);
			novaCelula.align = "center";
			novaCelula.style.backgroundColor = cl;
			novaCelula.innerHTML = "<input type='text' class='frm' id='item_"+qtde_itens+"_rastreabilidade' name='item_"+qtde_itens+"_rastreabilidade' size='12' maxlength='20' />";
			<?}?>
			
			cell++;
			novaCelula = novaLinha.insertCell(cell);
			novaCelula.align = "center";
			novaCelula.style.backgroundColor = cl;
			novaCelula.innerHTML = "<input type='checkbox' class='frm' id='item_"+qtde_itens+"_causador' name='item_"+qtde_itens+"_causador' value='t' />";
			
			cell++;
			novaCelula = novaLinha.insertCell(cell);
			novaCelula.align = "center";
			novaCelula.style.backgroundColor = cl;
			novaCelula.innerHTML = "<input type='text' id='item_"+qtde_itens+"_valor_unitario' name='item_"+qtde_itens+"_valor_unitario' size='10' class='frm money' maxlength='10' />";
			
			cell++;
			novaCelula = novaLinha.insertCell(cell);
			novaCelula.align = "center";
			novaCelula.style.backgroundColor = cl;
			novaCelula.innerHTML = "<input type='text' id='item_"+qtde_itens+"_total' name='item_"+qtde_itens+"_total' size='10' class='money' maxlength='10' />";
			
			cell++;
			novaCelula = novaLinha.insertCell(cell);
			novaCelula.align = "center";
			novaCelula.style.backgroundColor = cl;
			novaCelula.innerHTML = "<input type='button' id='item_"+qtde_itens+"_excluir' name='item_"+qtde_itens+"_excluir' value='Excluir' onclick=\"removeTableRow(this)\" />";
			
			$("#item_"+qtde_itens+"_total").maskMoney({symbol:"", decimal:".", thousands:'', precision:2, maxlength: 15});			
			$("#item_"+qtde_itens+"_valor_unitario").maskMoney({symbol:"", decimal:".", thousands:'', precision:2, maxlength: 15});			
			
			
			$('#qtde_itens').val(qtde_itens);
			
		}
		
		
	</script>
</head>
<body>
<?if ($msg_erro){?>
<table class="msg_erro" align="center" width="700px">
	<tr>
		<td>
			<?=$msg_erro?>
		</td>
	</tr>
</table>
<?}?>
<form action="<?=$PHP_SELF."?". $_SERVER['QUERY_STRING']?>" method="post" enctype="multipart/form-data" name="frm_cadastro">
	<table class="formulario" width="700px" align="center" cellpadding="0" cellspacing="0">
	
		<tr class="titulo_tabela">
			<td> Solicitação ON-Line de Análise do Fornecedor - SOAF - JACTO X <?=$tipo_soaf_descricao?></td>
		</tr>
	
		<tr>
			<td>&nbsp;</td>
		</tr>
		
		<!-- INFORMAÇÕES BÁSICAS -->
		<tr>
			<td>
				<table width="600px" align="center">
					<tr class="subtitulo">
						<td colspan="2">Informações básicas</td>
					</tr>
					
					<tr>
						<td width="30%">Data de Abertura - SOAF</td>
						<td>Data de Início da Garantia</td>
					</tr>
					<tr>
						<td>
							<input type="text" name="data_abertura_soaf"   id="data_abertura_soaf" value="<?=$data_abertura_soaf?>" class="frm" readonly='readonly' />
						</td>
						<td>
							<input type="text" name="data_inicio_garantia" id="data_inicio_garantia" value="<?=$data_inicio_garantia?>"  class="frm" />
						</td>
					</tr>
					
					<tr>
						<td colspan="2">Data da Falha</td>
					</tr>
					<tr>
						<td colspan="2">
							<input type="text" name="data_falha" id="data_falha" value="<?=$data_abertura?>" class="frm" readonly='readonly' />
						</td>
					</tr>
					<?php if (in_array($tipo_soaf_descricao,$usa_garantia_dana_jacto)){?>	
					<tr>
						<td>Garantia Dana N°</td>
						<td>Garantia Jacto N°</td>
					</tr>
					<tr>
						<td>
							<input type="text" name="garantia_dana" id="garantia_dana" value="<?=$garantia_dana?>" class="frm" />
						</td>
						<td>
							<input type="text" name="garantia_jacto" id="garantia_jacto" value="<?=$garantia_jacto?>" class="frm" />
						</td>
					</tr>
					<?php }?>
				</table>
			</td>
		</tr>
		
		<tr>
			<td>&nbsp;</td>
		</tr>
		
		<!-- REGISTRO DO EQUIPAMENTO -->
		<tr>
			<td>
				<table width="600px" align="center">
					
					<tr class="subtitulo" >
						<td colspan="2">Registro do Equipamento</td>
					</tr>
					
					<tr>
						<td width="30%">Marca</td>
						<td>Modelo</td>
					</tr>
					
					<tr>
						<td>
							<input type="text" name="produto_marca" id="produto_marca" value="<?=$familia_produto?>" class='frm' readonly='readonly' />
						</td>
						<td>
							<input type="text" name="produto_modelo" id="produto_modelo"value="<?=$produto_referencia.'-'.$produto_descricao?>" class='frm' readonly='readonly' style="width:370px" />
						</td>
					</tr>
										
					<tr>
						<?if ($mostra_serie == 't'){?>
						<td>
							<?php echo $desc_numero_serie; ?>
						</td>
						<?}
						
						if ($mostra_serie_especifico == 't'){
						?>
						<td>
							<?echo $desc_numero_serie_especifico?>
						</td>
						<?}?>
						
					</tr>
					
					<tr>
						<?if ($mostra_serie == 't'){?>
							<td>
								<input type="text" name="produto_serie" id="produto_serie" value="<?=$serie_produto?>" class='frm' readonly='readonly' />
							</td>
						<?
						}
						
						if ($mostra_serie_especifico=='t'){
						?>
						<td>
							<input type="text" name="produto_serie_especifico" id="produto_serie_especifico" value="<?=$produto_serie_especifico?>" class='frm' />
						</td>
						<?
						}
						?>
						
					</tr>
					<?
					if ($mostra_familia=='t'){
					?>
					<tr>
						<td>
							<?php echo $desc_familia; ?>
						</td>
					</tr>
					
					<tr>
						<td>
							<?if (in_array($tipo_soaf_descricao,$usa_familia_especifico)){?>
								<input type="text" name="produto_familia_especifico" id="produto_familia_especifico" value="<?=$familia_produto_especifico?>" class='frm' />
							<?}else{?>
								<input type="text" name="produto_familia" id="produto_familia" value="<?=$familia_produto?>" class='frm' />
							<?}?>
						</td>
					</tr>
					<?}?>
					<tr>
						<td>Horas</td>
						<?php if (in_array($tipo_soaf_descricao,$usa_lote)){ ?>
							<td>Lote</td>
						<?php } ?>
					</tr>
					
					<tr>
						<td>
							<input type="text" name="produto_horas_trabalhadas" id="produto_horas_trabalhadas" value="<?=$produto_horas_trabalhadas?>" maxlength='10' class='frm money' />
						</td>
						<?php if ($tipo_soaf_descricao == 'EATON'){ ?>
						<td>
							<input type="text" name="produto_lote" id="produto_lote" value="<?=$produto_lote?>" class='frm' />
						</td>
						<?php } ?>
					</tr>
					
					<?if (in_array($tipo_soaf_descricao,$usa_aplicacao)){?>
					<tr>
						<td colspan="2">Aplicação</td>
					</tr>
					<tr>
						<td colspan="2">
							<input type="text" name="produto_aplicacao" id="produto_aplicacao" value="<?=$produto_aplicacao?>" class='frm' />
						</td>
					</tr>					
					<?}?>
					
				</table>
			</td>
		</tr>
		
		<tr>
			<td>&nbsp;</td>
		</tr>
		
		<!-- REGISTRO DO CLIENTE -->
		<tr>
			<td>
				<table width="600px" align="center" >
					<tr class="subtitulo">
						<td colspan="2">Registro do Cliente</td>
					</tr>
					<tr>
						<td colspan="2">Nome</td>
					</tr>
					<tr>
						<td colspan="2">
							<input type="text" name="cliente_nome" id="cliente_nome" style="width:550px" value="<?=$condumidor_nome?>" class='frm' readonly='readonly' />
						</td>
					</tr>
					<tr>
						<td colspan="2">Endereço</td>
					</tr>
					<tr>
						<td colspan="2">
							<input type="text" name="cliente_endereco" id="cliente_endereco" style="width:550px" value="<?=$condumidor_endereco.' - n° '.$condumidor_numero.' - '.$condumidor_complemento?>" class='frm' readonly='readonly' />
						</td>
					</tr>
					<tr>
						<td width="30%">Cidade</td>
						<td>Estado</td>
					</tr>
					<tr>
						<td>
							<input type="text" name="cliente_cidade" id="cliente_cidade" value="<?=$consumidor_cidade?>" class='frm' readonly='readonly' />
						</td>
						<td>
							<input type="text" name="cliente_estado" id="cliente_estado" size='2' value="<?=$consumidor_estado?>" class='frm' readonly='readonly' />
						</td>
					</tr>
					
				</table>
			</td>
		</tr>
		
		<tr>
			<td>&nbsp;</td>
		</tr>
		
		<!-- REGISTRO DA FALHA -->
		<tr>
			<td>
				<table width="600px" align="center">
					
					<tr class="subtitulo">
						<td colspan="2">Registro da Falha</td>
					</tr>
					
					<tr>
						<td colspan="2">Data da Montagem Cliente</td>
						
					</tr>
					
					<tr>
						<td colspan="2">
							<input type="text" name="data_montagem_cliente" id="data_montagem_cliente" value="<?=$data_montagem_cliente?>" class='frm' />
						</td>
					</tr>
					
					<tr>
						<td width="30%">Data da Entrega Técnica</td>
						<td>Data da Reclamação</td>
					</tr>
					
					<tr>
						<td>
							<input type="text" name="data_entrega_tecnica" id="data_entrega_tecnica" value="<?=$data_entrega_tecnica?>" class='frm' />
						</td>
						
						<td>
							<input type="text" name="data_reclamacao" id="data_reclamacao" value="<?=$data_reclamacao?>" class='frm' />
						</td>
					</tr>
					
					<tr>
						<td colspan="2">Descrição da Falha</td>
					</tr>
					<tr>
						<td colspan="2">
							<textarea name="falha_reclamacao" id="falha_reclamacao" cols="80" rows="5" class="frm"><?=$falha_reclamacao?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan="2">Causa da Falha</td>
					</tr>
					<tr>
						<td colspan="2">
							<textarea name="falha_causa" id="falha_causa" cols="80" rows="5" class="frm"><?=$falha_causa?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan="2">Correção da Falha</td>
					</tr>
					<tr>
						<td colspan="2">
							<textarea name="falha_correcao" id="falha_correcao" cols="80" rows="5" class="frm"><?=$falha_correcao?></textarea>
						</td>
					</tr>
					
				</table>
			</td>
		</tr>
				
		<tr>
			<td>&nbsp;</td>
		</tr>
		
		<tr>
			<td>
				<table width="600px" align="center">
					
					<tr class="subtitulo">
						<td>Anexar fotos ou documentos</td>
					</tr>
					
					<tr>
						<td>
							<input type="hidden" name="MAX_FILE_SIZE" value="3000000" />
							Escolha um arquivo para anexar: <input name="uploadedfile" type="file" class='frm'  /><br>
							Escolha um arquivo para anexar: <input name="uploadedfile2" type="file" class='frm' /><br>
							Escolha um arquivo para anexar: <input name="uploadedfile3" type="file" class='frm' />
						</td>
					</tr>
					
				</table>
			</td>
		</tr>
		
		<tr>
			<td>&nbsp;</td>
		</tr>
		
		<tr>
			<td>
				<table width="600px" align="center">
					
					<tr class="subtitulo">
						<td>OBS/Laudo do Fornecedor</td>
					</tr>
					
					<tr>
						<td>
							<textarea name="obs_laudo" id="obs_laudo" cols="80" rows="5" class="frm"><?=$obs_laudo?></textarea>
						</td>
					</tr>
					
				</table>
			</td>
		</tr>
		
		<tr>
			<td>&nbsp;</td>
		</tr>
		
	</table>
	
	<table width="600px" align="center">
		
		<tr class="subtitulo">
			<td>Itens</td>
		</tr>
		
		<tr>
			<td>
				<table class="tabela" name="table_itens" id="table_itens" width="100%" align="center" cellspacing="2" cellpadding="0">
					<tr class="titulo_coluna">
						<th>Item</th>
						<th>Referência</th>
						<th>Descrição</th>
						<th>Qtde.</th>
						<?
						if (in_array($tipo_soaf_descricao,$usa_rastreabilidade_item)){
						?>
						<th>Rastreabilidade</th>
						<?
						}
						?>
						<th>Item Causador</th>
						<th>Valor Unitário</th>
						<th>Total</th>
						<th>Ação</th>
					</tr>
					<?php
					
					$sql = "
						SELECT  tbl_peca.referencia,
								tbl_peca.descricao, 
								tbl_os_item.qtde 
						
						from tbl_os_item 
						
						JOIN tbl_os_produto on (tbl_os_item.os_produto = tbl_os_produto.os_produto) 
						JOIN tbl_os on (tbl_os_produto.os = tbl_os.os and tbl_os.fabrica = $login_fabrica) 
						JOIN tbl_soaf on (tbl_os_item.soaf = tbl_soaf.soaf) 
						JOIN tbl_tipo_soaf on (tbl_soaf.tipo_soaf = tbl_tipo_soaf.tipo_soaf) 
						JOIN tbl_peca on (tbl_os_item.peca = tbl_peca.peca and tbl_peca.fabrica=$login_fabrica) 
						
						WHERE tbl_os.fabrica=$login_fabrica 
						AND tbl_os.os=$os_soaf 
						AND tbl_soaf.tipo_soaf=$tipo_soaf 
						
					";
					$res = pg_query($con,$sql);
					
					$qtde_pecas = (pg_num_rows($res)>0) ? pg_num_rows($res) : 1 ;
					
					$qtde_itens = ($_POST['qtde_itens']) ? $qtde_itens : $qtde_pecas ; 
					$n = 0;
					if (pg_num_rows($res)>0){
						for ($i = 0; $i < $qtde_itens; $i++ ){
						
							$n += 1;
							$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";
							
							if (!$_POST['qtde_itens']){
							
								$peca_referencia = pg_result($res,$i,'referencia');
								$peca_descricao  = pg_result($res,$i,'descricao');
								$peca_qtde       = pg_result($res,$i,'qtde');
							
							}else{
								
								$peca_referencia = $_POST['item_'.$n.'_referencia'];
								$peca_descricao  = $_POST['item_'.$n.'_descricao'];
								$peca_qtde       = $_POST['item_'.$n.'_qtde'];
								$item_rastreabilidade = $_POST['item_'.$n.'_rastreabilidade'];
								$item_valor_unitario  = $_POST['item_'.$n.'_valor_unitario'];
								$item_total           = $_POST['item_'.$n.'_total'];
							}
							
						?>
						<tr bgcolor="<?php echo $cor?>">
							
							<td align="center"><?=$n?></td>
							
							<td align="center">
								<input type="text" name="item_<?=$n?>_referencia" id="item_<?=$n?>_referencia" class="frm" value="<?=$peca_referencia?>" size="15" />
							</td>
							
							<td align="center">
								<input type="text" name="item_<?=$n?>_descricao" id="item_<?=$n?>_descricao" class="frm" value="<?=$peca_descricao?>" size="40" />
							</td>
							
							<td align="center">
								<input type="text" name="item_<?=$n?>_qtde" id="item_<?=$n?>_qtde" class="frm" size="5" value="<?=$peca_qtde?>" />
							</td>
							<?
							if (in_array($tipo_soaf_descricao,$usa_rastreabilidade_item)){
							?>
								<td align="center">
									<input type="text" name="item_<?=$n?>_rastreabilidade" id="item_<?=$n?>_rastreabilidade" value="<?=$item_rastreabilidade?>" class="frm" maxlength="20" size="12" />
								</td>
							<?
							}
							?>
							<td align="center">
								<?
								$checked = ($_POST['item_'.$n.'_causador'] and $_POST['item_'.$n.'_causador'] == 't' ) ? "CHECKED" : "";
								?>
								<input type="checkbox" name="item_<?=$n?>_causador" id="item_<?=$n?>_causador" value='t' class="frm" <?echo $checked?> />
							</td>
							
							<td align="center">
								<input type="text" name="item_<?=$n?>_valor_unitario" id="item_<?=$n?>_valor_unitario" value="<?=$item_valor_unitario?>" class="frm money" size="10" maxlength='10' />
							</td>
							
							<td align="center">
								<input type="text" name="item_<?=$n?>_total" id="item_<?=$n?>_total" class="frm money" value="<?=$item_total?>" size="10" maxlength='10'  />
							</td>
							<td>
								<input type="button" value="Excluir"  name="item_<?=$n?>_excluir" id="item_<?=$n?>_excluir"  onclick="removeTableRow(this)"  />
							</td>
						</tr>
						<?
						}
					}
					?>
					
					<input type="hidden" name="qtde_itens" id="qtde_itens" value="<?=$qtde_itens?>"  />
				</table>
				
				<table  class="tabela" width="100%" align="center" cellspacing="2" cellpadding="0">
					<tr class="titulo_coluna">
						<td align="left" style="width:90%">TOTAL</td>
						<td align="right">
							<input type="text" name="total_itens" id="total_itens" value="<?=$total_itens?>" class='frm money' size="10" maxlength='10' />
						</td>
					</tr>
				</table>
			</td>
			
		</tr>
		
		<tr>
			<td align="center">
				<input type="button" value="Adicionar novo Item" style="width:250px" onclick="adicionaLinha()" />
			</td>
		</tr>
	</table>
	
	<table class="formulario" width="700px" align="center" cellpadding="0" cellspacing="0" >	
		
		<tr>
			<td> &nbsp; </td>
		</tr>
		
		<?php
		if (in_array($tipo_soaf_descricao,$usa_detalhe_soaf)){
		?>
		<tr>
			<td>
				<table width="600px" align="center">
					
					<tr class="subtitulo">
						<td colspan="2" >Mão de Obra</td>
					</tr>
					
					<tr>
						<td width="30%">Horas de Mão de Obra</td>
						<td>Valor de Mão de Obra</td>
					</tr>
					
					<tr>
						<td>
							<input type="text" name="mao_de_obra_horas" id="mao_de_obra_horas" value="<?=$mao_de_obra_horas?>" class='money frm' maxlength='10'  />
						</td>
						<td>
							<input type="text" name="mao_de_obra_valor" id="mao_de_obra_valor" value="<?=$mao_de_obra_valor?>" class='money frm' maxlength='10'  />
						</td>
					</tr>
					
					<tr>
						<td>Total de Mão de Obra</td>
					</tr>
					<tr>
						<td>
							<input type="text" name="mao_de_obra_total" id="mao_de_obra_total" value="<?=$mao_de_obra_total?>" class='money frm' maxlength='10'  />
						</td>
					</tr>
					
				</table>
			</td>
		</tr>
		
		<tr>
			<td>&nbsp;</td>
		</tr>
		
		<tr>
			<td>
				<table width="600px" align="center">
					
					<tr class="subtitulo">
						<td colspan="2">Viagem</td>
					</tr>
					
					<tr>
						<td width="30%">Horas de Viagem</td>
						<td>KM Percorrido</td>
					</tr>
					
					<tr>
						<td>
							<input type="text" name="viagem_horas" id="viagem_horas" value="<?=$viagem_horas?>" class="frm money" maxlength='10'  />
						</td>
						
						<td>
							<input type="text" name="viagem_km" id="viagem_km" value="<?=$viagem_km?>" class="frm km" maxlength='10'  />
						</td>
					</tr>
					
					<tr>
						<td>Valor de Mão de Obra</td>
						<td>Valor R$/KM</td>
					</tr>
					
					<tr>
						<td>
							<input type="text" name="viagem_mao_de_obra" id="viagem_mao_de_obra" value="<?=$viagem_mao_de_obra?>" class="frm money" />
						</td>
						<td>
							<input type="text" name="viagem_valor_km" id="viagem_valor_km" value="<?=$viagem_valor_km?>" class="frm money" />
						</td>
					</tr>
					
					<tr>
						<td>Outros Gastos</td>
						<td>Total da Viagem</td>
					</tr>
					
					<tr>
						<td>
							<input type="text" name="viagem_outros" id="viagem_outros" value="<?=$viagem_outros?>" class="frm money" />
						</td>
						<td>
							<input type="text" name="viagem_total" id="viagem_total" value="<?=$viagem_total?>" class="frm money" />
						</td>
					</tr>
					
				</table>
			</td>
		</tr>
		
		<tr>
			<td>&nbsp;</td>
		</tr>
		
		<?php 
		}
		?>
		
		<tr>
			<td>
				<table width="600px" align="center">
					
					<tr class="subtitulo" colspan="2">
						<td colspan="2">Total da SOAF</td>
					</tr>
					<?php
					if (in_array($tipo_soaf_descricao,$usa_detalhe_soaf)){
					?>
					<tr>
						<td width="30%">Total Mão de Obra</td>
						<td>Total Viagem</td>
					</tr>
					
					<tr>
						<td>
							<input type="text" name="total_mao_de_obra" id="total_mao_de_obra" value="<?=$total_mao_de_obra?>" class="frm money" />
						</td>
						<td>
							<input type="text" name="total_viagem" id="total_viagem" value="<?=$total_viagem?>" class="frm money" />
						</td>
					</tr>
					<?
					}
					?>
					<tr>
						<?php
						if (in_array($tipo_soaf_descricao,$usa_detalhe_soaf)){
						?>
						<td>Total de Peças</td>
						<?}?>
						<td>Total </td>
					</tr>
					
					<tr>
						<?php
						if (in_array($tipo_soaf_descricao,$usa_detalhe_soaf)){
						?>
						<td>
							<input type="text" name="total_pecas" id="total_pecas" value="<?=$total_pecas?>" class="frm money" />
						</td>
						<?}?>
						<td>
							<input type="text" name="total_soaf" id="total_soaf" value="<?=$total_soaf?>" class="frm money" />
						</td>
					</tr>
					
				</table>
			</td>
		</tr>
		
		<tr>
			<td>&nbsp;</td>
		</tr>
		
		<tr>
			<td align="center">
				<input type="hidden" name="os_soaf" value="<?=$os_soaf?>" />
				<input type="hidden" name="btn_acao" value="" />
				<input type="hidden" name="tipo_soaf" value="<?=$tipo_soaf?>" />
				<input type="hidden" name="soaf" value="<?=$soaf?>" />
				<input type="button" value="Gravar" onclick="javascript: if (document.frm_cadastro.btn_acao.value == '' ) {document.frm_cadastro.btn_acao.value='cadastrar' ; document.frm_cadastro.submit() } else { alert ('Aguarde submissão') }" />
			</td>
		</tr>
		
		<tr>
			<td>&nbsp;</td>
		</tr>		
	
	</table>
</form>
</body>
</html>




<?php
include_once 'rodape.php';
?>

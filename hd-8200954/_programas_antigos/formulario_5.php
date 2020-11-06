<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

//$cnpj = trim($_GET['cnpj']);
if(strlen($cnpj) > 0){
	
	$sql = "SELECT nome, cnpj, endereco, email FROM tbl_posto WHERE cnpj = '$cnpj'";
	$res = pg_exec($con,$sql);

	echo pg_result($res,0,cnpj);
	echo "<br>";


	
	exit;
}



$msg_erro="";
if(strlen($_POST['Enviar'])>0){

//Formulario 1
	// #################### PERGUNTA 1 ##################### //
	if(strlen($_POST['perg_1'])>0)       $perg_1        =	$_POST['perg_1']; //else//		$msg_erro	=	" É necessário preecher a pergunta 1";
	if(strlen($_POST['entretanto_1'])>0) $entretanto_1  =	$_POST['entretanto_1'];
	if(strlen($_POST['obs_1'])>0)        $obs_1         =	$_POST['obs_1'];

	// #################### PERGUNTA 2 ##################### //
	if(strlen($_POST['perg_2'])>0)           $perg_2        =	$_POST['perg_2']; //else		$msg_erro	.=	" <br>É necessário preecher a pergunta 2";
	if(strlen($_POST['entretanto_2'])>0)     $entretanto_2  =	$_POST['entretanto_2'];
	if(strlen($_POST['obs_2'])>0)            $obs_2         =	$_POST['obs_2'];

	// #################### PERGUNTA 3 ##################### //
	if(strlen($_POST['arno'])>0){          $arno          = $_POST['arno']; $arno_check	= "checked";}
	if(strlen($_POST['walita'])>0){        $walita        = $_POST['walita']; $walita_check	= "checked";}
	if(strlen($_POST['faet'])>0){          $faet          =	$_POST['faet']; $faet_check	= "checked";}
	if(strlen($_POST['blackedecker'])>0){  $blackedecker  =	$_POST['blackedecker']; $blackedecker_check	= "checked";}
	if(strlen($_POST['bosch'])>0){         $bosch         =	$_POST['bosch']; $bosch_check	= "checked";}
	if(strlen($_POST['electrolux'])>0){    $electrolux    =	$_POST['electrolux']; $electrolux_check	= "checked";}
	if(strlen($_POST['philco'])>0){        $philco        =	$_POST['philco']; $philco_check	= "checked";}
	if(strlen($_POST['gradiente'])>0){     $gradiente     =	$_POST['gradiente']; $gradiente_check	= "checked";}
	if(strlen($_POST['philips'])>0){       $philips       =	$_POST['philips']; $philips_check	= "checked"; }
	if(strlen($_POST['toshiba'])>0){       $toshiba       =	$_POST['toshiba']; $toshiba_check	= "checked";}
	if(strlen($_POST['cce'])>0){           $cce           =	$_POST['cce']; $cce_check	= "checked";}
	if(strlen($_POST['sony'])>0){          $sony          =	$_POST['sony']; $sony_check	= "checked";}
	if(strlen($_POST['obs_3'])>0)          $obs_3         =	$_POST['obs_3'];

	// #################### PERGUNTA 4 ##################### //
	if(strlen($_POST['perg_4'])>0)        $perg_4       =	$_POST['perg_4'];//else $msg_erro	.=	" <br>É necessário preecher a pergunta 4";
	if(strlen($_POST['entretanto_4'])>0)  $entretanto_4 =	$_POST['entretanto_4'];
	if(strlen($_POST['obs_4'])>0)         $obs_4        =	$_POST['obs_4'];

	// #################### PERGUNTA 5 ##################### //
	if(strlen($_POST['perg_5'])>0)        $perg_5       =	$_POST['perg_5'];// else $msg_erro	.=	" <br>É necessário preecher a pergunta 5";
	if(strlen($_POST['entretanto_5'])>0)  $entretanto_5 =	$_POST['entretanto_5'];
	if(strlen($_POST['obs_5'])>0)         $obs_5        =	$_POST['obs_5'];

	// #################### PERGUNTA 6 ##################### //
	if(strlen($_POST['eletrodomesticos'])>0){
		$eletrodomesticos	=	$_POST['eletrodomesticos'];
		$eletrodomesticos_check	= "checked";
	}

	if(strlen($_POST['eletroeletronicos'])>0){
		$eletroeletronicos	=	$_POST['eletroeletronicos'];
		$eletroeletronicos_check	= "checked";
	}

	if(strlen($_POST['refrigeracao'])>0){
		$refrigeracao	=	$_POST['refrigeracao'];
		$refrigeracao_check	= "checked";
	}

	if(strlen($_POST['industrial'])>0){
		$industrial	=	$_POST['industrial'];
		$industrial_check	= "checked";
	}

	if(strlen($_POST['outros'])>0){
		$outros	=	$_POST['outros'];
		$outros_check	= "checked";
	}

	if(strlen($_POST['outros_6'])>0){
		$outros_6	=	$_POST['outros_6'];
	}

	if(($eletrodomesticos=="null") and ($eletroeletronicos=="null")  and ($refrigeracao=="null")  and ($industrial=="null") and ($outros_6=="null") ){
//		$msg_erro	.=	" <br>É necessário preecher a pergunta 6";
//		echo "nao deu certo";
	}


	// #################### PERGUNTA 7 ##################### //
	if(strlen($_POST['revendedor_1'])>0)  $revendedor_1 =	$_POST['revendedor_1'];
	if(strlen($_POST['revendedor_2'])>0)  $revendedor_2 =	$_POST['revendedor_2'];
	if(strlen($_POST['revendedor_3'])>0)  $revendedor_3 =	$_POST['revendedor_3'];
	if(strlen($_POST['revendedor_4'])>0)  $revendedor_4 =	$_POST['revendedor_4'];
	if(strlen($_POST['revendedor_5'])>0)  $revendedor_5 =	$_POST['revendedor_5'];
	if(strlen($_POST['revendedor_6'])>0)  $revendedor_6 =	$_POST['revendedor_6'];
	if(strlen($_POST['revendedor_7'])>0)  $revendedor_7 =	$_POST['revendedor_7'];
	if(strlen($_POST['assinatura'])>0)    $assinatura   =	$_POST['assinatura'];

//Formulario 2
	//Identificação da Empresa
	if(strlen($_POST['razao_social']) > 0) $razao_social = $_POST['razao_social']; else $msg_erro .= "Digite a razão social";
	if(strlen($_POST['cnpj']) > 0)           $cnpj           = $_POST['cnpj'];	else $msg_erro .= "<br>Digite o CNPJ";
	if(strlen($_POST['ie']) > 0)             $ie             = $_POST['ie'];	else $msg_erro .= "<br>Digite a I.E";
	if(strlen($_POST['nome_fantasia']) > 0)  $nome_fantasia  = $_POST['nome_fantasia'];	else $msg_erro .= "<br>Digite o nome fantasia";
	if(strlen($_POST['data_fundacao']) > 0)  $data_fundacao  = $_POST['data_fundacao'];	else $msg_erro .= "<br>Digite a data da fundacao";
	if(strlen($_POST['endereco']) > 0)       $endereco       = $_POST['endereco'];	else $msg_erro .= "<br>Digite o endereço";
	if(strlen($_POST['bairro']) > 0)         $bairro         = $_POST['bairro'];	else $msg_erro .= "<br>Digite o bairro";
	if(strlen($_POST['cidade_estado']) > 0)  $cidade_estado  = $_POST['cidade_estado'];	else $msg_erro .= "<br>Digite a cidade/ UF";
	if(strlen($_POST['cep']) > 0)            $cep            = $_POST['cep'];	else $msg_erro .= "<br>Digite o CEP";
	if(strlen($_POST['fone']) > 0)           $fone           = $_POST['fone'];	else $msg_erro .= "<br>Digite o Fone";
	if(strlen($_POST['email']) > 0)          $email          = $_POST['email'];	else $msg_erro .= "<br>Digite o Email";

	//Informações do Banco
	if(strlen($_POST['nome_cliente']) > 0)   $nome_cliente   = $_POST['nome_cliente'];	else $msg_erro .= "<br>Digite o nome do cliente";
	if(strlen($_POST['banco']) > 0)          $banco          = $_POST['banco'];	else $msg_erro .= "<br>Digite o banco";
	if(strlen($_POST['agencia']) > 0)        $agencia        = $_POST['agencia'];	else $msg_erro .= "<br>Digite a agência";
	if(strlen($_POST['conta_corrente']) > 0) $conta_corrente = $_POST['conta_corrente'];	else $msg_erro .= "<br>Digite a conta corrente";


	//Informações complementares
	$audio_video     = $_POST['audio_video'];
	$eletroportateis = $_POST['eletroportateis'];
	$branca          = $_POST['branca'];

	if(strlen($_POST['tecnico_responsavel']) > 0)   $tecnico_responsavel   = $_POST['tecnico_responsavel'];	else $msg_erro .= "<br>Digite o nome do Técnico Responsável";
	if(strlen($_POST['marcas_presta_servico']) > 0) $marcas_presta_servico = $_POST['marcas_presta_servico'];	else $msg_erro .= "<br>Digite as marcas que seu posto presta serviço";

	//Componentes da Empresa
	if(strlen($_POST['comp_nome_1']) > 0)   $comp_nome_1   = $_POST['comp_nome_1'];
	if(strlen($_POST['comp_cpf_1']) > 0)    $comp_cpf_1    = $_POST['comp_cpf_1'];
	if(strlen($_POST['comp_cargo_1']) > 0)  $comp_cargo_1  = $_POST['comp_cargo_1'];
	if(strlen($_POST['comp_part_1']) > 0)   $comp_part_1   = $_POST['comp_part_1'];

	if(strlen($_POST['comp_nome_2']) > 0)   $comp_nome_2   = $_POST['comp_nome_2'];
	if(strlen($_POST['comp_cpf_2']) > 0)    $comp_cpf_2    = $_POST['comp_cpf_2'];
	if(strlen($_POST['comp_cargo_2']) > 0)  $comp_cargo_2  = $_POST['comp_cargo_2'];
	if(strlen($_POST['comp_part_2']) > 0)   $comp_part_2   = $_POST['comp_part_2'];

	if(strlen($_POST['comp_nome_3']) > 0)   $comp_nome_3    = $_POST['comp_nome_3'];
	if(strlen($_POST['comp_cpf_3']) > 0)    $comp_cpf_3     = $_POST['comp_cpf_3'];
	if(strlen($_POST['comp_cargo_3']) > 0)  $comp_cargo_3   = $_POST['comp_cargo_3'];
	if(strlen($_POST['comp_part_3']) > 0)   $comp_part_3    = $_POST['comp_part_3'];

	//Fontes de referencia
	if(strlen($_POST['aramoveis']) > 0)      $aramoveis      = $_POST['aramoveis'];
	if(strlen($_POST['caloi']) > 0)          $caloi          = $_POST['caloi'];
	if(strlen($_POST['fame']) > 0)           $fame           = $_POST['fame'];
	if(strlen($_POST['rudnick']) > 0)        $rudnick        = $_POST['rudnick'];
	if(strlen($_POST['atlas']) > 0)          $atlas          = $_POST['atlas'];
	if(strlen($_POST['castor']) > 0)         $castor         = $_POST['castor'];
	if(strlen($_POST['bem']) > 0)            $bem            = $_POST['bem'];
	if(strlen($_POST['semp_toshiba']) > 0)   $semp_toshiba   = $_POST['semp_toshiba'];
	if(strlen($_POST['bergamo']) > 0)        $bergamo        = $_POST['bergamo'];
	if(strlen($_POST['esmaltec']) > 0)       $esmaltec       = $_POST['esmaltec'];
	if(strlen($_POST['ponto_frio']) > 0)     $ponto_frio     = $_POST['ponto_frio'];
	if(strlen($_POST['suggar']) > 0)         $suggar         = $_POST['suggar'];
	if(strlen($_POST['todeschini']) > 0)     $todeschini     = $_POST['todeschini'];
	if(strlen($_POST['orthocrin']) > 0)      $orthocrin     = $_POST['orthocrin'];
	if(strlen($_POST['latinatec']) > 0)      $latinatec      = $_POST['latinatec'];
	if(strlen($_POST['bs_continental']) > 0) $bs_continental = $_POST['bs_continental'];

	//Outras fontes de referencia
	if(strlen($_POST['fontes_empresa_1']) > 0)  $fontes_empresa_1  = $_POST['fontes_empresa_1'];
	if(strlen($_POST['fontes_telefone_1']) > 0) $fontes_telefone_1 = $_POST['fontes_telefone_1'];
	if(strlen($_POST['fontes_empresa_2']) > 0)  $fontes_empresa_2  = $_POST['fontes_empresa_2'];
	if(strlen($_POST['fontes_telefone_2']) > 0) $fontes_telefone_2 = $_POST['fontes_telefone_2'];
	if(strlen($_POST['fontes_empresa_3']) > 0)  $fontes_empresa_3  = $_POST['fontes_empresa_3'];
	if(strlen($_POST['fontes_telefone_3']) > 0) $fontes_telefone_3 = $_POST['fontes_telefone_3'];


	//Maiores Revendedores
	if(strlen($_POST['revendedor_nome_1']) > 0) $revendedor_nome_1 = $_POST['revendedor_nome_1'];
	if(strlen($_POST['revendedor_nome_2']) > 0) $revendedor_nome_2 = $_POST['revendedor_nome_2'];
	if(strlen($_POST['revendedor_nome_3']) > 0) $revendedor_nome_3 = $_POST['revendedor_nome_3'];
	if(strlen($_POST['revendedor_nome_4']) > 0) $revendedor_nome_4 = $_POST['revendedor_nome_4'];

	//Termo de Aceitação
	if(strlen($_POST['termo']) > 0) $termo = $_POST['termo'];	else $msg_erro .= "<br>Assinale o Termo de Aceitação";

//Formulário 3

	if(strlen($_POST['$modelo_2']) > 0) $modelo_2  = trim($_POST['$modelo_2']);
	if(strlen($_POST['$qtde_2']) > 0)   $qtde_2    = trim($_POST['$qtde_2']);
	if(strlen($_POST['$modelo_3']) > 0) $modelo_3  = trim($_POST['$modelo_3']);
	if(strlen($_POST['$qtde_3']) > 0)   $qtde_3    = trim($_POST['$qtde_3']);
	if(strlen($_POST['$modelo_4']) > 0) $modelo_4  = trim($_POST['$modelo_4']);
	if(strlen($_POST['$qtde_4']) > 0)   $qtde_4    = trim($_POST['$qtde_4']);
	if(strlen($_POST['$modelo_5']) > 0) $modelo_5  = trim($_POST['$modelo_5']);
	if(strlen($_POST['$qtde_5']) > 0)   $qtde_5    = trim($_POST['$qtde_5']);
	if(strlen($_POST['$modelo_6']) > 0) $modelo_6  = trim($_POST['$modelo_6']);
	if(strlen($_POST['$qtde_6']) > 0)   $qtde_6    = trim($_POST['$qtde_6']);
	if(strlen($_POST['$modelo_7']) > 0) $modelo_7  = trim($_POST['$modelo_7']);
	if(strlen($_POST['$qtde_7']) > 0)   $qtde_7    = trim($_POST['$qtde_7']);
	if(strlen($_POST['$modelo_8']) > 0) $modelo_8  = trim($_POST['$modelo_8']);
	if(strlen($_POST['$qtde_8']) > 0)   $qtde_8    = trim($_POST['$qtde_8']);
	if(strlen($_POST['$modelo_9']) > 0) $modelo_9  = trim($_POST['$modelo_9']);
	if(strlen($_POST['$qtde_9']) > 0)   $qtde_9    = trim($_POST['$qtde_9']);
	if(strlen($_POST['$modelo_10']) > 0)$modelo_10 = trim($_POST['$modelo_10']);
	if(strlen($_POST['$qtde_10']) > 0)  $qtde_10   = trim($_POST['$qtde_10']);
	if(strlen($_POST['$modelo_11']) > 0)$modelo_11 = trim($_POST['$modelo_11']);
	if(strlen($_POST['$qtde_11']) > 0)  $qtde_11   = trim($_POST['$qtde_11']);
	if(strlen($_POST['$modelo_12']) > 0)$modelo_12 = trim($_POST['$modelo_12']);
	if(strlen($_POST['$qtde_12']) > 0)  $qtde_12   = trim($_POST['$qtde_12']);
	if(strlen($_POST['$modelo_14']) > 0)$modelo_14 = trim($_POST['$modelo_14']);
	if(strlen($_POST['$qtde_14']) > 0)  $qtde_14   = trim($_POST['$qtde_14']);
	if(strlen($_POST['$modelo_15']) > 0)$modelo_15 = trim($_POST['$modelo_15']);
	if(strlen($_POST['$qtde_15']) > 0)  $qtde_15   = trim($_POST['$qtde_15']);
	if(strlen($_POST['$modelo_16']) > 0)$modelo_16 = trim($_POST['$modelo_16']);
	if(strlen($_POST['$qtde_16']) > 0)  $qtde_16   = trim($_POST['$qtde_16']);
	if(strlen($_POST['$modelo_17']) > 0)$modelo_17 = trim($_POST['$modelo_17']);
	if(strlen($_POST['$qtde_17']) > 0)  $qtde_17   = trim($_POST['$qtde_17']);


	if(strlen($_POST['bancadas_1']) > 0 ) $bancadas_1 = trim($_POST['bancadas_1']);//	else $msg_erro .= "<br>Selecione Sim ou Não para FORRAÇÃO PARA PREVENIR RISCOS NOS APARELHOS";
	if(strlen($_POST['bancadas_2']) > 0 ) $bancadas_2 = trim($_POST['bancadas_2']);//	else $msg_erro .= "<br>Selecione Sim ou Não para DISJUNTOR ELETROMAGNÉTICO";
	if(strlen($_POST['bancadas_3']) > 0 ) $bancadas_3 = trim($_POST['bancadas_3']);//	else $msg_erro .= "<br>Selecione Sim ou Não para TRANSFORMADOR ISOLADOR";
	if(strlen($_POST['bancadas_4']) > 0 ) $bancadas_4 = trim($_POST['bancadas_4']);//	else $msg_erro .= "<br>Selecione Sim ou Não para EM CASO DE TV, LAMPADA SÉRIE";
	if(strlen($_POST['bancadas_5']) > 0 ) $bancadas_5 = trim($_POST['bancadas_5']);//	else $msg_erro .= "<br>Selecione Sim ou Não para ILUMINAÇÃO INDIVIDUAL";
	if(strlen($_POST['bancadas_6']) > 0 ) $bancadas_6 = trim($_POST['bancadas_6']);//	else $msg_erro .= "<br>Selecione Sim ou Não para SUPORTE SUPERIOR PARA INSTRUMENTOS";

	//recepcao
	if(strlen($_POST['recpcao_1']) > 0 ) $recpcao_1 = trim($_POST['recpcao_1']);//	else $msg_erro .= "<br>Selecione Sim ou Não para LOCAL E EQUIPAMENTOS ESPECÍFICO PARA TESTES DOS APARELHOS CONSERTADOS";
	if(strlen($_POST['recpcao_2']) > 0 ) $recpcao_2 = trim($_POST['recpcao_2']);//	else $msg_erro .= "<br>Selecione Sim ou Não para LOCAL ESPECÍFICO PARA O CLIENTE ESPERAR";
	if(strlen($_POST['recpcao_3']) > 0 ) $recpcao_3 = trim($_POST['recpcao_3']);//	else $msg_erro .= "<br>Selecione Sim ou Não para BALCÃO OU LOCAL DE ATENDIMENTO SEPARADO DA OFICINA";


	//Deposito
	if(strlen($_POST['deposito_1']) > 0 ) $deposito_1 = trim($_POST['deposito_1']);//	else $msg_erro .= "<br>Selecione Sim ou Não para PRATELEIRAS PARA TODOS OS APARELHOS";
	if(strlen($_POST['deposito_2']) > 0 ) $deposito_2 = trim($_POST['deposito_2']);//	else $msg_erro .= "<br>Selecione Sim ou Não para AS PRATELEIRAS SÃO FORRADAS PARA EVITAR RISCOS NOS APARELHOS";
	if(strlen($_POST['deposito_3']) > 0 ) $deposito_3 = trim($_POST['deposito_3']);//	else $msg_erro .= "<br>Selecione Sim ou Não para É DIVIDIDO EM ÁREAS COMO : PRONTOS, AG.PEÇA, AG.APROVAÇÃO DE ORÇAMENTO, GARANTIA , ETC.";


	//Estoque
	if(strlen($_POST['estoque_1']) > 0 )  $estoque_1 = trim($_POST['estoque_1']);//	else $msg_erro .= "<br>Selecione Sim ou Não para CONTROLES ITEM A ITEM DAS QUANTIDADES";
	if(strlen($_POST['estoque_2']) > 0 )  $estoque_2 = trim($_POST['estoque_2']);//	else $msg_erro .= "<br>Selecione Sim ou Não para COMPUTADOR EXCLUSIVO PARA USO NO ESTOQUE";
	if(strlen($_POST['estoque_3']) > 0 )  $estoque_3 = trim($_POST['estoque_3']);//	else $msg_erro .= "<br>Selecione Sim ou Não para CONTROLE DE REQUISIÇÕES DE PEÇAS";
	if(strlen($_POST['estoque_4']) > 0 )  $estoque_4 = trim($_POST['estoque_4']);//	else $msg_erro .= "<br>Selecione Sim ou Não para ACOMODAÇÃO CORRETA DOS COMPONENTES";


	//Área técnica
	if(strlen($_POST['tecnicos_formados']) > 0 )  $tecnicos_formados   = trim($_POST['tecnicos_formados']);//	else $msg_erro .= "<br>Digite os técnicos formados";
	if(strlen($_POST['tecnicos_cada_area']) > 0 ) $tecnicos_cada_area  = trim($_POST['tecnicos_cada_area']);//	else $msg_erro .= "<br>Digite os técnicos para cada área";
	if(strlen($_POST['tecnicos_qtde']) > 0 )      $tecnicos_qtde       = trim($_POST['tecnicos_qtde']);//	else $msg_erro .= "<br>Digite a quantidade de técnicos";
	if(strlen($_POST['tecnicos_treinados']) > 0 ) $tecnicos_treinados  = trim($_POST['tecnicos_treinados']);	//else $msg_erro .= "Digite os técnicos treinados";

//	if(strlen($msg_erro) == 0){
		$email_origem  = "telecontrol@telecontrol.com.br";
		$email_destino = "tecnico@telecontrol.com.br";
		$assunto       = "AUTO CADASTRAMENTO";
		$corpo .= "Razão Social  = $razao_social  \n";
		$corpo .= "Cnpj          = $cnpj          \n";
		$corpo .= "Ie            = $ie            \n";
		$corpo .= "Nome_fantasia = $nome_fantasia \n";
		$corpo .= "Data_fundacao = $data_fundacao \n";
		$corpo .= "Endereco      = $endereco      \n";
		$corpo .= "Bairro        = $bairro        \n";
		$corpo .= "Cidade_estado = $cidade_estado \n";
		$corpo .= "Cep           = $cep           \n";
		$corpo .= "Fone          = $fone          \n";
		$corpo .= "Email         = $email         \n";
		$corpo .= "\n";
		$corpo .= "Nome_cliente  = $nome_cliente  \n";
		$corpo .= "Banco         = $banco         \n";
		$corpo .= "Agencia       = $agencia       \n";
		$corpo .= "Conta corrente= $conta_corrente\n";
		$corpo .= "\n";
		$corpo .= "Audio video    = $audio_video    \n";
		$corpo .= "Eletroportateis= $eletroportateis\n";
		$corpo .= "Branca         = $branca         \n";
		$corpo .= "\n";
		$corpo .= "Tecnico responsavel  = $tecnico_responsavel  \n";
		$corpo .= "Marcas presta servico= $marcas_presta_servico\n";
		$corpo .= "\n";
		$corpo .= "Comp. nome 1 = $comp_nome_1 \n";
		$corpo .= "Comp. cpf 1  = $comp_cpf_1  \n";
		$corpo .= "Comp. cargo 1= $comp_cargo_1\n";
		$corpo .= "Comp. part 1 = $comp_part_1 \n";
		$corpo .= "                           \n";
		$corpo .= "Comp. nome 2 = $comp_nome_2 \n";
		$corpo .= "Comp. cpf 2  = $comp_cpf_2  \n";
		$corpo .= "Comp. cargo 2= $comp_cargo_2\n";
		$corpo .= "Comp. part 2 = $comp_part_2 \n";
		$corpo .= "                           \n";
		$corpo .= "Comp. nome 3 = $comp_nome_3 \n";
		$corpo .= "Comp. cpf 3  = $comp_cpf_3  \n";
		$corpo .= "Comp. cargo 3= $comp_cargo_3\n";
		$corpo .= "Comp. part 3 = $comp_part_3 \n";
		$corpo .= "\n";
		$corpo .= "Aramoveis     = $aramoveis     \n";
		$corpo .= "Caloi         = $caloi         \n";
		$corpo .= "Fame          = $fame          \n";
		$corpo .= "Rudnick       = $rudnick       \n";
		$corpo .= "Atlas         = $atlas         \n";
		$corpo .= "Castor        = $castor        \n";
		$corpo .= "Bem           = $bem           \n";
		$corpo .= "Semp_toshiba  = $semp_toshiba  \n";
		$corpo .= "Bergamo       = $bergamo       \n";
		$corpo .= "Esmaltec      = $esmaltec      \n";
		$corpo .= "Ponto_frio    = $ponto_frio    \n";
		$corpo .= "Suggar        = $suggar        \n";
		$corpo .= "Todeschini    = $todeschini    \n";
		$corpo .= "Orthocrin      = $ortocrin      \n";
		$corpo .= "Latinatec     = $latinatec     \n";
		$corpo .= "Bs_continental= $bs_continental\n";
		$corpo .= "\n";
		$corpo.="Auto Cadastramento dos postos\n\n";
		$corpo .= "Pergunta 1:\n";
		$corpo .= "\n";
		$corpo .= "pergunta    = $perg_1      \n";
		$corpo .= "entretanto  = $entretanto_1\n";
		$corpo .= "obs         = $obs_1       \n";
		$corpo .= "\n";
		$corpo .= "Pergunta 2:\n";
		$corpo .= "\n";
		$corpo .= "pergunta     = $perg_2      \n";
		$corpo .= "entretanto   = $entretanto_2\n";
		$corpo .= "obs          = $obs_2       \n";
		$corpo .= "\n";
		$corpo .= "Pergunta 3:\n";
		$corpo .= "\n";
		$corpo .= "arno        = $arno        \n";
		$corpo .= "walita      = $walita      \n";
		$corpo .= "faet        = $faet        \n";
		$corpo .= "blackedecker= $blackedecker\n";
		$corpo .= "bosch       = $bosch       \n";
		$corpo .= "electrolux  = $electrolux  \n";
		$corpo .= "philco      = $philco      \n";
		$corpo .= "gradiente   = $gradiente   \n";
		$corpo .= "philips     = $philips     \n";
		$corpo .= "toshiba     = $toshiba     \n";
		$corpo .= "cce         = $cce         \n";
		$corpo .= "sony        = $sony        \n";
		$corpo .= "obs_3       = $obs_3       \n";
		$corpo .= "\n";
		$corpo .= "\n";
		$corpo .= "Pergunta 4:\n";
		$corpo .= "\n";
		$corpo .= "pergunta    = $perg_4      \n";
		$corpo .= "entretanto  = $entretanto_4\n";
		$corpo .= "obs         = $obs_4       \n";
		$corpo .= "\n";
		$corpo .= "Pergunta 5:\n";
		$corpo .= "\n";
		$corpo .= "pergunta    = $perg_5      \n";
		$corpo .= "entretanto  = $entretanto_5\n";
		$corpo .= "obs         = $obs_5       \n";
		$corpo .= "\n";
		$corpo .= "Pergunta 6:\n";
		$corpo .= "\n";
		$corpo .= "eletrodomesticos  = $eletrodomesticos \n";
		$corpo .= "eletroeletronicos = $eletroeletronicos\n";
		$corpo .= "refrigeracao      = $refrigeracao     \n";
		$corpo .= "industrial        = $industrial       \n";
		$corpo .= "outros            = $outros           \n";
		$corpo .= "outros_6          = $outros_6         \n";
		$corpo .= "\n";
		$corpo .= "Pergunta 7:\n";
		$corpo .= "\n";
		$corpo .= "Revendedor 1 = $revendedor_1\n";
		$corpo .= "Revendedor 2 = $revendedor_2\n";
		$corpo .= "Revendedor 3 = $revendedor_3\n";
		$corpo .= "Revendedor 4 = $revendedor_4\n";
		$corpo .= "Revendedor 5 = $revendedor_5\n";
		$corpo .= "Revendedor 6 = $revendedor_6\n";
		$corpo .= "Revendedor 7 = $revendedor_7\n";
		$corpo .= "Assinatura   = $assinatura  \n";
		$corpo .= "\n";
		$corpo .= "Formulário 2\n";
		$corpo .= "\n";
		$corpo .= "OSCILOSCÓPIO = $modelo_1 \n";
		$corpo .= "qtde_1   = $qtde_1 \n";
		$corpo .= "MULTÍMETRO DIGITAL = $modelo_2 \n";
		$corpo .= "qtde_2   = $qtde_2 \n";
		$corpo .= "MULTÍMETRO ANALÓGICO = $modelo_3 \n";
		$corpo .= "qtde_3   = $qtde_3 \n";
		$corpo .= "GERADOR DE BARRAS = $modelo_4 \n";
		$corpo .= "qtde_4   = $qtde_4 \n";
		$corpo .= "GERADOR DE ÁUDIO = $modelo_5 \n";
		$corpo .= "qtde_5   = $qtde_5 \n";
		$corpo .= "GERADOR DE RF = $modelo_7 \n";
		$corpo .= "qtde_7   = $qtde_7 \n";
		$corpo .= "LASER POWER METER = $modelo_8 \n";
		$corpo .= "qtde_8   = $qtde_8 \n";
		$corpo .= "ANALISADOR DE CINESCÓPIOS = $modelo_9 \n";
		$corpo .= "qtde_9   = $qtde_9 \n";
		$corpo .= "SIMULADOR DE LINHA TELEFÔNICA= $modelo_10\n";
		$corpo .= "qtde_10  = $qtde_10 \n";
		$corpo .= "ESTAÇÃO DE SOLDA COM TEMPERATURA CONTROLADA= $modelo_11\n";
		$corpo .= "qtde_11  = $qtde_11 \n";
		$corpo .= "ESTAÇÃO DE SOLDA A AR QUENTE= $modelo_12\n";
		$corpo .= "qtde_12  = $qtde_12 \n";
		$corpo .= "PULSEIRA ANTI-ESTÁTICA= $modelo_14\n";
		$corpo .= "qtde_14  = $qtde_14 \n";
		$corpo .= "MANTA ANTI-ESTÁTICA= $modelo_15\n";
		$corpo .= "qtde_15  = $qtde_15 \n";
		$corpo .= "FERRO DE SOLDAR= $modelo_16\n";
		$corpo .= "qtde_16  = $qtde_16 \n";
		$corpo .= "PARAFUSADEIRA= $modelo_17\n";
		$corpo .= "qtde_17  = $qtde_17 \n";
		$corpo .= "\n";
		$corpo .= "Fontes empresa 1 = $fontes_empresa_1 \n";
		$corpo .= "Fontes telefone 1= $fontes_telefone_1\n";
		$corpo .= "Fontes empresa 2 = $fontes_empresa_2 \n";
		$corpo .= "Fontes telefone 2= $fontes_telefone_2\n";
		$corpo .= "Fontes empresa 3 = $fontes_empresa_3 \n";
		$corpo .= "Fontes telefone 3= $fontes_telefone_3\n";
		$corpo .= "\n";
		$corpo .= "Revendedor nome 1= $revendedor_nome_1\n";
		$corpo .= "Revendedor nome 2= $revendedor_nome_2\n";
		$corpo .= "Revendedor nome 3= $revendedor_nome_3\n";
		$corpo .= "Revendedor nome 4= $revendedor_nome_4\n";
		$corpo .= "\n";
		$corpo .= "Bancadas 1= $bancadas_1\n";
		$corpo .= "Bancadas 2= $bancadas_2\n";
		$corpo .= "Bancadas 3= $bancadas_3\n";
		$corpo .= "Bancadas 4= $bancadas_4\n";
		$corpo .= "Bancadas 5= $bancadas_5\n";
		$corpo .= "Bancadas 6= $bancadas_6\n";
		$corpo .= "\n";
		$corpo .= "Recpcao_1= $recpcao_1\n";
		$corpo .= "Recpcao_2= $recpcao_2\n";
		$corpo .= "Recpcao_3= $recpcao_3\n";
		$corpo .= "\n";
		$corpo .= "Deposito_1= $deposito_1\n";
		$corpo .= "Deposito_2= $deposito_2\n";
		$corpo .= "Deposito_3= $deposito_3\n";
		$corpo .= "\n";
		$corpo .= "Estoque_1 = $estoque_1 \n";
		$corpo .= "Estoque_2 = $estoque_2 \n";
		$corpo .= "Estoque_3 = $estoque_3 \n";
		$corpo .= "Estoque_4 = $estoque_4 \n";
		$corpo .= "\n";
		$corpo .= "Tecnicos formados = $tecnicos_formados \n";
		$corpo .= "Tecnicos cada_area= $tecnicos_cada_area\n";
		$corpo .= "Tecnicos qtde     = $tecnicos_qtde     \n";
		$corpo .= "Tecnicos treinados= $tecnicos_treinados\n";
		$corpo .= "\n\n\n";
		$corpo.="_______________________________________________\n";
		$corpo.="Telecontrol\n";
		$corpo.="www.telecontrol.com.br\n";
		$body_top = "--Message-Boundary\n";
		$body_top .= "Content-type: text/html; charset=iso-8859-1\n";
		$body_top .= "Content-transfer-encoding: 7BIT\n";
		$body_top .= "Content-description: Mail message body\n\n";
		@mail($email_destino, stripslashes($assunto), $corpo, "From: ".$email_origem); 
//	}


}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE> Auto Avaliação de Posto Autorizado </TITLE>
<META NAME="Generator" CONTENT="EditPlus">
<META NAME="Author" CONTENT="">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">
</HEAD>

<BODY>

<? if(strlen($msg_erro) > 0){?>
<TABLE bgcolor='#FF3300' align='center' style='font-size: 14px; color: #FFFFFF; font-weight: bold;'>
<TR>
	<TD><? echo "$msg_erro"; ?></TD>
</TR>
</TABLE>
<?}?>


<FORM ACTION='<? $PHP_SELF ?>' METHOD='POST' name='formulario_1'>

<TABLE border='1' width='600' align='center' cellspacing='0' cellpadding='0' style='font-family: verdana;' >
<TR>
	<TD align='center' style='font-size: 18px'><b>Auto-Avaliação de Posto Autorizado<b></TD>
</TR>
<TR>
	<TD><br><p align="justify">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Esta pesquisa não tem caráter eliminatório. O objetivo principal  é traçar o perfil do fornecedor 
		para que possamos nos adequar às necessidades do mercado. 
		<br>
		O preenchimento desta ficha é obrigatório. 
		<br>
		Após a análise desta ficha, nosso inspetor poderá marcar uma visita para observação de sua empresa. 

		<br>
		<font color='#ff0000'>
		(*** Fernando, o envio de fotos deve ser feito pela Web, ou o posto deve dizer que não possui máquina digital. Aceite 3 fotos, Fachada, Área de atendimento ao consumidor e bancada ***) 
		<br>
		Que negócio é este de "Observação do Representante ???"
		</font>

		</p></TD>
</TR>
</TABLE>
<br>
<TABLE border='1' cellspacing='0' cellpadding='0' width='600' align='center' style='font-family: verdana; font-size: 12px' >
<TR>
	<TD><b>1.Reúno condições para realizar um bom atendimento aos consumidores ?<b></TD>
</TR>
<TR>
	<TD>
	<?
		$checked_1="";
		$checked_2="";
		if($perg_1=="sim") $checked_1="checked";
		if($perg_1=="nao") $checked_2="checked";
		echo "<INPUT TYPE='radio' NAME='perg_1' value='sim' $checked_1>Sim";
		echo "<INPUT TYPE='radio' NAME='perg_1' value='nao' $checked_2>Não";
	?>
	</TD>
</TR>
<TR>
	<TD>
	Entretanto:<br>
		<TEXTAREA NAME="entretanto_1" ROWS="2" COLS="50"><? echo $entretanto_1;?></TEXTAREA><br>
	Observação do Representante:<br>
		<INPUT TYPE="text" NAME="obs_1" size='50' value='<? echo $obs_1;?>'>
	</TD>
</TR>
</TABLE>
<br>

<TABLE border='1' cellspacing='0' cellpadding='0' width='600' align='center' style='font-family: verdana; font-size: 12px;' >
<TR>
	<TD><b>2. Possuo condições de armazenagem organizada e segura de peças e produtos dos consumidores ?</b></TD>
</TR>
<TR>
	<TD>
	<?
		$checked_1="";
		$checked_2="";
		if($perg_2=="sim") $checked_1="checked";
		if($perg_2=="nao") $checked_2="checked";
		echo "<INPUT TYPE='radio' NAME='perg_2' value='sim' $checked_1>Sim";
		echo "<INPUT TYPE='radio' NAME='perg_2' value='nao' $checked_2>Não";
	?>
	</TD>
</TR>
<TR>
	<TD>
	Entretanto:<br>
		<TEXTAREA NAME="entretanto_2" ROWS="2" COLS="50"><? echo $entretanto_2;?></TEXTAREA><br>
	Observação do Representante:<br>
		<INPUT TYPE="text" NAME="obs_2" size='50' value='<? echo $obs_2;?>'>
	</TD>
</TR>
</TABLE>
<br>

<TABLE border='1' cellspacing='0' cellpadding='0' width='600' align='center' style='font-family: verdana; font-size: 12px' >
<TR>
	<TD><b>3. Atualmente minha empresa presta serviço autorizado para:</b> 		
		<br>
		<font color='#ff0000'>
		Acrescentar nossos outros fabricantes
		</font>

</TD>
</TR>
<TR>
	<TD>
		<INPUT TYPE="checkbox" NAME="arno"		value='arno'		<?echo $arno_check;		?>>Arno
		<INPUT TYPE="checkbox" NAME="walita"	value='walita'		<?echo $walita_check;	?>>Walita
		<INPUT TYPE="checkbox" NAME="faet"		value='faet'		<?echo $faet_check;		?>>Faet
		<INPUT TYPE="checkbox" NAME="blackedecker" value='blackedecker' <?echo $blackedecker_check;?>>Black & Decker
		<INPUT TYPE="checkbox" NAME="bosch"		value='bosch'		<?echo $bosch_check;	?>>Bosch
		<INPUT TYPE="checkbox" NAME="electrolux" value='electrolux'	<?echo $electrolux_check;?>>Electrolux<br>
		<INPUT TYPE="checkbox" NAME="philco"	value='philco'		<?echo $philco_check;	?>>Philco
		<INPUT TYPE="checkbox" NAME="gradiente" value='gradiente'	<?echo $gradiente_check;?>>Gradiente
		<INPUT TYPE="checkbox" NAME="philips"	value='philips'		<?echo $philips_check;	?>>Philips
		<INPUT TYPE="checkbox" NAME="toshiba"	value='toshiba'		<?echo $toshiba_check;	?>>Toshiba
		<INPUT TYPE="checkbox" NAME="cce"		value='cce'			<?echo $cce_check;		?>>CCE
		<INPUT TYPE="checkbox" NAME="sony"		value='sony'		<?echo $sony_check;		?>>Sony
	</TD>
</TR>
<TR>
	<TD>
	Observação do Representante:<br><INPUT TYPE="text" NAME="obs_3" size='50' value='<?echo $obs_3;?>'>
	</TD>
</TR>
</TABLE>
<br>

<TABLE border='1' width='600' cellspacing='0' cellpadding='0' align='center' style='font-family: verdana; font-size: 12px' >
<TR>
	<TD><b>4. Conheço o Codigo de Defesa do Consumidor e sei  quais são os direitos dos consumidores ?</b></TD>
</TR>
<TR>
	<TD>
	<?
		$checked_1='';
		$checked_2='';
		if($perg_4=="sim") $checked_1="checked";
		if($perg_4=="nao") $checked_2="checked";
		echo "<INPUT TYPE='radio' NAME='perg_4' value='sim' $checked_1>Sim";
		echo "<INPUT TYPE='radio' NAME='perg_4' value='nao' $checked_2>Não";
	?>
	</TD>
</TR>
<TR>
	<TD>
	Entretanto:<br><TEXTAREA NAME="entretanto_4" ROWS="2" COLS="50"><? echo $entretanto_4;?></TEXTAREA><br>
	Observação do Representante:<br><INPUT TYPE="text" NAME="obs_4" size='50' value='<? echo $obs_4;?>'>
	</TD>
</TR>
</TABLE>
<br>

<TABLE border='1' cellspacing='0' cellpadding='0' width='600' align='center' style='font-family: verdana; font-size: 12px' >
<TR>
	<TD><b>5.Tenho ferramentas adequadas e pessoal capacitado para consertar eletroportáteis ?
	</b>
		<br>
		<font color='#ff0000'>
		Fazer com Check-Box as seguintes opções:
		Linha Branca, Fogões, Eletroportáteis, Autorádios, Eletrônicos, TVs convencionais, TVs de plasma e LCD, Computadores, Notebooks, Telefones, Centrais Telefônicas, Vídeo Games, Chuveiros, Aquecedores, Coifas.
		</font>

	</TD>
</TR>
<TR>
	<TD>
	<?
		$checked_1='';
		$checked_2='';
		if($perg_5=="sim") $checked_1="checked";
		if($perg_5=="nao") $checked_2="checked";
		echo "<INPUT TYPE='radio' NAME='perg_5' value='sim' $checked_1>Sim";
		echo "<INPUT TYPE='radio' NAME='perg_5' value='nao' $checked_2>Não";
	?>
	</TD>
</TR>
<TR>
	<TD>
	Entretanto:<br><TEXTAREA NAME="entretanto_5" ROWS="2" COLS="50"><? echo $entretanto_5;?></TEXTAREA><br>
	Observação do Representante:<br><INPUT TYPE="text" NAME="obs_5" size='50' value='<? echo $obs_5;?>'>
	</TD>
</TR>
</TABLE>
<br>

<TABLE border='1' cellspacing='0' cellpadding='0' width='600' align='center' style='font-family: verdana; font-size: 12px' >
<TR>
	<TD><b>6. Minha especialidade é :</b></TD>
</TR>
<TR>
	<TD>
		<INPUT TYPE="checkbox" NAME="eletrodomesticos" value='eletrodomesticos' <? echo $eletrodomesticos_check;?>>Eletrodomésticos
		<INPUT TYPE="checkbox" NAME="eletroeletronicos" value='eletroeletronicos' <? echo $eletroeletronicos_check;?>>Eletro-eletrônicos
		<INPUT TYPE="checkbox" NAME="refrigeracao" value='refrigeracao' <? echo $refrigeracao_check;?>>Refrigeração
		<INPUT TYPE="checkbox" NAME="industrial" value='industrial' <? echo $industrial_check;?>>Industrial<br>
		<INPUT TYPE="checkbox" NAME="outros" value='outros' <? echo $outros_check;?>>Outros:
		<INPUT TYPE="text" NAME="outros_6" value='<? echo $outros_6;?>' size='60'>
	</TD>
</TR>
</TABLE>
<br>

<TABLE border='1' cellspacing='0' cellpadding='0' width='600' align='center' style='font-family: verdana; font-size: 12px' >
<TR>
	<TD><b>7. Os maiores revendedores de produtos da minha região são :</b></TD>
</TR>
<TR>
	<TD>
		1.<INPUT TYPE="text" NAME="revendedor_1" value='<?echo $revendedor_1;?>'><br>
		2.<INPUT TYPE="text" NAME="revendedor_2" value='<?echo $revendedor_2;?>'><br>
		3.<INPUT TYPE="text" NAME="revendedor_3" value='<?echo $revendedor_3;?>'><br>
		4.<INPUT TYPE="text" NAME="revendedor_4" value='<?echo $revendedor_4;?>'><br>
		5.<INPUT TYPE="text" NAME="revendedor_5" value='<?echo $revendedor_5;?>'><br>
		6.<INPUT TYPE="text" NAME="revendedor_6" value='<?echo $revendedor_6;?>'><br>
		7.<INPUT TYPE="text" NAME="revendedor_7" value='<?echo $revendedor_7;?>'>
	</TD>
</TR>
</TABLE>





<br><br><br>

<TABLE border='1' cellspacing='0' cellpadding='0' align='center' width='600' style='font-family: verdana; '>
<TR>
	<TD align='center'><b>FICHA CADASTRO POSTOS AUTORIZADOS</b></TD>
</TR>
</TABLE>
<br>

<TABLE border='1' width='600' align='center' cellspacing='0' cellpadding='0' border='0' style='font-size: 12px; font-weight: bold;'>
<TR>
	<TD align='left' colspan='4' style='font-size: 14'><b>Identificação da Empresa</b></TD>
</TR>
<TR>
	<TD align='right' bgcolor='#C0C2C7'>Razão Social:</TD>
	<TD align='left' colspan='3'><INPUT TYPE="text" NAME="razao_social" size='80' <? if(strlen($razao_social) > 0) echo "value='$razao_social'"; ?>></TD>
</TR>
<TR>
	<TD align='right' bgcolor='#C0C2C7'>CNPJ.:</TD>
	<TD><INPUT TYPE="text" NAME="cnpj" <? if(strlen($cnpj) > 0) echo "value='$cnpj'"; ?>></TD>
	<TD align='right' bgcolor='#C0C2C7'>I.E.:</TD>
	<TD><INPUT TYPE="text" NAME="ie" <? if(strlen($ie) > 0) echo "value='$ie'"; ?>></TD>
</TR>
<TR>
	<TD align='right' bgcolor='#C0C2C7'>Nome Fantasia:</TD>
	<TD><INPUT TYPE="text" NAME="nome_fantasia" <? if(strlen($nome_fantasia) > 0) echo "value='$nome_fantasia'"; ?>></TD>
	<TD align='right' bgcolor='#C0C2C7'>Data Fundação:</TD>
	<TD><INPUT TYPE="text" NAME="data_fundacao" <? if(strlen($data_fundacao) > 0) echo "value='$data_fundacao'"; ?>></TD>
</TR>
<TR>
	<TD align='right' bgcolor='#C0C2C7'>Endereço:</TD>
	<TD colspan='3'><INPUT TYPE="text" NAME="endereco" <? if(strlen($endereco) > 0) echo "value='$endereco'"; ?>></TD>
</TR>
<TR>
	<TD align='right' bgcolor='#C0C2C7'>Bairro:</TD>
	<TD><INPUT TYPE="text" NAME="bairro" <? if(strlen($bairro) > 0) echo "value='$bairro'"; ?>></TD>
	<TD align='right' bgcolor='#C0C2C7'>CIDADE/ UF:</TD>
	<TD><INPUT TYPE="text" NAME="cidade_estado" <? if(strlen($cidade_estado) > 0) echo "value='$cidade_estado'"; ?>></TD>
</TR>
<TR>
	<TD align='right' bgcolor='#C0C2C7'>CEP.:</TD>
	<TD><INPUT TYPE="text" NAME="cep" <? if(strlen($cep) > 0) echo "value='$cep'"; ?>></TD>
	<TD align='right' bgcolor='#C0C2C7'>Fone:</TD>
	<TD><INPUT TYPE="text" NAME="fone" <? if(strlen($fone) > 0) echo "value='$fone'"; ?>></TD>
</TR>
<TR>
	<TD align='right' bgcolor='#C0C2C7'>Email:</TD>
	<TD><INPUT TYPE="text" NAME="email" <? if(strlen($email) > 0) echo "value='$email'"; ?>></TD>
</TR>
</TABLE>
<TABLE border='1' width='600' align='center' cellspacing='0' cellpadding='0' border='0' style='font-size: 12px; font-weight: bold;'>
<TR >
	<TD align='left' colspan='2' style='font-size: 14'><b>Conta Bancária:<b></TD>
</TR>
<TR>
	<TD align='right' bgcolor='#C0C2C7'>Nome do Cliente:</TD>
	<TD>&nbsp;<INPUT TYPE="text" NAME="nome_cliente" size='78' <? if(strlen($nome_cliente) > 0) echo "value='$nome_cliente'"; ?>></TD>
</TR>
<TR>
	<TD align='right' bgcolor='#C0C2C7'>Banco:</TD>
	<TD>&nbsp;<INPUT TYPE="text" NAME="banco" size='78' <? if(strlen($banco) > 0) echo "value='$banco'"; ?>></TD>
</TR>
<TR>
	<TD align='right' bgcolor='#C0C2C7'>Agência:</TD>
	<TD>&nbsp;<INPUT TYPE="text" NAME="agencia" size='78' <? if(strlen($agencia) > 0) echo "value='$agencia'"; ?>></TD>
</TR>
<TR>
	<TD align='right' bgcolor='#C0C2C7'>Conta Corrente:</TD>
	<TD>&nbsp;<INPUT TYPE="text" NAME="conta_corrente" size='78' <? if(strlen($conta_corrente) > 0) echo "value='$conta_corrente'"; ?>></TD>
</TR>
</TABLE>

<TABLE border='1' width='600' align='center' cellspacing='0' cellpadding='0' border='0' style='font-size: 12px; font-weight: bold;'>
<TR>
	<TD colspan='4' style='font-size: 14'><b>Informações Complementares</b></TD>
</TR>
<TR>
	<TD align='right' bgcolor='#C0C2C7'>Produtos que atende</TD>
	<TD>&nbsp;
		<INPUT TYPE="checkbox" NAME="audio_video" value='Audio e video' <? if(strlen($audio_video) > 0) echo "checked"; ?>>Áudio e Vídeo
		<INPUT TYPE="checkbox" NAME="eletroportateis" value='Eletroportáteis' <? if(strlen($eletroportateis) > 0) echo "checked"; ?>>Eletroportáteis
		<INPUT TYPE="checkbox" NAME="branca" value='Branca' <? if(strlen($branca) > 0) echo "checked"; ?>>Branca
	</TD>
</TR>
<TR>
	<TD align='right' bgcolor='#C0C2C7' nowrap>Responsável Técnico:</TD>
	<TD>&nbsp;<INPUT TYPE="text" NAME="tecnico_responsavel" size='50' <? if(strlen($tecnico_responsavel) > 0) echo "value='$tecnico_responsavel'"; ?>></TD>
</TR>
<TR>
	<TD align='right' bgcolor='#C0C2C7'>Marcas p/ as quais Presto Serviço Autorizado</TD>
	<TD>&nbsp;<INPUT TYPE="text" NAME="marcas_presta_servico" size='50' <? if(strlen($marcas_presta_servico) > 0) echo "value='$marcas_presta_servico'"; ?>></TD>
</TR>
</TABLE>
<TABLE border='1' width='600' align='center' cellspacing='0' cellpadding='0' border='0' style='font-size: 12px; font-weight: bold;'>
<TR>
	<TD colspan='5' style='font-size: 14'>Componentes da Empresa</TD>
</TR>
<TR bgcolor='#C0C2C7'>
	<TD>&nbsp;</TD>
	<TD>Nome</TD>
	<TD>CPF</TD>
	<TD>Cargo</TD>
	<TD>% Part.</TD>
</TR>
<TR>
	<TD bgcolor='#C0C2C7'>&nbsp;1</TD>
	<TD><INPUT TYPE="text" NAME="comp_nome_1" size='30' <? if(strlen($comp_nome_1) > 0) echo "value='$comp_nome_1'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="comp_cpf_1" <? if(strlen($comp_cpf_1) > 0) echo "value='$comp_cpf_1'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="comp_cargo_1 <? if(strlen($comp_cargo_1) > 0) echo "value='$comp_cargo_1'"; ?>"></TD>
	<TD><INPUT TYPE="text" NAME="comp_part_1" size='2' <? if(strlen($comp_part_1) > 0) echo "value='$comp_part_1'"; ?>></TD>
</TR>
<TR>
	<TD bgcolor='#C0C2C7'>&nbsp;2</TD>
	<TD><INPUT TYPE="text" NAME="comp_nome_2" size='30' <? if(strlen($comp_nome_2) > 0) echo "value='$comp_nome_2'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="comp_cpf_2" <? if(strlen($comp_cpf_2) > 0) echo "value='$comp_cpf_2'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="comp_cargo_2" <? if(strlen($comp_cargo_2) > 0) echo "value='$comp_cargo_2'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="comp_part_2" size='2' <? if(strlen($comp_part_2) > 0) echo "value='$comp_part_2'"; ?>></TD>
</TR>
<TR>
	<TD bgcolor='#C0C2C7'>&nbsp;3</TD>
	<TD><INPUT TYPE="text" NAME="comp_nome_3" size='30' <? if(strlen($comp_nome_3) > 0) echo "value='$comp_nome_3'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="comp_cpf_3" <? if(strlen($comp_cpf_3) > 0) echo "value='$comp_cpf_3'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="comp_cargo_3" <? if(strlen($comp_cpf_3) > 0) echo "value='$comp_cpf_3'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="comp_part_3" size='2' <? if(strlen($comp_part_3) > 0) echo "value='$comp_part_3'"; ?>></TD>
</TR>
</TABLE>
<TABLE border='1' width='600' align='center' cellspacing='0' cellpadding='0' border='0' style='font-size: 12px; font-weight: bold;'>
<TR>
	<TD colspan='4' style='font-size: 14'>Fontes de Referência</TD>
</TR>
<TR>
	<TD bgcolor='#C0C2C7'>Aramóveis</TD>
	<TD><INPUT TYPE="checkbox" value='Aramoveis' NAME="aramoveis" <? if(strlen($aramoveis) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7' >Caloi</TD>
	<TD><INPUT TYPE="checkbox" value='Caloi' NAME="caloi" <? if(strlen($caloi) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>Fame</TD>
	<TD><INPUT TYPE="checkbox" value='fame' NAME="fame" <? if(strlen($fame) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>Rudnick</TD>
	<TD><INPUT TYPE="checkbox" value='Rudnick' NAME="rudnick" <? if(strlen($rudnick) > 0) echo "checked"; ?>></TD>
</TR>
<TR>
	<TD bgcolor='#C0C2C7'>Atlas</TD>
	<TD><INPUT TYPE="checkbox" value='Atlas' NAME="atlas" <? if(strlen($atlas) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>Castor</TD>
	<TD><INPUT TYPE="checkbox" value='Castor' NAME="castor" <? if(strlen($castor) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>B&M</TD>
	<TD><INPUT TYPE="checkbox" value='B&M' NAME="bem" <? if(strlen($bem) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>Semp Toshiba</TD>
	<TD><INPUT TYPE="checkbox" value='Semp Toshiba' NAME="semp_toshiba" <? if(strlen($semp_toshiba) > 0) echo "checked"; ?>></TD>
</TR>
<TR>
	<TD bgcolor='#C0C2C7'>Bergamo</TD>
	<TD><INPUT TYPE="checkbox" value='Bergamo' NAME="bergamo" <? if(strlen($bergamo) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>Esmaltec</TD>
	<TD><INPUT TYPE="checkbox" value='Esmaltec' NAME="esmaltec" <? if(strlen($esmaltec) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>Ponto Frio</TD>
	<TD><INPUT TYPE="checkbox" value='Ponto Frio' NAME="ponto_frio" <? if(strlen($ponto_frio) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>Suggar</TD>
	<TD><INPUT TYPE="checkbox" value='Suggar' NAME="suggar" <? if(strlen($suggar) > 0) echo "checked"; ?>></TD>
</TR>
<TR>
	<TD bgcolor='#C0C2C7'>Todeschini</TD>
	<TD><INPUT TYPE="checkbox" value='Todeschini' NAME="todeschini" <? if(strlen($todeschini) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>Orthocrin</TD>
	<TD><INPUT TYPE="checkbox" value='Orthocrin' NAME="orthocrin" <? if(strlen($orthocrin) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>Latinatec</TD>
	<TD><INPUT TYPE="checkbox" value='Latinatec' NAME="latinatec" <? if(strlen($latinatec) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>BS Continental</TD>
	<TD><INPUT TYPE="checkbox" value='BS Continental' NAME="bs_continental" <? if(strlen($bs_continental) > 0) echo "checked"; ?>></TD>
</TR>
</TABLE>
<TABLE border='1' width='600' align='center' cellspacing='0' cellpadding='0' border='0' style='font-size: 12px; font-weight: bold;'>
<TR>
	<TD colspan='3' style='font-size: 14'>Outras Fontes de Referência com Telefone</TD>
</TR>
<TR align='center' bgcolor='#C0C2C7'>
	<TD>&nbsp;</TD>
	<TD>Empresa</TD>
	<TD>Telefone</TD>
</TR>
<TR>
	<TD bgcolor='#C0C2C7'>&nbsp;1</TD>
	<TD><INPUT TYPE="text" NAME="fontes_empresa_1" size='60' <? if(strlen($fontes_empresa_1) > 0) echo "value='$fontes_empresa_1'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="fontes_telefone_1" <? if(strlen($fontes_telefone_1) > 0) echo "value='$fontes_telefone_1'"; ?>></TD>
</TR>
<TR>
	<TD bgcolor='#C0C2C7'>&nbsp;2</TD>
	<TD><INPUT TYPE="text" NAME="fontes_empresa_2" size='60' <? if(strlen($fontes_empresa_2) > 0) echo "value='$fontes_empresa_2'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="fontes_telefone_2" <? if(strlen($fontes_telefone_1) > 0) echo "value='$fontes_telefone_2'"; ?>></TD>
</TR>
<TR>
	<TD bgcolor='#C0C2C7'>&nbsp;3</TD>
	<TD><INPUT TYPE="text" NAME="fontes_empresa_3" size='60' <? if(strlen($fontes_empresa_3) > 0) echo "value='$fontes_empresa_2'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="fontes_telefone_3" <? if(strlen($fontes_telefone_3) > 0) echo "value='$fontes_telefone_3'"; ?>></TD>
</TR>
</TABLE>

<TABLE border='1' width='600' align='center' cellspacing='0' cellpadding='0' border='0' style='font-size: 12px; font-weight: bold;'>
<TR>
	<TD colspan='4' style='font-size: 14'>Maiores Revendedores dos Produtos  na região</TD>
</TR>
<TR>
	<TD bgcolor='#C0C2C7'>&nbsp;1&nbsp;</TD>
	<TD><INPUT TYPE="text" NAME="revendedor_nome_1" size='42' <? if(strlen($revendedor_nome_1) > 0) echo "value='$revendedor_nome_1'"; ?>></TD>
	<TD bgcolor='#C0C2C7'>&nbsp;2&nbsp;</TD>
	<TD><INPUT TYPE="text" NAME="revendedor_nome_2" size='42' <? if(strlen($revendedor_nome_2) > 0) echo "value='$revendedor_nome_2'"; ?>></TD>
</TR>
<TR>
	<TD bgcolor='#C0C2C7'>&nbsp;3&nbsp;</TD>
	<TD><INPUT TYPE="text" NAME="revendedor_nome_3" size='42' <? if(strlen($revendedor_nome_3) > 0) echo "value='$revendedor_nome_3'"; ?>></TD>
	<TD bgcolor='#C0C2C7'>&nbsp;4&nbsp;</TD>
	<TD><INPUT TYPE="text" NAME="revendedor_nome_4" size='42' <? if(strlen($revendedor_nome_3) > 0) echo "value='$revendedor_nome_3'"; ?>></TD>
</TR>
</TABLE>
<br><br><br>


<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='1' style='font-size: 12px'>

<TR>
	<TD align='center' style='font-size: 18px'><b>Questionário de Estrutura</b></TD>
</TR>
</TABLE>
<BR>
<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='1' style='font-size: 12px'>
<TR align='center' bgcolor='#9699A0' style='font-weight: bold'>
	<TD width='550'>INSTRUMENTOS</TD>
	<TD>MODELO</TD>
	<TD>QUANT.</TD>
</TR>
<TR>
	<TD>OSCILOSCÓPIO</TD>
	<TD><INPUT TYPE="text" NAME="modelo_1" <? if(strlen($modelo_1) > 0) echo "value='$modelo_1'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="qtde_1" <? if(strlen($qtde_1) > 0) echo "value='$qtde_1'"; ?>></TD>
</TR>
<TR>
	<TD>MULTÍMETRO DIGITAL</TD>
	<TD><INPUT TYPE="text" NAME="modelo_2" <? if(strlen($modelo_2) > 0) echo "value='$modelo_2'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="qtde_2" <? if(strlen($qtde_2) > 0) echo "value='$qtde_2'"; ?>></TD>
</TR>
<TR>
	<TD>MULTÍMETRO ANALÓGICO</TD>
	<TD><INPUT TYPE="text" NAME="modelo_3" <? if(strlen($modelo_3) > 0) echo "value='$modelo_3'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="qtde_3" <? if(strlen($qtde_3) > 0) echo "value='$qtde_3'"; ?>></TD>
</TR>
<TR>
	<TD>GERADOR DE BARRAS</TD>
	<TD><INPUT TYPE="text" NAME="modelo_4" <? if(strlen($modelo_4) > 0) echo "value='$modelo_4'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="qtde_4" <? if(strlen($qtde_4) > 0) echo "value='$qtde_4'"; ?>></TD>
</TR>
<TR>
	<TD>GERADOR DE ÁUDIO</TD>
	<TD><INPUT TYPE="text" NAME="modelo_5" <? if(strlen($modelo_5) > 0) echo "value='$modelo_5'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="qtde_5" <? if(strlen($qtde_5) > 0) echo "value='$qtde_5'"; ?>></TD>
</TR>
<TR>
	<TD>GERADOR DE RF</TD>
	<TD><INPUT TYPE="text" NAME="modelo_7" <? if(strlen($modelo_7) > 0) echo "value='$modelo_7'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="qtde_7" <? if(strlen($qtde_7) > 0) echo "value='$qtde_7'"; ?>></TD>
</TR>
<TR>
	<TD>LASER POWER METER</TD>
	<TD><INPUT TYPE="text" NAME="modelo_8" <? if(strlen($modelo_8) > 0) echo "value='$modelo_8'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="qtde_8" <? if(strlen($qtde_8) > 0) echo "value='$qtde_8'"; ?>></TD>
</TR>
<TR>
	<TD>ANALISADOR DE CINESCÓPIOS</TD>
	<TD><INPUT TYPE="text" NAME="modelo_9" <? if(strlen($modelo_9) > 0) echo "value='$modelo_9'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="qtde_9" <? if(strlen($qtde_9) > 0) echo "value='$qtde_9'"; ?>></TD>
</TR>
<TR>
	<TD>SIMULADOR DE LINHA TELEFÔNICA</TD>
	<TD><INPUT TYPE="text" NAME="modelo_10" <? if(strlen($modelo_10) > 0) echo "value='$modelo_10'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="qtde_10" <? if(strlen($qtde_10) > 0) echo "value='$qtde_10'"; ?>></TD>
</TR>
<TR>
	<TD>ESTAÇÃO DE SOLDA COM TEMPERATURA CONTROLADA</TD>
	<TD><INPUT TYPE="text" NAME="modelo_11" <? if(strlen($modelo_11) > 0) echo "value='$modelo_11'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="qtde_11" <? if(strlen($qtde_11) > 0) echo "value='$qtde_11'"; ?>></TD>
</TR>
<TR>
	<TD>ESTAÇÃO DE SOLDA A AR QUENTE</TD>
	<TD><INPUT TYPE="text" NAME="modelo_12" <? if(strlen($modelo_12) > 0) echo "value='$modelo_12'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="qtde_12" <? if(strlen($qtde_12) > 0) echo "value='$qtde_12'"; ?>></TD>
</TR>
<TR>
	<TD>PULSEIRA ANTI-ESTÁTICA</TD>
	<TD><INPUT TYPE="text" NAME="modelo_14" <? if(strlen($modelo_14) > 0) echo "value='$modelo_14'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="qtde_14" <? if(strlen($qtde_14) > 0) echo "value='$qtde_14'"; ?>></TD>
</TR>
<TR>
	<TD>MANTA ANTI-ESTÁTICA</TD>
	<TD><INPUT TYPE="text" NAME="modelo_15" <? if(strlen($modelo_15) > 0) echo "value='$modelo_15'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="qtde_15" <? if(strlen($qtde_15) > 0) echo "value='$qtde_15'"; ?>></TD>
</TR>
<TR>
	<TD>FERRO DE SOLDAR</TD>
	<TD><INPUT TYPE="text" NAME="modelo_16" <? if(strlen($modelo_16) > 0) echo "value='$modelo_16'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="qtde_16" <? if(strlen($qtde_16) > 0) echo "value='$qtde_16'"; ?>></TD>
</TR>
<TR>
	<TD>PARAFUSADEIRA</TD>
	<TD><INPUT TYPE="text" NAME="modelo_17" <? if(strlen($modelo_17) > 0) echo "value='$modelo_17'"; ?>></TD>
	<TD><INPUT TYPE="text" NAME="qtde_17" <? if(strlen($qtde_17) > 0) echo "value='$qtde_17'"; ?>></TD>
</TR>
</TABLE>
<br>
<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='1' style='font-size: 12px'>
<TR bgcolor='#9699A0' style='font-weight: bold'>
	<TD width='550'>AS BANCADAS POSSUEM:</TD>
	<TD>SIM</TD>
	<TD>NÂO</TD>
</TR>
<TR>
	<TD>FORRAÇÃO PARA PREVINIR RISCOS NOS APARELHOS</TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_1" value='FORRAÇÃO PARA PREVENIR RISCOS NOS APARELHOS'></TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_1" value='nao'></TD>
</TR>
<TR>
	<TD>DISJUNTOR ELETROMAGNÉTICO</TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_2" value='DISJUNTOR ELETROMAGNÉTICO'></TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_2" value='nao'></TD>
</TR>
<TR>
	<TD>TRANSFORMADOR ISOLADOR</TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_3" value='TRANSFORMADOR ISOLADOR'></TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_3" value='nao'></TD>
</TR>
<TR>
	<TD>EM CASO DE TV, LAMPADA SÉRIE</TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_4" value='EM CASO DE TV, LAMPADA SÉRIE'></TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_4" value='nao'></TD>
</TR>
<TR>
	<TD>ILUMINAÇÃO INDIVIDUAL</TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_5" value='ILUMINAÇÃO INDIVIDUAL'></TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_5" value='nao'></TD>
</TR>
<TR>
	<TD>SUPORTE SUPERIOR PARA INSTRUMENTOS</TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_6" value='SUPORTE SUPERIOR PARA INSTRUMENTOS'></TD>
	<TD><INPUT TYPE="radio" NAME="bancadas_6" value='nao'></TD>
</TR>
</TABLE>
<br>

<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='1' style='font-size: 12px'>
<TR bgcolor='#9699A0' style='font-weight: bold'>
	<TD width='550'>A RECEPÇÂO POSSUI:</TD>
	<TD>SIM</TD>
	<TD>NÂO</TD>
</TR>
<TR>
	<TD>LOCAL E EQUIPAMENTOS ESPECÍFICO PARA TESTES DOS APARELHOS CONSERTADOS</TD>
	<TD><INPUT TYPE="radio" NAME="recpcao_1" value='LOCAL E EQUIPAMENTOS ESPECÍFICO PARA TESTES DOS APARELHOS CONSERTADOS'></TD>
	<TD><INPUT TYPE="radio" NAME="recpcao_1" value='nao'></TD>
</TR>
<TR>
	<TD>LOCAL ESPECÍFICO PARA O CLIENTE ESPERAR</TD>
	<TD><INPUT TYPE="radio" NAME="recpcao_2" value='LOCAL ESPECÍFICO PARA O CLIENTE ESPERAR'></TD>
	<TD><INPUT TYPE="radio" NAME="recpcao_2" value='nao'></TD>
</TR>
<TR>
	<TD>BALCÃO OU LOCAL DE ATENDIMENTO SEPARADO DA OFICINA</TD>
	<TD><INPUT TYPE="radio" NAME="recpcao_3" value='BALCÃO OU LOCAL DE ATENDIMENTO SEPARADO DA OFICINA'></TD>
	<TD><INPUT TYPE="radio" NAME="recpcao_3" value='nao'></TD>
</TR>
</TABLE>
<br>

<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='1' style='font-size: 12px'>
<TR bgcolor='#9699A0' style='font-weight: bold'>
	<TD width='550'>DEPÓSITO DE APARELHOS POSSUI:</TD>
	<TD>SIM</TD>
	<TD>NÂO</TD>
</TR>
<TR>
	<TD>PRATELEIRAS PARA TODOS OS APARELHOS</TD>
	<TD><INPUT TYPE="radio" NAME="deposito_1" value='PRATELEIRAS PARA TODOS OS APARELHOS'></TD>
	<TD><INPUT TYPE="radio" NAME="deposito_1" value='nao'></TD>
</TR>
<TR>
	<TD>AS PRATELEIRAS SÃO FORRADAS PARA EVITAR RISCOS NOS APARELHOS</TD>
	<TD><INPUT TYPE="radio" NAME="deposito_2" value='AS PRATELEIRAS SÃO FORRADAS PARA EVITAR RISCOS NOS APARELHOS'></TD>
	<TD><INPUT TYPE="radio" NAME="deposito_2" value='nao'></TD>
</TR>
<TR>
	<TD>É DIVIDIDO EM ÁREAS COMO : PRONTOS, AG.PEÇA, AG.APROVAÇÃO DE ORÇAMENTO, GARANTIA , ETC.</TD>
	<TD><INPUT TYPE="radio" NAME="deposito_3" value='É DIVIDIDO EM ÁREAS COMO : PRONTOS, AG.PEÇA, AG.APROVAÇÃO DE ORÇAMENTO, GARANTIA , ETC.'></TD>
	<TD><INPUT TYPE="radio" NAME="deposito_3" value='nao'></TD>
</TR>
</TABLE>
<br>

<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='1' style='font-size: 12px'>
<TR bgcolor='#9699A0' style='font-weight: bold'>
	<TD width='550'>O ESTOQUE POSSUI:</TD>
	<TD>SIM</TD>
	<TD>NÂO</TD>
</TR>
<TR>
	<TD>CONTROLES ITEM A ITEM DAS QUANTIDADES</TD>
	<TD><INPUT TYPE="radio" NAME="estoque_1" value='CONTROLES ITEM A ITEM DAS QUANTIDADES'></TD>
	<TD><INPUT TYPE="radio" NAME="estoque_1" value='nao'></TD>
</TR>
<TR>
	<TD>COMPUTADOR EXCLUSIVO PARA USO NO ESTOQUE</TD>
	<TD><INPUT TYPE="radio" NAME="estoque_2" value='COMPUTADOR EXCLUSIVO PARA USO NO ESTOQUE'></TD>
	<TD><INPUT TYPE="radio" NAME="estoque_2" value='nao'></TD>
</TR>
<TR>
	<TD>CONTROLE DE REQUISIÇÕES DE PEÇAS</TD>
	<TD><INPUT TYPE="radio" NAME="estoque_3" value='CONTROLE DE REQUISIÇÕES DE PEÇAS'></TD>
	<TD><INPUT TYPE="radio" NAME="estoque_3" value='nao'></TD>
</TR>
<TR>
	<TD>ACOMODAÇÃO CORRETA DOS COMPONENTES</TD>
	<TD><INPUT TYPE="radio" NAME="estoque_4" value='ACOMODAÇÃO CORRETA DOS COMPONENTES'></TD>
	<TD><INPUT TYPE="radio" NAME="estoque_4" value='nao'></TD>
</TR>
</TABLE>
<br>

<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='1' style='font-size: 12px'>
<TR align='center' bgcolor='#9699A0' style='font-weight: bold'>
	<TD>ÁREA TÉCNICA</TD>
</TR>
<TR>
	<TD>
		TÉCNICOS FORMADOS EM :( DESCREVA)<br>
		<TEXTAREA NAME="tecnicos_formados" ROWS="3" COLS="70"></TEXTAREA>
	</TD>
</TR>
<TR>
	<TD>
		POSSUI UM TÉCNICO PARA CADA ÁREA (áudio, vídeo, etc)<br>
		<TEXTAREA NAME="tecnicos_cada_area" ROWS="3" COLS="70"></TEXTAREA>
	</TD>
</TR>
<TR>
	<TD>
		QUANTOS TÉCNICOS ?<br>
		<TEXTAREA NAME="tecnicos_qtde" ROWS="1" COLS="70"></TEXTAREA>
	</TD>
</TR>
<TR>
	<TD>
		TREINAMENTOS QUE OS TÉCNICOS JÁ FIZERAM:( DESCREVA)<br>
		<TEXTAREA NAME="tecnicos_treinados" ROWS="3" COLS="70"></TEXTAREA>
	</TD>
</TR>
</TABLE>

<br>

<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='0' style='font-size: 12px; font-weight: bold;'>
<TR>
	<TD align='center' style='font-size: 18'><br>Termo de Aceitação</TD>
</TR>
<TR>
	<TD><p align='justify'>
		<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Estamos de acordo com as condições exigidas e apresentamos a documentação acessória 
		para sua avaliação, tendo em vista a manisfestação do nosso interesse e possuirmos  ferramentas
		adequadas e pessoal capacitado para prestar serviço DE AUDIO E VIDEO.
		</p>
	</TD>
</TR>
<TR align='center'>
	<TD><br>
	<INPUT TYPE="radio" NAME="termo" value='sim' <? if($termo == 'sim') echo "checked"; ?>>Sim
	<INPUT TYPE="radio" NAME="termo" value='nao' <? if($termo == 'nao') echo "checked"; ?>>Não
	</TD>
</TR>
</TABLE>


<br>

<TABLE align='center' width='600'>
<TR>
	<TD align='left'>
		Data da Avaliação<br>
		<?echo date('d/m/Y');?>

	</TD>
	<TD align='right'>Nome do responsável<br>
		<INPUT TYPE="text" NAME="assinatura" value='<?echo $assinatura;?>'>

	</TD>
</TR>
</TABLE>





<p align='center'><INPUT TYPE="submit" name='Enviar' value='Enviar'></p>

</FORM>


</BODY>
</HTML>

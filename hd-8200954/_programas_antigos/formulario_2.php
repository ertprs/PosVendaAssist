<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE> New Document </TITLE>
<META NAME="Generator" CONTENT="EditPlus">
<META NAME="Author" CONTENT="">
<META NAME="Keywords" CONTENT="">
<META NAME="Description" CONTENT="">
</HEAD>


<?

if(strlen($_POST['razao_social']) > 0) $razao_social = $_POST['razao_social'];
else $msg_erro = "Digite a razão social";

//Identificação da Empresa
if(strlen($_POST['cnpj']) > 0) $cnpj = $_POST['cnpj'];
else $msg_erro = "Digite o CNPJ";

if(strlen($_POST['ie']) > 0) $ie = $_POST['ie'];
else $msg_erro = "Digite a I.E";

if(strlen($_POST['nome_fantasia']) > 0) $nome_fantasia = $_POST['nome_fantasia'];
else $msg_erro = "Digite o nome fantasia";

if(strlen($_POST['data_fundacao']) > 0) $data_fundacao = $_POST['data_fundacao'];
else $msg_erro = "Digite a data da fundacao";

if(strlen($_POST['endereco']) > 0) $endereco = $_POST['endereco'];
else $msg_erro = "Digite o endereço";

if(strlen($_POST['bairro']) > 0) $bairro = $_POST['bairro'];
else $msg_erro = "Digite o bairro";

if(strlen($_POST['cidade_estado']) > 0) $cidade_estado = $_POST['cidade_estado'];
else $msg_erro = "Digite a cidade/ UF";

if(strlen($_POST['cep']) > 0) $cep = $_POST['cep'];
else $msg_erro = "Digite o CEP";

if(strlen($_POST['fone']) > 0) $fone = $_POST['fone'];
else $msg_erro = "Digite o Fone";

if(strlen($_POST['email']) > 0) $email = $_POST['email'];
else $msg_erro = "Digite o Email";

//Informações do Banco
if(strlen($_POST['nome_cliente']) > 0) $nome_cliente = $_POST['nome_cliente'];
else $msg_erro = "Digite o nome do cliente";

if(strlen($_POST['banco']) > 0) $banco = $_POST['banco'];
else $msg_erro = "Digite o banco";

if(strlen($_POST['agencia']) > 0) $agencia = $_POST['agencia'];
else $msg_erro = "Digite a agência";

if(strlen($_POST['conta_corrente']) > 0) $conta_corrente = $_POST['conta_corrente'];
else $msg_erro = "Digite a conta corrente";


//Informações complementares
$audio_video     = $_POST['audio_video'];
$eletroportateis = $_POST['eletroportateis'];
$branca = $_POST['branca'];

if(strlen($_POST['tecnico_responsavel']) > 0) $conta_corrente = $_POST['tecnico_responsavel'];
else $msg_erro = "Digite o nome do Técnico Responsável";

if(strlen($_POST['marcas_presta_servico']) > 0) $conta_corrente = $_POST['marcas_presta_servico'];
else $msg_erro = "Digite as marcas que seu posto presta serviço";

//Componentes da Empresa
if(strlen($_POST['comp_nome_1']) > 0) $comp_nome_1   = $_POST['comp_nome_1'];
if(strlen($_POST['comp_cpf_1']) > 0) $comp_cpf_1     = $_POST['comp_cpf_1'];
if(strlen($_POST['comp_cargo_1']) > 0) $comp_cargo_1 = $_POST['comp_cargo_1'];
if(strlen($_POST['comp_part_1']) > 0) $comp_part_1   = $_POST['comp_part_1'];

if(strlen($_POST['comp_nome_2']) > 0) $comp_nome_2   = $_POST['comp_nome_2'];
if(strlen($_POST['comp_cpf_2']) > 0) $comp_cpf_2     = $_POST['comp_cpf_2'];
if(strlen($_POST['comp_cargo_2']) > 0) $comp_cargo_2 = $_POST['comp_cargo_2'];
if(strlen($_POST['comp_part_2']) > 0) $comp_part_2   = $_POST['comp_part_2'];

if(strlen($_POST['comp_nome_3']) > 0)  $comp_nome_3    = $_POST['comp_nome_3'];
if(strlen($_POST['comp_cpf_3']) > 0)   $comp_cpf_3     = $_POST['comp_cpf_3'];
if(strlen($_POST['comp_cargo_3']) > 0) $comp_cargo_3   = $_POST['comp_cargo_3'];
if(strlen($_POST['comp_part_3']) > 0)  $comp_part_3     = $_POST['comp_part_3'];

//Fontes de referencia
if(strlen($_POST['aramoveis']) > 0)      $aramoveis   = $_POST['aramoveis'];
if(strlen($_POST['caloi']) > 0)          $caloi       = $_POST['caloi'];
if(strlen($_POST['fame']) > 0)           $fame        = $_POST['fame'];
if(strlen($_POST['rudnick']) > 0)        $rudnick     = $_POST['rudnick'];
if(strlen($_POST['atlas']) > 0)          $atlas       = $_POST['atlas'];
if(strlen($_POST['castor']) > 0)         $castor      = $_POST['castor'];
if(strlen($_POST['bem']) > 0)            $bem         = $_POST['bem'];
if(strlen($_POST['semp_toshiba']) > 0)   $semp_toshiba= $_POST['semp_toshiba'];
if(strlen($_POST['bergamo']) > 0)        $bergamo     = $_POST['bergamo'];
if(strlen($_POST['esmaltec']) > 0)       $esmaltec    = $_POST['esmaltec'];
if(strlen($_POST['ponto_frio']) > 0)     $ponto_frio  = $_POST['ponto_frio'];
if(strlen($_POST['suggar']) > 0)         $suggar      = $_POST['suggar'];
if(strlen($_POST['todeschini']) > 0)     $todeschini  = $_POST['todeschini'];
if(strlen($_POST['ortocrin']) > 0)       $ortocrin    = $_POST['ortocrin'];
if(strlen($_POST['latinatec']) > 0)      $latinatec   = $_POST['latinatec'];
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
if(strlen($_POST['termo']) > 0) $termo = $_POST['termo'];
else $msg_erro = "Assinale o Termo de Aceitação";


if(strlen($msg_erro) > 0){?>
<TABLE bgcolor='#FF3300' align='center' style='font-size: 14px; color: #FFFFFF; font-weight: bold;'>
<TR>
	<TD><? echo "$msg_erro"; ?></TD>
</TR>
</TABLE>
<?}?>

<BODY>

<FORM METHOD=POST ACTION="<?$PHP_SELF?>">

<TABLE border='0' align='center' width='600' style='font-family: verdana; '>
<TR>
	<TD align='center'><b>FICHA CADASTRO POSTOS AUTORIZADOS</b></TD>
</TR>
</TABLE>
<br>

<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='0' style='font-size: 12px; font-weight: bold;'>
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
<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='0' style='font-size: 12px; font-weight: bold;'>
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

<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='0' style='font-size: 12px; font-weight: bold;'>
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
	<TD>&nbsp;<INPUT TYPE="text" NAME="tecnico_responsavel" size='50' <? if(strlen($responsavel) > 0) echo "value='$responsavel'"; ?>></TD>
</TR>
<TR>
	<TD align='right' bgcolor='#C0C2C7'>Marcas p/ as quais Presto Serviço Autorizado</TD>
	<TD>&nbsp;<INPUT TYPE="text" NAME="marcas_presta_servico" size='50' <? if(strlen($marcas_presta_servico) > 0) echo "value='$marcas_presta_servico'"; ?>></TD>
</TR>
</TABLE>
<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='0' style='font-size: 12px; font-weight: bold;'>
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
<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='0' style='font-size: 12px; font-weight: bold;'>
<TR>
	<TD colspan='4' style='font-size: 14'>Fontes de Referência</TD>
</TR>
<TR>
	<TD bgcolor='#C0C2C7'>Aramóveis</TD>
	<TD><INPUT TYPE="checkbox" NAME="aramoveis" <? if(strlen($aramoveis) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7' >Caloi</TD>
	<TD><INPUT TYPE="checkbox" NAME="caloi" <? if(strlen($caloi) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>Fame</TD>
	<TD><INPUT TYPE="checkbox" NAME="fame" <? if(strlen($fame) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>Rudnick</TD>
	<TD><INPUT TYPE="checkbox" NAME="rudnick" <? if(strlen($rudnick) > 0) echo "checked"; ?>></TD>
</TR>
<TR>
	<TD bgcolor='#C0C2C7'>Atlas</TD>
	<TD><INPUT TYPE="checkbox" NAME="atlas" <? if(strlen($atlas) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>Castor</TD>
	<TD><INPUT TYPE="checkbox" NAME="castor" <? if(strlen($castor) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>B&M</TD>
	<TD><INPUT TYPE="checkbox" NAME="bem" <? if(strlen($bem) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>Semp Toshiba</TD>
	<TD><INPUT TYPE="checkbox" NAME="semp_toshiba" <? if(strlen($semp_toshiba) > 0) echo "checked"; ?>></TD>
</TR>
<TR>
	<TD bgcolor='#C0C2C7'>Bergamo</TD>
	<TD><INPUT TYPE="checkbox" NAME="bergamo" <? if(strlen($bergamo) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>Esmaltec</TD>
	<TD><INPUT TYPE="checkbox" NAME="esmaltec" <? if(strlen($esmaltec) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>Ponto Frio</TD>
	<TD><INPUT TYPE="checkbox" NAME="ponto_frio" <? if(strlen($ponto_frio) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>Semp Toshiba</TD>
	<TD><INPUT TYPE="checkbox" NAME="suggar" <? if(strlen($suggar) > 0) echo "checked"; ?>></TD>
</TR>
<TR>
	<TD bgcolor='#C0C2C7'>Todeschini</TD>
	<TD><INPUT TYPE="checkbox" NAME="todeschini" <? if(strlen($todeschini) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>Orthocrin</TD>
	<TD><INPUT TYPE="checkbox" NAME="orthocrin" <? if(strlen($orthochin) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>Latinatec</TD>
	<TD><INPUT TYPE="checkbox" NAME="latinatec" <? if(strlen($latinatec) > 0) echo "checked"; ?>></TD>
	<TD bgcolor='#C0C2C7'>BS Continental</TD>
	<TD><INPUT TYPE="checkbox" NAME="bs_continental" <? if(strlen($bs_continental) > 0) echo "checked"; ?>></TD>
</TR>
</TABLE>
<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='0' style='font-size: 12px; font-weight: bold;'>
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

<TABLE width='600' align='center' cellspacing='0' cellpadding='0' border='0' style='font-size: 12px; font-weight: bold;'>
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
<br><br>

<p align='center'><INPUT TYPE="submit" name='formulario_1' value='Enviar'></p>

</FORM>
</BODY>
</HTML>

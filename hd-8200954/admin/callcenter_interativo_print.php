<?
//as tabs definem a categoria do chamado
/* OBSERVACAO HBTECH
	* O produto Hibeats possui uma garantia estendida, ou seja, 1 ano de garantia normal e se ele entrar no site do hibeats ou solicitar via SAC a extensão o cliente ganha mais 6 meses de garantia ficando com 18 meses.
	* Para verificar os produtos que tem garantia estendida acessamos o bd do hibeats (conexao_hbflex.php) e verificamos o número de série.
		* Todos numeros de series vendidos estao no bd do hibeats, caso nao esteja lá não foi vendido ou a AKabuki não deu carga no bd.
		* AKabuki é a agencia que toma conta do site da hbflex, responsavel pelo bd e atualizacao do bd. Contato:
			Allan Rodrigues
			Programador
			AGÊNCIA KABUKI
			* allan@akabuki.com.br
			* www.akabuki.com.br
			( 55 11 3871-9976
	** Acompanhar os lancamentos destas garantias, liberado no ultimo dia do ano e ainda estamos acompanhando

*/
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
$admin_privilegios="call_center";
include 'autentica_admin.php';


$title = "Atendimento Call-Center";
$layout_menu = 'callcenter';

include 'funcoes.php';
function converte_data($date)
{
	$date = explode("-", preg_replace('/\//', '-', $date));
	$date2 = ''.$date[2].'-'.$date[1].'-'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}


function saudacao(){
	$hora = date("H");
	if($hora >= 7 and $hora <= 11){
		echo "bom dia";
	}
	if($hora>=12 and $hora <= 17){
		echo "boa tarde";
	}
	if($hora>=18){
		echo "boa noite";
	}
}

$callcenter = $_GET['callcenter'];
if(strlen($callcenter)>0){

	$sql = "SELECT	tbl_hd_chamado_extra.hd_chamado as callcenter,
					to_char(tbl_hd_chamado_extra.data_abertura,'DD/MM/YYYY') as abertura_callcenter,
					tbl_hd_chamado_extra.nome,
					tbl_hd_chamado_extra.endereco ,
					tbl_hd_chamado_extra.numero ,
					tbl_hd_chamado_extra.complemento ,
					tbl_hd_chamado_extra.bairro ,
					tbl_hd_chamado_extra.cep ,
                    tbl_hd_chamado_extra.fone ,
                    tbl_hd_chamado_extra.fone2 ,
					tbl_hd_chamado_extra.celular ,
					tbl_hd_chamado_extra.email ,
					tbl_hd_chamado_extra.cpf ,
					tbl_hd_chamado_extra.rg ,
					tbl_hd_chamado_extra.cliente ,
					tbl_cidade.nome as cidade_nome,
					tbl_cidade.estado,
					tbl_admin.login as atendente,
					to_char(tbl_hd_chamado.data,'DD/MM/YYYY') as data,
					tbl_hd_chamado.status,
					tbl_hd_chamado.categoria as natureza_operacao,
					tbl_posto.posto,
					tbl_posto_fabrica.contato_endereco,
					tbl_posto_fabrica.contato_numero,
					tbl_posto_fabrica.contato_bairro,
					tbl_posto_fabrica.contato_cidade,
					tbl_posto_fabrica.contato_cep,
					tbl_posto_fabrica.contato_estado,
					tbl_posto_fabrica.contato_email as posto_email,
					tbl_posto_fabrica.contato_fone_comercial as posto_fone,
					tbl_hd_chamado.titulo as assunto,
					tbl_hd_chamado_extra.revenda_nome,
					tbl_produto.produto,
					tbl_produto.referencia as produto_referencia,
					tbl_produto.descricao as produto_nome,
					tbl_produto.voltagem,
					tbl_produto.familia,
					tbl_defeito_reclamado.defeito_reclamado,
					tbl_defeito_reclamado.descricao as defeito_reclamado_descricao,
					tbl_hd_chamado_extra.reclamado,
					tbl_hd_chamado_extra.os,
					tbl_hd_chamado_extra.serie,
					to_char(tbl_hd_chamado_extra.data_nf,'DD/MM/YYYY') as data_nf,
					tbl_hd_chamado_extra.nota_fiscal,
					tbl_hd_chamado_extra.revenda,
					tbl_hd_chamado_extra.revenda_nome,
					tbl_posto_fabrica.codigo_posto,
					tbl_posto.nome as posto_nome,
					to_char(tbl_hd_chamado_extra.data_abertura_os,'DD/MM/YYYY') as data_abertura,
					tbl_hd_chamado_extra.receber_info_fabrica,
					tbl_os.sua_os as sua_os,
					tbl_hd_chamado_extra.consumidor_revenda,
					tbl_hd_chamado_extra.array_campos_adicionais,
					tbl_hd_motivo_ligacao.descricao AS hd_motivo_ligacao

		FROM tbl_hd_chamado
		JOIN tbl_hd_chamado_extra on tbl_hd_chamado.hd_chamado = tbl_hd_chamado_extra.hd_chamado
		LEFT JOIN tbl_cidade on tbl_hd_chamado_extra.cidade = tbl_cidade.cidade
		JOIN tbl_admin  on tbl_hd_chamado.atendente = tbl_admin.admin
		LEFT JOIN tbl_posto on tbl_hd_chamado_extra.posto = tbl_posto.posto
		LEFT JOIN tbl_posto_fabrica on tbl_posto.posto = tbl_posto_fabrica.posto  and tbl_posto_fabrica.fabrica = $login_fabrica
		LEFT JOIN tbl_produto on tbl_produto.produto = tbl_hd_chamado_extra.produto
		LEFT JOIN tbl_revenda on tbl_revenda.revenda = tbl_hd_chamado_extra.revenda
		LEFT JOIN tbl_defeito_reclamado on tbl_defeito_reclamado.defeito_reclamado = tbl_hd_chamado_extra.defeito_reclamado
		LEFT JOIN tbl_os on tbl_os.os = tbl_hd_chamado_extra.os and tbl_os.fabrica = $login_fabrica
		LEFT JOIN tbl_hd_motivo_ligacao ON tbl_hd_chamado_extra.hd_motivo_ligacao = tbl_hd_motivo_ligacao.hd_motivo_ligacao AND tbl_hd_motivo_ligacao.fabrica = $login_fabrica
		WHERE tbl_hd_chamado.fabrica = $login_fabrica
		AND tbl_hd_chamado.fabrica_responsavel = $login_fabrica
		AND tbl_hd_chamado.hd_chamado = $callcenter";
		$res = pg_exec($con,$sql);

		if(pg_numrows($res)>0){
			$callcenter                = pg_result($res,0,callcenter);
			$abertura_callcenter       = pg_result($res,0,abertura_callcenter);
			$data_abertura_callcenter  = pg_result($res,0,data);
			$natureza_chamado          = pg_result($res,0,natureza_operacao);
			$consumidor_nome           = pg_result($res,0,nome);
			$cliente                   = pg_result($res,0,cliente);
			$consumidor_cpf            = pg_result($res,0,cpf);
			$consumidor_rg             = pg_result($res,0,rg);
			$consumidor_email          = pg_result($res,0,email);
            $consumidor_fone           = pg_result($res,0,fone);
            $consumidor_fone2         = pg_result($res,0,fone2);
			$consumidor_celular       = pg_result($res,0,celular);
			$consumidor_cep           = pg_result($res,0,cep);
			$consumidor_endereco      = pg_result($res,0,endereco);
			$consumidor_numero        = pg_result($res,0,numero);
			$consumidor_complemento   = pg_result($res,0,complemento);
			$consumidor_bairro        = pg_result($res,0,bairro);
			$consumidor_cidade        = pg_result($res,0,cidade_nome);
			$consumidor_estado        = pg_result($res,0,estado);
			$assunto                  = pg_result($res,0,assunto);
			$sua_os                   = pg_result($res,0,sua_os);
			$os                       = pg_result($res,0,os);
			$data_abertura            = pg_result($res,0,data_abertura);
			$produto                  = pg_result($res,0,produto);
			$produto_referencia       = pg_result($res,0,produto_referencia);
			$produto_nome             = pg_result($res,0,produto_nome);
			$voltagem                 = pg_result($res,0,voltagem);
			$serie                    = pg_result($res,0,serie);
			$data_nf                  = pg_result($res,0,data_nf);
			$nota_fiscal              = pg_result($res,0,nota_fiscal);
			$revenda                  = pg_result($res,0,revenda);
			$revenda_nome             = pg_result($res,0,revenda_nome);
			$posto                    = pg_result($res,0,posto);
			$posto_nome               = pg_result($res,0,posto_nome);
			$defeito_reclamado        = pg_result($res,0,defeito_reclamado);
			$reclamado                = pg_result($res,0,reclamado);
			$status_interacao         = pg_result($res,0,status);
			$atendente                = pg_result($res,0,atendente);
			$receber_informacoes      = pg_result($res,0,receber_info_fabrica);
			$codigo_posto             = pg_result($res,0,codigo_posto);
			$os                       = pg_result($res,0,os);
			$contato_endereco         = pg_result($res,0,contato_endereco);
			$contato_numero           = pg_result($res,0,contato_numero);
			$contato_bairro           = pg_result($res,0,contato_bairro);
			$contato_cidade           = pg_result($res,0,contato_cidade);
			$contato_estado           = pg_result($res,0,contato_estado);
			$contato_cep              = pg_result($res,0,contato_cep);
			$posto_fone               = pg_result($res,0,posto_fone);
			$posto_email              = pg_result($res,0,posto_email);
			$familia                  = pg_result($res,0,familia);
			$consumidor_revenda       = pg_result($res,0,consumidor_revenda);
			$hd_motivo_ligacao        = utf8_decode(pg_result($res,0,hd_motivo_ligacao));
			$array_campos_adicionais        = json_decode(pg_result($res,0,array_campos_adicionais),1);
			if ($login_fabrica == 189) {
				$codigo_cliente_revenda = $array_campos_adicionais['codigo_cliente_revenda'];

			}
			$produto_descricao = $produto_referencia." - ".$produto_nome;

			$tipo_atendimento = array(1 => 'extensao', 2 => 'reclamacao_produto', 3 => 'reclamacao_empresa',
				4 => 'reclamacao_at',5 => 'duvida_produto', 6 => 'sugestao', 7 => 'assistencia', 8 => 'garantia', 9 => 'troca_produto');

			if($natureza_chamado=='reclamacao_produto') $natureza_chamado = "Reclamação do Produto";
			if($natureza_chamado=='duvida_produto')     $natureza_chamado = "Dúvida do Produto";
			if($natureza_chamado=='reclamacao_empresa') $natureza_chamado = "Reclamação da Empresa";
			if($natureza_chamado=='reclamacao_produto') $natureza_chamado = "Reclamação do Produto";
			if($natureza_chamado=='reclamacao_at')      $natureza_chamado = "Reclamação da Assistência Técnica";
			if($natureza_chamado=='duvida_produto')     $natureza_chamado = "Dúvida do Produto";
			if($natureza_chamado=='troca_produto')      $natureza_chamado = "Troca de Produto";
			if($natureza_chamado=='extensao')           $natureza_chamado = "Extensão de Garantia";
			if($natureza_chamado=='sugestao')           $natureza_chamado = "Sugestão";
			if($natureza_chamado=='compra')             $natureza_chamado = "Onde Comprar";

		}

}
$cliente_novo = $_GET['cliente_novo'];
if(strlen($cliente_novo)>0){
	$sql = "select tbl_hd_chamado_extra.cliente          ,
					tbl_hd_chamado_extra.nome            ,
					tbl_hd_chamado_extra.endereco        ,
					tbl_hd_chamado_extra.numero          ,
					tbl_hd_chamado_extra.complemento     ,
					tbl_hd_chamado_extra.bairro          ,
					tbl_hd_chamado_extra.cep             ,
                    tbl_hd_chamado_extra.fone            ,
                    tbl_hd_chamado_extra.fone2           ,
					tbl_hd_chamado_extra.celular         ,
					tbl_hd_chamado_extra.email           ,
					tbl_hd_chamado_extra.cpf             ,
					tbl_hd_chamado_extra.rg              ,
					tbl_cidade.nome as nome_cidade       ,
					tbl_cidade.estado as estado_cidade   ,
					tbl_posto_fabrica.codigo_posto       ,
					tbl_posto.nome as posto_nome         ,
					tbl_posto_fabrica.contato_endereco   ,
					tbl_posto_fabrica.contato_numero     ,
					tbl_posto_fabrica.contato_bairro     ,
					tbl_posto_fabrica.contato_cidade     ,
					tbl_posto_fabrica.contato_cep        ,
					tbl_posto_fabrica.contato_estado     ,
					tbl_posto.fone as posto_fone         ,
					tbl_posto.email as posto_email
			from tbl_hd_chamado_extra
			JOIN tbl_cidade using(cidade)
			LEFT JOIN tbl_posto_fabrica USING(posto)
			LEFT JOIN tbl_posto ON tbl_posto.posto = tbl_posto_fabrica.posto
			WHERE tbl_hd_chamado_extra.cliente = $cliente_novo
			AND   tbl_posto_fabrica.fabrica = $login_fabrica";
	$res = pg_exec($con,$sql);

	if(pg_numrows($res)>0){
			$consumidor_nome          = pg_result($res,0,nome);
			$cliente                  = pg_result($res,0,cliente);
			$consumidor_cpf           = pg_result($res,0,cpf);
			$consumidor_rg            = pg_result($res,0,rg);
			$consumidor_email         = pg_result($res,0,email);
            $consumidor_fone          = pg_result($res,0,fone);
            $consumidor_fone2         = pg_result($res,0,fone2);
			$consumidor_celular       = pg_result($res,0,celular);
			$consumidor_cep           = pg_result($res,0,cep);
			$consumidor_endereco      = pg_result($res,0,endereco);
			$consumidor_numero        = pg_result($res,0,numero);
			$consumidor_complemento   = pg_result($res,0,complemento);
			$consumidor_bairro        = pg_result($res,0,bairro);
			$consumidor_cidade        = pg_result($res,0,nome_cidade);
			$consumidor_estado        = pg_result($res,0,estado_cidade);
			$codigo_posto             = pg_result($res,0,codigo_posto);
			$posto_nome               = pg_result($res,0,posto_nome);
			$contato_endereco         = pg_result($res,0,contato_endereco);
			$contato_numero           = pg_result($res,0,contato_numero);
			$contato_bairro           = pg_result($res,0,contato_bairro);
			$contato_cidade           = pg_result($res,0,contato_cidade);
			$contato_estado           = pg_result($res,0,contato_estado);
			$contato_cep              = pg_result($res,0,contato_cep);
			$posto_email              = pg_result($res,0,posto_email);
			$posto_fone               = pg_result($res,0,posto_fone);
	}
}

//include "cabecalho.php";

if ($login_fabrica == 30) {

	$img_contrato = "../logos/cabecalho_print_".strtolower ($login_fabrica_nome).".gif";

	if ($familia == 2680 || $familia == 2681) {//HD 246018
		$img_contrato = "../logos/cabecalho_print_itatiaia.jpg";
	}

}

/*HD - 4258409*/
if ($login_fabrica == 85 && !empty($callcenter)) {
	$aux_sql = "SELECT array_campos_adicionais FROM tbl_hd_chamado_extra WHERE hd_chamado = $callcenter LIMIT 1";
	$aux_res = pg_query($con, $aux_sql);
	$rows    = pg_num_rows($aux_res);

	if ($rows > 0) {
		$array_adicionais = pg_fetch_result($aux_res, 0, 'array_campos_adicionais');
		$array_adicionais = json_decode($array_adicionais, true);
		
		if (!empty($array_adicionais["tecnico_esporadico_id"])) {
			$exibir_esporadico = true;

			$aux_sql = "SELECT codigo_externo, nome FROM tbl_tecnico WHERE tecnico = ". $array_adicionais["tecnico_esporadico_id"] ."LIMIT 1";
			$aux_res = pg_query($con, $aux_sql);

			$tecnico_esporadico["codigo"] = pg_fetch_result($aux_res, 0, 'codigo_externo');
			$tecnico_esporadico["nome"]   = pg_fetch_result($aux_res, 0, 'nome');
			$tecnico_esporadico["valor"]  = "R$ ". str_replace(".", ",", $array_adicionais["valor_servico_combinado"]);
		}
	}
}

$sql = "SELECT nome from tbl_fabrica where fabrica = $login_fabrica";
$res = pg_exec($con,$sql);
$nome_da_fabrica = pg_result($res,0,0);

echo "<table style='text-align:left;font-size:10Px;font-family:Verdana;width:600px;' cellspacing='2'>";
echo "<caption style='border-bottom:1px #000000 solid;font-weight:bold;text-transform:uppercase;text-align:left;'>Callcenter N° $callcenter</caption>";
if ($login_fabrica == 30) {
	echo "<tr>";
	echo '<td colspan="4"><img src="'.$img_contrato.'" height="40" alt="Logo"></td>';
	echo "</tr>";
}
echo "<tr>";
echo "<th>Data:</th>";
echo "<td>$data_abertura_callcenter</td>";
echo "<th>Status:</th>";
echo "<td>$status_interacao</td>";

echo "<th>Atendente:</th>";
echo "<td>".strtoupper($atendente)."</td>";

echo "</tr>";
echo "</table>";

echo "<br><table style='text-align:left;font-size:10Px;font-family:Verdana;width:600px;' cellspacing='2'>";
echo "<caption style='border-bottom:1px #000000 solid;font-weight:bold;text-transform:uppercase;text-align:left;'>Informações do Consumidor</caption>";
echo "<tr>";

echo "<th>Nome:</th>";
echo "<td colspan='".(($login_fabrica == 189) ? 4 : 7 )."'>$consumidor_nome</td>";
if ($login_fabrica == 189) {

echo "<th>Cód. Cliente:</th>";
echo "<td>$codigo_cliente_revenda</td>";
}

echo "</tr>";

	$cpf_cnpj = (strlen($consumidor_cpf) > 11) ? "CNPJ:" : "CPF:";

	echo "<tr>";
	echo "<th>$cpf_cnpj</th>";
	echo "<td nowrap>$consumidor_cpf</td>";
	echo "<th>Telefone:</th>";
	echo "<td nowrap>$consumidor_fone</td>";
	echo "<th>Email:</th>";
	echo "<td nowrap colspan='3'>$consumidor_email</td>";
	echo "</tr>";

echo "<tr>";
echo "<th>Endereço:</th>";
echo "<td  colspan='7'>$consumidor_endereco, $consumidor_numero $consumidor_complemento</td>";
echo "</tr>";
echo "<tr>";
echo "<th>Bairro:</th>";
echo "<td>$consumidor_bairro</td>";
echo "<th>Cidade:</th>";
echo "<td nowrap>$consumidor_cidade</td>";
echo "<th>Estado:</th>";
echo "<td>$consumidor_estado</td>";
echo "<th>CEP:</th>";
echo "<td>$consumidor_cep </td>";
echo "</tr>";

echo "<tr>";
echo "<th>Telefone Comercial:</th>";
echo "<td>$consumidor_fone2</td>";
echo "<th>Celular:</th>";
echo "<td colspan='5'>$consumidor_celular</td>";
echo "</tr>";
if(($login_fabrica == 86 || $login_fabrica == 90) AND(!empty($hd_motivo_ligacao) OR !empty($consumidor_revenda))){
	switch ($consumidor_revenda) {
		case 'R':
			$consumidor_revenda = 'Revenda';
			break;
		case 'C':
			$consumidor_revenda = 'Consumidor';
			break;
		case 'P':
			$consumidor_revenda = 'PA';
			break;
		case 'N':
			$consumidor_revenda = 'Consultor';
			break;
		case 'T':
			$consumidor_revenda = 'Representante';
			break;
	}
	echo "<tr>";
	echo "<th>Tipo:</th>";
	echo "<td>$consumidor_revenda</td>";
	if(!empty($hd_motivo_ligacao)){
		echo "<th>Motivo:</th>";
	echo "<td>$hd_motivo_ligacao</td>";
	}
	echo "</tr>";
}
echo "</table>";

echo "<br><table style='text-align:left;font-size:10Px;font-family:Verdana;width:600px;' cellspacing='2'>";
echo "<caption style='border-bottom:1px #000000 solid;font-weight:bold;text-transform:uppercase;text-align:left;'>Informações do Produto</caption>";
if (!in_array($login_fabrica, [52,189])) {
	echo "<tr>";
	echo "<th>Produto:</th>";
	echo "<td colspan='3'>$produto_descricao</td>";
	echo "</tr>";

	echo "<tr>";
		echo "<th>Produto:</th>";
		echo "<td>$produto_nome - Ref: $produto_referencia</td>";
		if ($login_fabrica ==59){
			echo "</tr><tr>";
			echo "<th>Voltagem:</th>";
			echo "<td>$voltagem</td>";
		}
		if ($login_fabrica != 145) {
			echo "<th>Série:</th>";
			echo "<td>$serie</td>";
		}
	echo "</tr>";

	echo "<tr>";
		echo "<th>Nota Fiscal:</th>";
		echo "<td>$nota_fiscal</td>";
		echo "<th>Data NF:<th>";
		echo "<td>$data_nf</td>";
	echo "</tr>";

	if(strlen($revenda) > 0){
		$sql = "SELECT cnpj FROM tbl_revenda WHERE revenda = $revenda";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res) > 0){
			$revenda = pg_result($res,0,cnpj);
		}
	}

	if($login_fabrica != 24){

		echo "<tr>";
			echo "<th>Revenda:</th>";
			echo "<td>$revenda_nome</td>";
			echo "<th>CNPJ da Revenda:</th>";
			echo "<td>$revenda</td>";
		echo "</tr>";

	}
}
else {

	if (in_array($login_fabrica, [189])) {

		$sql_produto = "SELECT serie,
		                       tbl_produto.referencia,
		                       tbl_produto.descricao as descricao_produto,
		                       to_char(tbl_hd_chamado_item.data_nf, 'DD/MM/YYYY') as data_nf,
                               tbl_hd_chamado_item.nota_fiscal,
                               tbl_hd_chamado_item.qtde,
		                       tbl_hd_chamado_item.tincaso AS lote
		                  FROM tbl_hd_chamado_item 
		                  JOIN tbl_produto USING(produto) 
		                  WHERE hd_chamado = $callcenter";
		$res_produto = pg_exec($con,$sql_produto);

		if (pg_num_rows($res_produto)>0) {
			for ($i=0;$i<pg_num_rows($res_produto);$i++) {
				$descricao_produto 	= pg_result($res_produto,$i,descricao_produto);
				$referencia_produto = pg_result($res_produto,$i,referencia);
				$lote 				= pg_result($res_produto,$i,lote);
				$data_nf 			= pg_result($res_produto,$i,data_nf);
				$nota_fiscal 		= pg_result($res_produto,$i,nota_fiscal);
				$qtde      		    = pg_result($res_produto,$i,qtde);
				echo "<tr>";
				echo "<th>Nota Fiscal:</th>";
				echo "<td>$nota_fiscal</td>";
				echo "<th>Data NF:<th>";
				echo "<td>$data_nf</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<th>Produto:</th>";
				echo "<td>$referencia_produto - $descricao_produto</td>";
				echo "<th>Qtde:</th>";
				echo "<td>$qtde</td>";
				echo "<th>Lote:</th>";
				echo "<td>$lote</td>";
				echo "</tr>";
				echo "<tr>";
				echo "<td colspan='100%' style='width:100%;border-bottom:solid 1px #ccc;'>&nbsp; </td>";
				echo "</tr>";
			}
		}

	} else {
		echo "<tr>";
		echo "<th>Nota Fiscal:</th>";
		echo "<td>$nota_fiscal</td>";
		echo "<th>Data NF:<th>";
		echo "<td>$data_nf</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td>Série</dh>";
		echo "<td>Produto</td>";
		echo "<td>Defeito Reclamado</td>";
		echo "</tr>";

		$sql_produto = "SELECT serie,tbl_produto.referencia,tbl_produto.descricao as descricao_produto,tbl_defeito_reclamado.descricao as descricao_defeito from tbl_hd_chamado_item join tbl_produto using(produto) join tbl_defeito_reclamado using(defeito_reclamado) where hd_chamado = $callcenter";
		$res_produto = pg_exec($con,$sql_produto);

		if (pg_num_rows($res_produto)>0) {
			for ($i=0;$i<pg_num_rows($res_produto);$i++) {
				$descricao_produto = pg_result($res_produto,$i,descricao_produto);
				$referencia_produto = pg_result($res_produto,$i,referencia);
				$descricao_defeito = pg_result($res_produto,$i,descricao_defeito);
				$serie= pg_result($res_produto,$i,serie);

				echo "<tr>";
				echo "<td>$serie</td>";
				echo "<td>$referencia_produto - $descricao_produto</td>";
				echo "<td>$descricao_defeito</td>";
				echo "</tr>";
			}
		}
	}
}
echo "</table>";

if ($login_fabrica == 85 && $exibir_esporadico === true) {
	echo "<br><table style='text-align:left;font-size:10Px;font-family:Verdana;width:600px;' cellspacing='2'>";
	echo "<caption style='border-bottom:1px #000000 solid;font-weight:bold;text-transform:uppercase;text-align:left;'>Técnico Esporádico</caption>";
	echo "<tr>";
	echo "<th>Código:</th>";
	echo "<td>". $tecnico_esporadico["codigo"]. "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<th>Nome:</th>";
	echo "<td>". $tecnico_esporadico["nome"]. "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<th>Valor Combinado:</th>";
	echo "<td>". $tecnico_esporadico["valor"]. "</td>";
	echo "</tr>";
	echo "</table>";
} else {

	echo "<br><table style='text-align:left;font-size:10Px;font-family:Verdana;width:600px;' cellspacing='2'>";
	echo "<caption style='border-bottom:1px #000000 solid;font-weight:bold;text-transform:uppercase;text-align:left;'>Posto Autorizado</caption>";
	echo "<tr>";
	echo "<th>Nome:</th>";
	echo "<td colspan='5'>$posto_nome</td>";
	echo "</tr>";

		echo "<tr>";
		echo "<th>Telefone:</th>";
		echo "<td nowrap>$posto_fone</td>";
		echo "<th>Email:</th>";
		echo "<td nowrap>$posto_email</td>";
		echo "</tr>";

	echo "<tr>";
	echo "<th>Endereço:</th>";
	echo "<td  colspan='5'>$contato_endereco, $contato_numero</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<th>Bairro:</th>";
	echo "<td>$contato_bairro</td>";
	echo "<th>Cidade:</th>";
	echo "<td>$contato_cidade</td>";
	echo "<th>Estado:<th>";
	echo "<td>$contato_estado</td>";
	echo "<th>CEP:</th>";
	echo "<td>$contato_cep</td>";

	echo "</tr>";

	echo "</table>";
}

echo "<br><table style='text-align:left;font-size:10Px;font-family:Verdana;width:600px;' cellspacing='2'>";
echo "<caption style='border-bottom:1px #000000 solid;font-weight:bold;text-transform:uppercase;text-align:left;'>Reclamação</caption>";
if ($login_fabrica == 51 and strlen($posto_nome) > 0) { // HD 63332
	echo "<tr>";
	echo "<th>Código:</th>";
	echo "<td>$codigo_posto</td>";
	echo "<th>Nome:</th>";
	echo "<td>$posto_nome</td>";
	echo "</tr>";
}
echo "<tr>";
echo "<th>Tipo:</th>";
echo "<td>$natureza_chamado</td>";

echo "</tr>";

echo "<tr>";
echo "<td colspan='4'>$reclamado</td>";
echo "</tr>";

echo "</table>";

echo "<br><table style='text-align:left;font-size:10Px;font-family:Verdana;width:600px;' cellspacing='2'>";
echo "<caption style='border-bottom:1px #000000 solid;font-weight:bold;text-transform:uppercase;text-align:left;'>Procedimento</caption>";
echo "<tr>";
echo "<td>";

$desc = (in_array($login_fabrica, [189])) ? "DESC" : ""; 

$sql = "SELECT
			tbl_hd_chamado_item.hd_chamado_item    ,
			to_char(tbl_hd_chamado_item.data,'DD/MM/YYYY HH24:MI:SS') as data,
			tbl_hd_chamado_item.comentario         ,
			tbl_admin.login    ,
			tbl_hd_chamado_item.interno            ,
			tbl_hd_chamado_item.status_item        ,
			tbl_hd_chamado_item.interno            ,
			tbl_hd_chamado_item.enviar_email
		FROM tbl_hd_chamado_item
		JOIN tbl_admin on tbl_hd_chamado_item.admin = tbl_admin.admin
		JOIN tbl_hd_chamado ON tbl_hd_chamado.hd_chamado = tbl_hd_chamado_item.hd_chamado
		WHERE tbl_hd_chamado_item.hd_chamado = $callcenter
		AND   tbl_hd_chamado.fabrica_responsavel = $login_fabrica
				order by tbl_hd_chamado_item.data {$desc}";
	//	echo $sql;
$res = pg_exec($con,$sql);
if(pg_numrows($res)>0){
	$xxM = pg_num_rows($res) + 1; // +1 pra nunca cair em 0.
	for($x=0;pg_numrows($res)>$x;$x++){
		$data               = pg_result($res,$x,data);
		$comentario         = pg_result($res,$x,comentario);
		$atendente_resposta = pg_result($res,$x,login);
		$status_item        = pg_result($res,$x,status_item);
		$interno            = pg_result($res,$x,interno);
		$enviar_email       = pg_result($res,$x,enviar_email);
		$xx = (in_array($login_fabrica, [189])) ? $xxM -= 1 : $xx + 1;
		?>
		<table width='100%' border='0' align='center' cellpadding="2" cellspacing="1" style=' border:#000000 1px solid; background-color: #FFFFFF;font-size:10px'>
		<tr>
		<td align='left' valign='top'>
			<table style='font-size: 10px' border='0' width='100%'>
			<tr>
			<td align='left' width='70%'>Resposta: <strong><?echo $xx;?></strong> Por: <strong><?echo nl2br($atendente_resposta);?></strong> </td>
			<td align='right' nowrap><?echo "$data";?></td>
			</tr>
			</table>
		</td>
		</tr>
		<? if($interno == "t"){?>
			<tr>
			<td align='center' valign='top' bgcolor='#EFEBCF'>
			<?echo "<font size='2'>Chamado Interno</font>";?>
			</td>
			</tr>
		<?}?>
		<? if($status_item == "Cancelado" or $status_item == "Resolvido"){?>
			<tr>
			<td align='center' valign='top' bgcolor='#EFEBCF'><?echo "<font size='2'>$status_item</font>";?>
			</td>
			</tr>
		<?}?>
		<? if($enviar_email == "t"){?>
			<tr>
			<td align='center' valign='top' bgcolor='#EFEBCF'><?echo "<font size='2'>Conteúdo enviado por e-mail para o consumidor</font>";?>
			</td>
			</tr>
		<?}?>
		<tr>
		<td align='left' valign='top' bgcolor='#FFFFFF'><?echo nl2br($comentario);?></td>
		</tr>
		</table><br>
<?
	}
}
echo "</td>";
echo "</tr>";
echo "</table>";

?>
<script language="JavaScript">
	window.print();
</script>

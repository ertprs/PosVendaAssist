<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

//Opções: 'auditoria', 'cadastros', 'call_center', 'financeiro', 'gerencia'  'info_tecnica'
$admin_privilegios = 'cadastros';
include 'autentica_admin.php';

/*------------------*/
include 'funcoes.php';
// Opcional
include '../helpdesk/mlg_funciones.php'; //Admin

$a_linhas_at = array(
    'AUTO' => 'Automotiva',
    'COMP' => 'Compressores',
    'DWLT' => 'Dewalt',
    'ELET' => 'Eletrodomésticos',
    'FECH' => 'Fechaduras',
    'FELE' => 'Ferramentas Elétricas',
    'FPNE' => 'Ferramentas Pneumáticas',
    'FPRO' => 'Ferramentas Profissionais',
    'GERA' => 'Geradores',
    'LASR' => 'Laser',
    'GEOM' => 'Metais Sanitários',
    'LAVP' => 'Lavadoras de Pressão',
    'METS' => 'Metais Sanitários',
    'MOTG' => 'Motores à Gasolina'
);
// if (count($_POST)) pre_echo($_POST, 'Form Data');

function parse_row($info_posto, $tipo = 'relatorio') {
	if (!is_array($info_posto)) return false;
	if (count($info_posto) <= 35) return null;

	if ($tipo == 'consulta') {
		$linha = array(
			'posto'					=> $info_posto['codigo_posto'],
			'razao_social'  		=> $info_posto['nome'],
			'cnpj'                  => $info_posto['cnpj'],
			'data_atualizacao'		=> $info_posto['data'],
			'fantasia'          	=> $info_posto['nome_fantasia'],
			'telefone'          	=> $info_posto['telefone'],
			'fax'					=> $info_posto['fax'],
			'contato'           	=> $info_posto['contato_1'],
			'email'	            	=> $info_posto['email_contato_1'],
			'contato_alternativo'	=> $info_posto['contato_2'],
			'email_alternativo'		=> $info_posto['email_contato_2'],

			'compra_fabrica'    	=> $info_posto['distrib_black'],
			'distribuidor'      	=> $info_posto['distribuidor'],

			'foco_atendimento'		=> ($info_posto['consumidor_revenda']=='C') ? 't' : 'f',
			'percentual'			=> $info_posto['consumidor_revenda_per'].'%',
			'linhas_atendimento'	=> $info_posto['linhas_black'],
			'percentual_linhas'		=> $info_posto['per_linhas_bd'],

			'treinamento_fabrica'   => $info_posto['treinamento_bd'],
			'treinamento_linha'     => explode('#', $info_posto['treino_linhas']),
			'treinamento_tecnico'   => explode('#', $info_posto['treino_tecnicos']),
			'treinamento_data'      => explode('#', $info_posto['treino_datas']),
			'tecnico_trabalha_at'   => explode('#', $info_posto['treino_ativos']),

			'cred_outras_marcas'    => $info_posto['outras_atende'],
			'outras_linhas'			=> explode('#', $info_posto['outras_linhas']),
			'outras_marcas'			=> explode('#', $info_posto['outras_marcas']),

			'treinamento_outras_marcas'=>$info_posto['outras_treino'],
			'treinamento_na_linha'	=> explode('#', $info_posto['o_tr_linhas']),
			'treinamento_do_tecnico'=> explode('#', $info_posto['o_tr_tecnicos']),
			'treinamento_em_data'	=> explode('#', $info_posto['o_tr_datas']),
			'tecnico_trabalha_posto'=> explode('#', $info_posto['o_tr_ativos']),

			'confirma_dados_banco'	=> $info_posto['dados_banco_ok'],
			'banco'					=> $info_posto['banco'],
			'entidade'				=> $info_posto['nomebanco'],
			'agencia'				=> $info_posto['agencia'],
			'conta'					=> $info_posto['conta'],
			'tipo_conta'			=> $info_posto['tipo_conta'],
			'responsavel_cadastro'  => $info_posto['responsavel_cadastro']
		);

	if ($linha['compra_fabrica'] == 't') $linha['distribuidor'] = 'Black & Decker';
	// Formata o nº de conta bancária
	$conta_bancaria = "<b>Entidade</b>: " . $linha['banco'] .
						" ({$linha['entidade']}),<br />"  .
						"<b>Ag.</b>: {$linha['agencia']}&nbsp;&ndash;&nbsp;" .
						"<b>Conta</b>: {$linha['conta']}<br />" .
						"({$linha['tipo_conta']})";
	$responsavel_cadastro = $linha['responsavel_cadastro'];

	$linha['conta_bancaria']		= $conta_bancaria;
	$linha['responsavel_cadastro']	= $responsavel_cadastro;

	//Formata os arrays que vieram do banco
	$linha['linhas_atendimento'] = explode(',', $linha['linhas_atendimento']);
	$linha['percentual_linhas']  = explode(',', $linha['percentual_linhas']);

	return $linha;
}else{ // Para o relatório
		$linha = array(
			'posto'					=> strtoupper($info_posto['codigo_posto']),
			'razao_social'  		=> strtoupper($info_posto['nome']),

			'data_atualizacao'		=> strtoupper($info_posto['data']),
			'NOME FANTASIA'         => strtoupper($info_posto['nome_fantasia']),
			'telefone'          	=> strtoupper($info_posto['telefone']),
			'telefone fax'			=> strtoupper($info_posto['fax']),
			'contato'           	=> array_filter(array($info_posto['contato_1'], $info_posto['contato_2'])),
			'email'	            	=> array_filter(array($info_posto['email_contato_1'], $info_posto['email_contato_2'])),

			'compra_fabrica'    	=> strtoupper($info_posto['distrib_black']),
			'distribuidor'      	=> strtoupper($info_posto['distribuidor']),



			'foco_atendimento'		=> ($info_posto['consumidor_revenda']=='C') ? 'CONSUMIDOR' : 'REVENDA',
			'percentual'			=> strtoupper($info_posto['consumidor_revenda_per'].'%'),
			'linhas_atendimento'	=> strtoupper($info_posto['linhas_black']),
			'percentual_linhas'		=> strtoupper($info_posto['per_linhas_bd']),

			'treinamento_fabrica'   => strtoupper($info_posto['treinamento_bd']),
			'treinamento_linha'     => explode('#', $info_posto['treino_linhas']),
			'treinamento_tecnico'   => explode('#', $info_posto['treino_tecnicos']),
			'treinamento_data'      => explode('#', $info_posto['treino_datas']),
			'tecnico_trabalha_at'   => explode('#', $info_posto['treino_ativos']),

			'cred_outras_marcas'    => strtoupper($info_posto['outras_atende']),
			'outras_linhas'			=> explode('#', $info_posto['outras_linhas']),
			'outras_marcas'			=> explode('#', $info_posto['outras_marcas']),

			'treinamento_outras_marcas'=> strtoupper($info_posto['outras_treino']),
			'treinamento_na_linha'	=> explode('#', $info_posto['o_tr_linhas']),
			'treinamento_do_tecnico'=> explode('#', $info_posto['o_tr_tecnicos']),
			'treinamento_em_data'	=> explode('#', $info_posto['o_tr_datas']),
			'tecnico_trabalha_posto'=> explode('#', $info_posto['o_tr_ativos']),

			'confirma_dados_banco'	=> strtoupper($info_posto['dados_banco_ok']),
			'banco'					=> strtoupper($info_posto['banco']),
			'entidade'				=> strtoupper($info_posto['nomebanco']),
			'agencia'				=> strtoupper($info_posto['agencia']),
			'conta'					=> strtoupper($info_posto['conta']),

			'responsavel_cadastro'  => strtoupper($info_posto['responsavel_cadastro'])
		);

		if ($linha['compra_fabrica'] == 'T') $linha['distribuidor'] = 'BLACK & DECKER';
		// Formata o nº de conta bancária
		$conta_bancaria = "<b>Entidade</b>: " . $linha['banco'] .
							" ({$linha['entidade']}),<br />"  .
							"<b>Ag.</b>: {$linha['agencia']}&nbsp;&ndash;&nbsp;" .
							"<b>Conta</b>: {$linha['conta']}<br />" .
							"({$linha['tipo_conta']})";
		$responsavel_cadastro = $linha['responsavel_cadastro'];

		$linha['conta_bancaria']		= $conta_bancaria;
		$linha['responsavel_cadastro']	= $responsavel_cadastro;

		//Formata os arrays que vieram do banco
		$linha['linhas_atendimento'] = explode(',', $linha['linhas_atendimento']);
		$linha['percentual_linhas']  = explode(',', $linha['percentual_linhas']);

		return $linha;
	}	
}

if (count(array_filter($_POST)) > 0) {

	$sql  = "SET DateStyle TO Postgres, dmy;
		SELECT tbl_at_postos_black.posto,
				tbl_at_postos_black.responsavel_cadastro,
				TO_CHAR(tbl_at_postos_black.data, 'DD/MM/YYYY') AS data,
				tbl_at_postos_black.fantasia		AS nome_fantasia,
				tbl_at_postos_black.telefone,
				tbl_at_postos_black.telefone_fax	AS fax,
				tbl_at_postos_black.contato_1,
				tbl_at_postos_black.email_contato_1,
				tbl_at_postos_black.contato_2,
				tbl_at_postos_black.email_contato_2,
				tbl_at_postos_black.distrib_black,
				tbl_at_postos_black.distribuidor,
				tbl_at_postos_black.consumidor_revenda,
				tbl_at_postos_black.consumidor_revenda_per,
				tbl_at_postos_black.linhas_black,
				tbl_at_postos_black.per_linhas_bd,
				tbl_at_postos_black.treinamento_bd,
				ARRAY_TO_STRING(tbl_at_postos_black.treino_linhas,	'#') AS treino_linhas,
				ARRAY_TO_STRING(tbl_at_postos_black.treino_tecnicos,'#') AS treino_tecnicos,
				ARRAY_TO_STRING(tbl_at_postos_black.treino_datas,	'#') AS treino_datas,
				ARRAY_TO_STRING(tbl_at_postos_black.treino_ativos,	'#') AS treino_ativos,
				tbl_at_postos_black.outras_atende,
				ARRAY_TO_STRING(tbl_at_postos_black.outras_linhas,	'#') AS outras_linhas,
				ARRAY_TO_STRING(tbl_at_postos_black.outras_marcas,	'#') AS outras_marcas,
				tbl_at_postos_black.outras_treino,
				ARRAY_TO_STRING(tbl_at_postos_black.o_tr_linhas,	'#') AS o_tr_linhas,
				ARRAY_TO_STRING(tbl_at_postos_black.o_tr_tecnicos,	'#') AS o_tr_tecnicos,
				ARRAY_TO_STRING(tbl_at_postos_black.o_tr_datas,		'#') AS o_tr_datas,
				ARRAY_TO_STRING(tbl_at_postos_black.o_tr_ativos,	'#') AS o_tr_ativos,
				tbl_at_postos_black.dados_banco_ok,
				tbl_posto.nome,
				tbl_posto_fabrica.codigo_posto,
				tbl_posto_fabrica.banco,
				tbl_banco.nome AS nomebanco,
				tbl_posto_fabrica.agencia,
				tbl_posto_fabrica.conta,
				tbl_posto_fabrica.tipo_conta
		  FROM tbl_at_postos_black
		  JOIN tbl_posto USING(posto)
		  JOIN tbl_posto_fabrica 
				ON tbl_posto_fabrica.posto   = tbl_at_postos_black.posto
				AND tbl_posto_fabrica.fabrica = $login_fabrica
				AND tbl_at_postos_black.posto <> '6359'
				AND tbl_at_postos_black.fantasia <> ''
				AND tbl_at_postos_black.responsavel_cadastro <> ''
			LEFT JOIN tbl_banco    
				ON tbl_banco.codigo          = tbl_posto_fabrica.banco";

		if (strtolower($btn_acao) == 'consultar') { //Joga os dados na tela

			$codigo_posto = anti_injection($_POST['posto_codigo']);

			if ($codigo_posto == '') {
				$msg_erro = 'Por favor, informe o código do Posto que gostaria consultar.';
			} else {
				$res = pg_query($con, $sql .= " WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto'");

				if (is_resource($res)) {
					if (pg_num_rows($res)) $info_posto = pg_fetch_assoc($res, 0);
					if (!is_array($info_posto) or pg_num_rows($res) == 0)
						$msg_erro = "Sem resultados para o Posto <b>$posto_codigo</b>.";
				} else {
					$msg_erro = "Erro ao ler as informações o Posto <b>$posto_codigo</b>. Tente novamente daqui uns segundos.";
				}
			}
		}

		if (strtolower($btn_acao) == 'download') { //Gera o relatório para download

			$res = pg_query($con, $sql);

			$formato_arquivo = 'xls';
			if (is_resource($res)) {
				if ($formato_arquivo == 'xls') {
					define('XLS_FMT', TRUE);
					define('LF', ',');
				} else {
					define('XLS_FMT', FALSE);
					define('LF', ",");
				}

				if (pg_num_rows($res) > 0) { //Tem resultados...
					$hoje = date('Y-m-d');
					$total= pg_num_rows($res);

/**/				if (XLS_FMT) {
						header('Content-type: application/msexcel');
						header("Content-Disposition: attachment; filename=dados_atualizados_postos_$hoje.xls");
					} else {
						header('Content-type: text/csv');
						header("Content-Disposition: attachment; filename=dados_atualizados_postos_$hoje.csv");
					}
/**/
					$row		= pg_fetch_assoc($res, 0);
					$linha		= parse_row($row);
					unset($linha['conta_bancaria']);
					$tot_linhas	= pg_num_rows($res);
					$campos		= array_keys($linha);

					foreach($campos as $campo) { // Pega os nomes das colunas para gerar o cabeçalho
						if ($campo == 'posto') $campo = 'POSTO';
						if ($campo == 'razao_social') $campo = 'RAZÃO SOCIAL';
						if ($campo == 'Nome fantasia') $campo = 'NOME FANTASIA';
						
						if ($campo == 'data_atualizacao') $campo = 'DATA DE ATUALIZAÇÃO';
						if ($campo == 'telefone') $campo = 'TELEFONE';
						if ($campo == 'telefone fax') $campo = 'TELEFONE FAX';
						if ($campo == 'contato') $campo = 'CONTATO';
						if ($campo == 'email') $campo = 'E-MAIL';
						if ($campo == 'compra_fabrica') $campo = 'COMPRA FABRICA (S / N)';
						if ($campo == 'distribuidor') $campo = 'DISTRIBUIDORA';
						if ($campo == 'foco_atendimento') $campo = 'FOCO ATENDIMENTO';
						if ($campo == 'percentual') $campo = 'PERCENTUAL';
						if ($campo == 'linhas_atendimento') $campo = 'LINHAS ATENDIMENTO';
						if ($campo == 'percentual_linhas') $campo = 'PERCENTUAL LINHAS';
						if ($campo == 'treinamento_fabrica') $campo = 'TREINAMENTO FABRICA';
						if ($campo == 'treinamento_linha') $campo = 'TREINAMENTO LINHA';
						if ($campo == 'treinamento_tecnico') $campo = 'TREINAMENTO TÉCNICO';
						if ($campo == 'treinamento_data') $campo = 'TREINAMENTO DATA';
						

						if (strpos($campo, 'tecnico')) $campo = str_replace('TÉCNICO', 'técnico', $campo);
						if (strpos($campo, 'fabrica')) $campo = str_replace('fabrica', 'fábrica', $campo);
	
			
						if ($campo == 'tecnico_trabalha_at') $campo = 'TREINAMENTO TRABALHA AT';
						if ($campo == 'cred_outras_marcas') $campo = 'CRED OUTRAS MARCAS';
						if ($campo == 'outras_linhas') $campo = 'OUTRAS LINHAS';
						if ($campo == 'outras_marcas') $campo = 'OUTRAS MARCAS';


						if ($campo == 'treinamento_outras_marcas') $campo = 'TREINAMENTO OUTRAS MARCAS';
						if ($campo == 'treinamento_na_linha') $campo = 'TREINAMENTO NA LINHA';
						if ($campo == 'treinamento_do_tecnico') $campo = 'TREINAMENTO TÉCNICO';


						if ($campo == 'treinamento_em_data') $campo = 'TREINAMENTO EM DATA';
				
					
						if ($campo == 'tecnico_trabalha_posto') $campo = 'TREINAMENTO TRABALHA POSTO';
						if ($campo == 'confirma_dados_banco') $campo = 'CONFIRMA DADOS BANCO';
						if ($campo == 'banco') $campo = 'BANCO';
						if ($campo == 'entidade') $campo = 'ENTIDADE';
						if ($campo == 'agencia') $campo = 'AGÊNCIA';
						if ($campo == 'conta') $campo = 'CONTA';
						//if ($campo == 'tipo_conta') $campo = 'TIPO CONTA';
						if ($campo == 'responsavel_cadastro') $campo = 'RESPONSAVEL PELO CADASTRO';


						$campo = ucfirst(str_replace('_', ' ', $campo));
						
						if ($campo == 'Cnpj') $campo = 'CNPJ'; //Estes dois vão depois por causa do UCFirst
						if (strpos($campo, 'Email')!==false) $campo = str_replace('Email', 'E-Mail', $campo);
						$xls_header  .= "<th bgcolor='#aaaaaa' color='#ffffff' width=300 >$campo</th>";
						$csv_campos[] = $campo;
					}

					if (XLS_FMT) {  // Monta o cabeçalho com os nomes dos campos, XLS-fake ou CSV
						echo "<table border='1' border BORDERCOLOR='black' style='border-collapse:collapse;'><thead><tr>$xls_header</tr></thead><tbody>";
					} else {
						echo implode(";", $csv_campos); //CSV
					}
					
					for ($i=0; $i < $tot_linhas; $i++) {
			        	$row = parse_row(pg_fetch_assoc($res, $i)); // A função interpreta os campos array, renomeia os campos e formata o nº de Conta
						unset($row['conta_bancaria']);
						$bgcolor = ($bgcolor=='yellow') ? 'cyan' : 'yellow';
						$xls_linha = "<tr valign='top' border='1'>";
						unset($csv_linha); //array

						$row['email']				= strtoupper(implode(LF, $row['email']));
						$row['contato']				= strtoupper(implode(LF, $row['contato']));

						$row['linhas_atendimento']	= strtoupper(implode(LF, $row['linhas_atendimento']));
						$row['percentual_linhas']	= strtoupper(implode(' %' . LF, $row['percentual_linhas']) . ' %');

						$row['treinamento_linha']	= strtoupper(implode(LF, $row['treinamento_linha']));
						$row['treinamento_tecnico']	= strtoupper(implode(LF, $row['treinamento_tecnico']));
						$row['treinamento_data']	= str_replace('-', '/', implode(LF, $row['treinamento_data']));
						$row['tecnico_trabalha_at']	= strtoupper(implode(LF, $row['tecnico_trabalha_at']));
						$row['tecnico_trabalha_at'] = strtoupper(str_replace('T','SIM',$row['tecnico_trabalha_at']));
						$row['tecnico_trabalha_at'] = strtoupper(str_replace('F','NÃO',$row['tecnico_trabalha_at']));

						$row['outras_linhas'] = strtoupper(implode(LF, $row['outras_linhas']));
						$row['outras_marcas'] = strtoupper(implode(LF, $row['outras_marcas']));

						$row['treinamento_na_linha']	= strtoupper(implode(LF, $row['treinamento_na_linha']));
						$row['treinamento_do_tecnico']	= strtoupper(implode(LF, $row['treinamento_do_tecnico']));
						$row['treinamento_em_data']		= strtoupper(str_replace('-', '/', implode(LF, $row['treinamento_em_data'])));
						$row['tecnico_trabalha_posto']	= strtoupper(implode(LF, $row['tecnico_trabalha_posto']));
						$row['tecnico_trabalha_posto'] = strtoupper(str_replace('T','SIM',$row['tecnico_trabalha_posto']));
						$row['tecnico_trabalha_posto'] = strtoupper(str_replace('F','NÃO',$row['tecnico_trabalha_posto']));
						

						
						$row['email']					= strtoupper($row['email']);
						$row['contato']					= strtoupper($row['contato']);
						$row['linhas_atendimento']		= strtoupper($row['linhas_atendimento']);
						$row['percentual_linhas']		= strtoupper($row['percentual_linhas']);
						$row['treinamento_linha']		= strtoupper($row['treinamento_linha']);
						$row['treinamento_tecnico']		= strtoupper($row['treinamento_tecnico']);
						$row['treinamento_data']		= strtoupper($row['treinamento_data']);
						$row['tecnico_trabalha_at']		= strtoupper($row['tecnico_trabalha_at']);
						$row['outras_linhas']			= strtoupper($row['outras_linhas']);
						$row['outras_marcas']			= strtoupper($row['outras_marcas']);
						$row['treinamento_na_linha']  	= strtoupper($row['treinamento_na_linha']);
						$row['treinamento_do_tecnico']	= strtoupper($row['treinamento_do_tecnico']);
						$row['treinamento_em_data']		= strtoupper($row['treinamento_em_data']);
						$row['tecnico_trabalha_posto']	= strtoupper($row['tecnico_trabalha_posto']);

						

						foreach($row as $key => $campo) {
							$campo = str_replace("\t", ' ', $campo); //Retira a tabulação
							if ($campo == 'T') $campo = 'SIM';
							if ($campo == 'F') $campo = 'NÃO';
							if ($key == 'cnpj') $campo = preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $campo);

							if ($formato != 'xls') $campo = str_replace("\n", LF, $campo); //Retira a quebra de linha, substinui ela peloa constante LF
							// colSpan=3
							$xls_linha  .= "<td width=300 BGCOLOR='$bgcolor'>$campo</td>";
							$csv_linha[] = (preg_match('/(\s|\r|;)/', $campo) or //Entre aspas se tiver aglum tipo de espaço ou dígito grande, tipo nº série
											in_array($key, array('referencia','cnpj','cpf','codigo_posto','nota_fiscal','serie','peca_referencia'))) ? "\"$campo\"" : $campo;
						}
						echo (XLS_FMT) ? "$xls_linha</tr>" : implode(";", $csv_linha);

					}
					if (XLS_FMT) echo "</tbody></table>";
					exit; // FIM do arquivo 'Excel'
				} else {
					$msg_erro = 'Sem dados para o período selecionado.';
				}
			} else { // Não deu erro no banco...
				$msg_erro = 'Erro ao recuperar os dados';
			}

		}
    }



/* Include cabeçalho Admin */
	$title = "Telecontrol - Assistência Técnica - Relatório Atualização Postos";
	//Opções: 'cadastro', 'callcenter', 'financeiro', 'gerencia', 'tecnica'
	$layout_menu = 'cadastro';

	include "cabecalho.php";

// Style para relatórios (formulário + tabela de resultados) para  aárea do admin
?>
<style type="text/css">

.menu_top {
	text-align: center;
	font: normal bold 10px Verdana, Geneva, Arial, Helvetica, sans-serif;
	border: 1px solid;
	color:#596d9b;
	background-color: #d9e2ef;
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	text-align: center;
	font: normal normal 10px Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	border: 0px solid;
	background-color: white;
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font: normal bold 10px Verdana, Geneva, Arial, Helvetica, sans-serif;
	color:#596d9b;
	background-color: #d9e2ef;
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: white;
}

caption, .titulo_tabela {
	background-color:#596d9b;
	font: bold 14px "Arial";
	color: white;
	text-align:center;
}


thead,.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color: white;
	text-align:center;
}
/* DL > DT+DD*/
div.formulario div.fs {
	background-color: #d9e2ef;
	border: 0 solid transparent;
	float: left;
	width: 320px;
	padding: 0;
	margin: 1ex 15px;
}
div.fs legend {
	text-align: left;
	padding: auto 2ex;
	font-weight: bold;
	text-transform: uppercase;
}

dl {
	display: block;
	margin: auto 8px;
	font: normal normal 11px/14px Verdana, Arial, Helvetica sans-serif;
	text-align: left;
}
dt {
	background-color:#596d9b;
	border: 1px solid #596d9b;
	font-weight: bold;
	font-size: 12px;
	color: white;
	width: 300px;
}
dd {
	border-collapse: collapse;
	border:1px solid #596d9b;
	margin: 0 0 1em 0;
	background-color: #FFF;
	-webkit-margin-start: 0;
	width: 300px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	-o-text-overflow: ellipsis;
}
dd > table {width: 98%;margin: auto 1%;table-layout: fixed;}
.formulario {
	background-color:#D9E2EF;
	font: normal normal 11px Arial;
}

.msg,.msg_erro{
	background-color:#FF0000;
	font: bold 16px "Arial";
	color: white;
	text-align:center;
}

.msg{
	background-color:#51AE51;
	color: white;
}

table.tabela tr td {
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
table th.tecnico, table td.tecnico {
	width: 130px; 
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	-o-text-overflow: ellipsis;
}
.texto_avulso {
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width: 700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}




/*============ TELA FORMULARIO =============*/

#janela {
	display: block;
	background-color: #ffffff;
	position: relative;
	text-align: left;
	font-family: Segou UI, Verdana, Arial, Helvetica, Sans-serif;
	font-size: 12px;
	border: 0px solid #b8bac6;
	overflow: hidden;
	width:700px;
}
#janela #ei_container p {
	font-size: 12px;
	padding: .5ex 1ex;
	overflow-y:auto;
}
#janela #ei_header {
	position: absolute;
	top:	0;
	left:	0;
	margin:	0;
	vertical-align: middle;
	width: 100%;
	_width: 680px;
	*width: 680px;
	height:  28px;
	border-radius: 0px 0px 0 0 ;
	-moz-border-radius: 0px 0px 0 0 ;
	background-color: #b8bac6;
	background: linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* W3C */
	background: -o-linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* Opera11.10+ */
	background: -ms-linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* IE10+ */
	background: -moz-linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* FF3.6+ */
	background: -webkit-linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* Chrome10+,Safari5.1+ */
	color: black;
	font: normal bold 13px Segoe UI, Verdana, MS Sans-Serif, Arial, Helvetica, sans-serif;
}
#janela #ei_container {
	background-color: #fdfdfd;
	margin: 1px;
	padding: 0;
	padding-bottom: 1ex;

	font-size: 11px;
	color: #313452;
	width: 100%;
	line-height: 1.6em;
	position: relative;
}
#ei_container #msgErro {
	background-color: rgba(255, 127, 127, 0.7);
	color: #fff;
	display: none;
	font-weight:bold;
	font-size: 11px;
	border: 2px solid red;
	border-radius: 5px;
	-moz-border-radius: 5px ;
	box-shadow: 2px 2px 3px #005;
	-moz-box-shadow: 2px 2px 3px #005;
	-webkit-box-shadow: 2px 2px 3px #005;
	width: 700px;
	margin: auto;
	padding: 6px 18px;
}

/*  Validação e erro    */
#janela .valid {background-color: #cfc;}
#ei_container .error {display:inline;color:darkred;font-weight:bold;}
#ei_container span.error {display:inline!important;color:white;background-color: #900;font-weight:bold;font-size: 11px;}
#ei_container p.erro {
	display:block;
	width:90%;
	/* Se entrar pelo login único, sobreescrever fundo e borda da class .erro ... */
	background-color: white;
	border: 0 solid transparent;
}
#ei_container p.erro > label {display:inline;color:darkred;width:auto!important;zoom:none;font-size: 10px;background:#f09090;text-align: left;font-weight:bold;}
#janela form input.error {background-color:#fcc;font-size: 11px;text-align: left;}
#janela form input.error:active {background-color:#ccf;}

#janela form {line-height:1.8em;}
#ei_container form input[type=text],
#ei_container form input[type=date],
#ei_container form input[type=number],
#ei_container form select,
#ei_container form input[type=text] {
	height: 16px;
	border: 1px solid #d3d3d3;
}
.tbl_treino caption {color:#009}
#ei_container form label, dt {
	color: #009;
	text-align:right;
	width: 130px;
	display:inline-block;
	_zoom:1;
}
dt {max-width: 50px;text-align:left;}
dd {display:inline;}
#ei_container form fieldset label {
	font-size: 11px;
}
#ei_container form fieldset, form div {
	margin:	18px auto;
	_margin-top: 4em;
	*margin-top: 4em;
	padding: auto 10px;
	border: 1px solid #d3d3d3;
	border-radius: 6px;
	_padding-bottom: 0.5em;
	*padding-bottom: 0.5em;
	position:relative;
	width: 650px;
	background:;
}
#ei_container form #fs_end label {width:70px}
#ei_container form .fs_linhas label {
	width: 170px;
	text-align: left;
}
#ei_container form fieldset input[type=radio] + label,
#ei_container form fieldset input[type=checkbox] + label,
#ei_container form label.normal {
	display: inline;
	text-align:left;
	width: auto; /* O IE não entende o width quando coloca inline-block, mas o mantém mesmo voltando para inline!*/
}
#ei_container form fieldset table.tbl_treino input {
	margin: auto;
}
#ei_container form legend {
	border-radius: 4px 4px 0 0;
	-moz-border-radius: 4px 4px 0 0;
	background-color: #d3d3d3;
	border-top: 2px solid #f4781e;
	background-color: #b8bac6;
	background: linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* W3C */
	background: -o-linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* Opera11.10+ */
	background: -ms-linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* IE10+ */
	background: -moz-linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* FF3.6+ */
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#f5f6f6), color-stop(21%,#dbdce2), color-stop(49%,#b8bac6), color-stop(80%,#dddfe3), color-stop(100%,#f5f6f6)); /* Chrome,Safari4+ */
	background: -webkit-linear-gradient(top, #f5f6f6 0%, #dbdce2 21%, #b8bac6 49%, #dddfe3 80%, #f5f6f6 100%); /* Chrome10+,Safari5.1+ */
	margin-left: 2ex;
	position: absolute;
	top: -22px;
	height: 16px;
	-o-top: -16px;
	padding: 0 4px 2px 4px;
	font-weight: bold;
	color: #333;
}
  #ei_container fieldset#info_cad label {
	width: 170px!important;
  }
  #ei_container fieldset#info_cad label.info {width: auto!important;zoom:none}
  #ei_container fieldset#info_cad input {width: 120px}
  #ei_container fieldset > fieldset {width: 640px; margin: 18px auto}
  #ei_container fieldset > fieldset > fieldset, .tbl_treino {width: 600px; margin: 18px auto}
  #ei_container div > fieldset {width: 90%; margin: 0px auto}
  #ei_container fieldset > fieldset > label {width: 100px}
  #ei_container table.tbl_treino {
	  font-size: 11px;
	  table-layout: fixed;
	  border-collapse: separate;
	  margin-top: 10px;
  }
  #ei_container table.tbl_treino thead th {
	  background-color: #d3d3d3;
	  height: 20px;
	  padding: 0 4px 2px 4px;
	  font-weight: bold;
	  color: #333;
	  height: 1.2em;
	  overflow: hidden;
	  vertical-align: middle;
  }
#ei_container form table.tbl_treino td {
	color: #009;
	margin: auto;
	text-align:left;
}
#ei_container form fieldset#fs_banco {text-align: left;}
#ei_container form #fs_banco label {width:70px}
#ei_container form fieldset.bool {border: 0}
input#fantasia {width:420px!important}

</style>

<!-- ARQUIVOS PARA CARRREGAR JANELA MODAL ------>
    <script type='text/javascript' src='js/modal/ajax.js'></script>
    <script type='text/javascript' src='js/modal/modal-message.js'></script>
    <script type='text/javascript' src='js/modal/ajax-dynamic-contentt.js'></script>
    <script type='text/javascript' src='js/modal/main.js'></script>
    <link rel='stylesheet' href='css/modal/modal-message.css' type='text/css'>
    <!-- -------------------------------------------->

    <!-- ARQUIVOS PARA MONTAR TABELA DE PAGINAÇÃO --->
    <script src='js/jquery.js' type='text/javascript'></script>
    <script src='js/table/jquery.dataTables.js' type='text/javascript'></script>
    <script src='js/table/demo_page.js' type='text/javascript'></script>
    <script src='js/table/jquery-ui-1.7.2.custom.js' type='text/javascript'></script>
    <!-- ---------------------------------------- -->


    <!--- CSS DA TABELA DE PAGINAÇÃO ---------------->
    <link rel='stylesheet' href='css/table/demo_table_jui.css' type='text/css' />
    <link rel='stylesheet' href='css/table/jquery-ui-1.7.2.custom.css' type='text/css' />

<script type="text/javascript">

   try{
        xmlhttp = new XMLHttpRequest();
    }catch(ee){
        try{
            xmlhttp = new ActiveXObject('Msxml2.XMLHTTP');
        }catch(e){
            try{
                xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
            }catch(E){
                xmlhttp = false;
            }
        }
    }

    //FUNÇÃO DA PAGINAÇÃO
    function fnFeaturesInit () {
        $('ul.limit_length>li').each( function(i) {
            if ( i > 10 ) {
                this.style.display = 'none';
            }
        } );

        $('ul.limit_length').append( '<li class="css_link">Mais<\/li>' );
        $('ul.limit_length li.css_link').click(function () {
            $('ul.limit_length li').each(function(i) {
                if ( i > 5 ) {
                    this.style.display = 'list-item';
                }
            });
            $('ul.limit_length li.css_link').css( 'display', 'none' );
        });
    }


    function closeMessage_1(){
        messageObj.close();//FECHA A JANELA MODAL
    }

	function preenche_campo(campo, valor) {
        //VERIFICA SE CAMPO EXISTE NO FORMULARIO
        var objnome1 = document.getElementsByName(campo).length;
        if(valor != '' && objnome1  == '1'){
            //LIMPA CAMPO
            document.getElementById(campo).value = '';
            //ADICIONA CONTEUDO
            document.getElementById(campo).value = valor;
        }
	}

	function Fechar_popup() { // Fecha depois de 2 seg.
		setTimeout('closeMessage_1()',2500);
	}

    function busca_dados_1(tipo, param) {
		var valor = document.getElementById(param).value;
		if (valor.replace(/(^\s+|\s+$)/g, '').length < 3) {
			alert("Digite pelo menos três caracteres para iniciar a pesquisa");
			return false;
		}
        //MONTA A JANELA MODAL
		displayMessage('pesquisa_posto_codigo.php?tipo='+tipo+'&posto='+valor,'800','500');
            $(document).mousemove( function() {
            //---TABLE DE PAGINAÇÃO---
            fnFeaturesInit();
            $(document).mousemove(function() {
                oTable = $('#example').dataTable({
                    'bJQueryUI': true,
                    'sPaginationType': 'full_numbers',
                    'bPaginate': true,
                    'iDisplayLength': 10,
                    //RETIRA EVENTO MOUSEMOVE DA JANELA MODAL
                    fnInitComplete:function() {
                        $(document).unbind('mousemove');
                    }
                });
            })
        });

    }

    function retorno(info){
		var vars = info.split('|');
		preenche_campo('posto_codigo', vars[0]);
		preenche_campo('posto_nome',   vars[1]);
		//alert("Info: " + vars[0] + " dado: " + vars[1]);
        messageObj.close();//FECHA A JANELA MODAL
    }
</script>
<script>
	$().ready(function() {
		$('#btn_limpar').click(function() {
			$('.formulario input').val('');
			$('.msg,.msg_erro').parent().parent().hide('fast');
			return false;
		});
		$('#download').click(function() {
			$(this).attr('disabled','disabled');
			$('#resultado').fadeOut('fast');
			$('form[name=xls]').submit();
			return false;
		});
		$('#consultar').click(function() {
			$('input#acao').val('consultar');
			$('form[name=frm_posto_atualiza]').submit();
		});
		$('#btn_todos').click(function() {
			$('input#acao').val('download');
			$('form[name=frm_posto_atualiza]').submit();
		});
	});
</script>

<table width='700' align='center' border='0' bgcolor='#d9e2ef'>
<? if (strlen ($msg_erro) > 0) { ?>
	<tr class="msg_erro">
		<td> <? echo $msg_erro; ?></td>
	</tr>
<? } ?>

<? if (strlen ($msg) > 0) { ?>
	<tr class="msg">
		<td> <? echo $msg; ?></td>
	</tr>
<? } ?>
</table>


<?/* Base do formulário (zenCode) */ ?>
<form action="<?php echo $PHP_SELF;?>" name="frm_posto_atualiza" style='margin:auto;text-align:center;' method="post">
	<table align='center' class="formulario" style='table-layout:fixed;width:700px;'>
		<caption border='1'>Parâmetros de Pesquisa</caption>
		<thead style='background-color: transparent'>
			<tr style='visibility:hidden; border-collapse:collapse'>
				<th style='width:120px'>&nbsp;</th>
				<th style='width:135px'>&nbsp;</th>
				<th style='width:110px'>&nbsp;</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tbody style='text-align:left;'>
			<tr><td colspan='4'>&nbsp;</td></tr>
			<tr>
				<td style='width:120px'>&nbsp;</td>
				<td style='width:135px'>
					<label for="data_inicial">&nbsp;Código do Posto *</label>
				</td>
				<td style='width:35px'>&nbsp;</td>
				<td>
					<label for="data_final">&nbsp;Nome do Posto</label>
				</td>
			</tr>
			<tr>
				<td>&nbsp</td>
				<td>
					<input type="text" maxlength="20" size='15' class="frm" name="posto_codigo" id="posto_codigo" value="<?=$posto_codigo?>" />
					<img src="../imagens/lupa.png" border="0" style="cursor:pointer" onclick='busca_dados_1("codigo","posto_codigo");' align="absmiddle">
				</td>
				<td>&nbsp;</td>
				<td>
					<input type="text" maxlength="50" size='30' class='frm' name="posto_nome" id="posto_nome" value="<?=$posto_nome?>" />
					<img src="../imagens/lupa.png" border="0" style="cursor:pointer" onclick='busca_dados_1("nome","posto_nome");' align="absmiddle">
				</td>
			</tr>
			<tr><td colspan='4'>&nbsp;</td></tr>
			<tr>
				<td colspan="4" align='center' style='text-align:center!important'>
					<input type='hidden' id='acao' name='btn_acao' value='' />
					<input type='submit' name='btn_acao' style="margin-right:3em;font-size:14px;" value='Consultar'> 
					&nbsp;&nbsp;&nbsp;
					<!--<button name='btl_limpar' type='reset' id='btn_limpar'>Limpar</button>-->
				</td>
			</tr>
			<tr><td colspan='4'>&nbsp;</td></tr>
			<tr>
				<td colspan="4" align='center' style='text-align:center!important'>
					<p>Para fazer download do arquivo com as informações dos postos, clique no botão 'Download'</p>
					<input type='submit' id='btn_todos' name='btn_acao' style="margin-right:4em;font-size:14px;" value='Download'>
				</td>
			</tr>
			<tr><td colspan='4'>&nbsp;</td></tr>
		</tbody>
	</table>
</form>
<p>&nbsp;</p>

<?
if (is_array($info_posto)) {
	//Cadastro
	$linha = parse_row($info_posto,'consulta');
	extract($linha);

?>
<div style='width:700px;margin:auto;height:100%;background-color:#D9E2EF;' class='formulario'>
	<!-- -->
	<div id="janela">

		<div id="ei_container">
			<!--[if IE]><br><br><br><![endif]-->
			<input type="text" id="void" tabindex='0' style='background-color:transparent;border:0 solid transparent;color:transparent' readonly />
			<form action="" id='frm_at_posto' method='post' autocomplete='off'>
				<input type='hidden' name='posto' value='<?=$login_posto?>' />
			<!--[if IE]><br /><![endif]-->
				<fieldset id='info_cad'>
					<legend>1. Dados de Contato</legend>
					<p>Razão Social: <b><?php echo $razao_social;?></b></p>
					<label for="fantasia">Nome Fantasia da Empresa</label>
						<input type="text" maxlength='50' name='fantasia' disabled class='required {minlength:5}' id='fantasia' tabindex='1' value="<?php echo $fantasia;?>"/>
					<br />
					<label for="fone">Telefone</label>
						<input type='text' id='fone' class='required foneBR' disabled tabindex='2' placeholder='Telefone para contato' name='contato_fone_comercial' value="<?php echo $telefone;?>"/>
					<label for="fax">Fax</label>
						<input type='text' id='fax'  class='required foneBR' disabled tabindex='3' placeholder='Nª de fax' name='contato_fax' value="<?php echo $fax;?>"/>
					<br />
					<label for="contato_1">Contato (1)</label>
						<input type='text' id='contato_1' tabindex='4' placeholder='Pessoa de contato'
							  class='required alpha' minlength='3' disabled maxlength='30' name='contato_nome' value="<?php echo $contato;?>"/>
					<label for="contato_2">Contato (2)</label>
						<input type='text' id='contato_2' class='alpha' maxlength='30' disabled  tabindex='6' placeholder='Pessoa de contato' name='contato_nome_extra' value="<?php echo $contato_alternativo;?>"/>
					<br />
					<label for="email_1">E-mail de contato</label>
						<input type="text" id="email_1" name="contato_email" tabindex='5' disabled class='required email' placeholder='Email para contato' value="<?php echo $email;?>"/>
					<label for="email_2">E-mail de contato</label>
						<input type="text" id="email_2" name="contato_email_extra" disabled tabindex='7' class='email' placeholder='Email para contato' value="<?php echo $email_alternativo;?>"/>
				</fieldset>
				
				<br><!--[if IE]><br><br><![endif]-->
				<fieldset id='fs_oper'>
					<legend>2. Sobre as operações do Posto Autorizado</legend>
					<p>A compra de peças é feita direto com a fábrica?</p>
					<fieldset id='fs_dnf' class='bool'>
						<?php
							$check_sim_1 ="";
							$check_nao_1 ="";
							if ($compra_fabrica == 't') {
								$check_sim_1 = "checked='checked'";
								$distribuidor = "";
							}else{
								$check_nao_1 = "checked='checked'";
								$distribuidores = $distribuidor;
							}
							
						?>
						<input type="radio" class='required' name="distrib_fabrica" disabled <?php echo $check_sim_1;?> data='dnf_block' tabindex='8' id="df_sim" value='t' />
						<label for="df_sim">Sim</label>
						<input type="radio" name="distrib_fabrica" data='dnf_block' disabled <?php echo $check_nao_1;?> tabindex='9' id="df_nao" value='f' />
						<label for="df_nao">Não</label>
						<?php
						if($compra_fabrica == 'f'){
						?>	
						<p id='dnf_block' class='oculto'>
							<label for="distrib_nao_fabrica" class='normal'>Informe seu Distribuidor</label>
							<input type='text' id='dnf' name='distrib_nao_fabrica' disabled tabindex='10' size='50'
							  maxlength='50' minlength='4' placeholder='Razão Social do Distribuidor' value="<?php echo $distribuidores;?>"/>
						</p>
						<?php
						}
						?>
					</fieldset>
					
			<!--[if IE]><br><br><br><![endif]-->
					<fieldset id='fs_atend'>
						<legend>2.1. Nº de Atendimentos</legend>
						<p>Qual é o maior volume de atendimento no seu posto autorizado?<br />
						   Selecione a resposta e informe o percentual que representa no total de atendimentos.</p>
						<?php
		
							if($foco_atendimento == t){
								$check_sim_2_1 = "checked='checked'";
								$distribuidor = "";
							}else{
								$check_nao_2_1 = "checked='checked'";
								$distribuidor = "";
							}
							$percentual;
						?>
						<div style="background:;float:left;height:45px;width:200px;border:0px;">
							<input type='radio' name='consumidor_revenda' <?php echo $check_sim_2_1;?> disabled value='C' id='cr_c' tabindex='11' />
							<label for='cr_c' style='width:170px'>Cliente final</label>
							<br />
							<input type='radio' name='consumidor_revenda' <?php echo $check_nao_2_1;?> disabled value='R' id='cr_r' tabindex='12' />
							<label for='cr_r' style='width:170px'>Estoque de revenda</label>
						</div>

						<div style="background:;float:left;height:45px;width:100px;border:0px;">
							<div style="background:;float:left;width:100%;margin-top:10px;border:0px;">
								&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" id="pr_cr" size='4' disabled align='right' tabindex='13' value="<?php echo $percentual;?>" class='required' name="consumidor_revenda_per" />%
							</div>
						</div>
						<br />
					</fieldset>
					
			<br><!--[if IE]><br><br><![endif]-->
					<fieldset id='fs_linhas' class='fs_linhas'>
						<legend>2.2. Linhas Credenciadas</legend>
						<p>A sua empresa é credenciada no atendimento de qual(is) linha(s) de produto(s) Black & Decker?<br />
							Na primeira coluna selecione a(s) linha(s) credenciada(s) e na segunda coluna
							informe qual percentual essa linha de produto representa no seu negócio.</p>
						<?php

						$check_2_2 = "checked='checked'";
						for ($i=0; $i < 12; $i++) {
							if($linhas_atendimento[$i] == 'A'){
						?>
						<label for='AUTO'>Automotiva</label>
							<input type='checkbox'  disabled name='linhas[]' <?php echo $check_2_2;?> tabindex='14' id='AUTO' value='AUTO'>&nbsp;
							<input type='text' name='linhas_per[]' tabindex='15' size='3' disabled maxlength='2' value="<?php echo $percentual_linhas[$i];?>"/>%
							<br />
						<?php 
							}
							if($linhas_atendimento[$i] == 'C'){
						?>
						<label for='COMP'>Compressores</label>
							<input type='checkbox' name='linhas[]' tabindex='16' id='COMP' disabled value='COMP' <?php echo $check_2_2;?>>&nbsp;
							<input type='text' name='linhas_per[]' size='3' maxlength='2' disabled align='right' value="<?php echo $percentual_linhas[$i];?>"/>%
							<br />
						<?php 
							}
							if($linhas_atendimento[$i] == 'E'){
						?>
						<label for='ELET'>Eletrodomésticos</label>
							<input type='checkbox' name='linhas[]' tabindex='17' id='ELET' disabled value='ELET' <?php echo $check_2_2;?>>&nbsp;
							<input type='text' name='linhas_per[]' tabindex='18' size='3' disabled maxlength='2' value="<?php echo $percentual_linhas[$i];?>"/>%
							<br />
						<?php 
							}
							if($linhas_atendimento[$i] == 'F'){
						?>
						<label for='FECH'>Fechaduras</label>
							<input type='checkbox' name='linhas[]' tabindex='19' id='FECH' disabled value='FECH' <?php echo $check_2_2;?>>&nbsp;
							<input type='text' name='linhas_per[]' tabindex='20' size='3' disabled maxlength='2' value="<?php echo $percentual_linhas[$i];?>"/>%
							<br />
						<?php 
							}
							if($linhas_atendimento[$i] == 'D'){
						?>
						<label for='DWLT'>Ferramentas DEWALT</label>
							<input type='checkbox' name='linhas[]' tabindex='21' id='DWLT' disabled value='DWLT' <?php echo $check_2_2;?>>&nbsp;
							<input type='text' name='linhas_per[]' tabindex='22' size='3' disabled maxlength='2' value="<?php echo $percentual_linhas[$i];?>">%
							<br />
						<?php 
							}
							if($linhas_atendimento[$i] == 'B'){
						?>
						<label for='FELE'>Ferramentas Elétricas</label>
							<input type='checkbox' name='linhas[]' tabindex='23' id='FELE' disabled value='FELE' <?php echo $check_2_2;?>>&nbsp;
							<input type='text' name='linhas_per[]' tabindex='24' size='3' disabled maxlength='2' value="<?php echo $percentual_linhas[$i];?>"/>%
							<br />
						<?php 
							}
							if($linhas_atendimento[$i] == 'P'){
						?>
						<label for='FPNE'>Ferramentas Pneumáticas</label>
							<input type='checkbox' name='linhas[]' tabindex='25' id='FPNE' disabled value='FPNE' <?php echo $check_2_2;?>>&nbsp;
							<input type='text' name='linhas_per[]' tabindex='26' size='3'  disabled maxlength='2' value="<?php echo $percentual_linhas[$i];?>"/>%
							<br />
						<?php 
							}
							if($linhas_atendimento[$i] == 'GR'){
						?>
						<label for='GERA'>Geradores</label>
							<input type='checkbox' name='linhas[]' tabindex='27' id='GERA' disabled value='GERA' <?php echo $check_2_2;?>>&nbsp;
							<input type='text' name='linhas_per[]' tabindex='28' size='3' disabled maxlength='2' value="<?php echo $percentual_linhas[$i];?>"/>%
							<br />
						<?php 
							}
							if($linhas_atendimento[$i] == 'LS'){
						?>
						<label for='LASR'>Laser</label>
							<input type='checkbox' name='linhas[]' tabindex='29' id='LASR' disabled value='LASR' <?php echo $check_2_2;?>>&nbsp;
							<input type='text' name='linhas_per[]' tabindex='30' size='3' disabled maxlength='2' value="<?php echo $percentual_linhas[$i];?>"/>%
							<br />
						<?php 
							}
							if($linhas_atendimento[$i] == 'LV'){
						?>
						<label for='LAVP'>Lavadoras de Pressão</label>
							<input type='checkbox' name='linhas[]' tabindex='31' id='LAVP' disabled value='LAVP' <?php echo $check_2_2;?>>&nbsp;
							<input type='text' name='linhas_per[]' tabindex='32' size='3' disabled maxlength='2' value="<?php echo $percentual_linhas[$i];?>"/>%
							<br />
						<?php 
							}
							if($linhas_atendimento[$i] == 'M'){
						?>
						<label for='GEOM'>Metais Sanitários</label>
							<input type='checkbox' name='linhas[]' tabindex='33' id='GEOM' disabled value='GEOM' <?php echo $check_2_2;?>>&nbsp;
							<input type='text' name='linhas_per[]' tabindex='34' size='3' disabled maxlength='2' value="<?php echo $percentual_linhas[$i];?>"/>%
							<br />
						<?php 
							}
							if($linhas_atendimento[$i] == 'GS'){
						?>
						<label for='MOTG'>Motores à Gasolina</label>
							<input type='checkbox' name='linhas[]' tabindex='35' id='MOTG' disabled value='MOTG' <?php echo $check_2_2;?>>&nbsp;
							<input type='text' name='linhas_per[]' tabindex='36' size='3' disabled maxlength='2' value="<?php echo $percentual_linhas[$i];?>"/>%
							<br />
							<?php 
							}
							$total = $total + $percentual_linhas[$i];
						}
							$valor_total = number_format($total, 2)
						?>
					<label style='text-align: left;font-weight:bold'>Total:</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $total;?>
						<span id="tot_per_linhas"></span>
					</fieldset>
					
				</fieldset>
			<br><!--[if IE]><br><br><![endif]-->
				<fieldset id='fs_tr_bd'>
					<legend title="Treinamentos">3. Treinamentos</legend>
					<p>	A sua empresa já recebeu treinamento da Black & Decker?<br />
						Caso a resposta seja sim, selecione também a(s) linha(s), informe o nome do técnico,
						data do treinamento e se o técnico treinado ainda trabalha na sua empresa selecione a opção "ativo".</p>
					<fieldset class='bool' id='fs_treinou'>
						<?php 
						if ($treinamento_fabrica == 't') {
							$check_sim_3 = "checked='checked'";
						}else{
							$check_nao_3 = "checked='checked'";
						}
						?>
						<input type="radio" class='required' id="tr_sim" disabled name='treinou' <?php echo $check_sim_3;?> tabindex='37' data='info_treinamentos' value='t' /><label for="tr_sim">Sim</label>&nbsp;&nbsp;
						<input type="radio" id="tr_nao" name='treinou' disabled tabindex='38' data='info_treinamentos' <?php echo $check_nao_3;?> value='f' /><label for="tr_nao">Não</label>
					</fieldset>
					
			<!--[if IE]><br /><br /><![endif]-->
					<fieldset class='bool'> <?	/* Class bool apenas para tirar a borda... O:) */	?>
					<table class='tbl_treino oculto' id='info_treinamentos'>
					<caption>Dados dos Treinamentos</caption>
						<thead>
							<tr style='height:20px;vertical-align:middle'>
								<th style='width: 200px'>Linha</th>
								<th style='width: 220px'>Técnico</th>
								<th style='width: 120px'>Data</th>
								<th style='width:  50px'title='O técnico ainda trabalha no Posto?'>Ativo <sup>?</sup></th>
							</tr>
						</thead>
						<tbody>
						<?
						for ($i=0; $i < 3; $i++) {
							$lin = $treinamento_linha[$i];
							$tec = $treinamento_tecnico[$i];
							$dat = $treinamento_data[$i];
							$act = ($tecnico_trabalha_at[$i] == 't')?'t':'f';
							$data_inicio_trei = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$1/$2/$3', $dat);
							if($i == '0' || $lin == 'D'){
							if($lin == 'D'){
								$check_3 = "checked='checked'";
							?>
							<tr>
								<td><input type="checkbox" id="tr_dewalt" disabled name="tr_dewalt" <?php echo $check_3;?> tabindex='39' />&nbsp;<label for="tr_dewalt">Ferramentas DEWALT</label></td>
								<td><input type="text" maxlength='40' disabled style='width: 240px' tabindex='40' value="<?php echo $tec;?>" placeholder='Nome do técnico' name='tr_dewalt_tecnico' /></td>
								<td><input type="text" maxlength='40' disabled style='width: 100px' tabindex='41' name='tr_dewalt_data' value="<?php echo $data_inicio_trei;?>" /></td>
								<td><input type="checkbox" disabled name="tr_dewalt_ativo" <?php if($act == t){?> checked='checked' <?php } ;?> tabindex='42' /></td>
							</tr>
							<?php
							}else{
							?>
							<tr>
								<td><input type="checkbox" id="tr_dewalt" disabled name="tr_dewalt" tabindex='39' />&nbsp;<label for="tr_dewalt">Ferramentas DEWALT</label></td>
								<td><input type="text" maxlength='40' disabled style='width: 240px' tabindex='40' placeholder='Nome do técnico' name='tr_dewalt_tecnico' /></td>
								<td><input type="text" maxlength='40' disabled style='width: 100px' tabindex='41' name='tr_dewalt_data' /></td>
								<td><input type="checkbox" name="tr_dewalt_ativo" disabled tabindex='42' /></td>
							</tr>
							<?php
							}
							}

							if($i == '1' || $lin == 'C'){
							if($lin == 'C'){
								$check_3 = "checked='checked'";
							?>
							<tr>
								<td><input type="checkbox" id="tr_compr" disabled name='tr_compr' tabindex='43' <?php echo $check_3;?> />&nbsp;<label for="tr_compr">Compressores</label></td>
								<td><input type="text" maxlength='40' disabled style='width: 240px' tabindex='44'
									placeholder='Nome do técnico' disabled name='tr_compr_tecnico' value="<?php echo $tec;?>" /></td>
								<td><input type="text" maxlength='40' disabled style='width: 100px' tabindex='45' name='tr_compr_data' value="<?php echo $data_inicio_trei;?>" /></td>
								<td><input type="checkbox" name="tr_compr_ativo" disabled tabindex='46' <?php if($act == t){?> checked='checked' <?php } ;?>/></td>
							</tr>
							<?php
							}else{
							?>
							<tr>
								<td><input type="checkbox" id="tr_compr" name='tr_compr' disabled tabindex='43' />&nbsp;<label for="tr_compr">Compressores</label></td>
								<td><input type="text" maxlength='40' style='width: 240px' disabled tabindex='44'
									placeholder='Nome do técnico' name='tr_compr_tecnico' /></td>
								<td><input type="text" maxlength='40' style='width: 100px' disabled tabindex='45' name='tr_compr_data' /></td>
								<td><input type="checkbox" name="tr_compr_ativo" disabled tabindex='46' /></td>
							</tr>
							<?php
							}
							}

							if($i == '2' || $lin == 'MT'){
							if($lin == 'MT'){
								$check_3 = "checked='checked'";
							?>
							<tr>
								<td><input type="checkbox" id="tr_martelos" disabled  name='tr_martelos' tabindex='47' <?php echo $check_3;?> />&nbsp;<label for="tr_martelos">Martelos</label></td>
								<td><input type="text" maxlength='40' style='width: 240px' disabled tabindex='48'
									placeholder='Nome do técnico' name='tr_martelos_tecnico' value="<?php echo $tec;?>" /></td>
								<td><input type="text" maxlength='40' style='width: 100px' disabled tabindex='49' name='tr_martelos_data' value="<?php echo $data_inicio_trei;?>" /></td>
								<td><input type="checkbox" name="tr_martelos_ativo" disabled tabindex='50' <?php if($act == t){?> checked='checked' <?php } ;?> /></td>
							</tr>
							<?php
							}else{
							?>
							<tr>
								<td><input type="checkbox" id="tr_martelos" name='tr_martelos' disabled tabindex='47'/>&nbsp;<label for="tr_martelos">Martelos</label></td>
								<td><input type="text" maxlength='40' style='width: 240px' disabled tabindex='48'
									placeholder='Nome do técnico' name='tr_martelos_tecnico' /></td>
								<td><input type="text" maxlength='40' style='width: 100px' disabled tabindex='49' name='tr_martelos_data' /></td>
								<td><input type="checkbox" name="tr_martelos_ativo" disabled tabindex='50' /></td>
							</tr>
							<?php
							}
							}
					
					}
							?>
						</tbody>
					</table>
					</fieldset>
					
			<br><!--[if IE]><br><br><![endif]-->
				<fieldset id='fs_outras'>
					<legend>4. Outras Marcas</legend>
					<p>O seu posto de serviços é credenciado para atendimento de outras marcas? Selecione
					Sim ou Não. Caso a resposta seja sim, selecione também a(s) linha(s) atendida(s) e
					informe o nome da(s) marca(s).</p>
				<br><!--[if IE]><br><br><![endif]-->
					<?php 
					if ($cred_outras_marcas == 't') {
						$check_sim_4 = "checked='checked'";
					}else{
						$check_nao_4 = "checked='checked'";
					}
					?>
					<fieldset id='fs_atente_outras' class='bool'>
						<input type="radio" class='required' id="atende_outras_sim" disabled data='o_fs_linhas' <?php echo $check_sim_4;?> name='atende_marcas' tabindex='51' value='t' />
						<label for="atende_outras_sim">Sim</label>&nbsp;&nbsp;
						<input type="radio" id="atende_outras_nao" data='o_fs_linhas' disabled name='atende_marcas' <?php echo $check_nao_4;?> tabindex='52' value='f' />
						<label for="atende_outras_nao">Não</label>
					</fieldset>
				
					
				<br><!--[if IE]><br><br><![endif]-->
					<fieldset id='o_fs_linhas' class='oculto fs_linhas'>
						<legend>4.1. Linhas</legend>
						<?
						$check_4_1 = "checked='checked'";
						for ($i=0; $i < 12; $i++) {
						//echo "CONTEUDO 1 =".$a_linhas_at[$outras_linhas[$i]]."<br>";
						//echo "CONTEUDO 2 =".$lin = $treinamento_na_linha[$i]."<br>";
						if($treinamento_na_linha[$i] == 'A'){
						?>
						<label for="o_AUTO">Automotiva</label>
						<input id="o_AUTO" name="o_AUTO" tabindex='53' disabled type="checkbox" <?php if($treinamento_na_linha[$i] == 'A'){ echo $check_4_1;}?> />&nbsp;
							<input type="text" tabindex='54' name='o_marcas_AUTO' id="o_todas" disabled maxlength='50' value= "<?php echo $outras_marcas[$i];?>" placeholder='Digite as marcas que atende desta linha' />
							<br />
						<?php
						}
						if($treinamento_na_linha[$i] == 'C'){
						?>
						<label for="o_COMP">Compressores</label>
						<input id="o_COMP" name="o_COMP" tabindex='55' type="checkbox" disabled <?php if($treinamento_na_linha[$i] == 'C'){ echo $check_4_1;}?> />&nbsp;
							<input type="text" tabindex='56' name='o_marcas_COMP' id="o_todas" disabled maxlength='50' value= "<?php echo $outras_marcas[$i];?>" placeholder='Digite as marcas que atende desta linha'  />
							<br />
						<?php
						}
						if($treinamento_na_linha[$i] == 'E'){
						?>
						<label for="o_ELET">Eletrodomésticos</label>
						<input id="o_ELET" name="o_ELET" tabindex='57' type="checkbox" disabled  <?php if($treinamento_na_linha[$i] == 'E'){ echo $check_4_1;}?>/>&nbsp;
							<input type="text" tabindex='58' name='o_marcas_ELET' disabled maxlength='50' value= "<?php echo $outras_marcas[$i];?>" placeholder='Digite as marcas que atende desta linha'  />
							<br />
						<?php
						}
						if($treinamento_na_linha[$i] == 'F'){
						?>
						<label for="o_FECH">Fechaduras</label>
						<input id="o_FECH" name="o_FECH" tabindex='59' type="checkbox" disabled <?php if($treinamento_na_linha[$i] == 'F'){ echo $check_4_1;}?>/>&nbsp;
							<input type="text" tabindex='60' name='o_marcas_FECH' disabled maxlength='50' value= "<?php echo $outras_marcas[$i];?>" placeholder='Digite as marcas que atende desta linha'  />
							<br />
						<?php
						}
						if($treinamento_na_linha[$i] == 'B'){
						?>
						<label for="o_FELE">Ferramentas Elétricas</label>
						<input id="o_FELE" name="o_FELE" tabindex='61' type="checkbox" disabled <?php if($treinamento_na_linha[$i] == 'B'){ echo $check_4_1;}?> />&nbsp;
							<input type="text" tabindex='62' name='o_marcas_FELE' disabled  maxlength='50' value= "<?php echo $outras_marcas[$i];?>" placeholder='Digite as marcas que atende desta linha'  />
							<br />
						<?php
						}
						if($treinamento_na_linha[$i] == 'P'){
						?>
						<label for="o_FPNE">Ferramentas Pneumáticas</label>
						<input id="o_FPNE" name="o_FPNE" tabindex='63' type="checkbox" disabled <?php if($treinamento_na_linha[$i] == 'P'){ echo $check_4_1;}?> />&nbsp;
							<input type="text" tabindex='64' name='o_marcas_FPNE' disabled maxlength='50' value= "<?php echo $outras_marcas[$i];?>" placeholder='Digite as marcas que atende desta linha'  />
							<br />
						<?php
						}
						if($treinamento_na_linha[$i] == 'D'){
						?>
						<label for="o_FPRO">Ferramentas Profissionais</label>
						<input id="o_FPRO" name="o_FPRO" tabindex='65' type="checkbox" disabled <?php if($treinamento_na_linha[$i] == 'D'){ echo $check_4_1;}?> />&nbsp;
							<input type="text" tabindex='66' name='o_marcas_FPRO' disabled maxlength='50' value= "<?php echo $outras_marcas[$i];?>" placeholder='Digite as marcas que atende desta linha'  />
							<br />
						<?php
						}
						if($treinamento_na_linha[$i] == 'GR'){
						?>
						<label for="o_GERA">Geradores</label>
						<input id="o_GERA" name="o_GERA" tabindex='67' type="checkbox"  disabled <?php if($treinamento_na_linha[$i] == 'GR'){ echo $check_4_1;}?> />&nbsp;
							<input type="text" tabindex='68' name='o_marcas_GERA'  disabled maxlength='50' value= "<?php echo $outras_marcas[$i];?>" placeholder='Digite as marcas que atende desta linha'  />
							<br />
						<?php
						}
						if($treinamento_na_linha[$i] == 'LS'){
						?>
						<label for="o_LASR">Laser</label>
						<input id="o_LASR" name="o_LASR" tabindex='69' type="checkbox" disabled  <?php if($treinamento_na_linha[$i] == 'LS'){ echo $check_4_1;}?> />&nbsp;
							<input type="text" tabindex='70' name='o_marcas_LASR' disabled maxlength='50' value= "<?php echo $outras_marcas[$i];?>" placeholder='Digite as marcas que atende desta linha'  />
							<br />
						<?php
						}
						if($treinamento_na_linha[$i] == 'LV'){
						?>
						<label for="o_LAVP">Lavadoras de Pressão</label>
						<input id="o_LAVP" name="o_LAVP" tabindex='71' type="checkbox" disabled <?php if($treinamento_na_linha[$i] == 'LV'){ echo $check_4_1;}?> />&nbsp;
							<input type="text" tabindex='72' name='o_marcas_LAVP' disabled maxlength='50' value= "<?php echo $outras_marcas[$i];?>" placeholder='Digite as marcas que atende desta linha'  />
							<br />
						<?php
						}
						if($treinamento_na_linha[$i] == 'M'){
						?>
						<label for="o_METS">Metais Sanitários</label>
						<input id="o_METS" name="o_METS" tabindex='73' disabled type="checkbox" <?php if($treinamento_na_linha[$i] == 'M'){ echo $check_4_1;}?>  />&nbsp;
							<input type="text" tabindex='74' name='o_marcas_METS' disabled maxlength='50' value= "<?php echo $outras_marcas[$i];?>" placeholder='Digite as marcas que atende desta linha'  />
							<br />
						<?php
						}
						if($treinamento_na_linha[$i] == 'GS'){
						?>
						<label for="o_MOTG">Motores à Gasolina</label>
						<input id="o_MOTG" name="o_MOTG" tabindex='75' type="checkbox" disabled <?php if($treinamento_na_linha[$i] == 'GS'){ echo $check_4_1;}?> />&nbsp;
							<input type="text" tabindex='76' name='o_marcas_MOTG' disabled maxlength='50' value= "<?php echo $outras_marcas[$i];?>" placeholder='Digite as marcas que atende desta linha'  />
							<br />
						<?php
						}
					}
					?>
					</fieldset>

					<br><!--[if IE]><br><br><![endif]-->
					<fieldset id='fs_tr_bd'>
						<legend title="Treinamentos">4.2. Treinamentos - Outras Marcas</legend>
						 <p>A sua empresa já recebeu treinamento para as linha(s) selecionada(s) na resposta anterior?
						    Ou seja, as linhas credenciadas para outros fabricantes. Se a resposta for sim, selecione
							a linha e informe o nome do técnico e data do treinamento.
							Caso a resposta seja sim, selecione também a(s) linha(s), informe o nome do técnico, data
							do treinamento e se o técnico treinado ainda trabalha na sua empresa selecione a opção "ativo"
						</p>
					<br><!--[if IE]><br><br><![endif]-->
					<?php 
					if ($treinamento_outras_marcas == 't') {
						$check_sim_4_2 = "checked='checked'";
					}else{
						$check_nao_4_2 = "checked='checked'";
					}
					?>
						<fieldset id='fs_o_treinou' class='bool'>
							<input type="radio" class='required' id="o_tr_sim" disabled name='outras_treinou' <?php echo $check_sim_4_2;?> tabindex='77' data='fs_o_treino' value='t' />
							<label for="o_tr_sim">Sim</label>&nbsp;&nbsp;
							<input type="radio" id="o_tr_nao" name='outras_treinou' disabled tabindex='78' <?php echo $check_nao_4_2;?> data='fs_o_treino' value='f' />
							<label for="o_tr_nao">Não</label>
						</fieldset>
						
				<br><!--[if IE]><br><br><![endif]-->

						<table class='tbl_treino oculto' id='info_treinamentos'>
							<caption>Dados dos Treinamentos</caption>
							<thead>
								<tr style='height:20px;vertical-align:middle;align:center;'>
									<th style='width: 180px'>Linha</th>
									<th style='width: 210px'>Técnico</th>
									<th style='width: 100px'>Data</th>
									<th style='width:  60px'title='O técnico ainda trabalha no Posto?'>Ativo  <sup>?</sup></th>
								</tr>
							</thead>
							<tbody>
						<?php
						for ($i=0; $i < 12; $i++) {
							$lin = $treinamento_na_linha[$i];
							$tec = $treinamento_do_tecnico[$i];
							$dat = $treinamento_em_data[$i];
							$act = ($tecnico_trabalha_posto[$i] == 't')?'t':'f';
							$data_inicio_consulta = preg_replace('/^(\d{2}).(\d{2}).(\d{4})$/', '$1/$2/$3', $dat);
							if($lin == 'A' && $lin <> 'NULL'){
							?>
								<tr>
									<td><input name="o_tr_AUTO" id="o_tr_AUTO" tabindex='79' disabled type="checkbox" <?php if($lin == 'A'){ echo $check_4_1;}?>/>&nbsp;<label for="o_tr_AUTO">Automotiva</label></td>
									<td><input type="text" maxlength='40' style='width: 230px' disabled tabindex='80' value="<?php echo $tec;?>" name='o_tr_AUTO_tecnico' /></td>
									<td><input type="text" maxlength='40' style='width: 100px' disabled tabindex='81' value="<?php echo $data_inicio_consulta;?>" name='o_tr_AUTO_data' /></td>
									<td><input type="checkbox" name="o_tr_AUTO_ativo" tabindex='82' disabled <?php if($act == 't'){ echo $check_4_1;}?> /></td>
								</tr>
							<?php
							}

							if($lin == 'C'){
							?>
								<tr>
									<td><input name="o_tr_COMP" id="o_tr_COMP" tabindex='83' type="checkbox" disabled <?php if($lin == 'C'){ echo $check_4_1;}?>/>&nbsp;<label for="o_tr_COMP">Compressores</label></td>
									<td><input type="text" maxlength='40' style='width: 230px' tabindex='84' disabled value="<?php echo $tec;?>" name='o_tr_COMP_tecnico' /></td>
									<td><input type="text" maxlength='40' style='width: 100px' tabindex='85' disabled value="<?php echo $data_inicio_consulta;?>" name='o_tr_COMP_data' /></td>
									<td><input type="checkbox" name="o_tr_COMP_ativo" disabled tabindex='86' <?php if($act == 't'){ echo $check_4_1;}?>/></td>
								</tr>
							<?php
							}
							if($lin == 'E'){
							?>
								<tr>
									<td><input name="o_tr_ELET" id="o_tr_ELET" tabindex='87' type="checkbox" disabled <?php if($lin == 'E'){ echo $check_4_1;}?>/>&nbsp;<label for="o_tr_ELET">Eletrodomésticos</label></td>
									<td><input type="text" maxlength='40' style='width: 230px' tabindex='88' disabled value="<?php echo $tec;?>" name='o_tr_ELET_tecnico' /></td>
									<td><input type="text" maxlength='40' style='width: 100px' tabindex='89' disabled value="<?php echo $data_inicio_consulta;?>" name='o_tr_ELET_data' /></td>
									<td><input type="checkbox" name="o_tr_ELET_ativo" tabindex='90' disabled <?php if($act == 't'){ echo $check_4_1;}?> /></td>
								</tr>
							<?php
							}
							if($lin == 'F'){
							?>
								<tr>
									<td><input name="o_tr_FECH" id="o_tr_FECH" tabindex='91' type="checkbox" disabled <?php if($lin == 'F'){ echo $check_4_1;}?> />&nbsp;<label for="o_tr_FECH">Fechaduras</label></td>
									<td><input type="text" maxlength='40' style='width: 230px' tabindex='92' disabled value="<?php echo $tec;?>" name='o_tr_FECH_tecnico' /></td>
									<td><input type="text" maxlength='40' style='width: 100px' tabindex='93' disabled value="<?php echo $data_inicio_consulta;?>"  name='o_tr_FECH_data' /></td>
									<td><input type="checkbox" name="o_tr_FECH_ativo" tabindex='94' disabled <?php if($act == 't'){ echo $check_4_1;}?> /></td>
								</tr>
							<?php
							}
							if($lin == 'B'){
							?>
								<tr>
									<td><input name="o_tr_FELE" id="o_tr_FELE" tabindex='95' type="checkbox" disabled <?php if($lin == 'B'){ echo $check_4_1;}?> />&nbsp;<label for="o_tr_FELE">Ferramentas Elétricas</label></td>
									<td><input type="text" maxlength='40' style='width: 230px' tabindex='96' disabled value="<?php echo $tec;?>" name='o_tr_FELE_tecnico' /></td>
									<td><input type="text" maxlength='40' style='width: 100px' tabindex='97' disabled value="<?php echo $data_inicio_consulta;?>" name='o_tr_FELE_data' /></td>
									<td><input type="checkbox" name="o_tr_FELE_ativo" disabled tabindex='98' <?php if($act == 't'){ echo $check_4_1;}?> /></td>
								</tr>
							<?php
							}
							if($lin == 'P'){
							?>
								<tr>
									<td><input name="o_tr_FPNE" id="o_tr_FPNE" tabindex='99' disabled type="checkbox" <?php if($lin == 'P'){ echo $check_4_1;}?> />&nbsp;<label for="o_tr_FPNE">Ferramentas Pneumáticas</label></td>
									<td><input type="text" maxlength='40' style='width: 230px' disabled tabindex='100' value="<?php echo $tec;?>" name='o_tr_FPNE_tecnico' /></td>
									<td><input type="text" maxlength='40' style='width: 100px' disabled tabindex='101' value="<?php echo $data_inicio_consulta;?>" name='o_tr_FPNE_data' /></td>
									<td><input type="checkbox" name="o_tr_FPNE_ativo" tabindex='102' disabled <?php if($act == 't'){ echo $check_4_1;}?> /></td>
								</tr>
							<?php
							}
							if($lin == 'D'){
							?>
								<tr>
									<td><input name="o_tr_FPRO" id="o_tr_FPRO" tabindex='103' disabled type="checkbox" <?php if($lin == 'D'){ echo $check_4_1;}?> />&nbsp;<label for="o_tr_FPRO">Ferramentas Profissionais</label></td>
									<td><input type="text" maxlength='40' style='width: 230px' disabled tabindex='104' value="<?php echo $tec;?>" name='o_tr_FPRO_tecnico' /></td>
									<td><input type="text" maxlength='40' style='width: 100px' disabled tabindex='105' value="<?php echo $data_inicio_consulta;?>" name='o_tr_FPRO_data' /></td>
									<td><input type="checkbox" name="o_tr_FPRO_ativo" disabled tabindex='106' <?php if($act == 't'){ echo $check_4_1;}?> /></td>
								</tr>
							<?php
							}
							if($lin == 'GR'){
							?>
								<tr>
									<td><input name="o_tr_GERA" id="o_tr_GERA" tabindex='107' disabled type="checkbox" <?php if($lin == 'GR'){ echo $check_4_1;}?>/>&nbsp;<label for="o_tr_GERA">Geradores</label></td>
									<td><input type="text" maxlength='40' style='width: 230px' disabled tabindex='108' value="<?php echo $tec;?>" name='o_tr_GERA_tecnico' /></td>
									<td><input type="text" maxlength='40' style='width: 100px' disabled tabindex='109' value="<?php echo $data_inicio_consulta;?>" name='o_tr_GERA_data' /></td>
									<td><input type="checkbox" name="o_tr_GERA_ativo" tabindex='110' disabled <?php if($act == 't'){ echo $check_4_1;}?> /></td>
								</tr>
							<?php
							}
							if($lin == 'LS'){
							?>
								<tr>
									<td><input name="o_tr_LASR" id="o_tr_LASR" id="o_tr_LASR" disabled tabindex='111' type="checkbox" <?php if($lin == 'LS'){ echo $check_4_1;}?>/>&nbsp;<label for="o_tr_LASR">Laser</label></td>
									<td><input type="text" maxlength='40' style='width: 230px' disabled tabindex='112' value="<?php echo $tec;?>" name='o_tr_LASR_tecnico' /></td>
									<td><input type="text" maxlength='40' style='width: 100px' disabled tabindex='113' value="<?php echo $data_inicio_consulta;?>" name='o_tr_LASR_data' /></td>
									<td><input type="checkbox" name="o_tr_LASR_ativo" tabindex='114' disabled <?php if($act == 't'){ echo $check_4_1;}?> /></td>
								</tr>
							<?php
							}
							if($lin == 'LV'){
							?>
								<tr>
									<td><input name="o_tr_LAVP" id="o_tr_LAVP" tabindex='115' disabled type="checkbox" <?php if($lin == 'LV'){ echo $check_4_1;}?>/>&nbsp;<label for="o_tr_LAVP">Lavadoras de Pressão</label></td>
									<td><input type="text" maxlength='40' style='width: 230px' disabled tabindex='116' value="<?php echo $tec;?>" name='o_tr_LAVP_tecnico' /></td>
									<td><input type="text" maxlength='40' style='width: 100px' disabled tabindex='117' value="<?php echo $data_inicio_consulta;?>" name='o_tr_LAVP_data' /></td>
									<td><input type="checkbox" name="o_tr_LAVP_ativo" tabindex='118' disabled <?php if($act == 't'){ echo $check_4_1;}?> /></td>
								</tr>
							<?php
							}
							if($lin == 'M'){
							?>
								<tr>
									<td><input name="o_tr_METS" id="o_tr_METS" tabindex='119' disabled type="checkbox" <?php if($lin == 'M'){ echo $check_4_1;}?>/>&nbsp;<label for="o_tr_METS">Metais Sanitários</label></td>
									<td><input type="text" maxlength='40' style='width: 230px' disabled tabindex='120' value="<?php echo $tec;?>" name='o_tr_METS_tecnico' /></td>
									<td><input type="text" maxlength='40' style='width: 100px' disabled tabindex='121' value="<?php echo $data_inicio_consulta;?>" name='o_tr_METS_data' /></td>
									<td><input type="checkbox" name="o_tr_METS_ativo" disabled tabindex='122' <?php if($act == 't'){ echo $check_4_1;}?> /></td>
								</tr>
							<?php
							}
							if($lin == 'GS'){
							?>
								<tr>
									<td><input name="o_tr_MOTG" id="o_tr_MOTG" tabindex='123' disabled type="checkbox" <?php if($lin == 'GS'){ echo $check_4_1;}?>/>&nbsp;<label for="o_tr_MOTG">Motores à Gasolina</label></td>
									<td><input type="text" maxlength='40' style='width: 230px' disabled tabindex='124' value="<?php echo $tec;?>" name='o_tr_MOTG_tecnico' /></td>
									<td><input type="text" maxlength='40' style='width: 100px' disabled tabindex='125' value="<?php echo $data_inicio_consulta;?>"name='o_tr_MOTG_data' /></td>
									<td><input type="checkbox" name="o_tr_MOTG_ativo" tabindex='126' disabled <?php if($act == 't'){ echo $check_4_1;}?> /></td>
								</tr>
							<?php
							}
						}
						?>
							</tbody>
						</table>
					</fieldset>
			</fieldset>
			<br><br><!--[if IE]><br><br><![endif]-->
			<fieldset id='fs_banco'>

				<legend>5. Dados Bancários</legend>
				<p>Por favor, verifique os dados bancários abaixo. Esses são os dados cadastrados para
				a sua empresa junto à B&D. Solicitamos que faça a confirmação se estiverem corretos
				e apenas se houve alguma alteração ou existir algum erro nos dados faça a correção
				informando os dados corretos. É importante que a conta informada seja jurídica e esteja
				em nome da empresa, caso contrário o sistema recusará a operação no momento do
				depósito, o que causará atraso no pagamento.</p>
		<br><!--[if IE]><br><br><![endif]-->

				<fieldset id='fs_info_banco'>
					<legend>5.1. Dados cadastrados para a sua empresa:</legend>
			
					<p>Confirmar os dados?</p>
					<?php 
					if ($confirma_dados_banco == 't') {
						$check_sim_5 = "checked='checked'";
					}else{
						$check_nao_5 = "checked='checked'";
					}
					?>
				<fieldset class='bool'>
					<input type="radio" class='required' id="banco_sim" name='banco_ok' disabled tabindex='127' <?php echo $check_sim_5;?> data='dados_banco' value='t' />
					<label for="banco_sim">Sim</label>&nbsp;&nbsp;
					<input type="radio" id="banco_nao" name='banco_ok' tabindex='128' disabled data='dados_banco' <?php echo $check_nao_5;?> value='f' />
					<label for="banco_nao">Não</label>
				</fieldset>
		<br><!--[if IE]><br><br><![endif]-->
				<fieldset id="dados_banco" class='oculto' style="width:550px;">
					<legend>5.2. Alterar Dados Bancários</legend>
					<label for="banco">Banco</label>
						<input type="text" name="banco" id="banco" style='width:310px' tabindex='130' disabled value="<?php echo $entidade;?>" title='Digite o nome do Banco ou o nº da Entidade' />
						<span id="nome_banco" style='margin-left:2em;'></span>
						<input type='hidden' id='banco_nome' name='banco_nome' />
						<!--placeholder='Núm. ou nome' -->
					<br />
					<label for="agencia">Agência</label>
						<input type="text" class='number' name="banco_agencia" id="banco_agencia" disabled style='width:100px' value="<?php echo $agencia;?>" tabindex='131' />
					<label for="conta" style='width:80px'>Nº de Conta</label>
						<input type="text" name="banco_conta" id="banco_conta" maxlength='10' disabled style='width:120px' value="<?php echo $conta;?>" tabindex='132' />
					<br>
				</fieldset>
					
				</fieldset>
		<br><!--[if IE]><br><br><![endif]-->
			</fieldset>
			
				<fieldset style="border:0;" id='responsavel'><label for="responsavel_questionario" disabled style="width:80%;" class='normal'>Informe o nome do responsável pelas respostas desse questionário:</label>
					<input type="text" name="responsavel_questionario" maxlength="30" style="width:300px;" disabled id="responsavel_questionario"
				    placeholder='Digite seu nome' tabindex='133' value="<?php echo $responsavel_cadastro;?>" />
				</fieldset>
			<div id="msgErro"></div>
		</fieldset>
	</form>
<!--	<div class='erro'> TODOS OS CAMPOS EM VERMELHO SÃO OBRIGATÓRIOS </DIV> -->
	</div>
	<!-- -->
</div>
<?php
}
include 'rodape.php'; 
?>

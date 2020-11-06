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
pre_echo($_POST, 'Form Data');
if (count(array_filter($_POST)) > 1) {

	$codigo_posto = anti_injection($_POST['posto_codigo']);

	if ($codigo_posto == '') {
		$msg_erro = 'Por favor, informe o código do Posto que gostaria consultar.';
	} else {
		$sql  = "SELECT tbl_at_postos_black.posto,
							tbl_at_postos_black.responsavel_cadastro,
							tbl_at_postos_black.data,
							tbl_at_postos_black.fantasia,
							tbl_at_postos_black.telefone,
							tbl_at_postos_black.telefone_fax,
							tbl_at_postos_black.contato_1,
							tbl_at_postos_black.email_contato_1,
							tbl_at_postos_black.contato_2,
							tbl_at_postos_black.email_contato_2,
							tbl_at_postos_black.distrib_black,
							tbl_at_postos_black.distribuidor,
							tbl_at_postos_black.at_consumidor,
							tbl_at_postos_black.at_revenda,
							tbl_at_postos_black.per_consumidor,
							tbl_at_postos_black.per_revenda,
							tbl_at_postos_black.linhas_black,
							tbl_at_postos_black.per_linhas_bd,
							tbl_at_postos_black.treinamento_bd,
							ARRAY_TO_STRING(tbl_at_postos_black.treino_linhas, '#') AS treino_linhas,
							ARRAY_TO_STRING(tbl_at_postos_black.treino_tecnicos, '#') AS treino_tecnicos,
							ARRAY_TO_STRING(tbl_at_postos_black.treino_datas, '#') AS treino_datas,
							ARRAY_TO_STRING(tbl_at_postos_black.treino_ativos, '#') AS treino_ativos,
							tbl_at_postos_black.outras_atende,
							ARRAY_TO_STRING(tbl_at_postos_black.outras_linhas, '#') AS outras_linhas,
							ARRAY_TO_STRING(tbl_at_postos_black.outras_marcas, '#') AS outras_marcas,
							tbl_at_postos_black.outras_treino,
							ARRAY_TO_STRING(tbl_at_postos_black.o_tr_linhas, '#') AS o_tr_linhas,
							ARRAY_TO_STRING(tbl_at_postos_black.o_tr_tecnicos, '#') AS o_tr_tecnicos,
							ARRAY_TO_STRING(tbl_at_postos_black.o_tr_datas, '#') AS o_tr_datas,
							ARRAY_TO_STRING(tbl_at_postos_black.o_tr_ativos, '#') AS o_tr_ativos,
							tbl_at_postos_black.dados_banco_ok,
							tbl_posto.nome,
							tbl_posto.cnpj,
							tbl_posto_fabrica.banco,
							tbl_posto_fabrica.nomebanco,
							tbl_posto_fabrica.agencia,
							tbl_posto_fabrica.contay,
							tbl_posto_fabrica.tipo_conta
					  FROM tbl_at_postos_black
					  JOIN tbl_posto USING(posto)
					  JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_at_postos_black.posto
					                        AND tbl_posto_fabrica.fabrica = $login_fabrica";

		if ($btn_acao == 'consultar') { //Joga os dados na tela

			$res = pg_query($con, $sql .= " WHERE tbl_posto_fabrica.codigo_posto = '$codigo_posto'");

			if (is_resource($res)) {
				if (pg_num_rows($res)) $info_posto = pg_fetch_assoc($res, 0);
				if (!is_array($info_posto) or pg_num_rows($res) == 0)
					$msg_erro = "Sem resultados para o Posto <b>$posto_codigo</b>.";
			} else {
				$msg_erro = "Erro ao ler as informações o Posto <b>$posto_codigo</b>. Tente novamente daqui uns segundos.";
			}
		}

		if ($btn_acao == 'relatorio') { //Gera o relatório para download

			$res = pg_query($con, $sql);

			$formato_arquivo = 'xls';
			if (is_resource($res)) {
				if ($formato_arquivo == 'xls') {
					define('XLS_FMT', TRUE);
					define('LF', '<br />');
				} else {
					define('XLS_FMT', FALSE);
					define('LF', "\n");
				}

				if (pg_num_rows($res) > 0) { //Tem resultados...
					$hoje = date('Y-m-d');
					$total= pg_num_rows($res);

					if (XLS_FMT) {
						header('Content-type: application/msexcel');
						header("Content-Disposition: attachment; filename=dados_atualizados_postos_$hoje.xls");
					} else {
						header('Content-type: text/csv');
						header("Content-Disposition: attachment; filename=dados_atualizados_postos_$hoje.csv");
					}
					$row = pg_fetch_assoc($res, 0);
					$linha = array(
						'posto'					=> $row['codigo_posto'],
						'razao_social'  		=> $row['nome'],
						'data_atualizacao'		=> $row['data'],
						'fantasia'          	=> $row['fantasia'],
						'telefone'          	=> $row['telefone'],
						'telefone_fax'			=> $row['telefone_fax'],
						'contato'           	=> $row['contato_1'],
						'e-mail'            	=> $row['email_contato_1'],
						'contato_alternativo'	=> $row['contato_2'],
						'e-mail_alternativo'	=> $row['email_contato_2'],

						'compra_fabrica'    	=> $row['distrib_black'],
						'Distribuidor'      	=> $row['distribuidor'],

						'at_consumidor'     	=> $row['at_consumidor'],
						'at_revenda'        	=> $row['at_revenda'],
						'percentual_consumidor'	=> $row['per_consumidor'],
						'percentual_revenda'	=> $row['per_revenda'],
						'linhas_atendimento'	=> $row['linhas_black'],
						'percentual_linhas'		=> $row['per_linhas_bd'],

						'treinamento_fabrica'   => $row['treinamento_bd'],
						'treinamento_linha'     => $row['treino_linhas'],
						'treinamento_tecnico'   => $row['treino_tecnicos'],
						'treinamento_data'      => $row['treino_datas'],
						'tecnico_trabalha_at'   => $row['treino_ativos'],

						'cred_outras_marcas'    => $row['outras_atende'],
						'outras_linhas'			=> $row['outras_linhas'],
						'outras_marcas'			=> $row['outras_marcas'],

						'treinamento_outras_marcas'=>$row['outras_treino'],
						'treinamento_na_linha'	=> $row['outras_linhas'],
						'treinamento_do_tecnico'=> $row['outras_tecnicos'],
						'treinamento_em_data'	=> $row['outras_datas'],
						'tecnico_trabalha_posto'=> $row['ouatras_ativo'],

						'confirma_dados_banco'	=> $row['dados_banco_ok'],
						'banco'					=> $row['banco'],
						'entidade'				=> $row['nomebanco'],
						'agencia'				=> $row['agencia'],
						'conta'					=> $row['contay'],
						'tipo_conta'			=> $row['tipo_conta'],
						'responsavel_cadastro'  => $row['responsavel_cadastro']
					);

					// Formata o nº de conta bancária
					$conta_bancaria = "Entidade: " . $linha['banco'] .
										" ({$linha['entidade']})," . LF .
										"<b>Ag.</b>: {$linha['agencia']}" . LF .
										"<b>Conta</b>: {$linha['conta']}" . LF .
										"({$linha['tipo_conta']}";
					$responsavel_cadastro = $linha['responsavel_cadastro'];
					unset(
						$linha['banco'],
						$linha['entidade'],
						$linha['agencia'],
						$linha['conta'],
						$linha['tipo_conta'],
						$linha['responsavel_cadastro']
					);
					$linha['conta_bancaria']		= $conta_bancaria;
					$linha['responsavel_cadastro']	= $responsavel_cadastro;

					//Formata os arrays que vieram do banco
					$temp_linhas = explode(',', $linha['linhas_atendimento']);
					foreach ($temp_linhas as $cod_linha) {
						$lista[] = $a_linhas_at[$cod_linha];
					}
					$linha['linhas_atendimento'] = implode(LF, $lista);
					unset($temp_linhas, $lista);

					$campos['linhas_per_bd'] = implode(' %' . LF, explode(',', $campos['linhas_per_bd'])) . ' %';

					foreach($campos as $campo) {
						$campo = str_replace('_', ' ', $campo);
						$xls_header  .= "<th>$campo</th>";
						$csv_campos[] = $campo;
					}

					if (XLS_FMT) {  // Monta o cabeçalho com os nomes dos campos, XLS-fake ou CSV
						echo "<table><thead><tr>$xls_header</tr></thead><tbody>";
					} else {
						echo implode(";", $csv_campos); //CSV
					}
					echo "\n";
					for ($i=0; $i < $total; $i++) {
			        	$row = pg_fetch_assoc($res, $i);
						$xls_linha = "\t\t<tr>\n";
						unset($csv_linha); //array

						foreach($row as $key => $campo) {
							$campo = str_replace("\t", ' ', $campo); //Retira a tabulação
							if ($formato != 'xls') $campo = str_replace("\n", LF, $campo); //Retira a quebra de linha, substinui ela peloa constante LF
							if (stripos($key, 'cpf') !== false or stripos($key, 'cnpj') !== false) {
								$campo = (strlen($campo) == 14) ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', "$1.$2.$3/$4-$5", $campo) : // CNPJ
																  preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', "$1.$2.$3-$4", $campo); // CPF, vai que um dia precisa...
							}
							$xls_linha  .= "\t\t\t<td>$campo</td>\n";
							$csv_linha[] = (preg_match('/(\s|\n|\r|;)/', $campo) or //Entre aspas se tiver aglum tipo de espaço ou dígito grande, tipo nº série
											in_array($key, array('referencia','nota_fiscal','serie','peca_referencia'))) ? "\"$campo\"" : $campo;
						}
						echo (XLS_FMT) ? "$linha\t\t</tr>" : implode(";", $csv_linha);
						echo "\n";
					}
					if (XLS_FMT) echo "\t</tbody>\n</table>";
					exit; // FIM do arquivo 'Excel'
				} else {
					$msg_erro = 'Sem dados para o período selecionado.';
				}
			} else { // Não deu erro no banco...
				$msg_erro = 'Erro ao recuperar os dados';
			}
		}
    }
}


/* Include cabeçalho Admin */
	$title = "Telecontrol - Assistência Técnica - Título da Tela";
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

caption,.titulo_tabela {
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
.texto_avulso {
    font: 14px Arial; color: rgb(89, 109, 155);
    background-color: #d9e2ef;
    text-align: center;
    width: 700px;
    margin: 0 auto;
    border-collapse: collapse;
    border:1px solid #596d9b;
}
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
<form action="<?=$PHP_SELF?>" name="frm_posto_atualiza" style='margin:auto;text-align:center;' method="post">
	<table align='center' class="formulario" style='table-layout:fixed;width:700px'>
		<caption border='1'>Parâmetros de Pesquisa</caption>
		<thead>
			<tr>
				<th style='width:120px'>&nbsp;</th>
				<th style='width:135px'>&nbsp;</th>
				<th style='width:110px'>&nbsp;</th>
				<th>&nbsp;</th>
			</tr>
			</thead>
			<tbody style='text-align:left;'>
			<tr>
				<td>&nbsp;</td>
				<td>
					<label for="data_inicial">&nbsp;Código do Posto *</label>
				</td>
				<td>&nbsp;</td>
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
					<button type='button' id='consultar' value='Consultar'>Consultar</button>
					&nbsp;&nbsp;&nbsp;
					<button name='btl_limpar' type='reset'  id='btn_limpar'>Limpar</button>
				</td>
			</tr>
			<tr><td colspan='4'>&nbsp;</td></tr>
			<tr>
				<td colspan="4" align='center' style='text-align:center!important'>
					<p>Para fazer download do arquivo com as informações dos postos, clique no botão 'Download'</p>
					<button  type='button' id='btn_todos' value='todos'
							title='Gera o arquivo de atualização para download'>Download</button>
				</td>
			</tr>
			<tr><td colspan='4'>&nbsp;</td></tr>
		</tbody>
	</table>
</form>
<p>&nbsp;</p>

<?
if (is_array($info_posto)) {
//pre_echo($info_posto, 'Informações do posto ' . $codigo_posto);
	//Cadastro
	$linha = array(
		'posto'					=> $info_posto['codigo_posto'],
		'razao_social'  		=> $info_posto['nome'],
		'cnpj'                  => $info_posto['cnpj'],
		'data_atualizacao'		=> $info_posto['data'],
		'fantasia'          	=> $info_posto['fantasia'],
		'telefone'          	=> $info_posto['telefone'],
		'telefone_fax'			=> $info_posto['telefone_fax'],
		'contato'           	=> $info_posto['contato_1'],
		'e-mail'            	=> $info_posto['email_contato_1'],
		'contato_alternativo'	=> $info_posto['contato_2'],
		'e-mail_alternativo'	=> $info_posto['email_contato_2'],

		'compra_fabrica'    	=> $info_posto['distrib_black'],
		'distribuidor'      	=> $info_posto['distribuidor'],

		'at_consumidor'     	=> $info_posto['at_consumidor'],
		'at_revenda'        	=> $info_posto['at_revenda'],
		'percentual_consumidor'	=> $info_posto['per_consumidor'],
		'percentual_revenda'	=> $info_posto['per_revenda'],
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
		'conta'					=> $info_posto['contay'],
		'tipo_conta'			=> $info_posto['tipo_conta'],
		'responsavel_cadastro'  => $info_posto['responsavel_cadastro']
	);
pre_echo($linha);

	// Formata o nº de conta bancária
	$conta_bancaria = "<b>Entidade</b>: " . $linha['banco'] .
						" ({$linha['entidade']}),<br />"  .
						"<b>Ag.</b>: {$linha['agencia']}&nbsp;&ndash;&nbsp;" .
						"<b>Conta</b>: {$linha['conta']}" .
						"({$linha['tipo_conta']})";
	$responsavel_cadastro = $linha['responsavel_cadastro'];

	unset(
		$linha['banco'],
		$linha['entidade'],
		$linha['agencia'],
		$linha['conta'],
		$linha['tipo_conta'],
		$linha['responsavel_cadastro']
	);
	$linha['conta_bancaria']		= $conta_bancaria;
	$linha['responsavel_cadastro']	= $responsavel_cadastro;

	//Formata os arrays que vieram do banco
	echo $linha['linhas_atendimento'] = explode(',', $linha['linhas_atendimento']);
	echo $linha['percentual_linhas']  = explode(',', $linha['percentual_linhas']);
	unset($temp_linhas, $lista);

/*
							ARRAY_TO_STRING(tbl_at_postos_black.treino_linhas, '#') AS treino_linhas,
							ARRAY_TO_STRING(tbl_at_postos_black.treino_tecnicos, '#') AS treino_tecnicos,
							ARRAY_TO_STRING(tbl_at_postos_black.treino_datas, '#') AS treino_datas,
							ARRAY_TO_STRING(tbl_at_postos_black.treino_ativos, '#') AS treino_ativos,
							tbl_at_postos_black.outras_atende,
							ARRAY_TO_STRING(tbl_at_postos_black.outras_linhas, '#') AS outras_linhas,
							ARRAY_TO_STRING(tbl_at_postos_black.outras_marcas, '#') AS outras_marcas,
							tbl_at_postos_black.outras_treino,
							ARRAY_TO_STRING(tbl_at_postos_black.o_tr_linhas, '#') AS o_tr_linhas,
							ARRAY_TO_STRING(tbl_at_postos_black.o_tr_tecnicos, '#') AS o_tr_tecnicos,
							ARRAY_TO_STRING(tbl_at_postos_black.o_tr_datas, '#') AS o_tr_datas,
							ARRAY_TO_STRING(tbl_at_postos_black.o_tr_ativos, '#') AS o_tr_ativos,


*/



pre_echo($linha);
	extract($linha);






?>
<div style='width:700px;margin:auto' class='formulario'>
	<p>Informações do Posto autorizado, atualizadas em data: <?=$data?></p>
	<p>Responsável do preenchimento: <?=$responsavel_cadastro?></p>
	<fieldset>
		<legend>Dados do Posto</legend>
		<dl>
			<dt>Razão Social</dt>
			<dd><?=$razao_social?></dd>
			<dt>CNPJ</dt>
			<dd><?=$cnpj?></dd>
			<br />
			<dt>Nome Fantasia</dt>
			<dd><?=$fantasia?></dd>
			<br />
			<dt>Telefone</dt>
			<dd><?=$telefone?></dd>
			<dt>FAX</dt>
			<dd><?=$telefone_fax?></dd>
			<dt>Dados Bancários</dt>
			<dd><?=$conta_bancaria?></dd>
		</dl>
	</fieldset>
	<fieldset>
		<legend>Dados de Contato</legend>
		<dl>Pessoa de Contato I
			<dt>Nome</dt>
			<dd><?=$contato?></dd>
			<dt>E-Mail</dt>
			<dd><?=$email?></dd>
		</dl>
	<? if ($contato_2 != '') { ?>
		<dl>Pessoa de Contato II
			<dt>Nome</dt>
			<dd><?=$contato_alternativo?></dd>
			<dt>E-Mail</dt>
			<dd><?=$email_contato_alternativo?></dd>
		</dl>
	<? } ?>
	</fieldset>
	<fieldset>
		<legend>Estoque</legend>
		<?	if ($distrib_black == 't') {
				echo "<p>O Posto compra as peças diretamente da <b>Black & Decker</b>.</p>";
			} else { ?>
		<dl>
			<dt>Distribuidor:</dt>
			<dd><?=$distribuidor?></dd>
		</dl>
	<? } ?>
	</fieldset>
	<fieldset>
		<legend>Atendimento Black & Decker</legend>
		<dl>
			<dt>OS Consumidor / mês</dt>
			<dd><?=$at_consumidor?> (representa um <?=$percentual_consumidor?>% dos atendimentos para a B&D).</dd>
			<dt>OS Revenda / mês</dt>
			<dd><?=$at_revenda?> (representa um <?=$percentual_revenda?>% dos atendimentos para a B&D).</dd>
			<dt>Linhas que atende e percentual sobre o total de atendimentos:</dt>
			<dd>
				<table>
					<thead>
						<tr>
							<th>Linha</th>
							<th>Per.</th>
						</tr>
					</thead>
					<tbody>
					<?
					for ($i=0; $i < count($linhas_atendimento); $i++) {
						echo "<tr><td>" . $a_linhas_at[$linhas_atendimento[$i]] .
							 "</td><td align='right'>". number_format($percentual_linhas[$i], 2) . " %</td></tr>\n";
		            }
					?>
					</tbody>
				</table>
			</dd>
		</dl>
	</fieldset>
	<fieldset>
		<legend>Treinamentos Black & Decker</legend>
	<? if ($treinamento_fabrica == 't') { ?>
				<table>
					<thead>
						<tr>
							<th>Linha</th>
							<th>Técnico</th>
							<th>Data</th>
							<th title='O técnico ainda trabalha neste P.A.?'>Técnico</th>
						</tr>
					</thead>
					<tbody>
					<?
					for ($i=0; $i < count($treinamento_linha); $i++) {
						$lin = $treinamento_linha[$i];
						$tec = $treinamento_tecnico[$i];
						$dat = $treinamento_data[$i];
						$act = ($tecnico_trabalha_at[$i] == 't')?'Sim':'Não';
						?>
						<tr>
							<td><?=$lin?></td>
							<td><?=$tec?></td>
							<td align='right'><?=$dat?></td>
							<td align='center'><?=$act?></td>
						</tr>
					<?}?>
					</tbody>
				</table>
	<?} else {?>
		<p>Nenhum Treinamento foi informado</p>
	<?}?>
	</fieldset>
	<? if ($cred_outras_marcas == 't') { ?>
	<fieldset>
		<legend>Outras Marcas</legend>
		<dl>
			<dt>Marcas atendidas por linha de Credenciamento</dt>
			<dd>
				<table class='relatorio'>
					<thead>
						<tr>
							<th>Linha</th>
							<th>Marca(s)</th>
						</tr>
					</thead>
					<tbody>
					<?
					for ($i=0; $i < count($outras_linhas); $i++) {
						echo "<tr valign='top'><td>" . $a_linhas_at[$outras_linhas[$i]] .
							 "</td><td>{$outras_marcas[$i]}</td></tr>\n";
		            }
					?>
					</tbody>
				</table>
			</dd>
		</dl>
	</fieldset>
	<fieldset>
		<legend>Treinamentos Outras Marcas</legend>
	<? if ($treinamento_outras_marcas == 't') { ?>
				<table>
					<thead>
						<tr>
							<th>Linha</th>
							<th>Técnico</th>
							<th>Data</th>
							<th title='O técnico ainda trabalha neste P.A.?'>Técnico</th>
						</tr>
					</thead>
					<tbody>
					<?
					for ($i=0; $i < count($treinamento_na_linha); $i++) {
						$lin = $treinamento_na_linha[$i];
						$tec = $treinamento_do_tecnico[$i];
						$dat = $treinamento_em_data[$i];
						$act = ($tecnico_trabalha_posto[$i] == 't')?'Sim':'Não';
						?>
						<tr>
							<td><?=$lin?></td>
							<td><?=$tec?></td>
							<td align='right'><?=$dat?></td>
							<td align='center'><?=$act?></td>
						</tr>
					<?}?>
					</tbody>
				</table>
	<?} else {?>
		<p>Nenhum Treinamento foi informado</p>
	<?}?>
	</fieldset>
	<?}?>
</div>

<?}?>
<? include 'rodape.php'; ?>

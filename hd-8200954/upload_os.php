<?php
	include_once 'dbconfig.php';
	include_once 'includes/dbconnect-inc.php';
	include 'autentica_usuario.php';

	$title = "UPLOAD DE OS";
	include_once 'cabecalho.php';

	/*error_reporting(E_ALL);
	ini_set('display_errors', 1);*/

	$layout = array (
		'fabrica'                      => "C�digo do Fabricante ($login_fabrica)",
		'cnpj_posto'                   => 'CNPJ do Posto Autorizado ',
		'os'                           => 'N�mero da Ordem de Servi�o ',
		'sequencial'                   => 'Sequencial em caso de Ordem de Servi�o de Revenda ',
		'consumidor_revenda'           => '"C" para Ordem de Servi�o de Consumidor e "R" para Revenda ',
		'data_abertura'                => 'Data da abertura',
		'data_fechamento'              => 'Data de fechamento (nulo se ainda n�o fechada)',
		'produto_referencia'           => 'Refer�ncia do Produto',
		'produto_serie'                => 'N�mero de S�rie',
		'cliente_documento'            => 'CPF ou CNPJ do Consumidor ',
		'cliente_nome'                 => 'Nome do Consumidor ',
		'cliente_fone'                 => 'Telefone do Consumidor ',
		'revenda_cnpj'                 => 'CNPJ da Revenda ',
		'revenda_nome'                 => 'Nome da Revenda ',
		'revenda_fone'                 => 'Fone da Revenda ',
		'nota_fiscal'                  => 'N�mero da Nota Fiscal de Compra ',
		'data_compra'                  => 'Data da Compra ',
		'defeito_reclamado_descricao'  => 'C�digo do Defeito Reclamado ',
		'defeito_constatado_descricao' => 'C�digo do Defeito Constatado ',
		'causa_defeito'                => 'C�digo da Causa do Defeito ',
		'peca'                         => 'Refer�ncia da Pe�a ',
		'qtde'                         => 'Quantidade trocada da Pe�a ',
		'defeito'                      => 'C�digo do Defeito da Pe�a ',
		'identificacao'                => 'C�digo do Servi�o Realizado ',
		'tipo_atendimento'             => 'Tipo de Atendimento',
		'segmento_atuacao'             => 'Segmento de atua��o',
		'promotor_treinamento'         => 'Promotor',
		'satisfacao'                   => 'Satisfa��o',
		'laudo'                        => 'Laudo',
		'subproduto'                   => 'Subproduto',
		'posicao'                      => 'Posi��o',
		'solucao'                      => 'C�digo da Solu��o (Utilizar a mesma tabela do Servi�o Realizado)'
	);

	$layout_model = $layout;

	if ( isset ( $_POST['enviar'] ) ) {

		try {

			$type = $_FILES['arquivo']['type'];

			if ( !in_array( 'text', explode('/', $type) ) ) {

				throw new Exception("Arquivo n�o encontrado ou n�o permitido");				

			}

			$fp = fopen( $_FILES['arquivo']['tmp_name'], "r+" );

			if ( !is_resource($fp) ) {

				throw new Exception("Falha ao abrir o arquivo.");
				
			}

			$linha = array();
			$result = array();

			$dev = ($_serverEnvironment == 'development') ? true : false;

			if ($dev == true) {
				
				include("../webservice/nusoap/nusoap.php");
				$client = new nusoap_client('http://novodevel.telecontrol.com.br/~silva/webservice/bosch/servidor_telecontrol_os.php');
			
			}else {

				include '/var/www/telecontrol/www/webservice/nusoap/nusoap.php';
				$client = new nusoap_client('http://ww2.telecontrol.com.br/webservice/bosch/servidor_telecontrol_os.php');
			
			}

			$sql = "SELECT 
						senha
					FROM tbl_posto_fabrica
					WHERE 
						fabrica = $login_fabrica 
						AND posto = $login_posto";
			$res = pg_query($con, $sql);

			$senha = pg_result($res, 0, "senha");

			$cont = 1;

			while (!feof($fp)) {

				$linha = fgets($fp, 4096);
				$linha = explode("\t", $linha);

				if ( count ($linha) < 5 && $dev == false) {
					continue;
				}

				$i = 0;
			
				// preenche array de layout da fabrica com valores do arquivo
				foreach($layout as $k => $v) {

					if ($i > ( count($linha) - 1 ) ) {

						$layout[$k] = '';
						continue;

					}

					$layout[$k] = $linha[$i];

					$i++;

				}

				if(
					strlen($layout["cnpj_posto"]) == 0 || 
					strlen($layout["os"]) == 0 || 
					strlen($layout["data_abertura"]) == 0 || 
					strlen($layout["data_fechamento"]) == 0 || 
					strlen($layout["produto_referencia"]) == 0 || 
					strlen($layout["cliente_documento"]) == 0 || 
					strlen($layout["cliente_nome"]) == 0 || 
					strlen($layout["nota_fiscal"]) == 0 || 
					strlen($layout["data_compra"]) == 0
				){

					throw new Exception("Erro na linha {$cont}. Verifique se todas as informa��es foram inseridas corretamente.");

				}
				 
				// call webservice com dados no array $layout
				//echo '<pre>'; print_r($layout); echo '</pre>';				

				$result[] = $client->call('ImportaOsExterna',array(
					'login'          =>$login_posto,
					'senha'          =>$senha,
					'chave'          =>'IntegraBosch@%',
					'fabrica'        => 20,
					'DadosOsExterna' => $layout,
					'DadosItemOS'    => array ( 
						'peca' => 	$layout['peca'],
						'qtde' => 	$layout['qtde']
					)
				));

				//echo '<pre>' . htmlspecialchars($client->response, ENT_QUOTES) . '</pre>';
				//echo $client->response;
				if ( $client->fault ) { 

					throw new Exception( $client->getError() );

				}

				$cont++;

			}

			//echo '<pre style="text-align:left;">'; print_r($result); echo '</pre>';

		} catch(Exception $e) {

			$msg_erro = $e->getMessage();

		}

	}

?>

<style type="text/css">
	
	.msg_erro{
		background-color:#FF0000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.sucesso{
		background-color:#008000;
		font: bold 14px "Arial";
		color:#FFFFFF;
		text-align:center;
	}

	.formulario{
		background-color:#D9E2EF;
		width:700px;
		margin:auto;
		font:11px Arial;
		text-align:left;
	}

	.subtitulo{

		background-color: #7092BE;
		font:bold 14px Arial;
		color: #FFFFFF;
		text-align:center;
	}

	.texto_avulso{
		font: 14px Arial; color: rgb(89, 109, 155);
		background-color: #d9e2ef;
		text-align: center;
		width:700px;
		margin: 0 auto;
		border-collapse: collapse;
		border:1px solid #596d9b;
	}

	#layout_integracao form p{

		padding:5px;

	}

	#layout_integracao form {
		width:350px;
		margin:auto;
	}

	#layout_integracao form  p label{ 

		float:left;
		width:80px;

	}

	#layout_integracao {
		margin-top:10px;
	}

	.formulario ul {
		margin-bottom:20px;
	}

	table.tabela tr td{
		font-family: verdana;
		font-size: 11px;
		border-collapse: collapse;
		border:1px solid #596d9b;
		text-align:center;
	}

	table.tabela tr th{
		font-size:12px;
	}

</style>

<?php 

	if ( !empty($result) ) {

		echo '<table class="tabela" cellpadding="1" style="width:700px; margin:10px auto;">
				<tr class="subtitulo">
					<th>OS</th>
					<th>OS Externa</th>
					<th>Resultado</th>
				</tr>';

		$i = 0;

		foreach($result as $k => $v) {

			if (!is_array ($v))
				continue;

			foreach($v as $item => $value) {

				if (!is_array ($value))
					continue;

				$status = isset($value['resultado']) ? $value['resultado'] : $value['operacao'];

				//print_r($value);
				$cor = ($i % 2) ? "#F7F5F0" : "#F1F4FA";

?>
				<tr bgcolor="<?=$cor?>">
					<td><?=$value['os']?></td>
					<td><?=$value['os_externa']?></td>
					<td><?=$status?></td>
				</tr>

<?php 

			}

			$i++;

		}

		echo '</table>';

	}

?>

<div id="layout_integracao" class="formulario">

	<?php if (!empty($msg_erro)) : ?>

		<div class="msg_erro"><?=$msg_erro?></div>

	<?php endif; ?>

	<div class="subtitulo">Upload de OS</div>
	
	<form action="<?=$PHP_SELF?>" enctype="multipart/form-data" method="POST">
	
		<p>
			<label for="arquivo">Arquivo</label>
			<input type="file" name="arquivo" id="arquivo" class="frm" />
		</p>

		<p style="text-align:center;">
			<input type="submit" name="enviar" value="Enviar" id="enviar" />
		</p>

	</form>

</div>

<div class="formulario">
	
	<div class="subtitulo">Defini��es para envio de Ordens de Servi�o por Arquivo </div>

	<ul>
		<li>O arquivo deve ter o nome <b>ordens.txt</b> em letras min�sculas</li>
		<li>Os campos devem ser separados por <b>TAB</b> chr(9)		</li>
		<li>Repetir o registro quando a Ordem de Servi�o tiver mais de uma pe�a		</li>
		<li> Enviar a Ordem de Servi�o at� que o site confirme seu fechamento		</li>
		<li> O campo F�BRICA deve vir preenchido com o n�mero <b>20</b>		</li>
		<li>CNPJ deve vir sem formata��o, com 14 posi��es 		</li>
		<li> CPF deve vir sem formata��o, com 11 posi��es		</li>
		<li> Todas as datas devem vir no formato YYYY-MM-DD		</li>
		<li> N�o enviar datas em branco, apenas respeitar a tabula��o		<p>
		</p></li>
		<li> Baixe aqui um <a href="os-upload-bosch.xls">exemplo</a> em Excel		</li>
		<li>Lembre-se de exportar o arquivo em formato texto, delimitado por <b>TAB</b>		</li>
	</ul>

	<div class="subtitulo">Layout do arquivo</div>

	<ul>
		
		<?php foreach ($layout_model as $key => $value) : ?>
				
			<li><?=$value?></li>
		
		<?php endforeach; ?>

	</ul>

	<div class="subtitulo">Tabela Necess�rias para Integra��o </div>

	<ul>
		<li> Planilha EXCEL com as seguintes pastas:		
			<ul>
				<li> Cadastro de Produtos</li>
				<li> Cadastro de Pe�as</li>
				<li> Lista B�sica dos Produtos</li>
				<li> Tabela de Defeito Reclamado</li>
				<li> Tabela de Defeito Constatado</li>
				<li> Tabela de Causas de Defeito</li>
				<li> Tabela de Defeitos de Pe�as</li>
				<li> Tabela de Servi�os Realizados</li>
			</ul>
		</li>		
	</ul>

	<p style="text-align:center; font-size:12px;">
		<a href="tabela_os_upload_xls.php"><b>Download dos arquivos</b></a>
	</p>

	<div class="clear">&nbsp;</div>

</div>

<?php require_once 'rodape.php'; ?>

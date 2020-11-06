<?php
/**
 * Classe Padrão para gerar log's
 *
 * @author Andreus Timm
 */
Class Log {

	/**
	 * Método estático para gerar os Log's
	 *
	 * @author   Andreus Timm
	 * @version  1.0
	 * @param    array  $vet Vetor com as informações do log
	 * @param    string $msg Mensagem a ser Gravada no arquivo
	 * @return   void
	 *
	 * @exemplo
	 *
	 *     $vet['dest'] = 'hepldesk@telecontrol.com.br';
	 *     $msg         = 'Mensagem de teste';
	 *     $assunto     = 'TITULO DA MSG';
	 *
	 *     Log::log2($vet, 'Teste 1');
	 *
	 */
	public static function envia_email($vet, $assunto, $msg, $style = false, $type = null) {

		if (!is_array($vet)) {
			throw new Exception('O primeiro parâmetro deve ser um vetor.');
		}

		if (empty($vet['head'])) {
			$vet['head']  = "MIME-Version: 1.0 \r\n";
			$vet['head'] .= "Content-type: text/html; charset=iso-8859-1 \r\n";
			$vet['head'] .= "From: helpdesk@telecontrol.com.br \r\n";
		}

		if (is_array($vet['dest'])) {

			foreach($vet['dest'] as $email) {
				if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
					throw new Exception('Email Destinatário inválido.');
				}

			}

			$new_email = implode(',', $vet['dest']);

		} else {

			if (!filter_var($vet['dest'], FILTER_VALIDATE_EMAIL)) {
				throw new Exception('Email Destinatário inválido.');
			}

			$new_email = $vet['dest'];

		}

		if ($style == true) {
			if ($type != null) {
				$titulo = strtoupper($type);
				$color = ($type == "erro") ? "color: #e00;" : "color: #363B61;";
			}

			$mstyle = "<div style='text-align: justify; border: 2px solid #363B61; border-radius: 8px; width: 600px;'>
				<img src='http://www.telecontrol.com.br/wp-content/uploads/2012/02/logo_tc_2009_texto.png' style='margin-left: 4px; margin-top: 4px;' />
				<h1 style='position: relative; margin-right: 130px; margin-top: 30px; float: right; $color'>$titulo</h1>

				<hr color='#363B61' style='height: 1px; width: 100%;' />

				<p style='margin-left: 4px;'>
				$msg
				</p>

				<hr color='#363B61' style='height: 1px; width: 100%;' />

				<p style='margin-left: 4px; margin-top: 8px; text-align: center;'>
				Por favor não responda este e-mail! <br />
				Em caso de dúvidas entre em contato com o <b>Suporte da Telecontrol</b> <br />
				<b>suporte@telecontrol.com.br</b> ou via <b>Chat Online</b>
				</p>
			</div>";

			$msg = $mstyle;
		}

		if (!mail($new_email, $assunto, $msg, $vet['head'])) {
			echo "erro ao enviar email";
		}

	}

	/**
	 * Método estático para gerar os Log's
	 *
	 * @author   Andreus Timm
	 * @version  1.0
	 * @param    array  $vet Vetor com as informações do log
	 * @param    string $msg Mensagem a ser Gravada no arquivo
	 * @return   void
	 *
	 * @exemplo
	 *
	 *     $vet['fabrica'] = 'fricon';
	 *     $vet['tipo']    = 'extrato';
	 *     $vet['log']     = 1;
	 *
	 *     Log::log2($vet, 'Teste 1');
	 *
	 */
	public static function log2($vet, $msg) {

		$data       = date('Y-m-d-H');

		if (!is_array($vet)) {
			throw new Exception('O primeiro parâmetro deve ser um vetor.');
		}

		if (!is_string($vet['fabrica'])) {
			throw new Exception('Fábrica não encontrada.');
		}

		if (!in_array($vet['tipo'], array('finaliza_os', 'reprocessamento' , 'extrato', 'pedido', 'peca', 'posto', 'produto','importa-pedido','excluidos','lista_basica','preco','custo_tempo','peca-al','preco-al','produto-pais','chat','pedido_troca', 'importa-faturamento','exporta-pedido','helpdesk','importa_csv','atualiza-status','verifica_os_aberta', 'exporta-posto-contato', 'cancela-faturamento','importa-preco','preco-produto','exporta-os'))) {

			throw new Exception('Tipo não encontrado.');
		}

		if (!in_array($vet['log'], array(1,2))) {
			throw new Exception('Tipo de Log não encontrado.');
		}

		if (strlen($msg) == 0) {
			throw new Exception('Mensagem não pode ser vazio.');
		}

		$tipo = $vet['tipo'];

		/**
		 * @since 2012.02.27 - adicionei mais um possivel index ao vetor,
		 *   no caso de necessitar customizar o nome do arquivo de log.
		 */
		$arquivo = $vet['arquivo'];

		if (empty($arquivo)) {
			switch ($vet['tipo']) {
				case 'extrato' :
				case 'pedido'  :
					$aux  = 'gera-';
					break;
				case 'pedido_troca' :
					$aux  = 'troca-';
					break;
				case 'chat'          :
					$aux = 'chat';
					break;
				case 'reprocessamento'       :
				case 'exporta'       :
				case 'exporta_pedido':
					$aux = 'exporta-';
					break;
				case 'peca'          :
				case 'preco'         :
				case 'posto'         :
				case 'produto'       :
				case 'lista_basica'  :
				case 'peca-al'       :
				case 'preco-al'      :
				case 'produto-pais'  :
				case 'custo_tempo'   :
					$aux  = 'importa-';
					break;
				case 'importa-pedido':
				case 'importa-faturamento':
				case 'cancela-faturamento':
				case 'excluidos'     :
					$aux = '';
					break;
				case 'finaliza_os':
					$aux = "finaliza-os-";
					break;
			}

			switch ($vet['log']) {
				case 1 :
					$log = 'log';
					break;
				case 2:
					$log = 'erro';
					break;
			}

			$arquivo = $aux.$tipo.'-'.$data.'.'.$log;
		}

		$dir        = '/tmp/'.$vet['fabrica'];
		$new_file   = $dir.'/'.$arquivo;

		if (!is_dir($dir)) {

			if (!mkdir($dir)) {
				throw new Exception('Erro ao criar diretório do fabricante.'."\n");
			}
		}

		$file = fopen($new_file, 'a+');

		if (!is_resource($file)) {
			throw new Exception('Erro criar arquivo de log.'."\n");
		}

		fwrite($file, $msg . "\n");
		fclose($file);

	}

}

if (!class_exists("Log2")) {
	Class Log2{

		private $mensagem 		= "";
		private $emails 		= array();
		private $tituloEmail 	= "";

		public function adicionaLog($log){

			if ($log == "linha") {
				$this->mensagem .= "<hr />";
			} else if (is_array($log) && array_key_exists("titulo", $log)) {
                $this->adicionaTituloEmail($log['titulo']);
			} else {
				$this->mensagem .= "<br /> {$log}";
			}

		}

		public function escreveLogs(){

			return $this->mensagem;

		}

		public function adicionaEmail($email){

			$this->emails[] = $email;

		}

		public function apagaEmails(){

			$this->emails = array();

		}

		public function listaEmails(){

			return implode(",", $this->emails);

		}

		public function adicionaTituloEmail($titulo){

			$this->tituloEmail = $titulo;

		}

		public function escreveEmails(){

			$send = implode(",", $this->emails);
			return $send;

		}

		public function enviaEmails(){

			$header  = "MIME-Version: 1.0\n";
			$header .= "Content-type: text/html; charset=iso-8859-1\n";
			$header .= "From: Telecontrol <telecontrol\@telecontrol.com.br>";

			if(!mail($this->escreveEmails(), $this->tituloEmail, $this->escreveLogs(), $header)){

				$return = "";
				$emails = $this->escreveEmails();
				$logs  	= $this->escreveLogs();

				if(empty($emails)){
					$return .= "Não foi inserido nenhum email. <br />";
				}

				if(empty($this->tituloEmail)){
					$return .= "Não foi inserido nenhum titulo para o email. <br />";
				}

				if(empty($logs)){
					$return .= "Não foi inserido nenhum log no corpo do email. <br />";
				}

				return $return;

			}else{
				return "200";
			}

		}

		public function enviaAnexoCSV($fabrica){

			include dirname(__FILE__) . '/../class/email/mailer/class.phpmailer.php';

			$data = date("d")."-".date("m")."-".date("Y");

			$csv = "/tmp/csv/{$fabrica}/pedido-csv-{$fabrica}-{$data}.csv";

			$e = implode(",", $this->emails);

			system("uuencode $csv arquivo.csv | mail -s 'Arquivo de Pedidos de Peças por Postos' '$e'");

			/* $mailer = new PHPMailer();
			$mailer->IsSMTP();
			$mailer->IsHTML();
			$mailer->AddReplyTo("telecontrol@telecontrol.com.br", "Telecontrol");

			foreach ($this->emails as $key => $value) {
				$mailer->AddAddress($value);
			}

			$mailer->AddAttachment("/tmp/csv/{$fabrica}/pedido-csv-{$fabrica}-{$data}.csv");

			$mensagem  = "Anexo de Pedidos de Peças por Postos";

			$mailer->Subject = "Anexo de Pedidos de Peças";
			$mailer->Body = $mensagem;
			$mailer->Send(); */

		}

	}
}
class PedidoCSV{

	private $fabrica;
	private $con;
	private $csv;

	public function __construct($con) {

		$this->con = $con;

	}

	public function setFabrica($f){
		$this->fabrica = $f;
	}

	public function insereDadosCSV($d){


		$this->csv .= $d;

	}

	public function dadosCSV(){

		return $this->csv;

	}

	public function gravaCSV(){

		if(!is_dir("/tmp/csv/{$this->fabrica}")){
			mkdir("/tmp/csv/{$this->fabrica}", 0777, true);
		}

		$data = date("d")."-".date("m")."-".date("Y");
		$fp = fopen("/tmp/csv/{$this->fabrica}/pedido-csv-{$this->fabrica}-{$data}.csv", "w");
		fwrite($fp, $this->csv);
		fclose($fp);

		return true;

	}

	public function getPedidos(){

		/* Seleciona os Postos para gravar o CSV */

		$sql_postos = "SELECT DISTINCT posto FROM tbl_pedido WHERE fabrica = {$this->fabrica} AND exportado::date = current_date";
		$res_postos = pg_query($this->con, $sql_postos);

		if(pg_num_rows($res_postos) > 0){

			for($i = 0; $i < pg_num_rows($res_postos); $i++){

				$postos[] = pg_fetch_result($res_postos, $i, 'posto');

			}

		}

		if(count($postos) > 0){

			for($i = 0; $i < count($postos); $i++) {

				$posto = $postos[$i];

				/* Busca os dados dos Postos */

				$sql_dados_posto = "SELECT
										tbl_posto_fabrica.codigo_posto,
										tbl_posto.nome
									FROM tbl_posto
									JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = {$this->fabrica}
									WHERE tbl_posto.posto = {$posto}";
				$res_dados_postos = pg_query($this->con, $sql_dados_posto);

				$codigo_posto 	= pg_fetch_result($res_dados_postos, 0, 'codigo_posto');
				$nome_posto 	= pg_fetch_result($res_dados_postos, 0, 'nome');

				/* Busca os Pedidos do Posto */

				$sql_pedidos = "SELECT pedido FROM tbl_pedido WHERE posto = {$posto} AND fabrica = {$this->fabrica}";
				$res_pedidos = pg_query($this->con, $sql_pedidos);

				if(pg_num_rows($res_pedidos) > 0){

					for($j = 0; $j < pg_num_rows($res_pedidos); $j++){

						$pedido = pg_fetch_result($res_pedidos, $j, 'pedido');

						$d = "\n \n Pedido {$pedido} - {$codigo_posto} {$nome_posto} \n";

						$this->insereDadosCSV($d);

						$sql_pedido_item = "SELECT
												tbl_peca.referencia,
												tbl_peca.descricao,
												tbl_pedido_item.qtde
											FROM tbl_pedido_item
											JOIN tbl_peca ON tbl_peca.peca = tbl_pedido_item.peca
											WHERE tbl_pedido_item.pedido = {$pedido}";
						$res_pedido_item = pg_query($this->con, $sql_pedido_item);

						for($k = 0; $k < pg_num_rows($res_pedido_item); $k++){

							$referencia = str_replace(",", ".", pg_fetch_result($res_pedido_item, $k, 'referencia'));
							$peca 		= str_replace(",", ".", pg_fetch_result($res_pedido_item, $k, 'descricao'));
							$qtde 		= str_replace(",", ".", pg_fetch_result($res_pedido_item, $k, 'qtde'));

							$d = "{$referencia};{$peca};{$qtde} \n";

							$this->insereDadosCSV($d);

						}

					}

				}

			}

		}

		return ($this->gravaCSV()) ? "ok" : "fail";

	}


}


/**
 *
 *  Classe para controle de execução das rotinas em PHP.
 *
 *  @author  Francisco Ambrozio
 *  @version 2012.02.0
 *
 *  Exemplo de uso:
 *
 *    // Na primeira linha possível do arquivo
 *    $phpCron = new PHPCron($login_fabrica, __FILE__);
 *    $phpCron->inicio();
 *
 *    // Na última linha a ser executada em caso do script rodar sem erros
 *     $phpCron->termino();
 *
 *
 */
class PHPCron
{
	private $fabrica;
	private $programa;
	private $perlProcessado;
	private $phpNam;

	public function __construct($login_fabrica, $php_script)
	{
		include_once dirname(__FILE__) . '/../dbconfig.php';
		include_once dirname(__FILE__) . '/../includes/dbconnect-inc.php';

		$this->fabrica = $login_fabrica;
		$this->phpNam = $php_script;
		$this->checkFabrica();

	}

	private function checkFabrica()
	{
		global $con;

		$local_fabrica = $this->fabrica;
		$query = pg_query($con, "SELECT nome FROM tbl_fabrica WHERE fabrica = $local_fabrica and ativo_fabrica");

		if (pg_num_rows($query) == 0) {
			throw new Exception('Fábrica desconhecida ou inativada: ' . $local_fabrica . "\n");
		}

	}

	private function getPerl()
	{
		global $con;

		$local_fabrica = $this->fabrica;
		$programa = $this->phpNam;

		$sql = "SELECT perl FROM tbl_perl WHERE programa = '$programa' and fabrica = $local_fabrica";
		$query = pg_query($con, $sql);

		if (pg_num_rows($query) == 0) {
		    $insert = "INSERT INTO tbl_perl (fabrica, programa, agenda, instrucao) VALUES ($local_fabrica, '$programa', '', 'Adicionado automaticamente em tempo de execucao')";
			$query = pg_query($con, $insert);

			$query = pg_query($con, "SELECT currval('seq_perl') AS perl");
		}

		$perl = pg_fetch_result($query, 0, 'perl');

		return $perl;

	}

	public function inicio()
	{
		global $con;

		$perl = $this->getPerl();
		$sql = "INSERT INTO tbl_perl_processado (perl, log) VALUES ($perl, 'Iniciado processamento')";
		$query = pg_query($con, $sql);

		$query = pg_query($con, "SELECT currval('seq_perl_processado')");
		$this->perlProcessado = pg_fetch_result($query, 0, 0);

		return 0;

	}

	public function termino()
	{
		global $con;

		$local_perlProcessado = $this->perlProcessado;
		$sql = "UPDATE tbl_perl_processado SET log = 'Terminado processamento com sucesso', fim_processo = current_timestamp WHERE perl_processado = $local_perlProcessado";
		$query = pg_query($con, $sql);

		if (pg_last_error()) {
			return 1;
		} else {
			return 0;
		}

	}
}

$arrayEstados  = array("AC", "AL", "AM", "AP", "BA", "CE", "DF", "ES", "GO", "MA", "MG", "MS", "MT", "PA", "PB", "PE", "PI", "PR", "RJ", "RN", "RO", "RR", "RS", "SC", "SE", "SP", "TO");

if(!function_exists("verificaCidade")) {
	function verificaCidade($cidade, $estado, $cep) {
		global $con;

		if (!class_exists("CEP")) {
			throw new Exception("Classe CEP não encontrada");
		}

		if (!empty($cidade) && !empty($estado) && !empty($cep)) {
			$sql = "SELECT nome FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				return pg_fetch_result($res, 0, "nome");
			} else {
				try {
					$endereco = CEP::consulta($cep);

					if (!empty($endereco["cidade"])) {
						return $endereco["cidade"];
					} else {
						return false;
					}
				} catch(Exception $e) {
					return false;
				}
			}
		} else {
			return false;
		}
	}
}
if(!function_exists("verificaCpfCnpj")) {
	function verificaCpfCnpj($cpf_cnpj) {
		global $con;

		try {
			$sql = "SELECT fn_valida_cnpj_cpf('{$cpf_cnpj}')";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				return false;
			} else {
				return true;
			}
		} catch(Exception $e) {
			return false;
		}
	}
}
/*function verificaCep($cep) {
	if (!class_exists("CEP")) {
		throw new Exception("Classe CEP não encontrada");
	}

	$cep = preg_replace("/\D/", "", $cep);

	if (!empty($cep)) {
		try {
			CEP::consulta($cep);

			return true;
		} catch(Exception $e) {
			return false;
		}
	} else {
		return false;
	}
}*/

<?php
header('Content-Type: text/html; charset=ISO-8859-1'); # HD 941072 - corrigindo charset via ajax/jquery
include '../../dbconfig.php';
include '../../includes/dbconnect-inc.php';

if ($_POST["buscaCidade"] == true) {
	$estado = strtoupper($_POST["estado"]);

	if (strlen($estado) > 0) {
		$sql = "SELECT DISTINCT * FROM (
					SELECT UPPER(TO_ASCII(nome, 'LATIN9')) AS cidade FROM tbl_cidade WHERE UPPER(TO_ASCII(nome, 'LATIN9')) ~ UPPER(TO_ASCII('{$cidade}', 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')
					UNION (
						SELECT UPPER(TO_ASCII(cidade, 'LATIN9')) AS cidade FROM tbl_ibge WHERE UPPER(TO_ASCII(cidade, 'LATIN9')) ~ UPPER(TO_ASCII('{$cidade}', 'LATIN9')) AND UPPER(estado) = UPPER('{$estado}')
					)
				) AS cidade ORDER BY cidade ASC";
		$res  = pg_query($con, $sql);
		$rows = pg_num_rows($res);

		if ($rows > 0) {
			$cidades = array();

			for ($i = 0; $i < $rows; $i++) {
				$cidades[$i] = array(
					"cidade"          => utf8_encode(pg_fetch_result($res, $i, "cidade")),
					"cidade_pesquisa" => utf8_encode(strtoupper(pg_fetch_result($res, $i, "cidade"))),
				);
			}

			$retorno = array("cidades" => $cidades);
		} else {
			$retorno = array("erro" => "Nenhuma cidade encontrada para o estado {$estado}");
		}
	} else {
		$retorno = array("erro" => "Nenhum estado selecionado");
	}

	exit(json_encode($retorno));
}

include '../../funcoes.php';
include '../../helpdesk/mlg_funciones.php';

include_once '../../class/email/mailer/class.phpmailer.php';

$mailer = new PHPMailer(); // Class para envio de email com autenticação no servidor

$serv_origem = $_SERVER['HTTP_REFERER'];
/*
 * Marcas em Base64 e em Base64 e com URL encode:
 * GF			R0Y=			R0Y%3D
 * RAYOVAC		UkFZT1ZBQw==	UkFZT1ZBQw%3D%3D
 * REMINGTON	kVNSU5HVE9O		UkVNSU5HVE9O
 *
 * Links a serem colocados nos sites:
 *
 * George Foreman
 * http://posvenda.telecontrol.com.br/assist/externos/callcenter/callcenter_cadastra_bestway.php?m=R0Y%3D
 * Rayovac
 * http://posvenda.telecontrol.com.br/assist/externos/callcenter/callcenter_cadastra_bestway.php?m=UkFZT1ZBQw%3D%3D
 * Remington
 * http://posvenda.telecontrol.com.br/assist/externos/callcenter/callcenter_cadastra_bestway.php?m=UkVNSU5HVE9O
 */

$array_estado = array(
	'AC'=>'AC - Acre',			'AL'=>'AL - Alagoas',	'AM'=>'AM - Amazonas',			'AP'=>'AP - Amapá',
	'BA'=>'BA - Bahia',			'CE'=>'CE - Ceará',		'DF'=>'DF - Distrito Federal',	'ES'=>'ES - Espírito Santo',
	'GO'=>'GO - Goiás',			'MA'=>'MA - Maranhão',	'MG'=>'MG - Minas Gerais',		'MS'=>'MS - Mato Grosso do Sul',
	'MT'=>'MT - Mato Grosso',	'PA'=>'PA - Pará',		'PB'=>'PB - Paraíba',			'PE'=>'PE - Pernambuco',
	'PI'=>'PI - Piauí',			'PR'=>'PR - Paraná',	'RJ'=>'RJ - Rio de Janeiro',	'RN'=>'RN - Rio Grande do Norte',
	'RO'=>'RO - Rondônia',		'RR'=>'RR - Roraima',	'RS'=>'RS - Rio Grande do Sul', 'SC'=>'SC - Santa Catarina',
	'SE'=>'SE - Sergipe',		'SP'=>'SP - São Paulo',	'TO'=>'TO - Tocantins'
);

// comentado GF = 'site'	=> 'http://www.georgeforeman.com.br/novo/default.asp', hd_chamado=2659076
$info_marca = array(
	'GF'	=> array(
		'linha'		=> 'ELE,567',
		'marca'		=> 157,
		'nome'		=> 'George Foreman',
		//'site'		=> 'http://www.georgeforeman.com.br',
		'site' => 'http://ww2.telecontrol.com.br/assist/externos/callcenter/callcenter_cadastra_bestway.php?m=R0Y%3D',
	),
	'REMINGTON'	=> array(
		'linha'		=> 'REM,639',
		'marca'		=> 177,
		'nome'		=> 'Remington',
		//'site'		=> 'http://www.produtosremington.com.br',
		'site' => 'http://ww2.telecontrol.com.br/assist/externos/callcenter/callcenter_cadastra_bestway.php?m=UkVNSU5HVE9O',
	),
	'RAYOVAC'	=> array(
		'linha'		=> 'RAI,655',
		'marca'		=> 178,
		'nome'		=> 'Rayovac',
		//'site'		=> 'http://la.rayovac.com/?pais_id=4',
		'site' => 'http://ww2.telecontrol.com.br/assist/externos/callcenter/callcenter_cadastra_bestway.php?m=UkFZT1ZBQw%3D%3D',
	),
);

$login_fabrica = 81;

if (isset($_GET["q"])){
	$busca      = getPost('busca');
	$tipo_busca = getPost('tipo_busca');
	$marca		= getPost('marca');
	$q			= preg_replace("/\W/", ".?", getPost('q'));

	if (strlen($q)>2){

		if ($tipo_busca=="produto"){
			$sql = "SELECT
			            tbl_produto.produto, tbl_produto.descricao
                    FROM tbl_produto
                    JOIN tbl_linha USING (linha)
                    WHERE tbl_linha.fabrica = $login_fabrica
                        AND ( tbl_produto.descricao  ~* '$q' OR tbl_produto.referencia ~* '$q' )
                        AND tbl_produto.marca = $marca";

			$res = pg_query($con,$sql);

			if ((pg_num_rows ($res)) > 0) {
				for ($i = 0; $i < pg_num_rows ($res); $i++) {
					$produto	= trim(pg_fetch_result($res,$i,'produto'));
					$descricao	= trim(pg_fetch_result($res,$i,'descricao'));
					echo "$produto|$descricao\n";
				}
			}
		}
	}
	die;
}

# HD 941072
if( isset($_GET["reclamado"]) )
{
	$tipo_busca = $_GET['tipo_busca'];
	$produto    = preg_replace("/\W/", ".?", $_GET['produto']);

	if( strlen($produto)>2 )
	{
		if( $tipo_busca=="defeito_reclamado" )
		{
			$sql = "SELECT DISTINCT tbl_defeito_reclamado.descricao,
						tbl_defeito_reclamado.defeito_reclamado
					FROM tbl_diagnostico
						JOIN tbl_defeito_reclamado
							ON tbl_diagnostico.defeito_reclamado = tbl_defeito_reclamado.defeito_reclamado
						JOIN tbl_produto
							ON tbl_diagnostico.familia = tbl_produto.familia
					WHERE tbl_diagnostico.fabrica = $login_fabrica
						AND tbl_diagnostico.ativo IS TRUE
						AND tbl_produto.produto = $produto
					UNION
					SELECT DISTINCT tbl_defeito_reclamado.descricao,
						tbl_familia_defeito_reclamado.defeito_reclamado
					FROM tbl_familia_defeito_reclamado
						JOIN tbl_defeito_reclamado
							ON tbl_defeito_reclamado.defeito_reclamado = tbl_familia_defeito_reclamado.defeito_reclamado
						AND tbl_defeito_reclamado.fabrica = $login_fabrica
					ORDER BY 1";

			$res = pg_query($con, $sql);

			echo "<select id='defeito_reclamado' name='defeito_reclamado' title='Defeito Reclamado' style='color: #333; width:280px; height:28px'>
			          <option value=''> - Selecione o Defeito Reclamado</option>";

			if( pg_num_rows($res) > 0 )
			{
				for( $i=0; $i<pg_num_rows($res); $i++ )
				{
					$defeito_reclamado = pg_result($res, $i, defeito_reclamado);
					$descricao         = pg_result($res, $i, descricao);
					echo "<option value='$defeito_reclamado'>$descricao</option>";
				}
			}

			echo "</select>";
		}
	}
	die;
}
# HD 941072 - fim


if( $_POST['nome_completo'] )
{
	$marcaID          = getPost('marcaID');

	//Campos não obrigatórios
	$sexo             = pg_quote(strtoupper(getPost('sexo')));
	$endereco         = pg_quote(mb_strtoupper(getPost('endereco')));
	$bairro           = pg_quote(mb_strtoupper(getPost('bairro')));
	$estado           = pg_quote(mb_strtoupper(getPost('estado')));
	$cidade2          = pg_quote(mb_strtoupper(getPost('cidade')));
	$complemento	  = pg_quote(mb_strtoupper(getPost('complemento')));
	$cep              = pg_quote(preg_replace("/\D/","",getPost('cep')));
	$produto          = pg_quote(getPost('produto'), true);
	if ($produto == "''") $produto = 'NULL';

	// Campos sem aspas
	$assunto           = getPost('assunto');
	$nome              = (getPost('nome_completo'));
	$sexo2			   = getPost('sexo');
	$data_nascimento   = getPost('data_nascimento');
	$data_nascimento2  = getPost('data_nascimento');
	$email             = (getPost('email'));
	$ddd               = getPost('ddd');
	$telefone          = getPost('telefone');
	$numero			   = getPost('numero');
	$celular_sp        = getPost('celular_sp');
	$cidade            = getPost('cidade');
	$mensagem          = getPost('mensagem');
	$endereco2		   = getPost('endereco');
	$bairro2		   = getPost('bairro');
	$estado2		   = getPost('estado');

	$defeito_reclamado = getPost('defeito_reclamado');

	//pre_echo($_POST);
	if (strlen($data_nascimento) > 8) {
		list($di, $mi, $yi) = preg_split('/[\/\.-]/', $data_nascimento);
		//pre_echo(preg_split('/[\/\.-]/', $data_nascimento), 'Data de Nascimento');
		if(!checkdate((int) $mi, (int) $di, (int) $yi)){
			$msg_erro[] = "Data de Nascimento Inválida";
		}else{
			$data_nascimento = "'$yi-$mi-$di'";
			if($data_nascimento > date('Y-m-d'))
				$msg_erro[] = "Data de Nascimento Inválida";
		}
	}

	if (empty($assunto)) {
		$msg_erro[] = 'Escolha um assunto';
	}

	if (empty($nome)) {
		$msg_erro[] = 'Digite o Nome';
	}

	if (empty($sexo2)) {
		$msg_erro[] = 'Informe o Sexo';
	}

	if (empty($data_nascimento)){
		$msg_erro[] = 'Informe a Data de Nascimento';
	}

	if (empty($email)) {
		$msg_erro[] = 'Digite o email';
	} else {
		if(!filter_var($email, FILTER_VALIDATE_EMAIL))
			$msg_erro[] = 'Digite um email válido';
	}

	if (empty($telefone)) {
		$msg_erro[] = 'Digite o telefone';
	} else {
		$telefone = "$telefone";
	}

	if (empty($cep)) {
		$msg_erro[] = 'Informe o CEP';
	}else{
		$cep = "'$cep'";
	}

	if (empty($endereco2)) {
		$msg_erro[] = 'Digite o Endereço';
	}

	if (empty($numero)) {
		$msg_erro[] = 'Digite o Número';
	}else{
		$numero = "'$numero'";
	}

	if (empty($bairro2)) {
		$msg_erro[] = 'Digite o Bairro';
	}

	if (empty($cidade)) {
		$msg_erro[] = 'Digite a Cidade';
	}

	if (empty($produto)) {
		$msg_erro[] = 'Digite o Produto';
	}

	/* HD 941072 - BestWay */
	if (empty($defeito_reclamado)) {
		$msg_erro[] = 'Digite o Defeito Reclamado';
	}

	if (strlen($mensagem)==0) {
		$msg_erro[] = 'Digite a mensagem';
	}

	if( !is_null($estado) and !is_null($cidade) and count($msg_erro)==0 )
	{
		/* $sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(TO_ASCII(nome, 'LATIN9')) = UPPER(TO_ASCII('{$cidade}', 'LATIN9')) AND UPPER(estado) = UPPER('{$estado2}')";
		$res = pg_query($con,$sql);

		if(pg_numrows($res)>0){
			$cidade = pg_fetch_result($res,0,0);
		}else{
			$sql = "INSERT INTO tbl_cidade(nome, estado) VALUES (upper('$cidade'),'$estado2')";
			$res = pg_query($con,$sql);
			$msg_erro .= pg_last_error($con);
			$res    = pg_query($con,"SELECT CURRVAL ('seq_cidade')");
			$cidade = pg_fetch_result ($res,0,0);
		} */

		/* Verifica Cidade */
		$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) == 0){

			$sql = "SELECT cidade, estado FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){

				$cidade = pg_fetch_result($res, 0, 'cidade');
				$estado = pg_fetch_result($res, 0, 'estado');

				$sql = "INSERT INTO tbl_cidade (nome, estado) VALUES ('$cidade', '$estado')";
				$res = pg_query($con, $sql);

			}else{
				$cidade = 'null';
			}

		}else{
			$cidade = pg_fetch_result($res, 0, 'cidade');
		}

		/* Fim - Verifica Cidade */

	}

	if( count($msg_erro) == 0 )
	{
		$titulo = 'Atendimento interativo';

		$sql_admins = "SELECT
		                   admin, email
                       FROM
                           tbl_admin
                       WHERE fabrica = $login_fabrica
                           AND fale_conosco IS TRUE
                           AND ativo        IS TRUE
                       ORDER BY admin";

		$res_admins = @pg_query($con, $sql_admins);

		if( is_resource($res_admins) )
		{
			if( pg_num_rows($res_admins) == 0 )
			{
				$atendentes = array(2489, 3241); // Fallback se não houver resultados
			}
			else
			{
				$admins = pg_fetch_all($res_admins);

				foreach( $admins as $a_admin )
				{
					$at[$a_admin['admin']] = $a_admin['email'];
				}

				$atendentes = array_keys($at);
			}
		}else{
			$atendentes = array(2489, 3241); //Fallback se não houver resultados
		}

		$sql = "SELECT
				    admin, COUNT(*)
                FROM
                	tbl_hd_chamado
                WHERE fabrica              = $login_fabrica
                   AND fabrica_responsavel = 81
                   AND data                > NOW() - INTERVAL '7 DAY'
                   AND admin IN(".implode(", ", $atendentes).")
                GROUP BY admin
                ORDER BY COUNT(*) ASC;";

		$res = pg_query($con,$sql);

		if( pg_num_rows($res) == 0 )
		{
			$admin = $atendentes[0];
		}
		else
		{
			$admin = pg_fetch_result($res, 0, 0);
		}

		$email_at = $at[$admin];

		$res = pg_query($con,"BEGIN TRANSACTION");

		$sql = "INSERT INTO tbl_hd_chamado
				(
					admin              ,
					data               ,
					status             ,
					atendente          ,
					fabrica_responsavel,
					titulo             ,
					categoria          ,
					fabrica
				)
				VALUES
				(
					$admin             ,
					CURRENT_TIMESTAMP  ,
					'Aberto'           ,
					$admin             ,
					$login_fabrica     ,
					'$titulo'          ,
					'$assunto'         ,
					$login_fabrica
				)";

		$res = pg_query($con,$sql);

		if( is_resource($res) )
		{
			$res        = pg_query($con,"SELECT CURRVAL ('seq_hd_chamado')");
			$hd_chamado = pg_fetch_result($res,0,0);

			// Coloca aspas nestes campos de texto para dar o INSERT, mudei o nome para poder usar o valor sem aspas no e-mail.
			$i_nome		  = pg_quote($nome);
			$i_email	  = pg_quote($email);
			$i_telefone	  = pg_quote($telefone);
			$i_celular_sp = pg_quote($celular_sp);

			/* HD 941072 - Gravando o Celular opcionalmente */
			if( empty($i_celular_sp) )
			{
				$campo_celular_sp = ' ';
				$valor_celular_sp = ' ' ;
			}
			else
			{
				$campo_celular_sp = ' celular, ';
				$valor_celular_sp = $i_celular_sp.' ,';
			}

			$sql = "INSERT INTO tbl_hd_chamado_extra
					(
						hd_chamado        ,
						produto           ,
						reclamado         ,
						defeito_reclamado,
						nome              ,
						endereco          ,
						numero 			  ,
						complemento 	  ,
						bairro            ,
						cep               ,
						fone              ,
						$campo_celular_sp
						email             ,
						sexo              ,
						data_nascimento   ,
						cidade
					)
					VALUES
					(
						$hd_chamado       ,
						$produto          ,
						'$mensagem'       ,
						$defeito_reclamado,
						$i_nome           ,
						$endereco         ,
						$numero 		  ,
						$complemento 	  ,
						$bairro           ,
						$cep              ,
						$i_telefone       ,
						$valor_celular_sp
						$i_email          ,
						$sexo             ,
						$data_nascimento,
						$cidade
					)";

			$res = pg_query($con, $sql);

			if( $dbErro = pg_last_error($con) )
			{
				$msg_erro[] = $dbErro;
			}

			if( strlen($dbErro) == 0 )
			{
				$res = pg_query($con, "COMMIT TRANSACTION");
				extract($info_marca[$marcaID], EXTR_PREFIX_ALL, 'marca');

				$subject  = "Contato via site $marca_nome - Protocolo de Atendimento Nº ".$hd_chamado;
				$message  = "<b>Foi aberto um novo Help Desk</b> <br /><br />";
				$message .= "<b>Nome </b>: $nome <br />";
				$message .= "<b>E-mail </b>: $email <br />";
				$message .= "<b>Telefone </b>: $telefone <br />";
				$message .= "<b>Help Desk </b>: $hd_chamado <br />";
				$message .= "<b>Mensagem </b>: $mensagem <br />";
				$message .= "<p>Segue abaixo o link para acesso ao chamado:</p>
				             <p>
				                 <a href='http://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$hd_chamado'>
				                 	http://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=$hd_chamado
				                 </a>
				             </p>";
				// Link de Produção:        http://posvenda.telecontrol.com.br/assist/admin/callcenter_interativo_new.php?callcenter=
                // Link de Desenvolvimento: http://192.168.0.199/chamados/hd-941072/admin/callcenter_interativo_new.php?callcenter=
				$para     = ((date('H')+date('i'))%2) ? 'sac.bestway@telecontrol.com.br':'sac.bestway1@telecontrol.com.br';// = 'fabiano.souza@telecontrol.com.br'; // definir pra quem vai mandar e-mail depois, se vai pra quem tem menos chamados, ver com Rolon.
				//$para = "guilherme.monteiro@telecontrol.com.br";

				$headers  = "MIME-Version: 1.0 \r\n";
				$headers .= "Content-type: text/html \r\n";
				$headers .= "From: Telecontrol Networking <helpdesk@telecontrol.com.br> \r\n";
				$headers .= "Reply-to: $email \r\n";

				$username = 'tc.sac.bestway@gmail.com';
				$senha = 'tcbestway';

			    /* $mailer = new PhpMailer(true);

			    $mailer->IsSMTP();
			    $mailer->Mailer = "smtp";

			    $mailer->Host = 'ssl://smtp.gmail.com';
			    $mailer->Port = '465';
			    $mailer->SMTPAuth = true;

			    $mailer->Username = $username;
			    $mailer->Password = $senha;
			    $mailer->SetFrom($username, $username);
			    $mailer->AddAddress($para,$para );
			    $mailer->Subject = utf8_encode($subject);
			    $mailer->Body = $message;
			    $mailer->IsHTML(true);

			    try{
					$mailer->Send();
					echo "<script language='javascript'>
				              alert('Mensagem enviada com Sucesso!');
				              window.location = '$marca_site';
				          </script>";
			    }catch(Exception $e){
			    	echo "<script language='javascript'>
				              alert('Houve um erro ao enviar o E-mail, tente novamente!');
				              window.location = '$marca_site';
				          </script>";
				} */

				if(mail($para, utf8_encode($subject), utf8_decode($message), $headers))
				{
					  echo "<script language='javascript'>
				              alert('Mensagem enviada com Sucesso!');
				              window.location = '$marca_site';
				          </script>";
				}
				else
				{
				    echo "<script language='javascript'>
				              alert('Houve um erro ao enviar o E-mail, tente novamente!');
				              window.location = '$marca_site';
				          </script>";
				}
			}else{
				$res = pg_query($con, "ROLLBACK TRANSACTION");
			}
		}
	}
}

//p_echo ("Detectando origem...");

$serv_origem = 'rayovac';

if ($serv_origem){
	if (strpos($serv_origem, 'rayovac'))	$marcaID = 'RAYOVAC';
	if (strpos($serv_origem, 'remington'))	$marcaID = 'REMINGTON';
	if (strpos($serv_origem, 'foreman'))	$marcaID = 'GF';

	//echo "Via HTTP Referer... $serv_origem<br>";
}
$marcaID = 'GF';// apagar antes de subir = hd_chamado=2659076
if (!$marcaID) {
	//echo "Vía parâmetro\n";
	if ($_GET['acao']=='fale_conosco' or $_GET['m'] or $_POST['marca']) {
		$marcaID = $_GET['m'];
		if (!$marcaID) $marcaID = base64_encode($_POST['marca']);
		if (!$marcaID) {
			 die('Endereço desconhecido');
		}
		$marcaID = base64_decode($marcaID);
		if (strpos('RAYOVACGFREMINGTON', $marcaID)===false) {
			 die('Endereço desconhecido');
		}
	} else die('Endereço desconhecido');
}

$marca = $info_marca[$marcaID]['marca'];
?>

<!--
<script type="text/javascript" src="../../js/jquery-1.5.2.min.js"></script>
-->
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script type='text/javascript' src='../../js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='../css/jquery.autocomplete.min.js'></script>
<!--
<script type="text/javascript" src="http://ww2.telecontrol.com.br/js/jquery.maskedinput.min.js"></script>
-->
<script type="text/javascript" src="../../admin/js/jquery.mask.js"></script>

<!--
	# HD 941072 - alterado o CSS para ficar direto na página pois não estava funcionando via include
<link type="text/css" href="../css/jquery.autocomplete.css" rel="stylesheet" />-->
<style type="text/css">
	.ac_results { padding: 0px; border: 1px solid black; background-color: white; overflow: hidden; z-index: 99999; }
	.ac_results ul { width: 100%; list-style-position: outside; list-style: none; padding: 0; margin: 0; }
	.ac_results li { margin: 0px; padding: 2px 5px; cursor: default; display: block;
		/*
		if width will be 100% horizontal scrollbar will apear
		when scroll mode will be used
		*/
		/*width: 100%;*/
		font: menu; font-size: 12px;
		/*
		it is very important, if line-height not setted or setted
		in relative units scroll will be broken in firefox
		*/
		line-height: 16px; overflow: hidden;
	}
	.ac_loading { background: white url('../css/indicator.gif') right center no-repeat; }
	.ac_odd { background-color: #eee; }
	.ac_over { background-color: #0A246A; color: white; }
</style>

<script type="text/javascript">
	var php_self = window.location.pathname;

	$(function()
	{
		// HD 896924 - Validação para o nono dígito de celulares que iniciam com 11+9
		// jQuery Mask Input - usar o jQuery v1.7.2.min
        /* Author: Igor Escobar       - http://blog.igorescobar.com/
         * Plugin: jQuery Mask Plugin - https://github.com/igorescobar/jQuery-Mask-Plugin
         * <input autocomplete="off" maxlength="14" name="telefone3" id="celular_sp" />
         */
        var mask_field = $('#celular_sp'),
	        options = { onKeyPress:function(phone)
	        {
	            if( /^\([1-9][0-9]\) *[0-8]/i.test(phone) )
	            {
	                mask_field.mask('(00) 0000-0000', options); // 9º Dígito de São Paulo com DDD 11 + 9 para celulares
	            }
	            else
	            {
	                mask_field.mask('(00) 00000-0000', options);  // Máscara default para telefones
	            }
	        }};

/*		if( $("#celular_sp").val().match(/^\(11\) 9/i) )
		{
		   $("#celular_sp").mask('(00) 00000-0000', options);
		}
		else
		{
		   $("#celular_sp").mask('(00) 0000-0000', options);
		}*/
		mask_field.mask('(00) 0000-0000', options);
		// fim - HD 896924 - Validação para o nono dígito de celulares que iniciam com 11

		$("#data_nascimento").mask('00-00-0000');
		$("#cep").mask('00000-000');
		//$(".ddd").mask("99");
		$("#telefone").mask('(00) 0000-0000');

		$("#estado").change(function () {
			if ($(this).val().length > 0) {
				buscaCidade($(this).val());
			} else {
				$("#cidade > option[rel!=default]").remove();
			}
		});

		/* # HD 941072 - Busca produto pela descrição */
		$("#produto_descricao").autocomplete(php_self + "?tipo_busca=produto&marca=<?php echo $marca; ?>",
		{
			minChars      : 3,
			delay         : 150,
			width         : 350,
			matchContains : true,
			formatItem    : function(row){ return row[1]  },
			formatResult  : function(row){ return row[1]; }
		});

		$("#produto_descricao").result(function(event, data, formatted)
		{
			$("#produto").val(data[0]);
			$("#produto_descricao").val(formatItemDescricao(data));
			defeitoReclamado(data[0]);
		});

		function defeitoReclamado(produto)
		{
			$.get(php_self,
			{ 'reclamado': '', 'tipo_busca': 'defeito_reclamado', 'produto': produto },
				function (data){
					if( data ){
						$("#div_defeitos").html(data);
						return true;
					}
				}
			);
		}

		function formatItemDescricao(row)
		{
			return /*row[0] + " - " + */row[1];
		}
		/* # HD 941072 - fim */

		$('#cep').on('blur', function(){
			if($(this).val() == '')
				return true;

			if($(this).val().length == 9){
				$('#loading-gif').show();
				var cep = $(this).val().replace(/\D/, '');
				$.get(
					'ajax_cep.php',
					{ 'ajax': 'cep', 'cep': cep },
					function (data){
						if(data == 'ko'){
							$('#endereco').focus();
							return true;
						}

						if(data.indexOf(';') >= 0){
							r = data.split(';');

							var text = r[3];

				            text = text.replace(new RegExp('[ÁÀÂÃ]','gi'), 'A');
				            text = text.replace(new RegExp('[ÉÈÊ]','gi'), 'E');
				            text = text.replace(new RegExp('[ÍÌÎ]','gi'), 'I');
				            text = text.replace(new RegExp('[ÓÒÔÕ]','gi'), 'O');
				            text = text.replace(new RegExp('[ÚÙÛ]','gi'), 'U');
				            text = text.replace(new RegExp('[Ç]','gi'), 'C');

							text = text.toUpperCase();

							r[3] = text;

							$('#endereco').val(r[1]);
							$('#bairro').val(r[2]);
							$('#estado').val(r[4]);
							$('#numero').val(r[5]).focus();

							buscaCidade(r[4], r[3]);
							$('#loading-gif').hide();
						}

					}
				)
			}else{
				alert('CEP inválido');
			}
		});
	});

	function buscaCidade (estado, cidade) {
		$.ajax({
			async: false,
			url: "callcenter_cadastra_bestway.php",
			type: "POST",
			data: { buscaCidade: true, estado: estado },
			cache: false,
			complete: function (data) {
				data = $.parseJSON(data.responseText);

				if (data.cidades) {
					$("#cidade > option[rel!=default]").remove();

					var cidades = data.cidades;

					$.each(cidades, function (key, value) {
						var option = $("<option></option>");
						$(option).attr({ value: value.cidade_pesquisa });
						$(option).text(value.cidade);

						if (cidade != undefined && value.cidade.toUpperCase() == cidade.toUpperCase()) {
						 	$(option).attr({ selected: "selected" });
						}

						$("#cidade").append(option);
					});
				} else {
					$("#cidade > option[rel!=default]").remove();
				}
			}
		});
	}

	function frmValidaFormContato()
	{
		var erro      = 0;
		assunto		  = document.getElementById('assunto');
		nome_completo = document.getElementById('nome_completo');
		sexo		  = document.getElementById('sexo');
		data_nasc 	  = document.getElementById('data_nascimento');
		email         = document.getElementById('email');
		telefone      = document.getElementById('telefone');
		celular_sp    = document.getElementById('celular_sp');
		endereco      = document.getElementById('endereco');
		numero        = document.getElementById('numero');
		bairro        = document.getElementById('bairro');
		cidade        = document.getElementById('cidade');
		estado        = document.getElementById('estado');
		cep 		  = document.getElementById('cep');
		produto 	  = document.getElementById('produto_descricao');
		mensagem      = document.getElementById('mensagem');
		defeito       = document.getElementById('defeito_reclamado');

		if( (assunto.value == '' || assunto.value == ' ') && erro == 0 ){
			alert("Escolha o " + assunto.title);
			assunto.focus();
			erro = 1;
			return false;
		}

		if( (nome_completo.value == '' || nome_completo.value == ' ') && erro == 0 ){
			alert("Preencha o campo " + nome_completo.title);
			nome_completo.focus();
			erro = 1;
			return false;
		}

		if( ( !$('input[name=sexo]').is(':checked') ) && erro == 0){
			alert("Escolha o sexo");
			$('input[name=sexo]').focus();
			erro = 1;
			return false;
		}

		if( (data_nasc.value == '' || data_nasc.value == ' ') && erro == 0 ){
			alert("Preencha o campo " + data_nascimento.title);
			data_nasc.focus();
			erro = 1;
			return false;
		}

		if( (email.value == '' || email.value == ' ') && erro == 0 ){
			alert("Preencha o campo " + email.title);
			email.focus();
			erro = 1;
			return false;
		}

		if( (telefone.value == '' || telefone.value == ' ') && erro == 0 ){
			alert("Preencha o campo " + telefone.title);
			telefone.focus();
			erro = 1;
			return false;
		}

		if( telefone.value.length != 14 )
		{
			alert("Preencha completamente o campo " + telefone.title);
			telefone.focus();
			erro = 1;
			return false;
		}

		if( celular_sp.value.length > 0 && erro == 0 )
		{
			/* Testando a digitação do 9º dígito pra São Paulo no DDD 11 */
			if( /^\(11\) 9/i.test(celular_sp.value) && celular_sp.value.length < 15 )
			{
				alert("Preencha o 9º dígito corretamente no campo " + celular_sp.title);
				celular_sp.focus();
				erro = 1;
				return false;
			}
			else if( !/^\(11\) 9/i.test(celular_sp.value) && celular_sp.value.length < 14 )
			{
				alert("Preencha completamente o campo " + celular_sp.title);
				celular_sp.focus();
				erro = 1;
				return false;
			}
		}

		if( (cep.value == '' || cep.value == ' ') && erro == 0 ){
			alert("Preencha o campo " + cep.title + ' para carregar o endereço');
			cep.focus();
			erro = 1;
			return false;
		}

		if( (endereco.value == '' || endereco.value == ' ') && erro == 0 ){
			alert("Preencha o campo endereço");
			endereco.focus();
			erro = 1;
			return false;
		}

		if( (numero.value == '' || numero.value == ' ') && erro == 0 ){
			alert("Preencha o campo Número");
			numero.focus();
			erro = 1;
			return false;
		}

		if( (bairro.value == '' || bairro.value == ' ') && erro == 0 ){
			alert("Preencha o campo bairro");
			bairro.focus();
			erro = 1;
			return false;
		}

		if( (cidade.value == '' || cidade.value == ' ') && erro == 0 ){
			alert("Preencha o campo cidade");
			cidade.focus();
			erro = 1;
			return false;
		}

		if( (estado.value == '' || estado.value == ' ') && erro == 0 ){
			alert("Preencha o campo " + estado.title);
			erro = 1;
			return false;
		}

		if( (produto.value == '' || produto.value == ' ') && erro == 0 ){
			alert("Preencha o campo " + produto.title);
			produto.focus();
			erro = 1;
			return false;
		}

		if( defeito.value == '' && erro == 0 ){
			alert("Preencha o campo " + defeito.title);
			defeito.focus();
			erro = 1;
			return false;
		}

		if( (mensagem.value == '' || mensagem.value == ' ') && erro == 0 ){
			alert("Preencha o campo " + mensagem.title);
			mensagem.focus();
			erro = 1;
			return false;
		}
		//return (erro == 0);
	}
</script>


<?php
# Pesquisa pelo AutoComplete AJAX do produto para popular o combo de Defeitos Reclamados
$q = strtolower($_GET["q"]);

if( isset($_GET["q"]) )
{
	$busca      = $_GET["busca"];
	$tipo_busca = $_GET["tipo_busca"];

	if( strlen($q) > 2 )
	{
		if( $tipo_busca == 'posto' )
		{
			$sql = "SELECT
						tbl_posto.posto,
						tbl_posto.cnpj,
						tbl_posto.nome,
						tbl_posto_fabrica.codigo_posto,
						tbl_posto_fabrica.nome_fantasia,
						tbl_posto.fone,
						tbl_posto_fabrica.contato_fone_comercial as contato_fone_comercial,
						tbl_posto_fabrica.contato_email as contato_email,
						tbl_posto_fabrica.contato_email as email
					FROM tbl_posto
						JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
					WHERE tbl_posto_fabrica.fabrica = $login_fabrica
						AND tbl_posto_fabrica.credenciamento IN ('CREDENCIADO', 'EM DESCREDENCIAMENTO')";

			if ($busca == "codigo"){
				$sql .= " AND tbl_posto_fabrica.codigo_posto ilike '%$q%' ";
			}else{
				$sql .= "  AND (
								UPPER(tbl_posto.nome) like UPPER('%$q%')
							OR  UPPER(tbl_posto_fabrica.nome_fantasia) like UPPER('%$q%')
								)";
			}

			$res = pg_query($con,$sql);

			if( pg_num_rows ($res) > 0 )
			{
				for( $i=0; $i<pg_num_rows ($res); $i++ )
				{
					$posto         = trim(pg_fetch_result($res, $i, posto));
					$cnpj          = trim(pg_fetch_result($res, $i, cnpj));
					$nome          = trim(pg_fetch_result($res, $i, nome));
					$codigo_posto  = trim(pg_fetch_result($res, $i, codigo_posto));
					$nome_fantasia = trim(pg_fetch_result($res, $i, nome_fantasia));
					$fone          = trim(pg_fetch_result($res, $i, fone));
					$fone_2        = trim(pg_fetch_result($res, $i, contato_fone_comercial));
					$email         = trim(pg_fetch_result($res, $i, email));
					$email_2       = trim(pg_fetch_result($res, $i, contato_email));

					echo "$posto|$cnpj|$codigo_posto|$nome|$nome_fantasia|$fone|$email";
					
					echo "\n";
				}
			}
		}
	}
	exit;
}
?>

<?php include $marcaID . '_form.php'; ?>

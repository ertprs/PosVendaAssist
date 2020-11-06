<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include '../js/js_css.php';
?>

<style type="text/css">
	
	table { cursor: default;}

	.titulo_cab{
		background: #C9D7E7;
		padding: 5px;
		color: #000000;
		font: bold;
		list-style-type: none;
	}

	.anexo {
		color: #000000;
		clear: none;
		padding: 2px 0px 2px 0px;
		font-style: normal;
		font-variant: normal;
		text-align: center;
		cursor: pointer;
	}

	.anexo label {
		cursor: pointer;
	}

	.anexo:hover {
		color: red;
		background-color: #EAEEF5;
	}
</style>

<!-- jQuery / Plugin Editor -->
<script type='text/javascript' src='../admin/js/jquery-1.3.2.js'></script>
<script src="../plugins/ckeditor/ckeditor.js"></script>

<script type="text/javascript">

$(window).load(function(){
    CKEDITOR.replace("comentario", { enterMode : CKEDITOR.ENTER_BR, toolbar : 'Basic', uiColor : '#A0BFE0', width: 500 });
});

var analise = {
	addInteracaoFile:function(){
		var newLiFile = "<li class='titulo_cab' style='margin:3px'> Arquivo: <input type='file' name='arquivo[]' > <img src='imagens/close_black_opaque.png' class='itemDelete' style='cursor:pointer;float:right'> </li>";
		$("#container_file_interacao").append(newLiFile);
	}			
}

$(document).ready(function() {
	$('.itemDelete').live('click', function() {
		$(this).closest('li').remove();
		var qtdeArquivos = $('#qtdeArquivos').val();
		qtdeArquivos--;
		$('#qtdeArquivos').val(qtdeArquivos);
	});

	$(".anexo").click(function(){
		var table = $(this).find("table");
		var label = $(this).find("label");

		if(label.attr('rel') == '1') {
			label.html("Mostrar anexos");
			label.attr('rel', '0');
		} else {
			label.html("Esconder anexos");
			label.attr('rel', '1');
		}

		table.toggle();
	});
});
</script>

<?
if($_POST['hd_chamado']) $hd_chamado = trim ($_POST['hd_chamado']);
if($_GET ['hd_chamado']) $hd_chamado = trim ($_GET ['hd_chamado']);

//HD 7277 Paulo - tirar acento do arquivo upload
function retira_acentos( $texto ){
 $array1 = array("á", "à", "â", "ã", "ä", "é", "è", "ê", "ë", "í", "ì", "î", "ï", "ó", "ò", "ô", "õ", "ö", "ú", "ù", "û", "ü", "ç" , "Á", "À", "Â", "Ã", "Ä", "É", "È", "Ê", "Ë", "Í", "Ì", "Î", "Ï", "Ó", "Ò", "Ô", "Õ", "Ö", "Ú", "Ù", "Û", "Ü", "Ç" );
 $array2 = array("a", "a", "a", "a", "a", "e", "e", "e", "e", "i", "i", "i", "i", "o", "o", "o", "o", "o", "u", "u", "u", "u", "c" , "A", "A", "A", "A", "A", "E", "E", "E", "E", "I", "I", "I", "I", "O", "O", "O", "O", "O", "U", "U", "U", "U", "C" );
 return str_replace( $array1, $array2, $texto );
}

if (strlen ($btn_acao) > 0) {

	if($_POST['comentario']) 		 $comentario    = trim ($_POST['comentario']);
	if($_POST['titulo'])     		 $titulo        = trim ($_POST['titulo']);
	if($_POST['fabrica'])    		 $fabrica       = trim ($_POST['fabrica']);
	if($_POST['nome'])       		 $nome          = trim ($_POST['nome']);
	if($_POST['projeto'])       	 $projeto       = trim ($_POST['projeto']);
	if($_POST['email'])      		 $email         = trim ($_POST['email']);
	if($_POST['fone'])       		 $fone          = trim ($_POST['fone']);
	if($_POST['combo_tipo_chamado']) $xtipo_chamado = trim ($_POST['combo_tipo_chamado']);
	if(!strlen($xtipo_chamado))      $xtipo_chamado = trim ($_POST['combo_tipo_chamado_2']);

	if($projeto == '- ESCOLHA -') {
		$msg_erro = "Selecione o projeto";
	}

	$arquivo = isset($_FILES["arquivo"]) ? $_FILES["arquivo"] : FALSE;

	/*--==VALIDAÇÕES=====================================================--*/

	$fabricante_responsavel = 10;
	$status                 = "Novo";

	if (strlen($comentario) < 2)  $msg_erro  = "Comentário muito pequeno"       ;
	if (strlen($fone) < 7)        $msg_erro  = "Entre com o número do telefone!";
	if(strlen($fabrica==0))       $msg_erro  = "Selecione a Fábrica"            ;

	if (strlen($xtipo_chamado) ==0){
		$msg_erro="Escolha o tipo de chamado";
	}else{
	 	$xtipo_chamado =  str_replace($filtro,"", $xtipo_chamado);
	}

	if (strlen($msg_erro) == 0){
		$res = @pg_exec($con,"BEGIN TRANSACTION");
		if (strlen($titulo) == 0){
			$msg_erro="Por favor insira um titulo!";
		}
		//Faz update do hd_chamado_item com o tempo de execução da ultima interação.
		$sql =" UPDATE tbl_hd_chamado_item
				SET termino = current_timestamp
				WHERE hd_chamado_item in(SELECT hd_chamado_item
							 FROM tbl_hd_chamado_item
							 WHERE hd_chamado = (select hd_chamado from tbl_hd_chamado_atendente where admin = $login_admin and data_termino is null limit 1)
								AND termino IS NULL
							 ORDER BY hd_chamado_item desc
							 LIMIT 1 );";

		$res = pg_exec ($con,$sql);

		//se o admin não for analista de help-desk nem programador o help-desk é aberto para o suporte atender
		$sql = "SELECT responsabilidade from tbl_admin where admin = $login_admin";
		$res = pg_exec($con,$sql);
		if (pg_result($res,0,0) == 'Programador' OR pg_result($res,0,0) == 'Analista de Help-Desk') {
			$atendente = $login_admin;
		} else {
			$atendente = 435; //suporte
		}

		if(!strlen($hd_chamado)){
			$sql =	"INSERT INTO tbl_hd_chamado (
						admin                                                        ,
						fabrica                                                      ,
						fabrica_responsavel                                          ,
						titulo                                                       ,
						atendente                                                    ,
						tipo_chamado                                                 ,
						projeto                                                 ,
						status
					) VALUES (
						$login_admin                                                 ,
						$fabrica                                                     ,
						$fabricante_responsavel                                      ,
						'$titulo'                                                    ,
						$atendente                                                   ,
						$xtipo_chamado                                               ,
						$projeto                                               ,
						'$status'
					);";
;
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);
			$msg_erro = substr($msg_erro,6);
			$res = @pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado')");
			$hd_chamado  = pg_result ($res,0,0);

			//--======================================================================
			$sql = "UPDATE tbl_hd_chamado_atendente
							SET data_termino = CURRENT_TIMESTAMP
							WHERE admin      =  $login_admin
							AND   data_termino IS NULL
							";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

			$sql = "INSERT INTO tbl_hd_chamado_atendente(
											hd_chamado ,
											admin      ,
											data_inicio
									)VALUES(
									$hd_chamado       ,
									$login_admin      ,
									CURRENT_TIMESTAMP
									)";
			$res = pg_exec ($con,$sql);
			$msg_erro = pg_errormessage($con);

		}//fim do inserir chamado

		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);

		$sql =	"INSERT INTO tbl_hd_chamado_item (
					hd_chamado                                                   ,
					comentario                                                   ,
					admin
				) VALUES (
					$hd_chamado                                                  ,
					'$comentario'                                                ,
					$login_admin
				);";

		$res = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);

		$res = @pg_exec ($con,"SELECT CURRVAL ('seq_hd_chamado_item')");
		$hd_chamado_item  = pg_result ($res,0,0);

		$sql = "UPDATE tbl_hd_chamado 
				SET status = NULL 
				WHERE hd_chamado = $hd_chamado 
				AND   status 	 = 'Resolvido'";

		$res      = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);

		$sql = "UPDATE tbl_hd_chamado 
				SET projeto = $projeto 
				WHERE hd_chamado = $hd_chamado";

		$res      = pg_exec ($con,$sql);
		$msg_erro = pg_errormessage($con);
		$msg_erro = substr($msg_erro,6);

		if(!strlen($msg_erro)) {
			/**
			 * Criação do uploar de anexos dos requisitos.
			 * HD 815312
			 * @author Gabriel Silveira
			 */

			$chamado 	= $hd_chamado;
			$pathToSave = dirname(__FILE__).'/documentos/hd-'.$chamado.'-itens/';

			if (!is_dir($pathToSave)) {
				system("mkdir -m 777 $pathToSave");
			}

			$arquivos = array( array( ) );

			foreach($_FILES as $key=>$info ) {

				foreach( $info as $key=>$dados ) {
					for( $i = 0; $i < sizeof( $dados ); $i++ ) {
					    // Dessa forma, fica mais facil trabalharmos o array depois, para salvar o arquivo
					    $arquivos[$i][$key] = $info[$key][$i];
					}
				}
			}
			
			$i = 1;

			// Fazemos o upload normalmente, igual no exemplo anterior
			foreach( $arquivos as $arquivo ) {

				// Verificar se o campo do arquivo foi preenchido
				if( $arquivo['name'] != '' ) {
					
					$arquivoTmp  = $arquivo['tmp_name'];
					$arquivoPath = $pathToSave.$hd_chamado_item."-".$arquivo['name'];

					if( !move_uploaded_file( $arquivoTmp, $arquivoPath ) ) {
					
						$msg_erro = 'Erro no upload do arquivo '.$i;
					
					}
				} 

				//ROTINA DE UPLOAD DE ARQUIVO
				if (!strlen($msg_erro)) {

					$att_max_size = 2097152; // Tamanho máximo do arquivo (em bytes)

					if ($arquivo['error']==1) {
						if ($arquivo['size']==0) $msg_erro.= 'Tamanho do arquivo inválido! ';
						$msg_erro.= 'O arquivo não pôde ser anexado.<br>';
					}

					if (strlen($arquivo["tmp_name"]) > 0 && $arquivo["tmp_name"] != "" and !$msg_erro) {

						$a_tipos = array(
							/* Imagens */
							'bmp'	=> 'image/bmp',
							'gif'	=> 'image/gif',
							'ico'	=> 'image/x-icon',
							'jpg'	=> 'image/jpeg;image/pjpeg',
							'jpeg'	=> 'image/jpeg;image/pjpeg',
							'png'	=> 'image/png;image/x-png',
							'tif'	=> 'image/tiff',
							/* Texto */
							'csv'	=> 'text/comma-separated-values;text/csv;application/vnd.ms-excel',
							'eps'	=> 'application/postscript',
							'pdf'	=> 'application/pdf',
							'ps'	=> 'application/postscript',
							'rtf'	=> 'text/rtf',
							'tsv'	=> 'text/tab-separated-values;text/tsv;application/vnd.ms-excel',
							'txt'	=> 'text/plain',
							/* Office */
							'doc'	=> 'application/msword',
							'ppt'	=> 'application/vnd.ms-powerpoint',
							'xls'	=> 'application/vnd.ms-excel',
							'docx'	=> 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
							'pptx'	=> 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
							'xlsx'	=> 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
							/* Star/Open/BR/LibreOffice.org */
							'odt'	=> 'application/vnd.oasis.opendocument.text;application/x-vnd.oasis.opendocument.text',
							'ods'	=> 'application/vnd.oasis.opendocument.spreadsheet;application/x-vnd.oasis.opendocument.spreadsheet',
							'odp'	=> 'application/vnd.oasis.opendocument.presentation;application/x-vnd.oasis.opendocument.presentation',
							/* Compactadores */
							'sit'	=> 'application/x-stuffit',
							'hqx'	=> 'application/mac-binhex40',
							'7z'	=> 'application/octet-stream',
							'lha'	=> 'application/octet-stream',
							'lzh'	=> 'application/octet-stream',
							'rar'	=> 'application/octet-stream;application/x-rar-compressed;application/x-compressed',
							'zip'	=> 'application/zip'
						);

						// Pega extensão do arquivo
						$a_att_info	  = pathinfo($arquivo['name']);
						$ext          = $a_att_info['extension'];
						$arquivo_nome = $a_att_info['filename']; // Tira a extensão do nome... PHP 5.2.0+
						$aux_extensao = "'$ext'";

						/*var_dump($a_att_info);
						var_dump($ext);
						var_dump($arquivo_nome);
						var_dump($aux_extensao);
						var_dump(array_key_exists($ext, $a_tipos));*/
						// Verifica o mime-type do arquivo, ou a extensão
						$tipo = array_key_exists($ext, $a_tipos);
						
						if ($arquivo['type'] == 'application/octet-stream') {
						// Tem navegadores que usam o 'application/octet-stream' para tipos desconhecidos...
							$tipo = array_key_exists($ext, $a_tipos);
						}

						if ($tipo) {// Verifica tamanho do arquivo

							if ($arquivo["size"] > $att_max_size)
								$msg_erro.= "Arquivo tem tamanho muito grande! Deve ser de no máximo 2MB. Envie outro arquivo.<br>";

						} else {

							$msg_erro.= "Arquivo $i em formato inválido!<br>";
						}

						if (strlen($msg_erro) == 0) { // Processa o arquivo
							//  Substituir tudo q não for caracteres aceitos para nome de arquivo para '_'

							$arquivo_nome = preg_replace("/[^a-zA-Z0-9_-]/", '_', retira_acentos($arquivo_nome));
							$nome_anexo   = strtolower(dirname(__FILE__)."/documentos/" . $hd_chamado_item . '-' . $arquivo_nome . '.' . $ext);
						}

					}

				}//fim do upload

				$i++;

			}	
		}

		if(strlen($msg_erro) > 0){
			$res = pg_exec ($con,"ROLLBACK TRANSACTION");
			$msg_erro = 'Não foi possível Inserir o Chamado';
		}else{
			$res = pg_exec($con,"COMMIT");
		}
	}
}


if(strlen($hd_chamado)>0){
	$sql= " SELECT tbl_hd_chamado.hd_chamado                              ,
					tbl_hd_chamado.admin                                  ,
					to_char (tbl_hd_chamado.data,'DD/MM HH24:MI') AS data ,
					tbl_hd_chamado.titulo                                 ,
					tbl_hd_chamado.status                                 ,
					tbl_hd_chamado.atendente                              ,
					tbl_hd_chamado.tipo_chamado 						  ,
					tbl_hd_chamado.fabrica_responsavel                    ,
					tbl_hd_chamado.projeto                                ,
					tbl_fabrica.nome                                      ,
					tbl_admin.login                                       ,
					tbl_admin.nome_completo                               ,
					tbl_admin.fone                                        ,
					tbl_admin.email                                       ,
					at.nome_completo AS atendente_nome
			FROM tbl_hd_chamado
			JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado.admin
			JOIN tbl_fabrica ON tbl_fabrica.fabrica = tbl_admin.fabrica
			LEFT JOIN tbl_admin at ON tbl_hd_chamado.atendente = at.admin
			WHERE hd_chamado = $hd_chamado";

	$res = pg_exec ($con,$sql);
	if (pg_numrows($res) > 0) {
		$hd_chamado           = pg_result($res,0,hd_chamado);
		$admin                = pg_result($res,0,admin);
		$data                 = pg_result($res,0,data);
		$titulo               = pg_result($res,0,titulo);
		$status               = pg_result($res,0,status);
		$atendente            = pg_result($res,0,atendente);
		$fabrica_responsavel  = pg_result($res,0,fabrica_responsavel);
		$nome                 = pg_result($res,0,nome_completo);
		$projeto              = pg_result($res,0,projeto);
		$email                = pg_result($res,0,email);
		$fone                 = pg_result($res,0,fone);
		$fabrica_nome         = pg_result($res,0,nome);
		$login                = pg_result($res,0,login);
		$atendente_nome       = pg_result($res,0,atendente_nome);
		$tipo_chamado         = pg_result($res,0,tipo_chamado);
	}else{
		$msg_erro="Chamado não encontrado";
	}
}else{
	$status="Novo";
	$login = $login_login;
	$data = date("d/m/Y");
	$sql = "SELECT * FROM tbl_admin WHERE admin = $login_admin";
	$resX = pg_exec ($con,$sql);

	$nome                 = pg_result($resX,0,nome_completo);
	$email                = pg_result($resX,0,email);
	$fone                 = pg_result($resX,0,fone);

}

$TITULO = "Lista de Chamadas - Telecontrol Hekp-Desk";
$ONLOAD = "frm_chamado.titulo.focus()";
if (strlen ($hd_chamado) > 0) $ONLOAD = "";
include "menu.php";
?>
<? if($login_admin ==822 or $login_admin==398) {
	echo "<script type='text/javascript' src='../admin/js/fckeditor/fckeditor.js'></script>";
}
?>
<script>
<? if($login_admin ==822 or $login_admin==398) { ?>
window.onload = function(){
	var oFCKeditor = new FCKeditor( 'comentario' ) ;
	oFCKeditor.BasePath = "../admin/js/fckeditor/" ;
	oFCKeditor.ToolbarSet = 'Chamado' ;
	oFCKeditor.ReplaceTextarea() ;
}
<?}?>
</script>
<form name='frm_chamado' action='<? echo $PHP_SELF ?>' method='post' enctype='multipart/form-data' >
<table width = '500' align = 'center' border='0' cellpadding='2'  style='font-family: arial ; font-size: 12px'>

<input type='hidden' name='hd_chamado' value='<?= $hd_chamado ?>'>

<?
if (strlen ($hd_chamado) > 0) {
	echo "<tr>";
	echo "<td colspan='4' align='center' class = 'Titulo2' height='30'><strong>Chamado nº. $hd_chamado </strong></td>";
	echo "</tr>";
}
?>

<tr>
	<td width="100" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Login </strong></td>
	<td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>&nbsp;<?= $login ?> </td>
	<td width="100" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Abertura </strong></td>
	<td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'><?= $data ?> </td>
</tr>

<?
if (strlen ($hd_chamado) > 0) {
?>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Status </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>&nbsp;<?= $status ?> </td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Analista </strong></td>
	<td             bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'><?= $atendente_nome ?> </td>
</tr>
<? } ?>


<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Título </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3'>&nbsp;<input type='text' size='50' name='titulo' maxlength='50' value='<?= $titulo ?>' <? if (strlen ($hd_chamado) > 0) echo " disabled " ?> > </td>
</tr>

<tr>
	<td width="100" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Nome </strong></td>
	<td 			bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>&nbsp;<input type='text' size='30' maxlength='30'  name='nome' value='<?= $nome ?>'></td>
	<td width="100" bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Projeto </strong></td>
	<td 			bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px; text-align: center;'>
		<select style='width: 97%;' name='projeto'>
			<option>- ESCOLHA -</option>
			<?	
				$sql_p = "SELECT *
						  FROM tbl_projeto
						  WHERE projeto_principal IS NOT NULL";

				$res_p = pg_query($con, $sql_p);

				while($projeto_r = pg_fetch_object($res_p))
					echo "<option value='$projeto_r->projeto' ". ($projeto_r->projeto == $projeto ? "SELECTED" : "") ." >$projeto_r->nome</option>";
			?>
		</select>
	</td>
</tr>

<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Email </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>&nbsp;<input type='text' size='30' name='email' value='<?= $email ?>'></td>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Fone </strong></td>
	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width=1px'>&nbsp;<input type='text' size='15' name='fone' value='<?= $fone ?>'></td>
</tr>
<tr>
	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width=1px'><strong>&nbsp;Fábrica </strong></td>

<?
//trazer fabricas ativas
$sql = "SELECT   *
		FROM     tbl_fabrica
		WHERE ativo_fabrica IS TRUE
		ORDER BY nome";

$res = pg_exec ($con,$sql);
$n_fabricas = pg_numrows($res);
	echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px' >";
	echo "<select class='frm' style='width: 200px;' name='fabrica' class='caixa'></center>\n";
	echo "<option value=''>- FÁBRICA -</option>\n";
	for ($x = 0 ; $x < pg_numrows($res) ; $x++){
		$x_fabrica = trim(pg_result($res,$x,fabrica));
		$nome      = trim(pg_result($res,$x,nome));
		echo "<option value='$x_fabrica' ". ($x_fabrica == $fabrica ? "SELECTED" : "") .">$nome</option>\n";
	}
	echo "</select>\n";
	echo"</td>";
?>

	<td bgcolor="#CED8DE" style='border-style: solid; border-color: #6699CC; border-width:1px'><strong>&nbsp;Tipo </strong></td>

	<td bgcolor="#E5EAED" style='border-style: solid; border-color: #6699CC; border-width:1px'>
<?
	$sql = "SELECT	tipo_chamado,
					descricao
			FROM tbl_tipo_chamado
			WHERE tipo_chamado in (4,5,6)
			ORDER BY descricao;";
	$res = pg_exec($con,$sql);
	
	if(pg_num_rows($res)){

		if (strlen ($hd_chamado) && strlen($tipo_chamado)) { echo " <input type='hidden' size='60' name='combo_tipo_chamado_2'  value='$tipo_chamado' >"; }
		
		echo "<select name=\"combo_tipo_chamado\" size=\"1\" " . (strlen($hd_chamado) && strlen($tipo_chamado) ? " disabled " : "") . " class='Caixa'>";

		while($chamado = pg_fetch_object($res)) {

			echo "<option value='$chamado->tipo_chamado' " . ($tipo_chamado == $chamado->tipo_chamado ? " SELECTED " : "") . " >";

			fecho($chamado->descricao, $con, $sistema_lingua);
			
			echo "</option>";
		}
}
?>



	</td>

</tr>
</table>
<?

$sql= "SELECT   tbl_hd_chamado_item.hd_chamado_item,
		to_char (tbl_hd_chamado_item.data,'DD/MM HH24:MI') AS data   ,
				tbl_hd_chamado_item.comentario                            ,
				tbl_admin.nome_completo AS autor
		FROM tbl_hd_chamado_item
		JOIN tbl_admin ON tbl_admin.admin = tbl_hd_chamado_item.admin
		WHERE hd_chamado = $hd_chamado
		ORDER BY hd_chamado_item";
$res = @pg_exec ($con,$sql);

if (@pg_numrows($res) > 0) {
	echo "<table width = '750' align = 'center' cellpadding='0' cellspacing='0'>";
	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_esquerdo_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_centro_azul_claro.gif' colspan='5' align = 'center' width='100%' style='font-family: arial ; color:#666666'><b>Interações</b></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_top_direito_azul_claro.gif' rowspan='2'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";

	echo "<tr bgcolor='#D9E8FF' style='font-family: arial ; color: #666666'>";
	echo "<td ><font size='2'><strong>Nº </strong></font></td>";
	echo "<td nowrap><font size='2'><strong>Data</strong></font></td>";
	echo "<td ><font size='2'><strong>Coment&aacute;rio </strong></font></td>";
	echo "<td nowrap><font size='2'><strong>Autor </strong></font></td>";
	echo "<td nowrap style='text-align: center;'><font size='2'><strong>Anexos </strong></font></td>";
	echo "</tr>";
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		
		$x=$i+1;
		$hd_chamado_item = pg_result($res,$i,hd_chamado_item);
		$data_interacao  = pg_result($res,$i,data);
		$autor           = pg_result($res,$i,autor);
		$item_comentario = pg_result($res,$i,comentario);
		
		$dir = "documentos/";

		$cor='#ffffff';
		if ($i % 2 == 0) $cor = '#F2F7FF';

		echo "<tr  style='font-family: arial ; font-size: 12px' height='25' bgcolor='$cor'>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_esquerdo.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "<td nowrap width='50'>$x </td>";
		echo "<td nowrap>$data_interacao </td>";
		echo "<td >" . nl2br ($item_comentario) . "</td>";
		echo "<td nowrap > $autor</td>";

		if(!count(glob($dir."hd-$hd_chamado-itens/$hd_chamado_item*"))) {
			echo "<td style='text-align: center'>Nenhum anexo encontrado";
		} else {

			echo "<td class='anexo'><label rel='0'>Mostrar anexos</label>";


			if (($dh  = glob($dir."hd-$hd_chamado-itens/$hd_chamado_item*"))) {

				echo "<table align='center' style='border-collapse: collapse; display: none;'>";

				foreach($dh as $filename) {
					echo "<tr>
							  <td style='border-bottom: 0px; background-color: transparent'>
							  	  <a href='$filename' rel='nozoom' name='anexosInteracao' target='blank' style='display: block;'>" . str_replace("hd--itens/-", "", str_replace(array($dir, $hd_chamado_item, $hd_chamado), "", $filename)) . "</a>
							  </td>
						  </tr>";
				}

				echo "</table>";
			}
		}

		echo "</td>";
		echo "<td background='/assist/helpdesk/imagem/fundo_tabela_centro_direito.gif' ><img src='/assist/imagens/pixel.gif' width='9'></td>";
		echo "</tr>";	
	}

	echo "<tr>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_esquerdo.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_centro.gif' colspan='5' align = 'center' width='100%'></td>";
	echo "<td background='/assist/helpdesk/imagem/fundo_tabela_baixo_direito.gif'><img src='/assist/imagens/pixel.gif' width='9'></td>";
	echo "</tr>";
	echo "</table>";
}

echo "<center>";
if (strlen ($hd_chamado) > 0) {
	if ($status == 'Resolvido') {
		echo "<b><font face='arial' color='#666666'>Este chamado está resolvido.</font></b><br>";
		echo "<b><font face='arial' color='#FF0000' size='-1'>Se você não concordar com a solução, pode reabri-lo digitando uma mensagem abaixo.</font></b><br>";
	}else{
		echo "<b><font face='arial' color='#666666'>Digite o texto para dar continuidade ao chamado</font></b><br>";
	}
}else{
	echo "<b><font face='arial' color='#666666'>Digite o texto do seu chamado</b></font><br>";
}

echo "<table width='500' border='1' align='center' cellpadding='2'  style='font-family: arial ; font-size: 12px'>";
echo "<tr>";
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width=1px' colspan='3' align='center'>";
echo "<textarea name='comentario' cols='50' rows='6' wrap='VIRTUAL'>$comentario</textarea>";
echo "</td>";
echo "</tr>";

echo "<tr>";
echo "<td bgcolor='#E5EAED' style='border-style: solid; border-color: #6699CC; border-width:1px; text-align: center'>";

$arq = $sistema_lingua == 'ES' ? "Archivo" : "Arquivo";

if (empty($qtdeArquivos)){
	$qtdeArquivos = 1;
}
////////////////// ARRUMAR!
echo '<ul id="container_file_interacao" style="vertical-align: middle">
		<li class="titulo_cab" style="margin: 3px"> '.$arq.': <input type="file" name="arquivo[]" ></li>				
	  </ul>
		<input type="button" value="Adicionar novo arquivo" onclick="analise.addInteracaoFile()" >
	';
echo "</td>";
echo "</tr>";
echo "<tr>";
echo "<td align='center'>";
echo "<font color='red' size=1>O ARQUIVO NÃO SERÁ ANEXADO CASO O TAMANHO EXCEDA O LIMITE DE 2 MB.</font> ";
echo "</td>";
echo "</tr>";

echo "</table>";
echo "<input type='submit' name='btn_acao' value='Enviar Chamado'>";
echo "</center>";

echo "</form>";


?>
		</td>
	</tr>
</table>

<? include "rodape.php" ?>

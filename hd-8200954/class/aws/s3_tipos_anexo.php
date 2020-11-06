<?php
/**
 * s3_tipos_anexo.php
 *
 * Arquivo de configuração da class anexaS3
 *
 * Contém as configurações dos tipos de anexo aceitos pela class,
 * permitindo a configuração da mesma apenas alterando este array.
 *
 * Os arquivos são salvos num "bucket" do Amazon S3, e o acesso pode
 * ser via CDN (com 'cache' no Brasil) ou no caso de arquivos cujo
 * acesso é restrito (como as vistas explodidas), vi apré-autenticação
 * com tempo limite de acesso.
 *
 * Parâmetros de configuração:
 *
 * bucket:		string	Nome completo do 'bucket' a ser utilizado
 * cdn:			string	OPTIONAL Rota do CDN. A class irá tentar ler primeiro aí
 * acl:			string	OPTIONAL 'Access Control Level': para estabelecer o nível
 * 						de acesso desse arquivo. A class do S3 cria já
 * 						constantes para isso: ACL_PUBLIC, ACL_PRIVATE e
 * 						ACL_AUTH_READ (tem ACL_AUTH_WRITE, mas não é usado)
 * authtime:	strtime Estabelece o "tempo de vida" da chave de autenticação
 * 						para arquivos não públicos. Para o uso nesta class,
 * 						deve ser uma string que o strtotime aceite, como
 * 						'+10 minutes' ou '+12 hours'
 * accepts:		regex	Expressão regular (sem '/') para validar o tipo MIME
 * 						do arquivo a ser anexado.
 * max_size:	int		tamanho máximo em bytes do arquivo a ser anexado
 * localdir:	string	'path' do diretório "local" onde buscar o arquivo
 * 						solicitado, caso não esteja no S3.
 * root_dir:	string	OPTIONAL path relativo (sem '/' no começo) que será usado como
 * 						"raiz" no bucket. Isto permite usar o mesmo 'bucket'
 * 						para vários tipos (p.e., veja os tipo 've' e 'co')
 * max_files:	int		[TO-DO] Número máximo de arquivos por ID
 * validaID:	string	OPTIONAL consulta SQL para validar o ID. Deve conter
 * 						uma SQL válida que retorne apenas 1 registro para esse ID
 *
 **/
if(!function_exists('colocaAspas')){
	function colocaAspas($valor){
		return "'$valor'";
	}
}

$vet_co = explode(',',utf8_decode(self::TIPOS_CO));
$vet_co = array_map("colocaAspas",$vet_co);
$vet_co = implode(',',$vet_co);

$vet_ve = explode(',',utf8_decode(self::TIPOS_VE));
$vet_ve = array_map("colocaAspas",$vet_ve);
$vet_ve = implode(',',$vet_ve);

return array(
	'os' => array(
		'bucket'    => 'br.com.telecontrol.os-anexo',
		'cdn'       => 'http://anexo-os.telecontrol.com.br',
		'acl'       => static::ACL_PRIVATE,
		'authtime'  => '+5 minutes',
		'accepts'   => 'jpg|jpeg|pjpeg|xml|pdf|png|doc|docx|odt|msword',
		'max_size'  => (4096*1024),
		'localdir'  => BASE_DIR . 'nf_digitalizada/',
		'max_files' => 4
	),
	'lt' => array(
		'bucket'    => 'br.com.telecontrol.webuploads',
		'cdn'       => 'http://comunicados.telecontrol.com.br',
		'root_dir'  => 'laudo_tecnico/',
		'acl'       => static::ACL_PRIVATE,
		'authtime'  => '+5 minutes',
		'accepts'   => 'txt|pdf|x-pdf|acrobat|text|zz-winassoc-doc|pjpeg|jpeg|png|gif|bmp|pps|vnd.ms-excel|xls|vnd.ms-powerpoint|vnd.msword|vnd.ms-word|msword|doc|winword|word|x-msw6|x-msword|zip|x-zip-compressed|vnd.ms-office',
		'max_size'  => (4096*1024),
		'localdir'  => BASE_DIR . 'comunicados/',
		'validaID'  => "SELECT comunicado, tipo
						  FROM tbl_comunicado
						 WHERE comunicado = %u
						   AND fabrica    = %u
						   AND tipo  IN ('".utf8_decode('Laudo Técnico')."')",
		'max_files' => 1
		),
	'co' => array(
		'bucket'    => 'br.com.telecontrol.posvenda-downloads',
		'cdn'       => 'http://comunicados.telecontrol.com.br',
		'root_dir'  => 'comunicados/',
		'acl'       => static::ACL_PRIVATE,
		'authtime'  => '+5 minutes',
		'accepts'   => 'txt|pdf|x-pdf|acrobat|text|zz-winassoc-doc|richtext|plain|pjpeg|jpeg|png|gif|bmp|pps|vnd.ms-excel|excel|xls|xlsx|vnd.ms-powerpoint|vnd.msword|vnd.ms-word|msword|doc|winword|word|x-msw6|x-msword|zip|x-zip-compressed|vnd.ms-office|octet-stream',
		'max_size'  => (4096*1024),
		'localdir'  => BASE_DIR . 'comunicados/',
		'validaID'  => "SELECT comunicado, tipo
						  FROM tbl_comunicado
						 WHERE comunicado = %u
						   AND fabrica    = %u
						   AND tipo  IN (" . $vet_co . ")",
		'max_files' => 1
		),
	've' => array(
		'bucket'    => 'br.com.telecontrol.posvenda-downloads',
		'cdn'       => 'http://vistaexplodida.telecontrol.com.br/',
		'root_dir'  => 'vista_explodida/',
		'acl'       => static::ACL_AUTH_READ,
		'authtime'  => '+10 minutes',
		'accepts'   => 'jpg|jpeg|pjpeg|pdf|png|gif|zip|xls|xlsx' . '|vnd.ms-excel|excel|msword|spreadsheet|octet-stream', // Validando ZIPs, para subir os que já estavam no sistema
		'max_size'  => (2048*1024),
		'localdir'  => BASE_DIR . 'comunicados/',
		'validaID'  => "SELECT comunicado, tipo
						  FROM tbl_comunicado
						 WHERE comunicado = %u
						   AND fabrica = %u
						   AND tipo    IN (" . $vet_ve . ")",
		'max_files' => 1
	),
	've_familia' => array(
		'bucket'    => 'br.com.telecontrol.posvenda-downloads',
		'cdn'       => 'http://vistaexplodida.telecontrol.com.br/',
		'root_dir'  => 'vista_explodida/familias/',
		'acl'       => static::ACL_AUTH_READ,
		'authtime'  => '+10 minutes',
		'accepts'   => 'zip|gzip', // Validando ZIPs
		'max_size'  => (1048576*1024), // 1Gb para não ter limite...
		'localdir'  => BASE_DIR . 'comunicados/',
		'max_files' => 1
	),
	'pc' => array(
		'bucket'    => 'br.com.telecontrol.imagem.peca',
		'cdn'       => 'http://imagempeca.telecontrol.com.br',
		'acl'       => static::ACL_PRIVATE,
		'authtime'  => '+5 minutes',
		'accepts'   => 'jpg|jpeg|pjpeg|gif|png',
		'max_size'  => (512*1024),
		'localdir'  => BASE_DIR . 'imagens_pecas/' . $login_fabrica,
		'validaID'  => "SELECT produto, fabrica, referencia, descricao FROM tbl_produto WHERE produto= %u AND fabrica = %u",
		'max_files' => 1
	),
	'pd' => array(
		'bucket'    => 'br.com.telecontrol.imagem.produto',
		'cdn'       => 'http://imagempeca.telecontrol.com.br',
		'acl'       => static::ACL_PRIVATE,
		'authtime'  => '+5 minutes',
		'accepts'   => 'jpg|jpeg|pjpeg|gif|png',
		'max_size'  => (512*1024),
		'localdir'  => BASE_DIR . 'imagens_produtos/' . $login_fabrica,
		'validaID'  => "SELECT peca, fabrica, referencia, descricao FROM tbl_peca WHERE peca= %u AND fabrica = %u",
		'max_files' => 1
	),
	'logos' => array(
		'bucket'    => 'br.com.telecontrol.imagem.produto',
		'acl'       => static::ACL_PRIVATE,
		'accepts'   => 'jpg|jpeg|pjpeg|gif|png',
		'max_size'  => (256*1024),
		'localdir'  => BASE_DIR . 'logos/' . $login_fabrica,
		'root_dir'  => 'logos/'
	),
	 'tj' => array(
       'bucket'    => 'br.com.telecontrol.os-anexo',
       'cdn'       => 'http://anexo-os.telecontrol.com.br',
       'acl'       => static::ACL_PRIVATE,
       'root_dir'  => 'troca_judicial/',
       'authtime'  => '+5 minutes',
       'accepts'   => 'jpg|jpeg|pjpeg|pdf|png|doc|docx|odt|msword',
       'max_size'  => (4096*1024),
       'localdir'  => BASE_DIR . 'nf_digitalizada/troca_judicial/',
       'max_files' => 1
   ),
	 'ge' => array(
       'bucket'    => 'br.com.telecontrol.os-anexo',
       'cdn'       => 'http://anexo-os.telecontrol.com.br',
       'acl'       => static::ACL_PRIVATE,
       'root_dir'  => 'garantia_estendida/',
       'authtime'  => '+5 minutes',
       'accepts'   => 'jpg|jpeg|pjpeg|pdf|png|doc|docx|odt|msword',
       'max_size'  => (4096*1024),
       'localdir'  => BASE_DIR . 'nf_digitalizada/garantia_estendida/',
       'max_files' => 1
   ),
	 'od' => array(
       'bucket'    => 'br.com.telecontrol.os-anexo',
       'cdn'       => 'http://anexo-os.telecontrol.com.br',
       'acl'       => static::ACL_PRIVATE,
       'root_dir'  => 'os_digitalizada/',
       'authtime'  => '+5 minutes',
       'accepts'   => 'jpg|jpeg|pjpeg|pdf|png|doc|docx|odt|msword',
       'max_size'  => (4096*1024),
       'localdir'  => BASE_DIR . 'nf_digitalizada/os_digitalizada/',
       'max_files' => 1
   ),
	 
);

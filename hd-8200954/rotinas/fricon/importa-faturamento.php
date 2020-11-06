<?php
/**
 *
 * importa-faturamento.php
 *
 * Geração de pedidos de pecas com base na OS
 *
 * @author  Ronald Santos
 * @version 2012.08.30
 *
*/

error_reporting(E_ALL ^ E_NOTICE);
define('ENV','producao');  // producao Alterar para produção ou algo assim
#define('ENV','teste');  // producao Alterar para produção ou algo assim

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../class/email/mailer/class.phpmailer.php';

    $data['login_fabrica'] 	= 52;
    $data['fabrica'] 	        = 'fricon';
    $data['arquivo_log'] 	= 'importa-faturamento';
    $data['tipo']       	= 'importa-faturamento';
    $data['log'] 		= 2;
    $data['arquivos'] 		= "/tmp";
    $data['data_sistema'] 	= Date('Y-m-d');
    $logs 			= array();
    $logs_erro			= array();
    $logs_cliente		= array();
    $erro 			= false;

    if (ENV == 'producao' ) {
	    $data['dest'] 		= 'paulo@telecontrol.com.br';
	    $data['dest']       = 'helpdesk@telecontrol.com.br';
	    $data['origem']		= "/home/fricon/fricon-telecontrol/";
	    $data['file']		= 'faturamento.txt';
	    $data['file2']		= 'faturamento_item.txt';
    } else {
	    $data['dest'] 		= 'ronald.santos@telecontrol.com.br';
	    $data['dest_cliente'] 	= 'ronald.santos@telecontrol.com.br';
	    $data['origem']		= dirname(__FILE__) . "/entrada/";
	    $data['file']		= 'faturamento.txt';
	    $data['file2']		= 'faturamento_item.txt';
    }

    extract($data);

	define('APP', 'Importa Faturamento - '.$fabrica);

    $arquivo_err = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.err";
    $arquivo_log = "{$arquivos}/{$fabrica}/{$arquivo_log}-{$data_sistema}.log";
    system ("mkdir {$arquivos}/{$fabrica}/ 2> /dev/null ; chmod 777 {$arquivos}/{$fabrica}/" );
	$fp = fopen("/tmp/fricon/faturamento.err","w");
	if(file_exists($origem.$file)){
	
		system("cp $origem$file /tmp/fricon/faturamento".date('Y-m-d-H-i').".txt");
        system("cp $origem$file2 /tmp/fricon/faturamento_item".date('Y-m-d-H-i').".txt");

        $sql = "DROP TABLE IF EXISTS fricon_nf;";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "CREATE TABLE fricon_nf (
                    txt_cnpj           text,
                    txt_nota_fiscal    text,
                    txt_serie          text,
                    txt_emissao        text,
                    txt_cfop           text,
                    txt_total          text,
                    txt_ipi            text,
                    txt_icms           text,
                    txt_transp         text,
                    txt_natureza       text,
                    txt_tipo           text
                )";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $linhas = file_get_contents($origem.$file);
        $linhas = explode("\n",$linhas);

        $erro = $msg_erro;

        foreach($linhas AS $linha){

            $msg_erro = "";

                list($txt_cnpj, $txt_nota_fiscal, $txt_serie, $txt_emissao, $txt_cfop, $txt_total, $txt_ipi, $txt_icms, $txt_transp, $txt_natureza, $txt_tipo) = explode("\t",$linha);
                if(!empty($txt_cnpj)){

                    $txt_cnpj = str_replace('.','',$txt_cnpj);
                    $txt_cnpj = str_replace('/','',$txt_cnpj);
                    $txt_cnpj = str_replace('-','',$txt_cnpj);
                    $txt_frete = str_replace("\r","",$txt_frete);

                    $res = pg_query($con,"BEGIN");
                    $sql = "INSERT INTO fricon_nf ( txt_cnpj    ,
                                        txt_nota_fiscal         ,
                                        txt_serie               ,
                                        txt_emissao             ,
                                        txt_cfop                ,
                                        txt_total               ,
                                        txt_ipi                 ,
                                        txt_icms                ,
                                        txt_transp              ,
                                        txt_natureza            ,
                                        txt_tipo
                                      ) VALUES (
                                        '$txt_cnpj'             ,
                                        '$txt_nota_fiscal'      ,
                                        '$txt_serie'            ,
                                        '$txt_emissao'          ,
                                        '$txt_cfop'             ,
                                        '$txt_total'            ,
                                        '$txt_ipi'              ,
                                        '$txt_icms'             ,
                                        '$txt_transp'           ,
                                        '$txt_natureza'         ,
                                        '$txt_tipo'
                                      );";
                    $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
                    $msg_erro .= pg_errormessage($con);
                    if(!empty($msg_erro)){
                        $res = pg_query($con,"ROLLBACK");
                        $erro .= $msg_erro;
                    } else {
                        $res = pg_query($con,"COMMIT");
                    }
                }
    #echo $msg_erro."\n";
    #exit;

        }
        $msg_erro = $erro;

        $sql = "UPDATE  fricon_nf
                SET     txt_cnpj        = trim (txt_cnpj)                       ,
                        txt_nota_fiscal = lpad (TRIM(txt_nota_fiscal) ,9, '0')  ,
                        txt_serie       = trim (txt_serie)                      ,
                        txt_emissao     = trim (txt_emissao)                    ,
                        txt_transp      = trim (txt_transp)                     ,
                        txt_cfop        = trim (txt_cfop)                       ,
                        txt_total       = trim (txt_total)                      ,
                        txt_natureza    = trim (txt_natureza)                   ,
                        txt_tipo        = trim(txt_tipo);
        ";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");

        $sql = "ALTER TABLE fricon_nf ADD COLUMN total FLOAT";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf ADD COLUMN emissao DATE";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf ADD COLUMN saida DATE";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf ADD COLUMN posto INT4";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf ADD COLUMN pedido INT4";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "UPDATE fricon_nf SET
                    emissao     = TO_DATE(txt_emissao,'YYYY-MM-DD'),
                    saida       = TO_DATE(txt_emissao,'YYYY-MM-DD'),
                    total       = REPLACE(txt_total,',','.')::numeric";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "UPDATE fricon_nf SET posto = (
                    SELECT tbl_posto.posto
                    FROM tbl_posto
                    JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
                                        AND   tbl_posto_fabrica.fabrica = $login_fabrica
                    WHERE fricon_nf.txt_cnpj = tbl_posto.cnpj
                )";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);
        #------------ IDENTIFICAR POSTOS NAO ENCONTRADOS PELO CNPJ --------------#
        $sql = "DROP TABLE IF EXISTS fricon_nf_sem_posto";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "SELECT * INTO fricon_nf_sem_posto FROM fricon_nf WHERE posto IS NULL";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "DELETE FROM fricon_nf
                WHERE posto IS NULL";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "DROP TABLE if exists fricon_nf_item ";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "CREATE TABLE fricon_nf_item (
                    txt_cnpj        text,
                    txt_nota_fiscal text,
                    txt_serie       text,
                    txt_referencia  text,
                    txt_pedido      text,
                    txt_pedido_item text,
                    txt_qtde        text,
                    txt_unitario    text,
                    txt_aliq_ipi    text,
                    txt_aliq_icms   text,
                    txt_valor_ipi   text,
                    txt_valor_icms  text,
                    txt_subst_icms  text,
                    txt_base_ipi    text,
                    txt_base_icms   text,
                    txt_base_subst_icms text,
                    txt_tipo        text
                )";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $linhas_item = file_get_contents($origem.$file2);
        $linhas_item = explode("\n",$linhas_item);

        $erro = $msg_erro;

        foreach($linhas_item AS $linha_item){

              list($txt_cnpj, $txt_nota_fiscal , $txt_serie , $txt_referencia  , $txt_pedido, $txt_pedido_item , $txt_qtde, $txt_unitario, $txt_aliq_ipi, $txt_aliq_icms, $txt_valor_ipi, $txt_valor_icms, $txt_subst_icms, $txt_base_ipi, $txt_base_icms, $txt_base_subst_icms, $txt_tipo) = explode ("\t",$linha_item);
              if(!empty($txt_cnpj)){

                  $txt_cnpj = str_replace('.','',$txt_cnpj);
                  $txt_cnpj = str_replace('/','',$txt_cnpj);
                  $txt_cnpj = str_replace('-','',$txt_cnpj);

                  $txt_base_icms = str_replace("\r","",$txt_base_icms);

                  $res = pg_query($con,"BEGIN");
                  $sql = "INSERT INTO fricon_nf_item ( txt_cnpj         ,
                                  txt_nota_fiscal ,
                                  txt_serie       ,
                                  txt_referencia  ,
                                  txt_pedido      ,
                                  txt_pedido_item ,
                                  txt_qtde        ,
                                  txt_unitario    ,
                                  txt_aliq_ipi    ,
                                  txt_aliq_icms   ,
                                  txt_valor_ipi   ,
                                  txt_valor_icms  ,
                                  txt_subst_icms  ,
                                  txt_base_ipi    ,
                                  txt_base_icms   ,
                                  txt_base_subst_icms ,
                                  txt_tipo
                                ) VALUES (
                                    '$txt_cnpj'        ,
                                    '$txt_nota_fiscal' ,
                                    '$txt_serie'       ,
                                    '$txt_referencia'  ,
                                    '$txt_pedido'      ,
                                    '$txt_pedido_item' ,
                                    '$txt_qtde'        ,
                                    '$txt_unitario'    ,
                                    '$txt_aliq_ipi'    ,
                                    '$txt_aliq_icms'   ,
                                    '$txt_valor_ipi'   ,
                                    '$txt_valor_icms'  ,
                                    '$txt_subst_icms'  ,
                                    '$txt_base_ipi'    ,
                                    '$txt_base_icms'   ,
                                    '$txt_base_subst_icms',
                                    '$txt_tipo'
                                );";
                  $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
                  $msg_erro .= pg_errormessage($con);

                  if(!empty($msg_erro)){
                      $res = pg_query($con,"ROLLBACK");
                      $erro .= $msg_erro;
                  } else {
                      $res = pg_query($con,"COMMIT");
                  }
              }

        }

        $msg_erro = $erro;
        #echo $sql;
        #exit;

        $sql = "UPDATE fricon_nf_item SET
                    txt_cnpj        	= TRIM(txt_cnpj)                     ,
					txt_nota_fiscal = lpad (TRIM(txt_nota_fiscal) ,9, '0')  ,
                    txt_serie       	= TRIM(txt_serie)                    ,
                    txt_referencia  	= TRIM(txt_referencia)               ,
                    txt_pedido      	= TRIM(txt_pedido)                   ,
                    txt_pedido_item    	= TRIM(txt_pedido_item)              ,
                    txt_qtde        	= TRIM(txt_qtde)                     ,
                    txt_unitario    	= TRIM(txt_unitario)                 ,
                    txt_aliq_ipi    	= TRIM(txt_aliq_ipi)				 ,
                    txt_aliq_icms   	= TRIM(txt_aliq_icms)				 ,
                    txt_valor_ipi   	= TRIM(txt_valor_ipi)				 ,
                    txt_valor_icms  	= TRIM(txt_valor_icms)				 ,
                    txt_subst_icms  	= TRIM(txt_subst_icms)				 ,
                    txt_base_ipi    	= TRIM(txt_base_ipi)				 ,
                    txt_base_icms   	= TRIM(txt_base_icms)				 ,
                    txt_base_subst_icms = TRIM(txt_base_subst_icms)          ,
                    txt_tipo            = TRIM(txt_tipo)                     ";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf_item ADD COLUMN posto INT4";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf_item ADD COLUMN peca INT4";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf_item ADD COLUMN qtde FLOAT";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf_item ADD COLUMN pedido INT4";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf_item ADD COLUMN pedido_item INT4";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf_item ADD COLUMN unitario FLOAT";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf_item ADD COLUMN aliq_ipi FLOAT";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf_item ADD COLUMN aliq_icms FLOAT";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf_item ADD COLUMN valor_ipi FLOAT";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf_item ADD COLUMN valor_icms FLOAT";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf_item ADD COLUMN subst_icms FLOAT";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf_item ADD COLUMN base_ipi FLOAT";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf_item ADD COLUMN base_icms FLOAT";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf_item ADD COLUMN base_subst_icms FLOAT";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);


        $sql = "UPDATE fricon_nf_item SET
                    qtde       = txt_qtde::numeric                        ,
                    unitario   = REPLACE(case when length(txt_unitario)    = 0 then '0' else txt_unitario end   ,',','.')::numeric   ,
                    aliq_ipi   = REPLACE(case when length(txt_aliq_ipi )   = 0 then '0' else txt_aliq_ipi end   ,',','.')::numeric   ,
                    aliq_icms  = REPLACE(case when length(txt_aliq_icms )  = 0 then '0' else txt_aliq_icms end   ,',','.')::numeric   ,
                    valor_ipi  = REPLACE(case when length(txt_valor_ipi )  = 0 then '0' else txt_valor_ipi end  ,',','.')::numeric  ,
                    valor_icms = REPLACE(case when length(txt_valor_icms ) = 0 then '0' else txt_valor_icms end ,',','.')::numeric ,
                    subst_icms = REPLACE(case when length(txt_subst_icms ) = 0 then '0' else txt_subst_icms end ,',','.')::numeric ,
                    base_ipi   = REPLACE(case when length(txt_base_ipi )   = 0 then '0' else txt_base_ipi end ,',','.')::numeric ,
                    base_icms  = REPLACE(case when length(txt_base_icms )  = 0 then '0' else txt_base_icms end  ,',','.')::numeric ,
                    base_subst_icms  = REPLACE(case when length(txt_base_subst_icms )  = 0 then '0' else txt_base_subst_icms end  ,',','.')::numeric  ";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "UPDATE fricon_nf_item SET posto = (
                    SELECT tbl_posto.posto
                    FROM tbl_posto
                    JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto
                                        AND   tbl_posto_fabrica.fabrica = $login_fabrica
                    WHERE fricon_nf_item.txt_cnpj = tbl_posto.cnpj
                )";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "UPDATE fricon_nf_item
                SET pedido = tbl_pedido.pedido
                FROM tbl_pedido
                WHERE fricon_nf_item.txt_pedido::numeric = tbl_pedido.pedido
                AND tbl_pedido.fabrica = $login_fabrica
                AND (txt_pedido is not null and length (trim (txt_pedido))> 0);";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "UPDATE fricon_nf_item
                SET pedido_item = tbl_pedido_item.pedido_item
                FROM  tbl_pedido_item, tbl_pedido
                WHERE fricon_nf_item.txt_pedido_item::int = tbl_pedido_item.pedido_item
                AND fricon_nf_item.pedido = tbl_pedido.pedido
                AND (txt_pedido_item is not null and length (trim (txt_pedido_item))> 0)
                AND tbl_pedido.fabrica = $login_fabrica";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "UPDATE fricon_nf_item
                SET peca = tbl_peca.peca
                FROM  tbl_peca
                WHERE fricon_nf_item.txt_referencia = tbl_peca.referencia
                AND tbl_peca.fabrica = $login_fabrica";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "UPDATE fricon_nf
                    SET pedido = fricon_nf_item.pedido
                FROM fricon_nf_item
                WHERE trim(fricon_nf.txt_nota_fiscal) = trim(fricon_nf_item.txt_nota_fiscal)
                AND  fricon_nf.posto = fricon_nf_item.posto";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        #------------ Desconsidera Notas ja Importadas ------------------
        $sql = "DELETE FROM fricon_nf_item
                WHERE pedido is null
                AND txt_tipo <> 'CON';";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);
        $sql = "DELETE FROM fricon_nf
                USING tbl_faturamento
                WHERE txt_nota_fiscal::int4         = tbl_faturamento.nota_fiscal::int4
                AND   txt_serie               = tbl_faturamento.serie
                AND   tbl_faturamento.fabrica = $login_fabrica";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "DELETE FROM fricon_nf_item
                USING tbl_faturamento
                JOIN  tbl_faturamento_item  USING(faturamento)
                WHERE txt_nota_fiscal::int4         = tbl_faturamento.nota_fiscal::int4
                AND   txt_serie               = tbl_faturamento.serie
                AND   tbl_faturamento_item.peca = fricon_nf_item.peca
                AND   tbl_faturamento.fabrica = $login_fabrica";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        #------------ NFs sem Itens --------------#
        $sql = "DROP TABLE IF EXISTS fricon_nf_sem_itens";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "SELECT fricon_nf.*
                INTO fricon_nf_sem_itens
                FROM fricon_nf
                LEFT JOIN fricon_nf_item ON fricon_nf.txt_nota_fiscal = fricon_nf_item.txt_nota_fiscal
                WHERE fricon_nf_item.txt_nota_fiscal IS NULL";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "DELETE FROM fricon_nf
                USING fricon_nf_sem_itens
                WHERE fricon_nf.txt_nota_fiscal = fricon_nf_sem_itens.txt_nota_fiscal
                AND   fricon_nf.txt_serie       = fricon_nf_sem_itens.txt_serie";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $res = pg_query($con,"BEGIN");
        #----------------- Importa REALMENTE ------------
        $sql = "INSERT INTO tbl_faturamento (
                    fabrica     ,
                    emissao     ,
                    saida       ,
                    transp      ,
                    posto       ,
                    total_nota  ,
                    cfop        ,
                    nota_fiscal ,
                    serie       ,
                    tipo_pedido,
                    natureza
                )
                    SELECT  $login_fabrica,
                            fricon_nf.emissao         ,
                            fricon_nf.saida         ,
                            substring(fricon_nf.txt_transp, 1,30),
                            fricon_nf.posto           ,
                            fricon_nf.total           ,
                            fricon_nf.txt_cfop        ,
                            fricon_nf.txt_nota_fiscal ,
                            fricon_nf.txt_serie       ,
                            (select tipo_pedido from tbl_pedido where pedido = fricon_nf.pedido),
                            fricon_nf.txt_natureza
                    FROM fricon_nf
                    LEFT JOIN tbl_faturamento ON  fricon_nf.txt_nota_fiscal::int4   = tbl_faturamento.nota_fiscal::int4
                                             AND  fricon_nf.txt_serie         = tbl_faturamento.serie
                                             AND  tbl_faturamento.fabrica     = $login_fabrica
                                             AND  tbl_faturamento.distribuidor IS NULL
                    WHERE tbl_faturamento.faturamento IS NULL
                ";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "ALTER TABLE fricon_nf_item ADD COLUMN faturamento INT4";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "UPDATE fricon_nf_item
                SET faturamento = tbl_faturamento.faturamento
                FROM tbl_faturamento
                WHERE tbl_faturamento.fabrica     = $login_fabrica
                AND   tbl_faturamento.nota_fiscal::int4 = fricon_nf_item.txt_nota_fiscal::int4
                AND   tbl_faturamento.serie       = fricon_nf_item.txt_serie";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        #------ Tratar itens sem nota ------

        $sql = "DELETE FROM fricon_nf_item
                WHERE faturamento IS NULL";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);


        $sql = "DROP TABLE if exists fricon_nf_item_sem_peca ";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);


        $sql = "SELECT * INTO fricon_nf_item_sem_peca
                FROM fricon_nf_item
                    WHERE peca IS NULL" ;
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);


        $sql = "DELETE FROM fricon_nf_item
                WHERE peca IS NULL" ;
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);


        $sql = "SELECT  DISTINCT faturamento,
                        pedido          ,
                        pedido_item     ,
                        peca            ,
                        qtde as qtde_fat,
                        unitario        ,
                        aliq_ipi        ,
                        aliq_icms       ,
                        valor_ipi       ,
                        valor_icms      ,
                        subst_icms      ,
                        base_ipi        ,
                        base_icms       ,
                        base_subst_icms ,
                        txt_tipo
                FROM fricon_nf_item;";
        $res = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        for($x = 0; $x < pg_numrows($res); $x++){
            $pedido          = pg_result($res,$x,'pedido');
            $pedido_item     = pg_result($res,$x,'pedido_item');
            $faturamento     = pg_result($res,$x,'faturamento');
            $peca            = pg_result($res,$x,'peca');
            $qtde_fat        = pg_result($res,$x,'qtde_fat');
            $unitario        = pg_result($res,$x,'unitario');
            $aliq_ipi        = pg_result($res,$x,'aliq_ipi');
            $aliq_icms       = pg_result($res,$x,'aliq_icms');
            $valor_ipi       = pg_result($res,$x,'valor_ipi');
            $valor_icms      = pg_result($res,$x,'valor_icms');
            $subst_icms      = pg_result($res,$x,'subst_icms');
            $base_ipi        = pg_result($res,$x,'base_ipi');
            $base_icms       = pg_result($res,$x,'base_icms');
            $base_subst_icms = pg_result($res,$x,'base_subst_icms');
            $tipo             = pg_result($res,$x,'txt_tipo');

            if($tipo == 'CON') {
                $pedido = "null";
                $pedido_item = "null";
            }

            $sql = "INSERT INTO tbl_faturamento_item (
                        faturamento     ,
                        pedido          ,
                        pedido_item     ,
                        peca            ,
                        qtde            ,
                        preco           ,
                        aliq_ipi        ,
                        aliq_icms       ,
                        valor_ipi       ,
                        valor_icms      ,
                        valor_subs_trib ,
                        base_ipi        ,
                        base_icms       ,
                        base_subs_trib

                    )
                    VALUES(
                        $faturamento,
                        $pedido     ,
                        $pedido_item ,
                        $peca       ,
                        $qtde_fat   ,
                        $unitario   ,
                        $aliq_ipi   ,
                        $aliq_icms  ,
                        $valor_ipi  ,
                        $valor_icms ,
                        $subst_icms ,
                        $base_ipi   ,
                        $base_icms  ,
                        $base_subst_icms
                    )";
            $res2 = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
            $msg_erro .= pg_errormessage($con);

            $sql = "SELECT  qtde as qtde_pedido,
                            pedido_item,
                            tipo_pedido,
                            posto
                    FROM    tbl_pedido
                    JOIN    tbl_pedido_item ON tbl_pedido.pedido = tbl_pedido_item.pedido
                    WHERE   tbl_pedido.pedido   = $pedido
                    AND     tbl_pedido.fabrica  = $login_fabrica
                    AND     peca                = $peca
                    AND     pedido_item         = $pedido_item
                    AND     qtde >= (qtde_faturada + qtde_cancelada)
                    LIMIT   1;";
            $res2 = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
            $msg_erro .= pg_errormessage($con);

            if(pg_numrows($res2) > 0){
                $pedido_item = pg_result($res2,0,'pedido_item');
                $qtde_pedido = pg_result($res2,0,'qtde_pedido');
                $tipo_pedido = pg_result($res2,0,'tipo_pedido');
                $posto_pedido = pg_result($res2,0,'posto');

                $sql = "UPDATE  tbl_pedido_item
                        SET     qtde_faturada =  (qtde_faturada + $qtde_fat)
                        WHERE   pedido_item = $pedido_item
                        AND     pedido = $pedido;";
                $res3 = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
                $msg_erro .= pg_errormessage($con);

				$sql = "UPDATE  tbl_pedido_item
                        SET		obs = 'Peça faturada após cancelamento'
						WHERE   pedido_item = $pedido_item
						AND		qtde_faturada > 0
						AND		qtde_faturada = qtde_cancelada
                        AND     pedido = $pedido;";
                $res3 = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
                $msg_erro .= pg_errormessage($con);

                $sql     = "select tipo_pedido from tbl_tipo_pedido where fabrica = ".$login_fabrica." and lower(descricao) = 'consignado';";
                $res_pedido_consignado = pg_query($con,$sql);fwrite($fp,$sql. ";\n");

                $tipo_pedido_consignado = pg_fetch_result($res_pedido_consignado,0,'tipo_pedido');

                if($tipo_pedido == $tipo_pedido_consignado){

                    $sql = "SELECT posto,peca FROM tbl_estoque_posto WHERE fabrica = $login_fabrica AND posto = $posto_pedido AND peca = $peca";
                    $res3 = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
                    $msg_erro .= pg_errormessage($con);
                    if(pg_numrows($res3) > 0){

                        $sql = "UPDATE tbl_estoque_posto SET
                                qtde = qtde + $qtde_fat
                                WHERE fabrica = $login_fabrica
                                AND posto = $posto_pedido
                                AND peca = $peca";
                        $res4 = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
                        $msg_erro .= pg_errormessage($con);
                    }else{
                        $sql = "INSERT INTO tbl_estoque_posto(
                                                                fabrica,
                                                                posto,
                                                                peca,
                                                                qtde
                                                            ) VALUES(
                                                                $login_fabrica,
                                                                $posto_pedido,
                                                                $peca,
                                                                $qtde_fat
                                                            )";
                        $res4 = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
                        $msg_erro .= pg_errormessage($con);
                    }
                        $sql = "SELECT os,os_item FROM tbl_os_produto JOIN tbl_os_item ON tbl_os_produto.os_produto = tbl_os_item.os_produto WHERE tbl_os_item.pedido = $pedido AND tbl_os_item.pedido_item = $pedido_item";
                        $resOS = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
                        $msg_erro .= pg_errormessage($con);
                        $os = (pg_numrows($resOS) > 0) ? pg_result($resOS,0,'os') : "null";
                        $os_item = (pg_numrows($resOS) > 0) ? pg_result($resOS,0,'os_item') : "null";
                        $tipo = "Consignado";

                        $sql = "INSERT INTO tbl_estoque_posto_movimento(
                                    fabrica,
                                    posto,
                                    os,
                                    os_item,
                                    peca,
                                    data,
                                    qtde_entrada,
                                    faturamento,
                                    pedido,
                                    obs,
                                    nf,
                                    tipo) VALUES(
                                    $login_fabrica,
                                    $posto_pedido,
                                    $os,
                                    $os_item,
                                    $peca,
                                    CURRENT_DATE,
                                    $qtde_fat,
                                    $faturamento,
                                    $pedido,
                                    'Reposição de estoque',
                                    '$txt_nota_fiscal',
                                    '$tipo'
                                    )
                                        ";
                        $res4 = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
                        $msg_erro .= pg_errormessage($con);

                }
            }
            $sql = "UPDATE tbl_faturamento_item
                    SET aliq_icms     = round((valor_icms / (preco*qtde))*100)
                    WHERE faturamento = $faturamento
			AND valor_icms > 0 and preco > 0 and qtde > 0  ";
            $res3 = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
            $msg_erro .= pg_errormessage($con);
        }

        $sql = "UPDATE tbl_pedido
                SET recebido_fabrica    = current_date
                FROM  fricon_nf_item
                WHERE tbl_pedido.pedido = fricon_nf_item.pedido";
        $res3 = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        $sql = "SELECT  DISTINCT fn_atualiza_status_pedido($login_fabrica,  pedido)
                FROM    fricon_nf_item
                WHERE   pedido IS NOT NULL
        ";
        $res3 = pg_query($con,$sql);fwrite($fp,$sql. ";\n");
        $msg_erro .= pg_errormessage($con);

        if(!empty($msg_erro)){
            $res = pg_query($con,"ROLLBACK");
            $erro .= $msg_erro;
        } else {
            $res = pg_query($con,"COMMIT");
		}

		system("mv $origem$file /tmp/fricon/faturamento".date('Y-m-d-H-i').".txt");
        system("mv $origem$file2 /tmp/fricon/faturamento_item".date('Y-m-d-H-i').".txt");

        if (!empty($msg_erro)) {
            $msg_erro .= "\n\n".$log_erro;
            $fp = fopen("/tmp/fricon/faturamento.err","w");
            fwrite($fp,$msg_erro);
            fclose($fp);
            $msg = 'Script: '.__FILE__.'<br />' . $msg_erro;
            Log::envia_email($data, APP, $msg);
        } else {
            $fp = fopen("/tmp/fricon/faturamento.err","w");
            fwrite($fp,$log_erro);
            fclose($fp);

            Log::log2($data, APP . ' - Executado com Sucesso - ' . date('Y-m-d-H-i'));

		}


    }

} catch (Exception $e) {
	$e->getMessage();
    $msg = "Arquivo: ".__FILE__."\r\n<br />Linha: " . $e->getLine() . "\r\n<br />Descrição do erro: " . $e->getMessage() ."<hr /><br /><br />". implode("<br /><br />", $logs);

    Log::envia_email($data,Date('d/m/Y H:i:s')." - fricon - Importa faturamento (importa-faturamento.php)", $msg);
}?>

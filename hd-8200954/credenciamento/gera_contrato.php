
<?php
include "../dbconfig.php";
include "../includes/dbconnect-inc.php";

if(in_array($_GET["fabrica"], array(35, 146,147,160,198))){
$cnpj = $_GET['cnpj'];
    $cnpj = str_replace(array("-", ".", " ", "/"), "", $cnpj);
    $cnpj = substr($cnpj,0,14);

    $sql = "SELECT DISTINCT tbl_posto.posto,
                upper(tbl_posto.nome) As nome,
                upper(tbl_posto.nome_fantasia) As nome_fantasia,
                tbl_posto_fabrica.contato_fone_comercial AS fone,
                tbl_posto_fabrica.contato_fax AS fax,
                tbl_posto_fabrica.contato_bairro AS bairro,
                tbl_posto_fabrica.nomebanco AS nomebanco,
                tbl_posto_fabrica.agencia AS agencia,
                tbl_posto_fabrica.conta AS conta,
                tbl_posto_fabrica.favorecido_conta AS responsavel,
                tbl_posto_fabrica.contato_atendentes AS responsavel_social,
                tbl_posto_fabrica.contato_endereco AS endereco,
                tbl_posto_fabrica.contato_numero AS numero,
                tbl_posto_fabrica.contato_complemento AS complemento,
                tbl_posto_fabrica.contato_cidade AS cidade,
                tbl_posto_fabrica.contato_estado AS estado,
                SUBSTR (tbl_posto_fabrica.contato_cep,1,2) || '.' || SUBSTR (tbl_posto_fabrica.contato_cep,3,3) || '-' || SUBSTR (tbl_posto_fabrica.contato_cep,6,3) AS cep,
                SUBSTR (tbl_posto.cnpj,1,2) || '.' || SUBSTR (tbl_posto.cnpj,3,3) || '.' || SUBSTR (tbl_posto.cnpj,6,3) || '/' || SUBSTR (tbl_posto.cnpj,9,4) || '-' || SUBSTR (tbl_posto.cnpj,13,2) AS cnpj,
                tbl_posto.posto,
                tbl_posto.ie,
                to_char(current_date,'DD') || ' de ' || to_char(current_date,'Month') || ' de ' || to_char(current_date,'YYYY') as data_contrato,
                tbl_posto_fabrica.contato_email AS email
            FROM tbl_posto 
            INNER JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica
            WHERE 
                tbl_posto.cnpj = '{$cnpj}' 
            LIMIT 1";

    $res = pg_exec($con,$sql);

    if(pg_numrows($res) > 0){

        $array_meses = array(
                        "",
                        "Janeiro",
                        "Fevereiro",
                        "Mar�o",
                        "Abril",
                        "Maio",
                        "Junho",
                        "Julho",
                        "Agosto",
                        "Setembro",
                        "Outubro",
                        "Novembro",
                        "Dezembro"
                    );

        $posto         = pg_result($res, 0, "posto");                          
        $posto_nome    = pg_result($res, 0, "nome");        
        $endereco      = pg_result($res, 0, "endereco");
        $numero        = pg_result($res, 0, "numero");
        $complemento   = pg_result($res, 0, "complemento");
        $cidade        = pg_result($res, 0, "cidade");
        $estado        = pg_result($res, 0, "estado");
        $cep           = pg_result($res, 0, "cep");
        $cnpj          = pg_result($res, 0, "cnpj");
        $ie            = pg_result($res, 0, "ie");
        $data_contrato = pg_result($res, 0, "data_contrato");
        $data_contrato = str_replace("January", "Janeiro", $data_contrato);
        $email         = pg_result($res, 0, "email");
        $posto_nome_fantasia    = pg_result($res, 0, "nome_fantasia");
        $fone                   = pg_result($res, 0, "fone");
        $fax                    = pg_result($res, 0, "fax");
        $bairro                 = pg_result($res, 0, "bairro");
        $nomebanco              = pg_result($res, 0, "nomebanco");
        $agencia                = pg_result($res, 0, "agencia");
        $conta                  = pg_result($res, 0, "conta");
        $responsavel            = pg_result($res, 0, "responsavel");
        $responsavel_social     = pg_result($res, 0, "responsavel_social");

        if ($fabrica == 198) {

            $sqlLinhasPosto = "
                SELECT tbl_linha.nome
                FROM tbl_posto_linha
                JOIN tbl_linha ON tbl_posto_linha.linha = tbl_linha.linha
                AND tbl_linha.fabrica = {$fabrica}
                WHERE tbl_posto_linha.posto = {$posto}
            ";
            $resLinhasPosto = pg_query($con, $sqlLinhasPosto);

            $linhasAtende = [];
            while ($dadosLinha = pg_fetch_object($resLinhasPosto)) {

                $linhasAtende[] = $dadosLinha->nome;

            }

            $texto = "
                <html>

                    <head>

                        <style>
                            body{
                                font-family: arial;
                                font-size: 14px;
                            }
                            h3{
                                text-align: center;
                            }
                            .tabela-meses{
                                width: 100%;
                            }
                            td, th{
                                padding: 2px 4px;
                            }
                        </style>

                    </head>

                    <body>  

                        <h3>CONTRATO DE CREDENCIAMENTO DE ASSIST�NCIA T�CNICA</h3>

                        <p>Pelo presente instrumento particular,</p>

                        <p>
                            </strong>FABRICANTE DISTRIBUIDOR,</strong> <strong>FRIGELAR COMERCIO E INDUSTRIA LTDA .</strong>,�pessoa jur�dica de direito privado, inscrita no CNPJ sob o n� 92.660.406/0001-19, com sede em  Porto Alegre/RS na Av. Pernambuco, 2285, Navegantes, doravante denominada simplesmente <strong>CONTRATANTE e;</strong>
                        </p>

                        <p>
                            <strong>POSTO</strong> {$posto_nome}, com sede em {$cidade} / {$estado}, {$endereco}, {$numero}, {$cep}, inscrita no CNPJ: {$cnpj}, Inscri��o Estadual {$ie},, doravante denominado simplesmente de <strong>CONTRATADA,</strong> 
                        </p>

                        <strong><h4>1 � OBJETIVO</h4></strong>
                        <p>
                            O presente <strong>CONTRATO</strong> tem como objetivo a presta��o de servi�o pela <strong>CONTRATADA</strong> em sua sede social como Assist�ncia T�cnica Autorizada das marcas representadas pela <strong>CONTRATANTE</strong>.
                            A <strong>CONTRATADA</strong> declara que possui estrutura f�sica para atendimento ao consumidor e aptid�es t�cnicas para desmonte, testes, avalia��es, diagn�sticos e conserto da (s) linha (s) de produtos <strong>".implode(",", $linhasAtende)." </strong>da <strong>CONTRATATANTE</strong>.
                        </p>

                        <strong><h4>2 � RESPONSABILIDADES</h4></strong>
                        <p>
                            2.1 A <strong>CONTRATADA</strong> se compromete a seguir  fielmente as normas e procedimento expressamente vinculadas pela Telecontrol e pela <strong>CONTRATANTE</strong>;
                        </p>
                        <p>
                            2.2 Em caso de a��es propostas por consumidores, que reste provada a culpa ou dolo da <strong>CONTRATADA</strong>, seus s�cios, diretores, gestores, prepostos, colaboradores ou funcion�rios, esta concorda desde j� que dever� assumir integralmente o polo passivo das a��es judiciais e extra judiciais, reclama��es em Procon, dentre outros �rg�os que venham a ser demandadas contra a Telecontrol ou a <strong>CONTRATANTE</strong>;
                        </p>
                        <p>
                            2.3 A <strong>CONTRATADA</strong> dever� manter em sigilo todas informa��es coletadas de consumidores e se compromete em hip�tese alguma a n�o divulgar tais dados conforme elucida a Lei n�.13709/2018 Lei Geral de Prote��o de Dados (LGPD). Ocorrendo vazamento destes dados e comprovado a culpa da <strong>CONTRATADA</strong>, esta assumir� toda e qualquer demanda judicial ou extrajudicial, bem como arcando com eventuais condena��es, honor�rios advocat�cios e demais despesas decorrentes, isentando desde j� a <strong>CONTRATANTE</strong> e Telecontrol e, de plano isentando-as de qualquer responsabilidade, independentemente do desfecho da demanda;
                        </p>
                        <p>
                            2.4 A <strong>CONTRATADA</strong> dever� seguir a recomenda��o da <strong>CONTRATANTE</strong> para realizar consertos, teste, diagn�sticos e desmonte dos produtos contemplados por este CONTRATO;
                        </p>
                        <p>
                            2.5 A <strong>CONTRATADA</strong> se compromete a preencher corretamente todos os dados e informa��es do consumidor na ORDEM DE SERVI�O TELECONTROL, seguindo as orienta��es e regras da <strong>CONTRATANTE</strong>;
                        </p>
                        <p>
                            2.6 A <strong>CONTRATADA</strong> se compromete a cumprir o que determina o C�digo de Defesa do Consumidor, contribuindo com a agilidade do processo de atendimento em GARANTIA.
                        </p>
                        <p>
                            2.7 �nus decorrentes de legisla��o especial, trabalhista e previdenci�ria, relativos aos funcion�rios da <strong>CONTRATADA</strong> s�o de sua inteira responsabilidade, ficando desde j� estabelecido que, ocorrendo reclama��o trabalhista ou qualquer contenda c�vel por parte de qualquer funcion�rio, ex-funcion�rios ou contratado da <strong>CONTRATADA</strong> que recaia sobre a <strong>CONTRATANTE</strong> ou Telecontrol, dever� a CONTRATADA  assumir e arcar com eventuais condena��es, honor�rios advocat�cios e demais despesas decorrentes, isentando, desde j�, a <strong>CONTRATANTE</strong> e  Telecontrol e, de plano concordar com sua exclus�o de qualquer responsabilidade, independentemente do desfecho da demanda, sendo todos eles de inteira responsabilidade da <strong>CONTRATADA</strong>
                        </p>

                        <strong><h4>3- PAGAMENTO DA TAXA DE M�O-DE-OBRA</h4></strong>
                        <p>
                            Os servi�os executados em ORDENS DE SERVI�O (garantia) dever�o ser obrigatoriamente registrados no Sistema Telecontrol. Todo dia 20 (vinte) de cada m�s a <strong>CONTRATANTE</strong> gerar� um extrato com todas as Ordens de Servi�os (OS) finalizadas naquele per�odo. A CONTRATADA dever� conferir o extrato e estando de acordo enviar Nota Fiscal de Presta��o de Servi�os para a <strong>CONTRATANTE</strong> at� o dia 05 (cinco) do m�s seguinte anexando-a no Sistema Telecontrol, conforme orienta��es contidas no site da Telecontrol. Dentro de 10 (dez) dias ap�s o recebimento da Nota Fiscal de Presta��o de Servi�o, o dep�sito ser� realizado na conta banc�ria da <strong>CONTRATADA</strong>.
                        </p>

                        <strong><h4>4- DURA��O DO CONTRATO</h4></strong>
                        <p>
                            O presente CONTRATO � v�lido por tempo indeterminado e poder� ser rescindido por qualquer das partes, mediante aviso pr�vio de 30 (trinta) dias, por escrito. A <strong>CONTRATADA</strong> obriga-se, a dar continuidade aos atendimentos dos produtos em seu poder durante o per�odo de aviso de rescis�o contratual.
                        </p>

                        <strong><h4>5 � DISPOSI��ES GERAIS</h4></strong>
                        <p>
                            A <strong>CONTRATADA</strong> declara conhecer e se compromete a cumprir o disposto no C�digo de Defesa do Consumidor e assume a responsabilidade de �in vigilando� por seus funcion�rios para esta finalidade.
                        </p>

                        <strong><h4>6 � FORO</h4></strong>
                        <p>
                            Estando de pleno acordo com todas as cl�usulas e condi��es aqui expostas, elegem as partes contratantes o Foro da Comarca de ( Porto Alegre � RS), para dirimir e resolver toda e qualquer quest�o proveniente do presente contrato, com expressa ren�ncia de qualquer outro, por mais privilegiado que seja.
                        </p>
                        <p>
                            E por estarem assim justas e acordadas, firmam o presente instrumento, em duas vias de igual teor e forma, juntamente com as testemunhas abaixo.
                        </p>
                        <p>
                            <center>(Porto Alegre  � RS), ".date("d")." de ".$array_meses[intval(date("m"))]." de ".date("Y")."</center>
                        </p>
                        <img src='https://posvenda.telecontrol.com.br/assist/admin/imagens/assinatura_contrato_frigelar.png' height='400' />
                    </body>
                </html>
            ";

        }

        if($fabrica == 146){

            $texto = "

                <html>

                    <head>

                        <style>
                            body{
                                font-family: arial;
                                font-size: 14px;
                            }
                            h3{
                                text-align: center;
                            }
                            .tabela-meses{
                                width: 100%;
                            }
                            td, th{
                                padding: 2px 4px;
                            }
                        </style>

                    </head>

                    <body>  

                        <h3>CONTRATO DE ASSIST�NCIA T�CNICA</h3>

                        <p>
                            Pelo presente instrumento particular, de um lado <strong>FERRAGENS NEGR�O COMERCIAL LTDA.</strong>,�pessoa jur�dica de direito privado, inscrita no CNPJ sob o n� 76.639.285/0001-77, Inscri�ao estadual 90529723-69, com sede em Curitiba - PR na Rua Algacyr Munhoz Mader, 2800, denominada simplesmente <strong>CONTRATANTE</strong> e, de outro lado a 
                            empresa {$posto_nome}, com sede em {$cidade} / {$estado}, {$endereco}, {$numero}, {$cep}, inscrita no CNPJ: {$cnpj}, Inscri��o Estadual {$ie}, denominada simplesmente <strong>AUTORIZADA</strong>, tem justo e contratado a presta��o de servi�os e assist�ncia t�cnica, que se reger� de conformidade com o disposto nas seguintes cl�usulas:
                        </p>

                        <p>
                            <strong>CL�USULA 1�:</strong> Devidamente credenciada, a AUTORIZADA passar� a executar servi�os de assist�ncia t�cnica que estiverem em garantia, conforme o ANEXO 1 , a todo e qualquer cliente, consumidor casual ou industrial, independente do munic�pio.
                        </p>

                        <p>
                            <strong>CL�USULA 2�:</strong> A <strong>AUTORIZADA</strong>, devidamente credenciada pela <strong>CONTRATANTE</strong> com a assinatura deste contrato, exercer� suas atividades na cidade de {$cidade} / {$estado}, vedada � abertura de filiais com a finalidade objeto deste instrumento em outros munic�pios, sem expressa concord�ncia da <strong>CONTRATANTE</strong>.
                        </p>

                        <p>
                            <strong>CL�USULA 3�:</strong> A <strong>CONTRATANTE</strong> reserva-se o direito de prestar diretamente assist�ncia t�cnica dos Produtos Worker, bem como contratar uma ou mais assist�ncias t�cnicas no mesmo ou em diversos munic�pios, n�o sendo, em raz�o deste fato, devida qualquer indeniza��o � <strong>AUTORIZADA</strong>.
                        </p>

                        <p>
                            <strong>CL�USULA 4�:</strong> Compromete-se a <strong>AUTORIZADA</strong> a manter pessoal, equipamento e instala��es condizentes com a atividade ora ajustada, garantindo a efici�ncia nos servi�os.
                        </p>

                        <p>
                            <strong>CL�USULA 5�:</strong> Compromete-se a <strong>AUTORIZADA</strong> a manter pasta de arquivo t�cnico sempre atualizado com as �ltimas informa��es fornecidas pela <strong>CONTRATANTE</strong>.
                        </p>

                        <p>
                            <strong>CL�USULA 6�:</strong> Das fun��es b�sicas da <strong>AUTORIZADA</strong>: <br />
                            - Atender as normas estabelecidas pelo C�digo de Defesa do Consumidor. <br />
                            - Relatar e informar a CONTRATANTE de todas as defici�ncias encontradas nos produtos por ela distribu�dos. <br />
                            - Quando da presta��o dos servi�os observar os procedimentos e rotinas t�cnicas compat�veis e/ou adequadas aos respectivos produtos.
                        </p>

                        <p>
                            <strong>CL�USULA 7�:</strong> A <strong>AUTORIZADA</strong> dever� relacionar as pe�as defeituosas trocadas em garantia, no formul�rio \"SOLICITA��O DE GARANTIA\", obrigando-se a deix�-las a disposi��o da <strong>CONTRATANTE</strong> por um per�odo de 90 dias, sob pena de serem consideradas fora de garantia pelo inspetor da <strong>CONTRATANTE</strong> e conseq�entemente n�o lhe caber qualquer esp�cie de ressarcimento.
                        </p>

                        <p>
                            <strong>CL�USULA 8�:</strong> Dentro do prazo de garantia as despesas com o transporte da <strong>CONTRATANTE</strong> at� a assist�ncia t�cnica das pe�as a serem substitu�das em garantia correr� por conta da <strong>CONTRATANTE</strong> e fora deste prazo a <strong>AUTORIZADA</strong> arcar� com o custo do fretamento.
                        <p>

                        <p>
                            <strong>CL�USULA 9�:</strong> Se o exame t�cnico procedido nas pe�as defeituosas trocadas em garantia n�o constatar as irregularidades apontadas nos relat�rios de assist�ncia t�cnica fica facultado � <strong>CONTRATANTE</strong> debitar da autorizada o respectivo valor bem como a taxa de servi�os incidentes.
                        </p>

                        <p>
                            <strong>CL�USULA 10�:</strong> Compromete-se a <strong>CONTRATANTE</strong> fornecer � AUTORIZADA, tabelas de pre�os, informa��es de servi�os e instru��es em geral, modelo de \"SOLICITA��O DE GARANTIA\" e outros que se fizerem necess�rios, tudo para uma perfeita e constante atualiza��o n�o s� comercial, mas tamb�m t�cnica.�
                        </p>

                        <p>
                            <strong>CL�USULA 11�:</strong> Quando a <strong>CONTRATANTE</strong> promover encontros de aperfei�oamento t�cnico, seja em forma de cursos est�gios, treinamentos ou outros, ser� importante a participa��o de um ou mais t�cnicos da <strong>AUTORIZADA</strong>.
                        </p>

                        <p>
                            <strong>CL�USULA 12�:</strong> Se a <strong>AUTORIZADA</strong> pretender efetuar publicidade em que envolva as marcas comercializadas pela <strong>CONTRATANTE</strong>, dever� antes, ser comunicada, para que manifeste de forma escrita sua concord�ncia ou n�o.
                        </p>

                        <p>
                            <strong>CL�USULA 13�:</strong> Se houver a rescis�o deste contrato, a <strong>AUTORIZADA</strong> dever�: <br />
                            - Devolver todo material ou equipamento da <strong>CONTRATANTE</strong>, que se encontrem sob sua responsabilidade, instalado ou em uso. <br />
                            - Suspender imediatamente toda e qualquer propaganda, falada, escrita, televisada que envolva ou mencione as marcas comercializadas pela <strong>CONTRATANTE</strong>. <br />
                            - Tirar de circula��o todos os impressos que a identifiquem como <strong>AUTORIZADA</strong> das marcas comercializadas pela <strong>CONTRATANTE</strong>.
                        </p>

                        <p>
                            <strong>CL�USULA 14�:</strong> O presente contrato � celebrado pelo prazo de 12 MESES, iniciando-se na data de sua assinatura, podendo ser rescindido nos seguintes casos: <br />
                            - De comum acordo, expressando-se a vontade por escrito. <br />
                            - Por qualquer parte mediante notifica��o por escrito, com anteced�ncia m�nima de 30 dias, sem nenhum �nus ou penalidade. <br />
                            - Se ocorrer a venda ou transfer�ncia a terceiros, recupera��o judicial ou fal�ncia da <strong>CONTRATANTE</strong> ou da <strong>AUTORIZADA</strong>.
                        </p>

                        <p>
                            <strong>CL�USULA 15�:</strong> A <strong>AUTORIZADA</strong> n�o ter� direito a qualquer indeniza��o ou multa, nem ser� ressarcida pelo eventual estoque de pe�as das marcas comercializadas pela <strong>CONTRATANTE</strong>, se houver a rescis�o deste contrato.
                        </p>

                        <p>
                            <strong>CL�USULA 16�:</strong> O presente contrato n�o gerar� qualquer rela��o de emprego entre as partes cabendo � <strong>AUTORIZADA</strong>o recolhimento de todos os impostos, taxas, emolumentos, contribui��es fiscais, trabalhistas e outras que vierem a incidir direta ou indiretamente sobre os servi�os prestados.
                        </p>

                        <p>
                            <strong>CL�USULA 17�:</strong> A <strong>AUTORIZADA</strong> responder� civil e criminalmente por todo e qualquer dano que vier a causar com os servi�os prestados, n�o cabendo � <strong>CONTRATANTE</strong> culpa alguma por eventuais ocorr�ncias.
                        </p>

                        <p>
                            <strong>CL�USULA 18�:</strong> Fica facultado � <strong>CONTRATANTE</strong> designar pessoal especializado para a qualquer momento proceder � vistoria nas instala��es da <strong>AUTORIZADA</strong>.
                        </p>

                        <p>
                            <strong>CL�USULA 19�:</strong> Todos os produtos das marcas comercializadas pela <strong>CONTRATANTE</strong> possuem garantia de acordo com o especificado em seu manual e certificado de garantia do produto, salvaguardadas as situa��es que caracterizem a perda de garantia. <br />
                            - O prazo de garantia � contado a partir da data de emiss�o da nota fiscal de venda ao consumidor. <br />
                            - Para ter direito a garantia � imprescind�vel a apresenta��o da nota fiscal de venda e o correto preenchimento do certificado de garantia.
                        </p>

                        <p>
                            <strong>CL�USULA 20�:</strong> A <strong>CONTRATANTE</strong> pagar� � <strong>AUTORIZADA</strong> pelos servi�os prestados as import�ncias descritas no ANEXO I. <br />
                            - Os valores previstos no ANEXO I, estar�o sujeitos, anualmente, � corre��o monet�ria calculado pela varia��o do IGPM ou outro �ndice que vier a substitu�-lo. <br />
                            - Os valores definidos no ANEXO I, ser�o conhecidos atrav�s de relat�rio de volume de produtos efetivamente consertados, apresentado pela <strong>AUTORIZADA</strong> at� o �ltimo dia �til de cada m�s, para confer�ncia e aprova��o pela <strong>CONTRATANTE</strong>, que tem o prazo de dois dias �teis para a libera��o deste relat�rio, para que, a <strong>AUTORIZADA</strong> emita a Nota Fiscal de Presta��o de Servi�os (PESSOA JUR�DICA) realizados no per�odo constante do relat�rio. <br />
                            - Os pagamentos definidos no ANEXO I, ser�o feitos a <strong>CONTRATADA</strong> no 5� (quinto) dia �til de cada m�s.
                        </p>

                        <p>
                            <strong>CL�USULA 21�:</strong> N�o ter�o direito � garantia:�<br />
                            - Produtos que estiverem fora do prazo de garantia especificado em seu manual. <br />
                            - Produtos que apresentarem sinais de terem sido usados inadequadamente por motivos diversos (imper�cia, imprud�ncia ou neglig�ncia). <br />
                            - Produtos que apresentarem sinais de m� conserva��o, desgaste natural, de terem sido violados alterados em suas caracter�sticas originais. <br />
                            - A <strong>CONTRATANTE</strong> n�o pagar� por servi�os e nem por pe�as aplicadas em produtos que se encontrarem fora do per�odo de garantia. 
                        </p>

                        <p>
                            <strong>CL�USULA 22�:</strong> Todas as pe�as substitu�das nos produtos que estivem dentro do per�odo de garantia ou n�o, dever�o obrigatoriamente ser originais de f�brica, sob pena de rescis�o contratual.
                        </p>

                        <p>
                            <table class='tabela-meses' border='1' cellspacing='0' cellspacing='0'>
                                <tr>
                                    <th colspan='2' align='left'>GARANTIAS:</th>
                                </tr>
                                <tr>
                                    <td>LAVADORA DE ALTA PRESS�O:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                                <tr>
                                    <td>UMIDIFICADOR:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                                <tr>
                                    <td>BOMBAS CENTR�FUGAS, PERIF�RICAS E AUTO ASPIRANTE:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                                <tr>
                                    <td>ESMERIL:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                                <tr>
                                    <td>FURADEIRA DE BANCADA:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                                <tr>
                                    <td>COMPRESSOR DE AR:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                                <tr>
                                    <td>MACACOS:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                                <tr>
                                    <td>OUTROS COMO, PULVERIZADOR, PISTOLA:</td>
                                    <td align='right'>3 MESES</td>
                                </tr>
                                <tr>
                                    <td>COFRE:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                                <tr>
                                    <td>LANTERNAS:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                                <tr>
                                    <td>BOMBA MULTIESTAGIO SUBMERSA 4.:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                                <tr>
                                    <td>BOMBA SUBMERSA:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                                <tr>
                                    <td>TOP MOP:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                                <tr>
                                    <td>TRANSFORMADOR DE SOLDA / INVERSORAS:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                                <tr>
                                    <td>M�QUINAS DE SOLDA MIG MAG:</td>
                                    <td align='right'>12 MESES</td>
                                </tr>
                                <tr>
                                    <td>PNEUM�TICOS:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                                <tr>
                                    <td>CARREGADORES DE BATERIA:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                            </table>
                        </p>

                        <p>
                            <strong>CL�USULA 23�:</strong> Este instrumento torna sem efeito, acordos firmados anteriormente � assinatura deste contrato.
                        </p>

                        <p>
                            <strong>CL�USULA 24�:</strong> A <strong>AUTORIZADA</strong> concorda com a divulga��o do seu endere�o na rela��o das assist�ncias t�cnicas autorizadas dos produtos comercializados pela CONTRATANTE, a qual tem afinidade de informar aos consumidores os locais onde � prestada a assist�ncia t�cnica objeto deste contrato.
                        </p>

                        <p>
                            <strong>CL�USULA 25�:</strong> A <strong>CONTRATANTE</strong> dever� reembolsar a <strong>AUTORIZADA</strong>, pagamentos dos impostos referentes � Notas Fiscais de pe�as e produtos enviados, estando dentro da garantia ou n�o, mediante o <strong>comprovante do imposto em quest�o. (Para os Estados onde h� este regime de imposto diferenciado).</strong>
                        </p>

                        <p>
                            E por estarem certos e ajustados entre si firmam o presente contrato em duas vias de igual teor, elegendo o F�rum de Curitiba, com ren�ncia a qualquer outro, por mais privilegiado que seja, para dirimir eventuais d�vidas ou quest�es relativas a este contrato.
                        </p>

                        <br /> <br /> <br />

                        <strong>__________________________________________________ , ".date("d")." de ".$array_meses[intval(date("m"))]." de ".date("Y")."</strong>

                        <br /> <br /> <br /> <br /> <br />

                        <p style='text-align: center;'>
                            <strong>
                                __________________________________________________ <br />
                                FERRAGENS NEGR�O COMERCIAL LTDA.
                            </strong>
                        </p>

                        <br /> <br /> <br />

                        <p style='text-align: center;'>
                            <strong>
                                __________________________________________________ <br />
                                AUTORIZADA <br />
                                ({$posto_nome})
                            </strong>
                        </p>

                        <br /> <br /> <br />

                    </body>

                </html>

            ";

        }

        if($fabrica == 147){

            $texto = "
                <html>

                    <head>

                        <style>
                            body{
                                font-size: 14px;
                                font-family: arial;
                            }
                            h3{
                                text-align: center;
                            }
                        </style>

                    </head>

                    <body>

                        <h3>CONTRATO DE CREDENCIAMENTO DE ASSIST�NCIA T�CNICA</h3>

                        <p>
                            Pelo presente instrumento particular,
                        </p>

                        <p>
                            <strong>TELECONTROL SISTEMA LTDA</strong>, sociedade empresarial com escrit�rio administrativo na Av. Carlos Art�ncio, 420 A, Bairro Fragata C, CEP 17.519-255, na cidade de Mar�lia, estado de S�o Paulo, inscrita no CNPJ sob n� 04.716.427/0001-41, neste ato representada por seu diretor ao final assinado, doravante denominada simplesmente \"TELECONTROL NETWORKING\", e
                        </p>

                        <p>
                            <strong> {$posto_nome} </strong>, sociedade empresarial com sede na {$endereco}, {$numero}, na cidade de {$cidade}, {$estado}, CEP {$cep}, inscrita no CNPJ sob n� {$cnpj}, neste ato representada por seu administrador, ao final assinado, doravante denominada \"AUTORIZADA\",
                        </p>

                        <p>
                            <strong>1 - OBJETIVO</strong>
                        </p>

                        <p> 
                            1.1. O objetivo do presente contrato � a presta��o, pela AUTORIZADA, em sua sede social, do servi�o de assist�ncia t�cnica das empresas que contratarem a TELECONTROL para a dministrar o p�s-venda.
                        </p>

                        <p>
                            <strong>2 - PAGAMENTO DA TAXA DE M�O-DE-OBRA</strong>
                        </p>

                        <p>
                            Os servi�os executados dever�o ser registrados no site da TELECONTROL. No dia 1 de cada m�s ser� gerado um extrato com todas as ordens de servi�os finalizadas. A AUTORIZADA dever� imprimir o extrato e enviar nota fiscal de presta��o de servi�os para a TELECONTROL, conforme orienta��es no site, no �ltimo dia �til do m�s, o dep�sito ser� realizado na conta banc�ria da AUTORIZADA.
                        </p>

                        <p>
                            <strong>3 - DURA��O DO CONTRATO</strong>
                        </p>

                        <p>
                            3.1. A validade do presente contrato � por tempo indeterminado e poder� ser rescindido por qualquer das partes, mediante um aviso pr�vio de 30 (trinta) dias, por escrito. A autorizada obriga-se, neste prazo do aviso, a dar continuidade aos atendimentos dos produtos em seu poder.
                        </p>

                        <p>
                            <strong>4 - RESPONSABILIDADES</strong>
                        </p>

                        <p>
                            4.1 A AUTORIZADA se compromete a seguir as normas de procedimento expressamente veiculadas pela TELECONTROL;
                        </p>

                        <p>
                            4.2 Em caso de a��es propostas por consumidores, que reste provada a culpa ou dolo da AUTORIZADA, seus s�cios, diretores, prepostos, colaboradores ou empregados, esta concorda desde j� que dever� assumir e integrar o polo passivo da sa��es judiciais que venham a ser demandadas contra a TELECONTROL e a empresas propriet�rias dos produtos em quest�o.
                        </p>

                        <p>
                            <strong>5- DISPOSI��ES GERAIS</strong>
                        </p>

                        <p>
                            5.1. A AUTORIZADA declara conhecer e se compromete a cumprir o disposto no C�digo de Defesa do Consumidor e assume a responsabilidade de 'in vigilando' por seus funcion�rios para esta finalidade.
                        </p>

                        <p>
                            <strong>6- FORO</strong>
                        </p>

                        <p>
                            Estando de pleno acordo com todas as cl�usulas e condi��es aqui expostas, elegem as partes contratantes o Foro da Comarca de Mar�lia, estado de S�o Paulo, para dirimir e resolver toda e qualquer quest�o, proveniente do presente contrato, com expressa renuncia de qualquer outro, por mais privilegiado que seja.
                        </p>

                        <p>
                            E, por estarem assim justas e acertadas, firmam o presente instrumento, em duas vias de igual teor e forma, juntamente com as testemunhas abaixo indicadas.
                        </p>

                        <p>
                            Mar�lia, ".date("d")." de ".$array_meses[intval(date("m"))]." de ".date("Y")."
                        </p>

                        <br /> <br /> <br />

            <table width=100% >
            <tr >
            <td width=45% rowspan=2 align=left nowrap>
            <img src='https://ww2.telecontrol.com.br/assist/credenciamento/assinatura_contrato.jpg' height=300 />                                        
            </td>
            <td width=10%>&nbsp;</td>
            <td width=45% align=left valign=top nowrap>
             <br><br><br>
              <hr width=100% size=1 color=#000 />
               <span style='text-transform:uppercase;'>".strtoupper($posto_nome)."</span><br>
                Autorizada
                 </td>
                 </tr>
                 <tr >
                  <td width=10%>&nbsp;</td>
                   <td width=45% align=left nowrap>
                    <br><br><br><br>
                 <hr width=100% size=1 color=#000 />
                 Nome:<br>
                 RG:
                 </td>
                 </tr>
                 </table>
                    </body>

                </html>
            ";

        }
        
        if($fabrica == 160){
            $texto = "<html>

                    <head>

                        <style>
                            body{
                                font-size: 14px;
                                font-family: arial;
                            }
                            h3{
                                text-align: center;
                            }
                            .tar{
                                text-align: right;
                            }
                            .tac{
                                text-align: center;
                            }
                            .paragrafo{
                                padding-left: 20px;
                            }
                        </style>

                    </head>

                    <body>
                        <p class='tar'><img width='140' src='einhell/logo_en1.jpg'/></p>
                        <h4>CONTRATO DE CREDENCIAMENTO E PRESTA��O DE SERVI�OS DE ASSIST�NCIA T�CNICA</h4>

                        <p>
                            Pelo presente instrumento particular, as Partes
                        </p>

                        <p>
                            <b>CONTRATANTE: �NCORA CHUMBADORES LTDA.</b>, pessoa jur�dica de direito privado, com sede na cidade de Vinhedo, Estado de S�o Paulo, na Avenida Benedito Storani n� 1345, bairro Santa Rosa, CEP 13289-004, com inscri��o no CNPJ/MF sob o n� 67.647.412/0004-31; e
                        </p>

                        <p>
                            <b> CONTRATADA: {$posto_nome} </b>, pessoa jur�dica de direito privado, com sede na {$endereco}, {$numero}, bairro {$bairro}, na cidade de {$cidade}, {$estado}, CEP {$cep}, com inscri��o no CNPJ/MF sob n� {$cnpj}, representadas na forma de seus respectivos atos societ�rios por seus representantes abaixo assinados; e,
                        </p>

                        <p>
                            CONSIDERANDO QUE:
                        </p>

                        < <p>(a) a CONTRATANTE tem por atividade o com�rcio, a importa��o e a distribui��o de ferramentas e equipamentos, tais como furadeiras el�tricas, lixadeiras, m�quinas de solda, equipamentos de jardinagem, compressores, lavadoras de alta press�o, serras el�tricas e bombas d��gua;</p>

                        <p>(b) a CONTRATADA, no exerc�cio de suas atividades, declara ter as condi��es t�cnicas para integrar a Rede de Assist�ncia T�cnica Credenciada da CONTRATANTE, por ser uma empresa especializada no ramo de manuten��o e conserto de ferramentas e equipamentos;</p>

                        <p>t�m entre si justo e acordado firmar o presente Contrato de Credenciamento e de Presta��o de Servi�os de Assist�ncia T�cnica (doravante designado �Contrato�), o qual ser� regido pelas seguintes cl�usulas e condi��es:</p>
                        <p></p>
                        <p></p>
                        <p class='tac'>
                            <b>CAP�TULO I - DO CREDENCIAMENTO</b>
                        </p>

                        <p>
                            <b>CL�USULA PRIMEIRA: </b> A CONTRATANTE, detentora exclusiva dos direitos de uso no Brasil do nome e da marca <b>EINHELL</b>, devidamente registrada no Instituto Nacional de Propriedade Industrial - INPI, neste ato nomeia a CONTRATADA, <u>sem exclusividade</u>, como <b>Assist�ncia T�cnica Autorizada</b> dos produtos pela primeira comercializados, com o objetivo de obter �xito e qualidade na presta��o
                         de servi�os de assist�ncia aos consumidores e revendedores, durante e ap�s os per�odos de garantia legal e/ou contratual dos respectivos produtos. </p>
                        <p>
                            <b>CL�USULA SEGUNDA: </b> A CONTRATADA � respons�vel pelo bom e adequado atendimento aos consumidores e revendedores dos produtos da marca EINHELL, de acordo com as estipula��es do presente instrumento, Manuais e Termos de Garantia dos produtos EINHELL e, sobretudo, de acordo com as especifica��es do C�digo de Defesa e Prote��o do Consumidor (Lei 8.078, de 11.9.90).
                        </p>
                        <p></p>
                        <p class='tac'><img width='300' src='einhell/logo_en2.jpg'/></p>
                        <p></p>
                        <p></p>
                        <p class='tar'><img width='140' src='einhell/logo_en1.jpg'/></p>
                        <p>
                            <b>CL�USULA TERCEIRA:</b> Qualquer altera��o a ser realizada no Contrato Social da CONTRATADA dever� ser precedida de pr�via ci�ncia � CONTRATANTE com prazo m�nimo de 10 (dez) dias de anteced�ncia.
                        </p>

                        <p>
                            <b>CL�USULA QUARTA:</b> A CONTRATADA � obrigada a fazer periodicamente a atualiza��o dos seus dados e documentos cadastrais, sendo de sua exclusiva responsabilidade perda ou extravio de quaisquer materiais, documentos, pe�as, etc., decorrentes da aus�ncia de comunica��o pr�via acerca de quaisquer mudan�as que venha a sofrer.
                        </p>
                        <p>
                            <b>CL�USULA QUINTA:</b> A CONTRATANTE e a CONTRATADA s�o pessoas jur�dicas independentes e distintas, estando, portanto, a CONTRATADA, obrigada a cumprir todas as cl�usulas do presente instrumento, e estando a CONTRATANTE isenta de toda e qualquer responsabilidade por encargos e obriga��es contra�das pela CONTRATADA, seja de ordem civil, tribut�ria, providenciaria, trabalhista, penal ou quaisquer outras decorrentes do exerc�cio do objeto do presente contrato.
                        </p>

                        <p>
                            <b>CL�USULA SEXTA:</b> Durante a vig�ncia do presente instrumento e enquanto a CONTRATADA integrar a Rede Credenciada de Assist�ncia T�cnica Autorizada da CONTRATANTE est� obrigada a:
                        </p>
                        <p></p>

                        <p>I. Preservar a imagem, o nome, a marca e o bom conceito dos produtos da CONTRATANTE.</p>


                        <p>II. Possuir em seus quadros funcionais, profissionais com capacidade e conhecimento t�cnico para prestar assist�ncia, atender e resolver as reclama��es e solicita��es dos Consumidores, independente de os produtos estarem dentro do per�odo de garantia legal ou contratual.</p>

                        <p>III. Somente utilizar a expressão <b>�REDE CREDENCIADA EINHELL�</b> ou <b><u>Assist�ncia T�cnica Autorizada EINHELL</u></b>, durante a vig�ncia do presente instrumento, sendo expressamente vedado o nome e a marca EINHELL para quaisquer outras finalidades. </p>

                        <p>IV. Disponibilizar em local de f�cil visualiza��o todo material que lhe for fornecido com a identifica��o visual da CONTRATANTE, assumindo todo e qualquer custo de exposi��o que possa ser exigido em virtude de leis federais, estaduais e/ou municipais.</p>

                        <p>V. Manter o pr�dio e o interior do seu estabelecimento em perfeito estado de conserva��o, limpeza e organiza��o.</p>
                        <p class='tac'>
                            <b>CAP�TULO II - DA PRESTA��O DOS SERVI�OS DE ASSIST�NCIA T�CNICA</b>
                        </p>
                        <p>
                            <b>CL�USULA S�TIMA:</b> A CONTRATADA se obriga a realizar serviços de assist�ncia t�cnica nos produtos comercializados pela CONTRATANTE, sempre que solicitado por consumidores e revendedores, compreendendo a substitui��o de pe�as e servi�os de m�o-de-obra, durante e ap�s os respectivos per�odos de garantia legal ou contratual, observadas as condi��es do presente contrato.
                        </p>
                        <p></p>
                        <p></p>
                        <p class='tac'><img width='300' src='einhell/logo_en2.jpg'/></p>
                        <p></p>
                        <p></p>
                        <p class='tar'><img width='140' src='einhell/logo_en1.jpg'/></p>
                        <p>
                            <b>CL�USULA OITAVA:</b> Os servi�os de assist�ncia t�cnica solicitados pelos consumidores e revendedores, dever�o ser conclu�dos pela CONTRATADA no prazo m�ximo de <u>20 (vinte) dias corridos</u>, contados da data da recebimento do(s) produto(s) para reparo, a qual deve ser devidamente documentada por meio de ordem de servi�o ou outro documento que registre a data de entrada.
                        </p>
                        <p class='paragrafo'>
                            <b>Par�grafo Primeiro:</b> A CONTRATADA obriga-se a respeitar o prazo acima e, quando for necess�ria sua dila��o para a solu��o do problema detectado no produto, dever� notificar formalmente o Suporte da CONTRATANTE, de forma obter o acompanhamento da CONTRATANTE ao atendimento ao consumidor. Seja qual for o problema, a CONTRATADA jamais poder� ultrapassar o prazo m�ximo de 30 (trinta) dias, a contar da solicita��o do servi�o de assist�ncia t�cnica pelo consumidor, para a resolu��o do problema, conforme prev� o par�grafo 1� do artigo 18 e demais disposi��es do C�digo de Defesa do Consumidor.
                        </p>
                        <p class='paragrafo'>
                            <b>Par�grafo Segundo:</b> O descumprimento dos prazos acima, implicar� na responsabiliza��o da    CONTRATADA, sendo-lhe debitado o valor do novo produto que venha a ser fornecido ao consumidor ou revendedor em substitui��o �quele deixado para conserto, sem preju�zo de a CONTRATADA responder, direta ou regressivamente, pelas perdas e danos e/ou preju�zos a que der causa � CONTRATANTE e/ou o consumidor/revendedor, inclu�dos honor�rios advocat�cios e custas processuais.
                        </p>
                        <p>
                            <b>CL�USULA NONA:</b> Pelos servi�os de m�o de obra de assist�ncia t�cnica executados nos produtos, a CONTRATANTE pagar� � CONTRATADA, os valores constantes da Tabela de Taxas de Servi�os respectiva, emitida pela CONTRATANTE, vigente � �poca do processamento das Ordens de Servi�os.
                            Estes servi�os ser�o pagos mensalmente, no prazo de 10 (dez) dias contados da data do envio do 'Formul�rio dos Servi�os Prestados acompanhado' das respectivas Ordens de Servi�os e Nota Fiscal de Presta��o de Servi�os.
                        </p>
                        <p>
                            <b>CL�USULA D�CIMA:</b> O fornecimento e a distribui��o de pe�as originais a serem utilizadas nos servi�os de assist�ncia t�cnica, ser�o realizados exclusivamente pela CONTRATANTE, ou por outra parte designada pela CONTRATANTE.
                        </p>  
                        <p>
                            <b>CL�USULA D�CIMA PRIMEIRA:</b> Nos servi�os prestados durante e ap�s o prazo de garantia, a CONTRATADA empregar�, exclusivamente pe�as originais fornecidas pela CONTRATANTE, responsabilizando-se integralmente pela qualidade dos servi�os prestados, como previsto no presente instrumento.
                        </p>
                        <p>
                            <b>CL�USULA D�CIMA SEGUNDA:</b> A CONTRATADA n�o � obrigada a manter um estoque m�nimo permanente de pe�as que permita o atendimento eficiente aos revendedores, consumidores, entretanto, responder�, nos termos da Cl�usula Oitava, pelo atraso decorrente da demora na solicita��o de pe�as � CONTRATANTE.
                        </p>
                        <p></p>
                        <p></p>
                        <p></p>
                        <p></p>
                        <p class='tac'><img width='300' src='einhell/logo_en2.jpg'/></p>
                        <p></p>
                        <p></p>
                        <p class='tar'><img width='140' src='einhell/logo_en1.jpg'/></p>
                        <p>
                            <b>CL�USULA D�CIMA TERCEIRA:</b> A CONTRATANTE enviar� sem qualquer �nus para a CONTRATADA, as pe�as defeituosas dos produtos consertados durante a garantia (legal ou contratual), desde que tais pe�as estejam abrangidas pelas condi��es constantes do Certificado de Garantia respectivo, sendo de responsabilidade da CONTRATADA a utiliza��o de pe�as originais fornecidas pela CONTRATANTE ou por quem esta indicar, sob pena de submeter o produto � perda de garantia e responder civil e criminalmente � CONTRATANTE, sem preju�zo das perdas e danos a que der causa.
                        </p>
                        <p>
                            <b>CL�USULA D�CIMA QUARTA:</b> A solicita��o de pe�as pela CONTRATADA se dar� mediante a coloca��o de pedidos por comunica��o via sistema telecontrol.
                        </p>
                        <p class='paragrafo'>
                            <b>Par�grafo Primeiro:</b> A CONTRATANTE remeter� as pe�as por Correio ou transportadora, no prazo de 48 horas, contadas do recebimento da solicita��o da CONTRATADA.
                        </p>
                        <p class='paragrafo'>
                            <b>Par�grafo Segundo:</b> O pagamento pela aquisi��o das pe�as utilizadas em produtos que estejam fora do prazo de garantia, ser� feito por boleto banc�rio enviado pela CONTRATANTE, ou pelo seu preposto, � CONTRATADA, juntamente com as pe�as, nos respectivos valores e vencimentos previamente acordados.
                        </p>
                        <p></p>
                        <p></p>
                        <p class='tac'>
                            <b>CAP�TULO III - DAS CONDI��ES GERAIS</b>
                        </p>
                        <p>
                            <b>CL�USULA D�CIMA QUINTA:</b> O presente Contrato ter� prazo de 12 (doze) meses a contar da data de sua assinatura e poder� ser renovado, mediante comunica��o escrita entre as partes com, no m�nimo, 30 (trinta) dias da anteced�ncia do seu t�rmino, cabendo as partes, � �poca, estabelecerem, se for o caso, novas condi��es contratuais, modificando dessa forma as cl�usulas correspondentes. 
                        </p>
                        <p>
                            <b>CL�USULA D�CIMA SEXTA:</b> Este Contrato poder� ser rescindido imotivadamente, por qualquer das partes, a qualquer tempo e sem qualquer �nus para qualquer delas, mediante comunica��o escrita enviada por uma � outra com anteced�ncia m�nima de 30 (trinta) dias. A rescis�o imotivada deste Contrato n�o eximir� nenhuma das partes das responsabilidades e obriga��es por elas contra�das inerentes a este Contrato.
                        </p>
                        <p class='paragrafo'>
                            <b>Par�grafo Primeiro:</b> Em caso de inadimplemento ou descumprimento de quaisquer das disposi��es contidas neste Contrato, a parte prejudicada notificar� a outra para, no prazo de 15 (quinze) dias a contar do recebimento da notifica��o, sanar e remediar a falha ou omiss�o. Permanecendo a falha ou omiss�o, independentemente de novo aviso ou notifica��o, o presente Contrato estar� automaticamente rescindido de pleno direito, sem preju�zo do direito da parte prejudicada de exigir da parte infratora o ressarcimento por eventuais preju�zos apurados em decorr�ncia do descumprimento contratual.
                        </p>
                        <p></p>
                        <p></p>
                        <p class='tac'><img width='300' src='einhell/logo_en2.jpg'/></p>
                        <p></p>
                        <p></p>
                        <p class='tar'><img width='140' src='einhell/logo_en1.jpg'/></p>
                        <p class='paragrafo'>
                            <b>Par�grafo Segundo:</b> Este Contrato poder� ser rescindido por ato unilateral da CONTRATANTE, que se dar� independentemente de qualquer procedimento judicial ou extrajudicial, n�o sendo devido o pagamento de qualquer indeniza��o � CONTRATADA, se ocorridos quaisquer dos eventos abaixo relacionados:
                        </p>
                        <p class='paragrafo'>
                            (i) A decreta��o de fal�ncia, de recupera��o judicial, de dissolu��o judicial ou liquida��o extrajudicial da CONTRATADA que possa p�r em risco o cumprimento deste Contrato; ou
                        </p>
                        <p class='paragrafo'>
                            (ii)    A altera��o ou a modifica��o da finalidade ou da estrutura societ�ria da CONTRATADA, que venha a prejudicar o cumprimento deste Contrato.
                        </p>
                        <p>
                            <b>CL�USULA D�CIMA S�TIMA:</b> Este Contrato representa o acordo integral celebrado entre as partes e os seus termos e disposi��es prevalecer�o sobre quaisquer outros entendimentos ou acordos anteriores mantidos entre as Partes, expressos ou impl�citos, referentes �s condi��es neles estabelecidas. Dessa forma, todos os documentos, minutas, cartas ou notas que possam existir at� esta data, al�m de todas as declara��es e garantias que possam ter sido feitas por ou a favor de cada uma das partes, tornam-se nulas e sem efeito.
                        </p>
                        <p>
                            <b>CL�USULA D�CIMA OITAVA:</b> O disposto neste Contrato n�o poder� ser alterado ou emendado, a n�o ser por meio de termo aditivo formal ou epistolar, subscrito pelas partes. 
                        </p>
                        <p>
                            <b>CL�USULA D�CIMA NONA:</b> Salvo expressa disposi��o em contr�rio, todos os prazos e condi��es do Contrato vencem independentemente de aviso, interpela��o judicial ou extrajudicial.
                        </p>
                        <p>
                            <b>CL�USULA VIG�SIMA:</b> O n�o exerc�cio por qualquer das Partes de seus direitos ou a n�o exig�ncia do cumprimento de obriga��es contra�das pela parte contr�ria nos prazos convencionados, n�o importar� em ren�ncia ou nova��o e n�o impedir� o seu exerc�cio ou a sua exig�ncia em qualquer tempo.
                        </p>
                        <p>
                            <b>CL�USULA VIG�SIMA PRIMEIRA:</b> A CONTRATADA n�o est�, sob nenhuma circunst�ncia, autorizada a representar a CONTRATANTE perante qualquer empresa privada, p�blica e afins. A CONTRATADA, por si, seus s�cios, administradores, empregados e prepostos, declaram-se cientes de que o uso de quaisquer meios n�o expressamente autorizados pela CONTRATADA, bem como meios anti�ticos ou il�citos por ventura utilizados com o intuito de facilitar negocia��es para e/ou com a CONTRATANTE, ter� como conseq��ncia imediata, a rescis�o do presente Contrato.
                        </p>

                        <p>
                            <b>CL�USULA VIG�SIMA SEGUNDA:</b> A CONTRATADA manter� registros genu�nos e exatos dos servi�os prestados e de todas as transa��es a eles relacionadas. Todos os registros ser�o mantidos por um m�nimo de 24 (vinte e quatro) meses a partir da data da execu��o dos servi�os. A qualquer momento, durante a vig�ncia deste Contrato ou durante 24 (vinte e quatro) meses contados da conclus�o dos servi�os, a CONTRATANTE poder� realizar uma auditoria nos registros da CONTRATADA pass�veis de serem publicados e relevantes ao objeto deste Contrato, no que se refere aos servi�os prestados e pagamentos efetuados.
                        </p>
                        <p></p>
                        <p></p>
                        <p class='tac'><img width='300' src='einhell/logo_en2.jpg'/></p>
                        <p></p>
                        <p></p>
                        <p class='tar'><img width='140' src='einhell/logo_en1.jpg'/></p>
                        <p>
                            <b>CL�USULA VIG�SIMA TERCEIRA:</b> Nenhuma das partes poder� ceder ou transferir a terceiros, total ou parcialmente, este Contrato e/ou qualquer direito ou obriga��o dele decorrente ou a ele relacionados, sem o consentimento pr�vio, por escrito, da outra parte.
                        </p>

                        <p>
                            <b>CL�USULA VIG�SIMA QUARTA:</b> As partes elegem o foro da Comarca de Vinhedo, Estado de S�o Paulo, para dirimir quaisquer quest�es oriundas do presente contrato, com exclus�o de qualquer outro por mais privilegiado que seja.
                        </p>
                        <p>
                            E, por estarem justas e contratadas, as partes assinam o presente contrato em 2 (duas) vias de igual teor e forma, na presen�a de 2 testemunhas.
                        </p>


                        <p>
                            Vinhedo/SP, ".date("d")." de ".$array_meses[intval(date("m"))]." de ".date("Y")."
                        </p>

                        <br /> <br /> <br />

                        <table width=100% >
                            <tr>
                                <td width=45% align=left nowrap><b>�NCORA CHUMBADORES LTDA.</b><br /><br /><br /></td>
                                <td width=10% align=left nowrap>&nbsp;</td>
                                <td width=45% align=left nowrap><b style='text-transform:uppercase;'>".strtoupper($posto_nome)."</b><br /><br /><br /></td>
                            </tr>
                            <tr>
                                <td width=45% align=left nowrap>
                                    <hr width=100% size=1 color=#000 />
                                    <b>Jos� Roberto Bernardi - Diretor Comercial</b>
                                </td>
                                <td width=10%>&nbsp;</td>
                                <td width=45% align=left valign=top nowrap>
                                    <hr width=100% size=1 color=#000 />
                                    <b>Autorizada</b>
                                </td>
                            </tr>
                            <tr>
                                <td colspan=3 align=left nowrap><br /><br /><br /></td>
                            </tr>
                            <tr>
                                <td colspan=3 align=left nowrap><b>Testemunhas:</b></td>
                            </tr>
                            <tr>
                                <td  width=45% align=left nowrap><br /><br /><b>1.</b></td>
                                <td  width=10% align=left nowrap>&nbsp;</td>
                                <td  width=45% align=left nowrap><br /><br /><b>2.</b></td>
                            </tr>
                            <tr>
                                <td colspan=3 align=left nowrap><br /><br /></td>
                            </tr>
                            <tr>
                                <td width=45% align=left nowrap>
                                    <hr width=100% size=1 color=#000 />
                                    Nome:<br>
                                    RG:<br>
                                    CPF:
                                </td>
                                <td width=10%>&nbsp;</td>
                                <td width=45% align=left nowrap>
                                    <hr width=100% size=1 color=#000 />
                                    Nome:<br>
                                    RG:<br>
                                    CPF:
                                </td>
                            </tr>
                        </table>
                        <p></p>
                        <p></p>
                        <p></p>
                        <p></p>
                        <p></p>
                        <p></p>
                        <p></p>
                        <p></p>
                        <p></p>
                        <p></p>
                        <p></p>
                        <p class='tac'><img width='300' src='einhell/logo_en2.jpg'/></p>
                        <p></p>
                        <p></p>

                    </body>

                </html>";
        }

        if ($fabrica == 35) {
            $link_logo = "http://posvenda.telecontrol.com.br/assist/credenciamento/cadence/topo_contrato.png";
            if (strtolower($_serverEnvironment) == 'development') {
                //$link_logo = "http://novodevel.telecontrol.com.br/~breno/chamados/hd-6190474/credenciamento/cadence/topo_contrato.png";
                $link_logo = "https://novodevel.telecontrol.com.br/~breno/PosVendaAssist/credenciamento/cadence/topo_contrato.png";
            }

            $data = date('D');
            $mes = date('M');
            $dia = date('d');
            $ano = date('Y');
            
            $semana = array(
                'Sun' => 'Domingo', 
                'Mon' => 'Segunda-Feira',
                'Tue' => 'Ter�a-Feira',
                'Wed' => 'Quarta-Feira',
                'Thu' => 'Quinta-Feira',
                'Fri' => 'Sexta-Feira',
                'Sat' => 'S�bado'
            );
            
            $mes_extenso = array(
                'Jan' => 'Janeiro',
                'Feb' => 'Fevereiro',
                'Mar' => 'Mar�o',
                'Apr' => 'Abril',
                'May' => 'Maio',
                'Jun' => 'Junho',
                'Jul' => 'Julho',
                'Aug' => 'Agosto',
                'Nov' => 'Novembro',
                'Sep' => 'Setembro',
                'Oct' => 'Outubro',
                'Dec' => 'Dezembro'
            );

            $dataAssinatura = $semana["$data"] . ", {$dia} de " . $mes_extenso["$mes"] . " de {$ano}";

            $texto = "
                <html>
                    <head>
                        <style>
                            body{
                                font-size: 14px;
                                font-family: arial;
                            }
                            h3{
                                text-align: center;
                            }
                        </style>
                    </head>
                    <body style='text-align:justify;'> 
                        <div style='width:730px;height:150px;background:url({$link_logo}) no-repeat;'>
                        </div>                        
                        <p>
                            <strong>CONTRATANTE: JCS BRASIL ELETRODOM�STICOS S.A.</strong>, sociedade an�nima de capital fechado, inscrita no CNPJ sob o n�. 03.106.170/0002-24, situada a Avenida Takata, Km 101, n.� 3309, Bairro Nossa Senhora da Concei��o, na cidade de Balne�rio Pi�arras/SC, por seus representantes legais ao final assinados, nos termos do Estatuto Social, doravante denominada CONTRATANTE.<br>
                        </p>
                        <p>
                            <strong>CONTRATADA: {$posto_nome}, sociedade empresarial limitada, com sede na cidade de {$cidade}/{$estado}, na {$endereco}, $numero - {$bairro} - CEP {$cep}, inscrita no CNPJ/MF sob o n� {$cnpj}</strong>, por seu representante legal ao final assinado, nos termos do Contrato Social, doravante denominada CONTRATADA.<br>
                        </p>
                        <p>
                            <strong>I-CONSIDERA��ES PRELIMINARES</strong><br>
                        </p>
                        <p>
                            1.1 A CONTRATANTE � empresa do ramo de eletro port�teis, e que atua no mercado nacional sem qualquer m�cula ou ofensa ao seu bom nome e imagem. A CONTRATANTE tem por princ�pios fabricar e comercializar produtos voltados a satisfa��o dos clientes e consumidores, prezando pela qualidade, inova��o.<br>
                        </p>
                        <p>
                            1.2 A CONTRATANTE tem ci�ncia que toda e qualquer atua��o sua e de seus parceiros devem ser pautadas na �tica, boa-f�, equidade, qualidade, clareza e seguran�a, atendendo, seja diretamente, ou atrav�s de seus parceiros, as especifica��es e determina��es do C�digo de Defesa do Consumidor e legisla��es t�cnicas.<br>
                        </p>
                        <p>                            
                            1.3 A CONTRATADA tem ci�ncia que em raz�o de sua condi��o de Servi�o T�cnico Autorizado deve respeitar as normas de seguran�a e qualidade e orienta��es de atendimento da CONTRATANTE, bem como, a obriga��o de agir com lisura, correi��o e boa f� no trato com os clientes, al�m de zelar pelo nome e imagem da mesma no mercado e perante os clientes.<br>
                        </p>
                        <p>                            
                            1.4 A legalidade da representatividade legal dever� ser comprovada com a juntada da c�pia de contrato social, ou qualquer outro instrumento legal que comprove a autonomia do representante, n�o sendo aceita a assinatura do presente instrumento por outra pessoa n�o autorizada nos moldes legais. As partes t�m entre si, justo e acertado, o presente contrato de presta��o de servi�os de Assist�ncia T�cnica, regido pelas seguintes cl�usulas:
                        </p>
                        <p>
                            <strong>II-DO OBJETO</strong><br>
                        </p>
                        <p>
                            2.1 As partes firmam o presente contrato que tem por objetivo credenciar a CONTRATADA como pessoa jur�dica autorizada para a presta��o de servi�o t�cnico autorizado para os produtos industrializados e comercializados pela CONTRATANTE, nos casos de reparo dentro e fora do per�odo determinado de garantia. Os pre�os relacionados aos servi�os realizados dentro do prazo de garantia encontram-se em Tabela de Pre�o anexa ao presente instrumento.<br>
                        </p>
                        <p>                        
                            2.2 A CONTRATADA obriga-se a prestar servi�os de assist�ncia t�cnica e de reposi��o de pe�as defeituosas para os produtos comercializados pela CONTRATANTE em v�rios modelos, que podem ser das marcas Oster e Cadence.<br>
                        </p>
                        <p>
                            2.3 Os servi�os de assist�ncia t�cnica, previstos neste contrato, ser�o prestados no endere�o da CONTRATADA, ou conforme a solicita��o da CONTRATANTE, na condi��o de servi�o externo com ressarcimento da quilometragem percorrida.
                        </p>
                        <p>
                            2.4 O presente contrato n�o impede que a CONTRATADA preste servi�os de assist�ncia t�cnica a outros produtos que n�o sejam do grupo JCS BRASIL, bem como, n�o impossibilita por parte da CONTRATANTE a contrata��o de outros prestadores de servi�os ainda que na mesma localidade.<br>
                        </p>
                        <p>
                            2.5 A CONTRATANTE reserva-se o direito de efetuar servi�os de Assist�ncia T�cnica na regi�o de atua��o da CONTRATADA, enviando profissional para que realize o atendimento diretamente ao cliente, bem como, nomeando para nela atuar mais de um prestador de Servi�o T�cnico Autorizado.<br>
                        </p>
                        <p>
                            <strong>III-DO PRAZO</strong><br>
                        </p>
                        <p>
                            3.1 O prazo de vig�ncia do contrato � indeterminado, ficando assegurado �s partes, o direito de, imotivadamente, a qualquer momento, rescindir unilateralmente o presente Contrato mediante aviso pr�vio e escrito � outra parte, com anteced�ncia m�nima de 30 (trinta) dias, sem pagamento de multa, ressalvadas as obriga��es correspondentes aos servi�os j� iniciados.<br>
                        </p>
                        <p>
                            3.2 A CONTRATANTE dever� realizar o pagamento dos servi�os realizados, conforme Tabela de Pre�os anexa, relativos �s Ordens de Servi�os atendidas neste per�odo. A CONTRATADA se compromete a finalizar todos os atendimentos no prazo do aviso pr�vio, ou seja, no m�ximo 30 dias. Os servi�os dever�o ser conclu�dos nos termos desta aven�a. Importante ressaltar que a parte solicita o descredenciamento com 30 dias de anteced�ncia e ter� esse prazo para finalizar os atendimentos.<br>
                        </p>
                        <p>
                            3.3 O presente contrato poder� ser rescindido imediatamente, independente de notifica��o pr�via, nos casos de descumprimento de qualquer uma das cl�usulas previstas neste contrato, sem direito a qualquer indeniza��o.<br>
                        </p>
                        <p>
                            <strong>IV-DO PAGAMENTO</strong><br>
                        </p>
                        <p>
                            4.1 Pela presta��o de servi�os realizada dentro do per�odo de garantia do produto, a CONTRATADA receber� da CONTRATANTE valor relativo � m�o-de-obra, conforme tabela de pre�os anexa, emitida pela CONTRATANTE. Ressalta-se que essa tabela poder� ter os valores reajustados de acordo com an�lise da CONTRATANTE. Os valores reajustados, bem como, os valores relativos a novos modelos de produtos ser�o informados por meio do sistema de gest�o de Ordens de Servi�o, ou estar�o dispon�veis para consulta no Sistema de gest�o de Ordens de Servi�o.<br>
                        </p>
                        <p>
                            4.2 Os servi�os prestados pela CONTRATADA dentro do per�odo de garantia do produto, dever�o ser realizados no prazo m�ximo de 30 dias, contados a partir da data de recebimento do produto pela CONTRATADA, conforme previsto no C�digo de Defesa do Consumidor. Caso o atendimento n�o seja realizado no per�odo de 30 dias, a CONTRATANTE reserva-se ao direito de cobrar da CONTRATADA os valores referentes � substitui��o do produto, restitui��o do valor pago pelo produto ao consumidor e demais valores oriundos de processos judiciais. Importante ressaltar que o tempo de atendimento ser� medido a partir da data da entrega do produto pelo consumidor � CONTRATADA, data de in�cio, e finalizar� na data de encerramento da Ordem de Servi�o no Sistema de Gest�o de Ordens de Servi�o.<br>
                        </p>
                        <p>                            
                            4.3 As pe�as necess�rias ao reparo dos produtos em garantia ser�o repostas � Assist�ncia T�cnica mediante solicita��o, que deve ser realizada pela pr�pria Ordem de Servi�o atrav�s do Sistema de Gest�o de Ordens de Servi�o.<br>
                        </p>
                        <p>                            
                            4.4 Para poder usufruir dos servi�os de assist�ncia t�cnica gratuita, os adquirentes dos produtos dever�o apresentar � CONTRATADA a respectiva nota fiscal de compra do produto, emitida pela CONTRATANTE, ou por seus revendedores, onde conste de uma forma precisa e detalhada a data da venda, a identifica��o do produto e demais informa��es pertinentes, quando se tratar de garantia de f�brica. A garantia de f�brica consta no Manual do Usu�rio que acompanha o produto.
                        </p>
                        <p>                            
                            4.5 Os servi�os de assist�ncia t�cnica gratuita, realizados durante o per�odo de garantia, ficam inteiramente subordinados � observ�ncia dos termos da mesma e n�o incluem a substitui��o de pe�as gastas ou estragadas em decorr�ncia de uso inadequado, ou danos decorrentes de quedas, ou uso em desacordo com o manual de instru��es.<br>
                        </p>
                        <p>
                            4.6 Pelos servi�os executados fora do per�odo de garantia de f�brica, a CONTRATADA dever� cobrar do propriet�rio do produto consertado uma remunera��o razo�vel e compat�vel com os servi�os realizados, bem como, um valor adequado pelas pe�as utilizadas no conserto. O atendimento fora do prazo de garantia dever� ser realizado no per�odo de at� 30 dias.<br>
                        </p>
                        <p>
                            4.7 O frete referente �s pe�as solicitadas para atendimentos de garantia de f�brica ser� de responsabilidade da CONTRATANTE.<br>
                        </p>
                        <p>                            
                            4.8 O frete referente �s pe�as solicitadas para atendimentos fora do per�odo de garantia de f�brica ser� de responsabilidade da CONTRATADA.<br>
                        </p>
                        <p>                            
                            4.9 Sempre que houver TROCA de produtos, o respectivo valor da M�o de Obra sofrer� redu��o, de acordo com a tabela anexa, inclusive os produtos de TROCA OBRIGAT�RIA, os quais s�o assim denominados a crit�rio da CONTRATANTE. A CONTRATANTE poder�, a seu crit�rio, alterar a classifica��o dos produtos de TROCA OBRIGAT�RIA e valor de M�o de Obra relativo a esses produtos que ser�o informados por meio do sistema de gest�o de Ordens de Servi�o ou estar�o dispon�veis para consulta no Sistema de gest�o de Ordens de Servi�o.<br>
                        </p>
                        <p>                            
                            4.10 A crit�rio do CONTRATANTE, os valores de M�o de Obra poder�o ser cancelados, caso o contratado: n�o cumpra o prazo m�nimo legal de conserto; abra atendimento reincidente decorrente de inefici�ncia administrativa e/ou t�cnica; abra atendimentos n�o cobertos pela garantia legal.<br>
                        </p>
                        <p>                            
                            4.11 Para os produtos pertencentes �s Linhas de Atendimento COM DESLOCAMENTO, a CONTRATANTE pagar� o valor de R$ 0,60 por KM rodado. O c�lculo da dist�ncia, bem como, do respectivo valor, ser� realizado automaticamente pelo sistema e passar� por auditoria. Fica a crit�rio da CONTRATANTE aprovar, ou n�o, a dist�ncia indicada pela CONTRATADA.<br>
                        </p>
                        <p>                        
                            4.12 O pagamento do valor dos servi�os prestados devido � CONTRATADA ser� realizado pela CONTRATANTE ap�s o recebimento dos documentos e componentes a seguir descritos:
                                <ul style='list-style-type:none'>
                                    <li>Nota fiscal de presta��o de servi�os contendo o(s) numero(s) do(s) extrato(s), englobando totalidade dos servi�os prestados;</li>
                                    <li>Nota fiscal de devolu��o com respectivas pe�as e produtos retorn�veis (devolu��o obrigat�ria demanda pelo extrato, que � gerado atrav�s do Sistema de gest�o de Ordens de Servi�o);</li>
                                </ul>
                        </p>
                        <p>                                    
                            4.13 O pagamento ser� efetuado atrav�s de dep�sito banc�rio, sendo que os dados banc�rios (Banco, Ag�ncia e Conta Corrente) ser�o indicados pela CONTRATADA. A conta corrente informada dever� ser de titularidade da empresa contratada.<br>
                        </p>
                        <p>                            
                            4.14 O controle de ordens de servi�o e solicita��o de pe�as dever� ser realizado pela CONTRATADA via Sistema de Gest�o de Ordens de Servi�o. O Sistema de Gest�o de Ordens de Servi�o atualmente utilizado pela CONTRATANTE � o Telecontrol, que ser� utilizado pela CONTRATADA, ficando a crit�rio da CONTRATANTE a altera��o quando entender necess�rio, e sob a responsabilidade da CONTRATADA a utiliza��o da nova plataforma.<br>
                        </p>
                        <p>                            
                            4.15 Na hip�tese da CONTRATANTE ser credora da CONTRATADA de import�ncias j� vencidas, devidas em fun��o do fornecimento de pe�as de fabrica��o da CONTRATANTE, a CONTRATADA desde j� autoriza a CONTRATANTE compensar tais valores com �queles devidos � CONTRATADA, fazendo-se os ajustes cont�beis necess�rios.<br>
                        </p>
                        <p>                            
                            4.16 Na hip�tese da CONTRATANTE ser credora da CONTRATADA de import�ncias j� vencidas e, n�o havendo a possibilidade de compensa��o conforme estabelecido na cl�usula anterior, a CONTRATADA autoriza desde j� a CONTRATANTE a ingressar com as medidas judiciais e extrajudiciais (Protesto) cab�veis para a sua cobran�a.<br>
                        </p>
                        <p>
                            <strong>V-DOS ENCARGOS</strong><br>
                        </p>
                        <p>
                            5.1 A CONTRATADA � respons�vel por todos os encargos de natureza tribut�ria, previdenci�ria , ou outros que venham a incidir sobre os valores dos servi�os prestados, sendo permitido � CONTRATANTE efetuar as reten��es e os recolhimentos previstos em lei.<br>
                        </p>
                        <p>                            
                            5.2 Havendo a necessidade da CONTRATADA realizar o pagamento de despesas adicionais relacionadas � presta��o de servi�o EM GARANTIA, poder� ser ressarcida dos valores devidamente pagos mediante a apresenta��o de NOTA DE D�BITO, desde que haja a autoriza��o pr�via da CONTRATANTE e a comprova��o do pagamento das despesas adicionais por parte da CONTRATADA.<br>
                        </p>
                        <p>
                            <strong>VI- DAS OBRIGA��ES DAS PARTES</strong><br>
                        </p>
                        <p>
                            6.1 A CONTRATADA compromete-se a manter a estrutura m�nima necess�ria para a presta��o dos servi�os, como: instala��es f�sicas, computador e impressora com acesso � internet, ferramentas de uso geral e especiais, t�cnico treinado e qualificado, conforme solicita��o da CONTRATANTE.<br>
                        </p>
                        <p>                            
                            6.2 A CONTRATADA compromete-se a utilizar �nica e exclusivamente pe�as originais ou especificadas, fornecidas pela CONTRATANTE ou revendedores autorizados e a respeitar a garantia dos servi�os prestados aos consumidores pelo prazo m�nimo, conforme estabelece o C�digo de Defesa do Consumidor.<br>
                        </p>
                        <p>                            
                            6.3 A CONTRATADA compromete-se a tentar manter um estoque m�nimo de pe�as que permita o atendimento imediato e satisfat�rio aos consumidores e em conformidade com os prazos divulgados pela CONTRATADA, n�o podendo tal prazo ultrapassar o per�odo m�ximo de 30 (trinta) dias.<br>
                        </p>
                        <p>                            
                            6.4 A CONTRATADA compromete-se a n�o cobrar dos consumidores benefici�rios dos servi�os na condi��o \"GARANTIA\", qualquer valor, a qualquer t�tulo, sob pena de ressarcimento das import�ncias cobradas indevidamente, al�m da rescis�o autom�tica deste contrato.<br>
                        </p>
                        <p>                            
                            6.5 A CONTRATADA compromete-se a executar os servi�os em conformidade com os procedimentos e zelar pela perfeita utiliza��o dos materiais.<br>
                        </p>
                        <p>                            
                            6.6 A CONTRATADA compromete-se a abrir Ordem de Servi�o no Sistema de Gest�o de Ordens de Servi�o no momento que der entrada de um produto para atendimento em garantia e entregar uma via impressa da Ordem de Servi�o ao consumidor.<br>
                        </p>
                        <p>                            
                            6.7 A CONTRATADA compromete-se a executar os servi�os em conformidade com as orienta��es da CONTRATANTE, inclusive quanto � preserva��o dos aparelhos dos consumidores em seu poder, incluindo, mas n�o se limitando, os seus colaboradores e subcontratados, respondendo a CONTRATADA por eventuais danos causados � CONTRATANTE e a terceiros, por atos resultantes de dolo, neglig�ncia, imprud�ncia ou imper�cia.<br>
                        </p>
                        <p>                            
                            6.8 A CONTRATADA compromete-se a zelar pela perfeita utiliza��o dos materiais cedidos pela CONTRATANTE para a realiza��o dos servi�os, tais como: materiais de Identifica��o visual, manuais t�cnicos, ferramentas, pol�ticas, documentos, expositores, pe�as e produtos para composi��o de estoque antecipado, etc., devendo devolv�-los � CONTRATANTE quando esta exigir.<br>
                        </p>
                        <p>                            
                            6.9 A CONTRATADA � respons�vel por devolver as pe�as substitu�das em garantia � CONTRATANTE sempre que solicitado.<br>
                        </p>
                        <p>
                            6.10 A CONTRATADA deve informar � CONTRATANTE sobre qualquer anomalia ou defeitos encontrados nos produtos submetidos � assist�ncia t�cnica, mantendo sobre o fato o mais absoluto sigilo.<br>
                        </p>
                        <p>                            
                            6.11 A CONTRATADA deve comunicar a CONTRATANTE se houver qualquer altera��o na sua raz�o social, endere�o e nos documentos constitutivos, bem como, se ocorrer a venda, cess�o, fus�o ou incorpora��o do seu estabelecimento.<br>
                        </p>
                        <p>                            
                            6.12 A CONTRATADA garante a continuidade da presta��o dos servi�os objeto deste contrato, por pessoal t�cnico qualificado, de forma profissional, de acordo com os padr�es da atividade e das instru��es da CONTRATANTE, inclusive na hip�tese de impossibilidade e/ou afastamento de qualquer dos funcion�rios da CONTRATADA, por qualquer motivo ou raz�o, incluindo, mas n�o se limitando, no caso de per�odo de f�rias, demiss�es, subcontrata��es, etc.<br>
                        </p>
                        <p>                            
                            6.13 A CONTRATANTE se obriga a efetuar todos os pagamentos, conforme pactuado neste Contrato.<br>
                        </p>
                        <p>                            
                            6.14 A CONTRATANTE se obriga a prestar todas as informa��es necess�rias para que a CONTRATADA realize os servi�os ora acertados.<br>
                        </p>
                        <p>                            
                            6.15 A CONTRATANTE divulgar� o nome da contratada na rela��o de estabelecimentos autorizados a prestar assist�ncia t�cnica dos produtos.<br>
                        </p>
                        <p>                            
                            6.16 � obrigat�rio � CONTRATADA ter o competente Alvar� de Licen�a de Funcionamento e manter as suas custas nas reparti��es p�blicas e nos �rg�os competentes, todas as inscri��es e registros necess�rios � presta��o de servi�os, em raz�o da import�ncia do CNPJ e Inscri��o Estadual para a emiss�o de Notas para o faturamento de pe�as enviadas � CONTRATADA. Havendo irregularidades em rela��o ao CNPJ e Inscri��o Estadual da CONTRATADA, a mesma ser� informada, sendo concedido o prazo de 10 (dez) dias para a regulariza��o.<br>
                        </p>
                        <p>                            
                            6.17 A CONTRATADA � obrigada a anexar a c�pia da Nota Fiscal de Compra do Produto (apresentada pelo consumidor) � respectiva Ordem de Servi�o gerada no Sistema de Gest�o de Ordem de Servi�o.<br>
                        </p>
                        <p>                            
                            <strong>VII-DA INFRAESTRUTURA NECESS�RIA � PRESTA��O DE SERVI�OS</strong><br>
                        </p>
                        <p>                            
                            7.1 A CONTRATADA declara e garante que possui a habilidade, experi�ncia, conhecimento t�cnico e infraestrutura necess�ria para a presta��o dos servi�os, bem como, os servi�os ser�o prestados pontualmente por pessoal t�cnico qualificado, de forma profissional e de acordo com os padr�es da atividade e das instru��es descritas pela CONTRATANTE.<br>
                        </p>
                        <p>                            
                            7.2 A CONTRATADA obriga-se a fornecer aos seus empregados e prepostos todos os equipamentos e materiais necess�rios � execu��o dos servi�os objeto do presente instrumento, obrigando-se, ainda, a fornecer apenas equipamentos e materiais que reconhecidamente n�o ofere�am risco potencial aos seus pr�prios empregados e prepostos, bem como, a terceiros.<br>
                        </p>
                        <p>                            
                            7.3 A CONTRATADA dever� cumprir as Normas Regulamentadoras do Minist�rio do Trabalho sobre seguran�a, higiene e medicina do trabalho, assim como as Normas e Procedimentos da Seguran�a.<br>
                        </p>
                        <p>                            
                            7.4 A CONTRATADA responsabilizar-se-� por todos os danos ou preju�zos materiais, corporais e morais, que vier causar � CONTRATANTE, seus empregados, terceiros e, principalmente, aos seus clientes e consumidores durante o processo de presta��o de servi�os ora contratados, por dolo ou culpa de seus empregados. Eventuais danos, avarias, sinistros ou inutiliza��es de objetos ou equipamentos, comprovadamente causados por empregados da CONTRATADA ser�o indenizados � CONTRATANTE, observando o artigo 412 do C�digo Civil, autorizando desde j� a CONTRATANTE descontar do pagamento da CONTRATADA a import�ncia correspondente aos preju�zos causados.<br>
                        </p>
                        <p>                            
                            7.5 A CONTRATADA responsabilizar-se-� pelos acidentes de empregados, equipamentos, prepostos e contratados derem causa durante a presta��o dos servi�os e indenizar� a CONTRATANTE por quaisquer danos decorrentes de demanda ou reclama��o movida por terceiros relacionados � les�o corporal ou morte de qualquer pessoa empregada ou n�o, incluindo clientes, autoridades e prestadores de servi�os de um modo geral.<br>
                        </p>
                        <p>
                            <strong>VIII- DA INEXIST�NCIA DE V�NCULO EMPREGAT�CIO</strong><br>
                        </p>
                        <p>                             
                            8.1 N�o se estabelece entre as partes qualquer forma de sociedade, associa��o, mandato, ag�ncia, cons�rcio, responsabilidade solid�ria e/ou v�nculo empregat�cio, permanecendo a CONTRATADA respons�vel por todas as obriga��es, �nus e encargos advindos da administra��o e opera��o de seu neg�cio.<br>
                        </p>
                        <p>                             
                            8.2 A celebra��o deste Contrato/Acordo n�o criar� qualquer v�nculo empregat�cio entre uma das Partes e os respectivos Representantes da outra Parte e de suas Afiliadas, eis que os mesmos continuar�o hier�rquica e funcionalmente subordinados a esta Parte e suas Afiliadas, de quem ser� a exclusiva responsabilidade pelo pagamento, conforme o caso, dos reembolsos/sal�rios, encargos trabalhistas e previdenci�rios, impostos e outros acr�scimos pertinentes relativos aos respectivos Representantes. A celebra��o deste Contrato/Acordo tamb�m n�o obriga as Partes no contexto da manuten��o da presta��o dos Servi�os ou celebra��o de quaisquer outros neg�cios relacionados, devendo, neste caso, ser observado o disposto nos respectivos instrumentos contratuais que venham a ser celebrados entre as Partes para estes objetivos.<br>
                        </p>
                        <p>                             
                            8.3 Caso a exist�ncia do v�nculo trabalhista venha a ser reconhecida, ainda que por decis�o judicial, obriga-se a CONTRATADA a indenizar a CONTRATANTE por todos os valores despendidos em decorr�ncia do reconhecimento deste v�nculo, inclusive, custas judiciais e honor�rios advocat�cios, obrigando-se a este pagamento, nas 24 (vinte e quatro) horas seguintes � data da notifica��o da CONTRATANTE para o cumprimento da decis�o que determinar o pagamento. Da mesma forma, obriga-se a CONTRADADA a envidar os seus melhores esfor�os para, de pronto, excluir a CONTRATANTE da lide.<br>
                        </p>
                        <p>                             
                            8.4 Fica expressamente pactuado que se a CONTRATANTE sofrer condena��o judicial em raz�o do n�o cumprimento, em �poca pr�pria, de qualquer obriga��o atribu�vel � CONTRATADA ou a seus subcontratados, origin�ria deste contrato, seja de natureza fiscal, trabalhista, previdenci�ria, criminal ou de qualquer outra esp�cie, a CONTRATANTE, poder� reter os pagamentos devidos � CONTRATADA por for�a deste contrato ou de qualquer outro contrato firmado com a CONTRATADA, aplicando-os na satisfa��o da respectiva obriga��o, liberando a CONTRATANTE da condena��o.<br>
                        </p>
                        <p> 
                            8.5 A presente obriga��o perdurar�, mesmo ap�s o t�rmino da vig�ncia do presente instrumento, at� prescreverem os direitos trabalhistas dos empregados, subcontratados e/ou terceiros a servi�o da CONTRATADA, em rela��o a esta e ao previsto no presente Contrato, de acordo com a legisla��o em vigor.<br>
                        </p>
                        <p>                             
                            8.6 A CONTRATADA reembolsar� a CONTRATANTE de todas as despesas que tiver decorrentes de:
                            <ul style='list-style-type:disc'>
                                <li> indeniza��es, em consequ�ncia de eventuais danos materiais, pessoais e morais causados a empregados, prepostos e/ou dirigentes da CONTRATANTE ou a terceiros, pela CONTRATADA ou seus prepostos na execu��o de suas atividades.
                                <li> indeniza��o � CONTRATANTE por quaisquer despesas eventualmente realizadas em decorr�ncia das hip�teses acima e honor�rios advocat�cios, audi�ncias e viagens necess�rias ao acompanhamento de eventuais a��es previstas acima.
                            </ul>
                        </p>
                        <p>
                            8.7 Estipulam as partes que, em caso de rescis�o do contrato, a CONTRATADA autoriza a CONTRATANTE a compensar do pagamento devido � CONTRATADA, a import�ncia equivalente a todos os valores despendidos pela CONTRATANTE em decorr�ncia das a��es interpostas por empregados utilizados para a execu��o do contrato, incluindo as custas judiciais e os honor�rios advocat�cios.<br>
                        </p>
                        <p>
                            8.8 A CONTRATADA autoriza a CONTRATANTE a compensar do pagamento devido � CONTRATADA por ocasi�o da rescis�o, os d�bitos existentes da CONTRATADA perante a CONTRATANTE, podendo reter os pagamentos para este fim.<br>
                        </p>
                        <p>                            
                            8.9 Caso o d�bito da CONTRATADA para com a CONTRATANTE exceda o valor que a CONTRATANTE tenha a obriga��o de pagar, a CONTRATADA indenizar� a CONTRATANTE, autorizando desde j� a emiss�o de Nota de D�bito pela CONTRATANTE para este fim.<br>
                        </p>
                        <p>                            
                            <strong>IX-DA CONFIDENCIALIDADE</strong><br>
                        </p>
                        <p>                            
                            9.1 O CONTRATADO, desde j�, reconhece e concorda que as informa��es a que tiver acesso em decorr�ncia do presente contrato tem relevante valor e que sua divulga��o n�o autorizada poder� acarretar danos substanciais � CONTRATANTE. Desta forma, o CONTRATADO, salvo pr�via e expressa autoriza��o da CONTRATANTE, compromete-se a n�o divulgar tais informa��es mesmo ap�s o t�rmino ou rescis�o do presente contrato, sob pena de arcar com indeniza��o advindas dos seus atos, na forma da Lei Civil.<br>
                        </p>
                        <p>                            
                            <strong>X- DA ANTICORRUP��O</strong><br>
                        </p>
                        <p>                            
                            10.1 Nenhuma das partes poder� oferecer, dar ou se comprometer a dar a quem quer que seja, ou aceitar ou se comprometer a aceitar de quem quer que seja, tanto por conta pr�pria quanto atrav�s de outrem, qualquer pagamento, doa��o, compensa��o, vantagens financeiras ou n�o financeiras ou benef�cios de qualquer esp�cie que constituam pr�tica ilegal ou de corrup��o de que trata a Lei Anticorrup��o n.� 12.846/2013 ou de quaisquer outras leis aplic�veis sobre o objeto do presente contrato, em especial o Foreign Corrupt Practices Act, - Act, 15 U.S.C. �� 78dd-1 et seq. - ('FCPA') dos Estados Unidos da Am�rica do Norte (\"Regras Anticorrup��o\"), seja de forma direta ou indireta quanto ao objeto deste contrato, ou de outra forma que n�o relacionada a este contrato, devendo garantir, ainda, que seus prepostos e colaboradores ajam da mesma forma.<br>
                        </p>
                        <p>                            
                            <strong>XI- DA PROPRIEDADE INTELECTUAL</strong><br>
                        </p>
                        <p>                            
                            11.1 A CONTRATADA n�o utilizar�, exceto mediante pr�via autoriza��o, qualquer nome, marca, logotipo ou s�mbolo de propriedade da CONTRATANTE, nem far� qualquer declara��o ou refer�ncia que indique a exist�ncia de qualquer v�nculo ou rela��o contratual ou negocial entre as partes, exceto se referida declara��o ou refer�ncia for autorizada previamente, por escrito, pela CONTRATANTE.<br>
                        </p>
                        <p>                            
                            11.2 Toda utiliza��o da marca Contratante, pela Contratada, em propaganda, promo��o ou publicidade, somente poder� ser realizada com autoriza��o pr�via da Contratante e seu custo total ser� de responsabilidade da Contratada.<br>
                        </p>
                        <p>                            
                            11.3 Terminada a vig�ncia deste contrato, a Contratada cessar� imediatamente o uso de qualquer impresso, propaganda ou exibi��o de nomes e s�mbolos pertencentes a Contratante, sob pena de incorrer nas san��es civis e criminais previstas na legisla��o vigente.<br>
                        </p>
                        <p>
                            <strong>XII-AN�LISE DE DESEMPENHO</strong><br>
                        </p>
                        <p>                            
                            12.1 A CONTRATANTE poder� determinar crit�rios de avalia��o para medir a performance do CONTRATADO nos atendimentos prestados aos clientes e consumidores, estando seus respectivos produtos dentro, ou fora do per�odo de garantia.<br>
                        </p>
                        <p>                            
                            12.2 Caber� exclusivamente � CONTRATANTE a implanta��o, altera��o e divulga��o da metodologia de avalia��o atrav�s do sistema de gest�o de ordens de servi�o, concedendo � CONTRATADA um per�odo razo�vel de tempo para que se adeque ao cumprimento dos crit�rios m�nimos de performance determinados.<br>
                        </p>
                        <p>
                            12.3 Os resultados da avalia��o tornar�o a CONTRATADA eleg�vel a 1. gratifica��es, 2. a��es de desenvolvimento ou 3. descredenciamento (pelo n�o cumprimento dos crit�rios m�nimos de performance determinados pelo Contratante).
                        </p>
                        <p>
                            <strong>XIII-DISPOSI��ES GERAIS</strong><br>
                        </p>
                        <p>                            
                            13.1 Este Contrato/Acordo � firmado em car�ter irretrat�vel e irrevog�vel, configurando obriga��es legais, v�lidas e vinculantes para as Partes, seus cession�rios e sucessores a qualquer t�tulo, exequ�veis de conformidade com os seus respectivos termos.<br>
                        </p>
                        <p>                            
                            13.2 Sujeitas aos termos e condi��es deste Contrato/Acordo, as Partes agir�o de boa-f� e envidar�o seus melhores esfor�os em condi��es razo�veis para tomar ou fazer com que sejam tomadas todas as provid�ncias e realizados todos os atos necess�rios, adequados ou aconselh�veis para consumar e dar efic�cia aos entendimentos aqui contemplados.<br>
                        </p>
                        <p>                            
                            13.3 Os direitos e obriga��es decorrentes deste Contrato/Acordo somente poder�o ser cedidos por qualquer das Partes mediante a anu�ncia, por escrito, da outra Parte, exceto em caso de cess�o, pela Parte Reveladora, para (a) sociedades controladas; (b) sociedades controladoras; ou (c) sociedades sob controle comum ao da Parte Reveladora. Qualquer cess�o em viola��o ao disposto nesta Cl�usula ser� nula e ineficaz.<br> 
                        </p>
                        <p>                            
                            13.4 Caso qualquer disposi��o deste Contrato/Acordo seja considerada inv�lida, ineficaz, inexequ�vel ou nula, as demais disposi��es deste Contrato/Acordo dever�o permanecer v�lidas, eficazes e vigentes, hip�tese em que as Partes dever�o substituir a disposi��o inv�lida, inexequ�vel ou nula por uma disposi��o v�lida e exequ�vel que corresponda, tanto quanto poss�vel, ao esp�rito e objetivo da disposi��o substitu�da.<br>
                        </p>
                        <p>                            
                            13.5 Este Contrato/Acordo somente poder� ser alterado mediante instrumento assinado por todas as Partes e qualquer ren�ncia ou consentimento somente ser� v�lido se prestado por escrito.<br>
                        </p>
                        <p>                            
                            13.6 O fato de uma das Partes deixar de exigir a tempo o cumprimento de qualquer das disposi��es ou de quaisquer direitos relativos a este Contrato/Acordo ou n�o exercer quaisquer faculdades aqui previstas n�o ser� considerado uma ren�ncia a tais disposi��es, direitos ou faculdades, n�o constituir� nova��o e n�o afetar� de qualquer forma a validade deste Contrato/Acordo.<br>
                        </p>
                        <p>                            
                            13.7 Este contrato substitui quaisquer entendimentos, acordos ou ajustes, verbais ou escritos, anteriormente havidos entre as partes, os quais s�o considerados sem qualquer efeito, mesmo para fins e efeitos de interpreta��o da vontade das partes, passando a reger-se o relacionamento, direitos e obriga��es unicamente com as disposi��es aqui estabelecidas.<br>
                        </p>
                        <p>                            
                            13.8 As partes elegem por m�tuo consenso o foro da Comarca de Balne�rio Pi�arras/SC para dirimir quaisquer controv�rsias acaso oriundas deste pacto, com ren�ncia de qualquer outro por mais privilegiado que possa vir a ser.<br>
                        </p>
                        <p><br><br><br><br>                        
			 E, por estarem justos e pactuados, assinam o presente em 2 (duas) vias de igual teor e forma, na presen�a de 2 (duas) testemunhas, para que surta seus jur�dicos e legais efeitos.
			</p>
                        <p><br><br>
                        Balne�rio Pi�arras/SC, $dataAssinatura.
                        </p><br>
                        <table style='text-align:center;font-size:12px;'>
                            <tr>
                                <td style='text-align:left;'>
                                    <p><br><br><br>
                                        <strong>CONTRATANTE:</strong><br>
                                    </p><br><br><br><br>
                                </td>
                            </tr>                        
                            <tr>
                                <td>
                                    <strong>_______________________________________________________</strong><br>                                
                                    DIRETOR<br>                                
                                    <strong>JCS BRASIL ELETRODOMESTICOS S.A</strong><br>
                                    CNPJ n�.03.106.170/0002-24
                                </td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>
                                    <strong>_______________________________________________________</strong><br>                                
                                    DIRETOR<br>                                
                                    <strong>JCS BRASIL ELETRODOMESTICOS S.A</strong><br>
                                    CNPJ n�.03.106.170/0002-24
                                </td>
                            </tr>
                            <tr>
                                <td><br><br><br><br><br></td>
                            </tr>                        
                            <tr>
                                <td style='text-align:left;'>
                                    <p>
                                        <strong>CONTRATADA:</strong><br>
                                    </p><br><br><br><br>
                                </td>
                            </tr>                        
                            <tr>                               
                                <td>
                                    <strong>_______________________________________________________</strong><br>                                
                                    {$posto_nome}<br>                                                                    
                                    CNPJ n�. {$cnpj}<br>
                                    {$responsavel_social}<br>
                                    CPF n�. <strong>_______________________</strong>              
                                </td>
                            </tr>
                            <tr>
                                <td><br><br><br><br><br></td>
                            </tr>                            
                            <tr>                        
                                <td style='text-align:left;'>
                                    <p>
                                        <strong>TESTEMUNHAS:</strong><br>
                                    </p><br><br><br><br>
                                </td>
                            </tr>                        
                            <tr>                               
                                <td style='text-align:left;'>
                                    <strong>_______________________________________________________</strong><br>                                
                                    Nome: <br>                                                                                                        
                                    CPF:<br>
                                </td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>                                
                                <td style='text-align:left;'>
                                    <strong>_______________________________________________________</strong><br>                                
                                    Nome: <br>                                                                                                        
                                    CPF:<br>
                                </td>
                            </tr>
                        </table>
                    </body>
                </html>
            ";
        }

        if(in_array($fabrica, array(35, 147, 160))){
            if(in_array($fabrica, array(147, 160))){

                if($_GET["envia_contrato"] == "true"){

                    $caminho = "/var/www/assist/www/credenciamento/contrato/";
                    if (strtolower($_serverEnvironment) == 'development') {
                        $caminho = "https://novodevel.telecontrol.com.br/~breno/PosVendaAssist/credenciamento/contrato/";
                        if ($fabrica == 147) {
                            $caminho = "hitachi/contrato/";
                        } else {
                            $caminho = "einhell/contrato/";
                        }
                    }                    

                    if(file_exists($caminho."contrato_assistencia_tecnica_{$posto}.pdf")){
                        unlink($caminho."contrato_assistencia_tecnica_{$posto}.pdf");
                    }

                    include "../classes/mpdf61/mpdf.php";

                    $mpdf = new mPDF(); 
                    $mpdf->SetDisplayMode('fullpage');
                    $mpdf->charset_in = 'windows-1252';
                    $mpdf->WriteHTML($texto);

                    $mpdf->Output($caminho."contrato_assistencia_tecnica_{$posto}.pdf", "F");

                    /* Envia Email */

                    $to = $email;
                    $from = "rede.autorizada@telecontrol.com.br";
                    if ($fabrica == 147) {
                        $subject ="CONTRATO HITACHI";
                    } else {
                        $from = "backoffice@telecontrol.com.br";
                        $subject ="CONTRATO EINHELL";
                    }

                    $file1 = $caminho."contrato_assistencia_tecnica_{$posto}.pdf";             
                    $files = array($file1);                    

                    /* $message .= "
                        <center>
                            <div style='width:600px'>
                                <p align='justify'>
                                    <font face='Verdana, Arial, Helvetica, sans-serif' size='3'>
                                        Em parceria com a Telecontrol, estamos credenciando novas assist�ncias t�cnicas para ingressar em nossa rede.
                                        <br /><br />
                                        &bull; Os contratos e a gest�o da rede ser�o realizados diretamente com a Hitachi.
                                        <br /><br />
                                        <strong>
                                            <font color='red'>Obs.</font>
                                            Somente o termo de ades�o dever� ser preenchido, assinado e devolvido para a Telecontrol.
                                        </strong>
                                    </font>
                                </p>
                                <p align='left'>
                                    <font face='Verdana, Arial, Helvetica, sans-serif' size='3'>
                                        Envio feito atrav�s dos Correios, enviar para o endere�o: <br />
                                        Av. Carlos Art�ncio, 420-B CEP: 17.519-255 - Bairro Fragata Mar�lia, SP - Brasil
                                        <br /><br />
                                        Envio feito atrav�s de E-mail, enviar para: <br />
                                        rede.autorizada@telecontrol.com.br
                                    </font>
                                </p>
                                <p align='left'>
                                    <font face='Verdana, Arial, Helvetica, sans-serif' size='3'>
                                        Duvidas:
                                        <br />SAC Rede Autorizada: 0800-718-7825
                                        <br />E-mail: rede.autorizada@telecontrol.com.br
                                    </font>
                                </p>
                            </div>
                        </center>
                    "; */

                    $message .= "<div style='width:700px;height:990px;background:url(http://posvenda.telecontrol.com.br/assist/admin/imagens_admin/contrato.jpg) no-repeat;'>
                    <p style='padding-top:170px;margin-left:480px;font-weight:bold;'>Mar�lia, ".date(d)." de ".$array_meses[intval($mes)]." de ".date(Y).".</p>

                    <p style='font-size:16px;font-weight:bold;margin-left:50px;'>
                    Prezado Assistente T&eacute;cnico,
                    </p>
                    <br>
                    <p style='margin-left:50px;'>
                    N�s da TELECONTROL estamos muito satisfeitos em poder contar com essa parceria e temos certeza que nossos clientes ser�o sempre muito bem atendidos.
                    </p>
                    <p style='text-align:justify;margin-left:50px'>
                    Para realizar o primeiro acesso ao sistema acesse o site www.telecontrol.com.br e clique no link <b>\"Primeiro Acesso\"</b> logo abaixo dos campos de login e senha.
                    </p>

                    <p style='text-align:justify;margin-left:50px'>
                    Colocamos a disposi��o de voc�s toda nossa equipe de profissionais atrav�s dos telefones abaixo ou pelo endere�o eletr�nico suporte@telecontrol.com.br. Todos est�o devidamente treinados e preparados para atender a quaisquer d�vidas.
                    </p>

                    <p style='text-align:justify;font-weight:bold;margin-left:50px'>
                    (31) 4062-7401 MG | (41) 4063-9872 PR <br>
                    (48) 4052-8762 SC | (21) 4063-4180 RJ <br>
                    (54) 4062-9112 RS | (85) 4062-9872 CE <br>
                    (11) 4063-4230 SP | (47) 4052-9292 Indaial-SC <br>
                    (81) 4062-8384 PE | (71) 4062-8851 Salvador-BA <br>
                    (47) 4054-9474 SC | (46) 4055-9292 Pato Branco-PR <br>
                    </p>

                    <p style='text-align:justify;margin-left:50px'>
                    Estamos trabalhando intensamente para trazer empresas realmente comprometidas com a qualidade e respeito aos interesses comerciais de todos. Aguardem novidades!
                    </p>

                    <p style='text-align:justify;margin-left:50px'>
                    Pedimos gentilmente que providencie a assinatura do contrato em anexo, imprescind�vel para esta uni�o, remetendo-o para o seguinte endere�o:
                    </p>

                    <p style='margin-left:150px;text-align:justify;'>
                    <b>
                    TELECONTROL SISTEMA LTDA <br>
                    A/C Sr. Luis Carlos dos Santos Martins <br>
                    Av. Carlos Art�ncio, 420 A - Bairro Fragata C <br>
                    Marilia / SP <br>
                    CEP 17.519-255 <br>
                    </b>
                    </p>
                    <p style='margin-left:50px'>
                    Seja bem vindo a maior rede de postos autorizados do pa�s. Agradecemos mais uma vez a credibilidade e confian�a.
                    </p>
                    <br>

                    <span style='margin-left:618px'>Cordialmente</span><br><br>
                    <span style='margin-left:567px'><b>Equipe Telecontrol</b></span> </div>";

                    $headers = "From: $from";

                    $semi_rand = md5(time());
                    $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

                    $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";

                    $message = "This is a multi-part message in MIME format.\n\n" . "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"iso-8859-1\"\n" . "Content-Transfer-Encoding: 7bit\n\n" . $message . "\n\n";
                    $message .= "--{$mime_boundary}\n";

                    for($x=0;$x<count($files);$x++){

                        $file = fopen($files[$x],"rb");
                        $data = fread($file,filesize($files[$x]));

                        fclose($file);

                        $data = chunk_split(base64_encode($data));

                        $message .= "Content-Type: {\"application/octet-stream\"};\n" . " name=\"contrato.pdf\"\n" .
                        "Content-Disposition: attachment;\n" . " filename=\"contrato.pdf\"\n" .
                        "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
                        $message .= "--{$mime_boundary}\n";

                    }

                    if($_GET["btn_email"] == "true"){

                        echo (mail($to, $subject, $message, $headers)) ? "<script>window.close();</script>" : "Erro ao enviar e-mail para {$to}";
                        exit;

                    }else{
                    
                        if(mail($to, $subject, $message, $headers)){
                            exit(json_encode(array("ok" => "Contrato enviado com sucesso!")));
                        }else{
                            exit(json_encode(array("erro" => "Falha ao tentar enviar o email, verifique o email cadastrado e tente novamente!")));
                        }

                    }

                    /* Envia Email */

                }

                if($_GET["download_contrato"] == "true"){

                    include "../classes/mpdf61/mpdf.php";

                    $mpdf = new mPDF(); 
                    $mpdf->SetDisplayMode('fullpage');
                    $mpdf->charset_in = 'windows-1252';
                    $mpdf->WriteHTML($texto);

                    $mpdf->Output("contrato_assistencia_tecnica_{$posto}.pdf", "d");

                    exit;

                }

                echo "<div style='width: 820px; margin: 0 auto;'>";
                    echo $texto;
                

                    echo "
                    <div style='text-align: center; padding-top: 100px; padding-bottom: 100px;'>
                        <input type='button' value='Enviar E-mail' onclick='javascript: window.open(\"gera_contrato.php?posto={$posto}&fabrica={$fabrica}&cnpj={$cnpj}&envia_contrato=true&btn_email=true\");' > &nbsp; 
                        <input type='button' value='Download do Contrato' onclick='javascript: window.open(\"gera_contrato.php?posto={$posto}&fabrica={$fabrica}&cnpj={$cnpj}&download_contrato=true\");'  >
                    </div>
                    ";

                echo "</div>";

                exit;
            }

            if (in_array($fabrica, array(35))) {

                if($_GET["envia_contrato"] == "true"){

                    $caminho = "/var/www/assist/www/credenciamento/contrato/";
                    if (strtolower($_serverEnvironment) == 'development') {
                        $caminho = "contrato/";                        
                    }

                    if(file_exists($caminho."contrato_assistencia_tecnica_{$posto}.pdf")){
                        unlink($caminho."contrato_assistencia_tecnica_{$posto}.pdf");
                    }

                        
                        include "../classes/mpdf61/mpdf.php";

                        $mpdf = new mPDF(); 
                        $mpdf->SetDisplayMode('fullpage');
                        $mpdf->charset_in = 'windows-1252';
                        $mpdf->WriteHTML($texto);
                    if ( $_GET["download_contrato_ajax"] == "true") {
                        $mpdf->Output($caminho."contrato_assistencia_tecnica_{$posto}.pdf", "D");
                        //$mpdf->Output($caminho."contrato_assistencia_tecnica_{$posto}.pdf", "F");
                        //$mpdf->Output("contrato_assistencia_tecnica_{$posto}.pdf", "D");
                    } else {
                        $mpdf->Output($caminho."contrato_assistencia_tecnica_{$posto}.pdf", "F");
                    }

                    /* Envia Email */

                    $to = $email;
                    $from = "monica.camargo@newellco.com";
                    $subject ="CONTRATO DE CREDENCIAMENTO";

                    $file1 = $caminho."contrato_assistencia_tecnica_{$posto}.pdf";                     
                    $file2 = $caminho."cadence/valores_mao_de_obra.pdf";
                    $files = array($file1,$file2);

                    $data_a = new DateTime();
                    $data_atual = $data_a->format('d/m/Y');

                    $message .= "
                    <table>
                        <tr>
                            <td><img src='{$link_logo}'></td>
                        </tr>
                        <tr>
                            <td style='width: 500px; text-align:justify;'> 
                                <p style='font-size:16px;font-weight:bold;margin-left:10px;'>
                                Prezada Assist&ecirc;ncia T&eacute;cnica,
                                </p>
                                <br>
                                <p style='margin-left:10px;'>
                                Voc� est� recebendo o Contrato de Presta��o de Servi�os, juntamente com nossa tabela de m�o de obra.
                                </p>
                                <p style='text-align:justify;margin-left:10px'>
                                A partir desta data voc� tem um prazo de 10 dias �teis para nos retornar o arquivo digital, devidamente assinado e com firma reconhecida, conforme instru��es abaixo.
                                </p>
                                <p style='text-align:justify;margin-left:10px'>
                                A via original deve ser postada via correios, imediatamente ap�s o envio digital. O prazo m�ximo para recebimento da via original assinada e com firma reconhecida ser� de 30 dias a partir desta data.
                                </p><br>
                                <p style='font-size:16px;font-weight:bold;margin-left:10px;'>
                                Orienta&ccedil;&otilde;es de preenchimento e assinatura
                                </p> 
                                <p style='text-align:justify;margin-left:10px'>
                                1. Viste todas as p�ginas e assine a �ltima p�gina, informando o n� do CPF do Representante Legal devidamente identificado nos dados da Contratada.
                                </p>
                                <p style='text-align:justify;margin-left:10px'>
                                2. Reconhecer firma na assinatura de uma c�pia do contrato e enviar digitalizado (em um �nico arquivo), via chamado Help desk no Telecontrol.
                                </p>
                                <p style='text-align:justify;margin-left:10px'>
                                3. Coletar assinatura de uma (01) testemunha. N�o sendo necess�rio reconhecer firma da assinatura da testemunha. A outra testemunha ser� por parte da CONTRATANTE.
                                </p>
                                <br>
                                <div style='text-align:left'>                                
                                <span>$data_atual</span><br><br>
                                <span><b>JCS Brasil</b></span><br><br>
                                <span><img src='http://posvenda.telecontrol.com.br/assist/logos/oster_clientes.jpg' width='150' height='80'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img src='http://posvenda.telecontrol.com.br/assist/logos/cadence.png' width='150' height='80'></span><br><br>
                                </div>
                            </td>
                        </tr>
                </table>
                    ";

                    $headers = "From: $from";

                    $semi_rand = md5(time());
                    $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";

                    $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";

                    $message = "This is a multi-part message in MIME format.\n\n" . "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"iso-8859-1\"\n" . "Content-Transfer-Encoding: 7bit\n\n" . $message . "\n\n";
                    $message .= "--{$mime_boundary}\n";

                    for($x=0;$x<count($files);$x++){

                        $file = fopen($files[$x],"rb");
                        $data = fread($file,filesize($files[$x]));

                        fclose($file);

                        $data = chunk_split(base64_encode($data));

                        if($x == 0) {
                            $nome_arquivo = $posto_nome;
                        } else {
                            $nome_arquivo = "valores";
                        }


                        $message .= "Content-Type: {\"application/octet-stream\"};\n" . " name=\"{$nome_arquivo}.pdf\"\n" .
                        "Content-Disposition: attachment;\n" . " filename=\"{$nome_arquivo}.pdf\"\n" .
                        "Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";
                        $message .= "--{$mime_boundary}\n";

                    }

                    if($_GET["btn_email"] == "true"){
                        if ($_GET["download_contrato_ajax"] === "false") {
                            if (mail($to, $subject, $message, $headers)) {
                                $titulo_comunicado_contrato = "Contrato de Presta��o de Servi�os de Assist�ncia T�cnica";
                                $msg_comunicado_contrato = "J� est� dispon�vel nos informes administrativos o Contrato de Presta��o de Servi�os de Assist�ncia T�cnica. Este contrato assegura �s partes os direitos e obriga��es distintas, garantindo o fiel cumprimento do acordado.<br>Pedimos que fa�a o Download e leia atentamente as cl�usulas que regem nossa parceria.<br>Ap�s assinado, reconhe�a firma na assinatura do Representante Legal e anexe a c�pia do contrato no Sistema Telecontrol.<br>� necess�rio envio, via correios, de 2 vias com assinatura reconhecida.";

                                $insert = "INSERT INTO tbl_comunicado (
                                        fabrica,
                                        posto,
                                        obrigatorio_site,
                                        tipo,
                                        ativo,
                                        descricao,
                                        mensagem
                                    ) VALUES (
                                        {$fabrica},
                                        {$posto},
                                        true,
                                        'Com. Contrato Posto',
                                        true,
                                        '{$titulo_comunicado_contrato}',
                                        '{$msg_comunicado_contrato}'
                                    ) RETURNING comunicado";
                                $result = pg_query($con, $insert);

                                if (strlen(pg_last_error()) > 0 ) {
                                    $sql = "ROLLBACK TRANSACTION";
                                    $res = pg_query($con, $sql);

                                } else {
                                    $comunicado = pg_fetch_result($result, 0, comunicado);
                                    // Inserir o anexo no comunicado
                                    if (file_exists($file1)) {

                                        /*include_once '../class/aws/anexaS3.class.php';
                                        include_once S3CLASS;*/
                                        include_once __DIR__.'../../class/aws/s3_config.php';
                                        include_once S3CLASS;

                                        $s3 = new anexaS3('co', (int) $fabrica, $comunicado);
                                        $info_arquivo = pathinfo($file1);
                                        preg_match("/\.(od[tsp]|pdf|docx?|xlsx?|pptx?|pps|gif|bmp|png|jpe?g|rtf|txt|zip){1}$/i", $info_arquivo["extension"], $ext);

                                        $extensao_anexo = $ext[1];

                                        if ($extensao_anexo == 'jpeg') $extensao_anexo = 'jpg';

                                        $aux_extensao = strtolower("'$extensao_anexo'");

                                        if (is_object($s3)) {
                                            $s3->set_tipo_anexoS3('co');
                                            if (!$s3->uploadFileS3($comunicado, $file1, true)) {
                                                $msg_erro["msg"][] = $s3->_erro;
                                            } else {
                                                $sql =  "UPDATE tbl_comunicado
                                                            SET extensao   = $aux_extensao
                                                          WHERE comunicado = $comunicado
                                                            AND fabrica    = $fabrica";
                                                $res = pg_query ($con,$sql);

                                                if(strlen(pg_last_error()) > 0){
                                                    $msg_erro["msg"][] = pg_last_error($con);
                                                }
                                            }
                                        }
                                    }                                
                                }

                                if (count($msg_erro["msg"]) > 0) {
                                    $sql = "ROLLBACK TRANSACTION";
                                    $res = pg_query($con, $sql);
                                    //echo "Erro ao anexar contrato!";
                                    exit(json_encode(array("ok" => "Erro ao anexar contrato!")));
                                } else {
                                    $sql = "COMMIT TRANSACTION";
                                    $res = pg_query($con, $sql);
                                    //echo "<script>alert('Contrato enviado com sucesso!'); window.close();</script>";
                                    exit(json_encode(array("ok" => "Contrato enviado com sucesso!")));
                                }
                            } else {
                                //echo "Erro ao enviar e-mail para {$to}";
                                exit(json_encode(array("erro" => "Falha ao tentar enviar o email para {$to}!")));
                            }
                        }
                        exit;
                    }else{
                    
                        if(mail($to, $subject, $message, $headers)){
                            exit(json_encode(array("ok" => "Contrato enviado com sucesso!")));
                        }else{
                            exit(json_encode(array("erro" => "Falha ao tentar enviar o email, verifique o email cadastrado e tente novamente!")));
                        }

                    }
                }

                if($_GET["download_contrato"] == "true"){

                    include "../classes/mpdf61/mpdf.php";

                    $mpdf = new mPDF(); 
                    $mpdf->SetDisplayMode('fullpage');
                    $mpdf->charset_in = 'windows-1252';
                    $mpdf->WriteHTML($texto);

                    $mpdf->Output("contrato_assistencia_tecnica_{$posto}.pdf", "d");

                    exit;

                }

                echo "<div style='width: 820px; margin: 0 auto;'>";
                    echo $texto;

                    echo "
                    <div style='text-align: center; padding-top: 100px; padding-bottom: 100px;'>
                        <input type='button' id='enviar_email' value='Enviar E-mail' onclick='javascript: enviaEmailContrato({$posto},{$fabrica},\"$cnpj\");' > &nbsp; 
                        <input type='button' value='Download do Contrato' onclick='javascript: window.open(\"gera_contrato.php?posto={$posto}&fabrica={$fabrica}&cnpj={$cnpj}&download_contrato=true\");'  >
                    </div>
                    ";

                echo "</div>";
                ?>

                <link rel="stylesheet" type="text/css" href="../admin/js/jquery-ui-1.8rc3.custom.css">
                <script type="text/javascript" src="../admin/js/jquery-1.6.1.min.js"></script>
                <script type="text/javascript" src="../admin/js/jquery-ui-1.8.14.custom.min.js"></script>

                <script language='javascript' src='../admin/ajax.js'></script>
                
                <script type="text/javascript">
                    function enviaEmailContrato(posto,fabrica,cnpj){
                    //function enviaEmailContrato(){
                        $.ajax({
                            type:    'GET',
                            url:     "gera_contrato.php",
                            data:    {  'fabrica':fabrica,
                                        'posto':posto,
                                        'cnpj':cnpj,
                                        'envia_contrato':true,
                                        'btn_email':true,
                                        'download_contrato_ajax':false
                                    },
                            async:   false,
                            beforeSend: function(){
                                $('#enviar_email').val("Enviando...");
                                $('#enviar_email').attr('disabled', true);
                            },
                            complete: function(data) {
                                data = $.parseJSON(data.responseText);
                                console.log(data);
                                if(data.ok){
                                    alert(data.ok);
                                    //window.open("gera_contrato.php?posto="+posto+"&fabrica="+fabrica+"&cnpj="+cnpj+"&envia_contrato=true&btn_email=true");
                                }else{
                                    alert(data.erro);
                                }
                                $('#enviar_email').val("Enviar E-mail");
                                $('#enviar_email').attr('disabled', false);
                            }
                        });     
                    }
                </script>
                <?php

                exit;
            }
        }else{

            include "../classes/mpdf61/mpdf.php";

            $mpdf = new mPDF(); 
            $mpdf->SetDisplayMode('fullpage');
            $mpdf->charset_in = 'windows-1252';
            $mpdf->WriteHTML($texto);

            $mpdf->Output("contrato_assistencia_tecnica_{$posto}.pdf", "d");

        }

        // echo $texto;

    }

    exit;

}

$array_meses = array('','Janeiro','Fevereiro','Mar�o','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro');
$mes = date('m');

echo "&nbsp;";
$texto = "<div style='width:700px;height:990px;background:url(http://posvenda.telecontrol.com.br/assist/admin/imagens_admin/contrato.jpg) no-repeat;'>
<p style='padding-top:170px;margin-left:480px;font-weight:bold;'>Mar�lia, ".date(d)." de ".$array_meses[intval($mes)]." de ".date(Y).".</p>

<p style='font-size:16px;font-weight:bold;margin-left:50px;'>
Prezado Assistente T&eacute;cnico,
</p>
<br>
<p style='margin-left:50px;'>
N�s da TELECONTROL estamos muito satisfeitos em poder contar com essa parceria e temos certeza que nossos clientes ser�o sempre muito bem atendidos.
</p>
<p style='text-align:justify;margin-left:50px'>
Para realizar o primeiro acesso ao sistema acesse o site www.telecontrol.com.br e clique no link <b>\"Primeiro Acesso\"</b> logo abaixo dos campos de login e senha.
</p>

<p style='text-align:justify;margin-left:50px'>
Colocamos a disposi��o de voc�s toda nossa equipe de profissionais atrav�s dos telefones abaixo ou pelo endere�o eletr�nico suporte@telecontrol.com.br. Todos est�o devidamente treinados e preparados para atender a quaisquer d�vidas.
</p>

<p style='text-align:justify;font-weight:bold;margin-left:50px'>
(31) 4062-7401 MG | (41) 4063-9872 PR <br>
(48) 4052-8762 SC | (21) 4063-4180 RJ <br>
(54) 4062-9112 RS | (85) 4062-9872 CE <br>
(11) 4063-4230 SP | (47) 4052-9292 Indaial-SC <br>
(81) 4062-8384 PE | (71) 4062-8851 Salvador-BA <br>
(47) 4054-9474 SC | (46) 4055-9292 Pato Branco-PR <br>
</p>

<p style='text-align:justify;margin-left:50px'>
Estamos trabalhando intensamente para trazer empresas realmente comprometidas com a qualidade e respeito aos interesses comerciais de todos. Aguardem novidades!
</p>

<p style='text-align:justify;margin-left:50px'>
Pedimos gentilmente que providencie a assinatura do contrato em anexo, imprescind�vel para esta uni�o, remetendo-o para o seguinte endere�o:
</p>

<p style='margin-left:150px;text-align:justify;'>
<b>
TELECONTROL SISTEMA LTDA <br>
A/C Sr. Luis Carlos dos Santos Martins <br>
Av. Carlos Art�ncio, 420 A - Bairro Fragata C <br>
Marilia / SP <br>
CEP 17.519-255 <br>
</b>
</p>
<p style='margin-left:50px'>
Seja bem vindo a maior rede de postos autorizados do pa�s. Agradecemos mais uma vez a credibilidade e confian�a.
</p>
<br>

<span style='margin-left:618px'>Cordialmente</span><br><br>
<span style='margin-left:567px'><b>Equipe Telecontrol</b></span> </div>";
?>
<html> 
<head> 

<title>Gerar contrato</title> 
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"> 
<style type="text/css"> 
.Titulo {
    text-align: center;
    font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
    font-size: 18px;
    font-weight: bold;
    color: #FFFFFF;
    background-color: #596D9B;
}
.Conteudo {
    font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
    font-size: 14px;
    font-weight: normal;
}

.msg_erro{
    background-color:#FF0000;
    font: bold 14px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.sucesso{
    background-color:green;
    font: bold 16px "Arial";
    color:#FFFFFF;
    text-align:center;
}

.no-print{
    display:none !important;
}
</style> 


<link rel="stylesheet" type="text/css" href="../plugins/shadowbox/shadowbox.css" media="all">
<link rel="stylesheet" type="text/css" href="../admin/js/jquery-ui-1.8rc3.custom.css">
<script type="text/javascript" src="../admin/js/jquery-1.6.1.min.js"></script>
<script type="text/javascript" src="../admin/js/jquery-ui-1.8.14.custom.min.js"></script>
<script src="../plugins/shadowbox/shadowbox.js" type="text/javascript"></script>

<script language='javascript' src='../admin/ajax.js'></script>
<script type='text/javascript' src='../admin/js/fckeditor/fckeditor.js'></script>
<script type="text/javascript">

    $(document).ready(function() {
        Shadowbox.init();
    });

    function emailPreview(){
        var mensagem = FCKeditorAPI.__Instances.mensagem.GetData();
        var corpo = "<table border='0' width='730px' height='990px' style='background:url(../admin/imagens_admin/contrato.jpg);background-repeat:no-repeat;font-size:14px;'><tr><td style='padding: 150px 20px 0px 50px;text-align:justify' valign='top'>"+mensagem+"<td></tr></table><br><center><input type='button' value='Enviar' onclick='enviaEmail();Shadowbox.close();'></center>";          
        Shadowbox.open({ 
            content :   corpo,
            player  :   "html",
            title   :   "Email",
            width   :   1290,
            height  :   990 
        });
    }

    window.onload = function(){
        var oFCKeditor = new FCKeditor( 'mensagem', 730,990 ) ;
        oFCKeditor.BasePath = "../admin/js/fckeditor/" ;
        oFCKeditor.ToolbarSet = 'Chamado' ;
        oFCKeditor.ReplaceTextarea();       
    }

    function imprimir(){    
        $("#erro").attr("style","display:none");
        $("#download").attr("style","display:none");
        window.print();
    }

    function mensagemEmail(){
        $("#mensagem_email").attr("style","display:block");
    }

    function enviaEmail(){
        var mensagem    = FCKeditorAPI.__Instances.mensagem.GetData();
        var fabrica     = $("#fabrica").val();
        var email       = $("#email_dest").val();
        var tipo        = $("#tipo").val();
                
        $.ajax({
            type:    'POST',
            url:     'contrato/envio_email_anexo.php',
            data:    {'fabrica':fabrica,'email':email,'tipo':tipo,'mensagem':mensagem},
            async:   false,
            success: function(data) {
                var retorno = data.split('|');
                $("#mensagem_email").attr("style","display:none");
                if(retorno[0] == "OK"){
                    $("#erro").addClass("sucesso");
                    $("#erro").html(retorno[1]);
                    $("#erro").show("fade", {}, "slow").delay(3000).hide("fade", {}, "slow");
                }else{
                    $("#erro").addClass("msg_erro");
                    $("#erro").html(retorno[1]);
                    $("#erro").attr('style','display:block');
                }
            }
        });     
    }
</script>

</head> 

<form action='<?=$PHP_SELF?>' method="post" enctype="multipart/form-data" name="email" <?php if($_GET['fabrica']) echo "class='no-print'";?>> 
<table width='650' border='0' align='center' cellpadding='3' cellspacing='1' style='font-family: verdana; font-size: 20px' bgcolor='#FFFFFF'>
<caption nowrap class='Titulo'>Gerar o contrato para F�brica</caption>
<tr><td align='center' colspan='2'><font color='red'>Preenche o campo email ou CNPJ para gerar o contrato</font></td></tr>
<tr><td align='center' class='Conteudo'>Digitar email</td>
<td>
<input type=text name=email value='<? echo $email; ?>'>
</td>
</tr>
<tr><td align='center' class='Conteudo'>Digitar cnpj</td>
<td>
<input type=text name=cnpj value='<?echo $cnpj; ?>'>
</td>
</tr>
<tr>
<td align='center' class='Conteudo'>F�brica
</td>
<td class='Conteudo'>
Crown<input type='radio' name=fabrica value='47' <?if(strlen($fabrica)==0 or $fabrica==47) echo "checked";?>>
Hbflex<input type='radio' name=fabrica value='25' <?if($fabrica==25) echo "checked";?>>
Telecontrol<input type='radio' name=fabrica value='10' <?if($fabrica==10) echo "checked";?>>

Cobimex<input type='radio' name=fabrica value='114' <?if(strlen($fabrica)==0 or $fabrica==114) echo "checked";?>>
Wurth<input type='radio' name=fabrica value='122' <?if(strlen($fabrica)==0 or $fabrica==122) echo "checked";?>>
Positec<input type='radio' name=fabrica value='122' <?if(strlen($fabrica)==0 or $fabrica==123) echo "checked";?>>
Saint-Gobain<input type='radio' name=fabrica value='122' <?if(strlen($fabrica)==0 or $fabrica==125) echo "checked";?>>
Unilever<input type='radio' name=fabrica value='122' <?if(strlen($fabrica)==0 or $fabrica==128) echo "checked";?>>
Ello<input type='radio' name=fabrica value='122' <?if(strlen($fabrica)==0 or $fabrica==136) echo "checked";?>>
</td>
</tr>
<tr>
<td align='center' class='Conteudo'>
Tipo do arquivo</td>
<td class='Conteudo'>
PDF<input type='radio' name=tipo_arquivo value='pdf' <?if(strlen($tipo_arquivo)==0 or $tipo_arquivo=='pdf')  echo "checked"; ?>>
DOC<input type='radio' name=tipo_arquivo value='doc' <?if ($tipo_arquivo=='doc') echo "checked";?>>
</td>
</tr>
<tr>
<td align='center' class='Conteudo' colspan='2'><input type="submit" name="btn_acao" value="Gerar o contrato"></td> 
</tr> 
</table> 
</form> 
</html> 

<?
$btn_acao=($_POST['btn_acao']) ? $_POST['btn_acao'] : $_GET['btn_acao'];

if(strlen($btn_acao)>0){
    $email=strtolower(trim($_POST['email']));
    $fabrica      = ($_POST['fabrica']) ? $_POST['fabrica'] : $_GET['fabrica'];
    $tipo_arquivo = ($_POST['tipo_arquivo']) ? $_POST['tipo_arquivo'] : $_GET['tipo_arquivo'];
    $cnpj = ($_POST['cnpj']) ? trim($_POST['cnpj']) : $_GET['cnpj'];
    $cnpj = str_replace("-","",$cnpj);
    $cnpj = str_replace(".","",$cnpj);
    $cnpj = str_replace(" ","",$cnpj);
    $cnpj = str_replace("/","",$cnpj);
    $cnpj = substr($cnpj,0,14);

    if(strlen($tipo_arquivo)==0){
        $msg="SELECIONE o tipo do arquivo";
    }
    if(strlen($fabrica)==0){
        $msg="Selecione a f�brica a ser gerado o contrato.";
    }
    if(strlen($email) >0 and strlen($msg)==0){
        $sql_cond="( email = '$email' or contato_email = '$email' )";
    }
    if(strlen($cnpj) >0){
        $sql_cond=" cnpj='$cnpj'";
    }

    if(strlen($cnpj)>0 and strlen($email)>0){
        $msg="Preenche um dos campos de email ou cnpj apenas para fazer a pesquisa";
    }

    if(strlen($msg)==0){
        $sql = "SELECT DISTINCT  tbl_posto.posto,
                    upper(tbl_posto.nome)   as nome   ,
                    tbl_posto_fabrica.contato_endereco AS endereco       ,
                    tbl_posto_fabrica.contato_numero AS numero         ,
                    tbl_posto_fabrica.contato_complemento AS complemento    ,
                    tbl_posto_fabrica.contato_cidade AS cidade         ,
                    tbl_posto_fabrica.contato_estado AS estado         ,
                    SUBSTR (tbl_posto_fabrica.contato_cep,1,2) || '.' || SUBSTR (tbl_posto_fabrica.contato_cep,3,3) || '-' || SUBSTR (tbl_posto_fabrica.contato_cep,6,3) AS cep ,
                    SUBSTR (tbl_posto.cnpj,1,2) || '.' || SUBSTR (tbl_posto.cnpj,3,3) || '.' || SUBSTR (tbl_posto.cnpj,6,3) || '/' || SUBSTR (tbl_posto.cnpj,9,4) || '-' || SUBSTR (tbl_posto.cnpj,13,2) AS cnpj     ,
                    tbl_posto.posto          ,
                    to_char(current_date,'DD') || ' de ' || to_char(current_date,'Month') || ' de ' || to_char(current_date,'YYYY') as data_contrato,
                    tbl_posto_fabrica.contato_email AS email
                FROM tbl_posto 
                JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $fabrica
                WHERE $sql_cond limit 1 ; ";

        $res = pg_exec($con,$sql);

        echo "<table border='1'>";
        if(pg_numrows($res) > 0){


            $key = md5($fabrica);
            $posto_nome     = pg_result($res,0,nome);
            $endereco       = pg_result($res,0,endereco);
            $numero         = pg_result($res,0,numero);
            $complemento    = pg_result($res,0,complemento);
            $cidade         = pg_result($res,0,cidade);
            $estado         = pg_result($res,0,estado);
            $cep            = pg_result($res,0,cep);
            $cnpj           = pg_result($res,0,cnpj);
            $posto          = pg_result($res,0,posto);
            $data_contrato  = pg_result($res,0,data_contrato);
            $data_contrato  = str_replace('January','Janeiro',$data_contrato);
            $email          = pg_result($res,0,email);
            $id = $posto;
            if($fabrica==25){
            $conteudo .="
            <div class=Section1>

            <p class=MsoNormal align=center style='text-align:center;mso-line-height-alt:
            10.0pt'><b>CONTRATO DE CREDENCIAMENTO DE ASSIST�NCIA T�CNICA<o:p></o:p></b></p>

            <p class=MsoNormal style='mso-line-height-alt:8.0pt'><o:p>&nbsp;</o:p></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'>Pelo
            presente instrumento particular,</p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><o:p>&nbsp;</o:p></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b>HB
            ASSIST�NCIA T�CNICA LTDA</b>., sociedade empresarial com escrit�rio
            administrativo na Av. Yojiro Takaoka, 4.384 - Loja 17 - Conj. 2083 - Alphaville
            - Santana de Parna�ba, SP, CEP 06.541-038, inscrita no CNPJ sob n�
            08.326.458/0001-47, neste ato representada por seu diretor ao final assinado,
            doravante denominada<span style='mso-spacerun:yes'>�
            </span>&quot;HB-TECH&quot;, e</p>


            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b>$posto_nome.</b>, sociedade empresarial com sede na $endereco,
            $numero $complemento, na cidade de $cidade, $estado, CEP $cep, inscrita no CNPJ sob n�
            $cnpj, neste ato representada por seu administrador, ao final
            assinado, doravante denominada &quot;AUTORIZADA&quot;,</p>


            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b
            style='mso-bidi-font-weight:normal'><span style='mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Considerando que:<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:35.45pt;text-align:justify;mso-line-height-alt:
            10.0pt;mso-pagination:none'><b><span style='mso-fareast-language:\#00FF;
            mso-bidi-language:#00FF'>(i) </span></b><span style='mso-fareast-language:\#00FF;
            mso-bidi-language:#00FF'><span style='mso-tab-count:1'>������ </span>a HBTECH desenvolveu 
            uma metodologia comercial e novo neg�cio atrav�s da marca HBFLEX, ou HBTECH, dentre outras 
            poss�veis, sob a qual vender� produtos com componentes el�tricos, eletr�nicos e mec�nicos;<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><b><span style='mso-fareast-language:#00FF;mso-bidi-language:#00FF'>(ii)
            </span></b><span style='mso-fareast-language:#00FF;mso-bidi-language:#00FF'><span
            style='mso-tab-count:1'>����� </span>a AUTORIZADA declara expressamente que possui 
            conhecimento, habilidade, tecnologia e know how de manuten��o e assist�ncia t�cnica 
            destes produtos;<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><b style='mso-bidi-font-weight:normal'><span style='mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'>(iii) </span></b><span style='mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'><span style='mso-tab-count:1'>����� </span>a linha de produtos estar� sempre 
            definida no Sistema &quot;Assist Telecontrol&quot;, conforme a especialidade da AUTORIZADA;<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><b style='mso-bidi-font-weight:normal'><span style='mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'>(iv) </span></b><span style='mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'><span style='mso-tab-count:1'>����� </span>a HBTECH, sempre que julgar conveniente e nos termos 
            de suas pol�ticas estrat�gicas, poder� introduzir novos produtos no mercado. A AUTORIZADA 
            dever� optar ou n�o pelo cadastramento para atendimento no Sistema &quot;Assist Telecontrol&quot;;<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><b style='mso-bidi-font-weight:normal'><span style='mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'>(v) </span></b><span style='mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'><span style='mso-tab-count:1'>����� </span>havendo Ordens de Servi�os cadastradas pela AUTORIZADA 
            no Sistema &quot;Assist Telecontrol&quot;, esta assume todas as responsabilidades 
            descritas neste contrato da respectiva linha de produtos;<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'>t�m
            entre si, justo e contratado, o seguinte:</p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>1.1. O objetivo do presente contrato � a presta��o,
            pela AUTORIZADA, em sua sede social, do servi�o de assist�ncia t�cnica aos
            produtos comercializados pela HB-TECH, cuja rela��o consta na tabela de m�o de
            obra, fornecida em anexo e faz parte integrante deste contrato.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>1.2. Os servi�os que ser�o prestados pela AUTORIZADA,
            junto aos clientes usu�rios dos produtos comercializados atrav�s da HB-TECH
            consistem em manuten��o corretiva e preventiva, seja atrav�s de repara��es a
            domicilio cujos custos ser�o por conta do consumidor, ou em sua oficina, quando
            os custos ser�o cobertos pela HB-TECH atrav�s de taxas de garantia.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>1.3. A HB-TECH</span><span style='mso-fareast-font-family:
            'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'> fornecer� � AUTORIZADA todos os elementos necess�rios
            e indispens�veis � boa presta��o dos servi�os em alus�o, desde que sejam de sua
            responsabilidade, especialmente no tocante � qualifica��es e especifica��es
            t�cnicas dos produtos, quando for o caso, tudo previamente autorizado (p.ex.
            desenhos t�cnicos, pe�as de reposi��o para produtos em garantia, treinamento,
            quando necess�rios, dentre outras hip�teses) .<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>2- DA EXECU��O DOS SERVI�OS DURANTE A GARANTIA<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>2.1. O prazo e condi��es de garantia dos produtos
            comercializados pela HB-TECH, s�o especificados no certificado de garantia,
            cujo in�cio � contado a partir da data emiss�o da nota fiscal de compra do
            produto pelo primeiro usu�rio.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>2.2. Se no per�odo de garantia os equipamentos
            apresentarem defeitos de fabrica��o, a AUTORIZADA providenciar� o reparo
            utilizando exclusivamente pe�as originais sem qualquer �nus.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>2.3. Para atendimento <st1:PersonName
            ProductID='em garantia a AUTORIZADA' w:st='on'>em garantia a AUTORIZADA</st1:PersonName>
            exigir�, do cliente usu�rio, a apresenta��o da NOTA FISCAL DE COMPRA.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>2.4. A ordem de servi�o utilizada pela AUTORIZADA para
            consumidores, dever� ser individual e conter:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>- N�MERO DE S�RIE<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>- DATA DA CHEGADA NA AUTORIZADA<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>- N�MERO DA NOTA FISCAL<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>- DATA DA COMPRA<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>- NOME DO CLIENTE<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>- NOME DO REVENDEDOR - TELEFONE.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>- COMPONENTES TROCADOS<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>- ENDERE�O COMPLETO DO CLIENTE<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>- MODELO DO EQUIPAMENTO<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>- DATA DA RETIRADA DO APARELHO<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>-DEFEITO CONSTATADO DE ACORDO COM TABELA FORNECIDA
            PARA TAL.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>3- PRE�O E CONDI��ES DE PAGAMENTO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>3.1. Para consertos efetuados em aparelhos no per�odo
            de garantia, a HB-TECH pagar� � AUTORIZADA os valores de taxas de acordo com a
            tabela fornecida em anexo, a qual faz parte integrante deste contrato.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>3.2. O pagamento dos servi�os prestados em garantia
            ser� efetuado da seguinte forma:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>A) A AUTORIZADA dever� encaminhar at� o dia 07 (sete)
            de cada m�s subseq�ente ao atendimento: <o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><span style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'>(i) Ordens de servi�o individuais devidamente
            preenchidas (item 4.7), ACOMPANHADAS DAS RESPECTIVAS C�PIAS DA N.F. DE VENDA AO
            CONSUMIDOR.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><span style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'>(ii) Ordens de servi�o coletivas devidamente
            preenchidas ACOMPANHADAS DAS RESPECTIVAS C�PIAS DAS NOTAS FISCAIS DE ENTRADA E SA�DA.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>B) Depois de efetuado o c�lculo pela HB-TECH, ser�
            solicitada a Nota fiscal de servi�os, (original) emitida contra:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'>HB ASSIST�NCIA T�CNICA LTDA.</p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'>Av. Yojiro Takaoka, 4.384 - Loja 17 - Conj. 2083 - Alphaville - </p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'>Santana de Parna�ba, SP, CEP 06.541-038</p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'>CNPJ 08.326.458/0001-47</p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>E DEVER� ENVIAR A MESMA PARA O ENDERE�O ABAIXO:<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'>HB ASSIST�NCIA T�CNICA LTDA.</p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'>Av. Yojiro Takaoka, 4.384 - Loja 17 - Conj. 2083 - Alphaville - </p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'>Santana de Parna�ba, SP, CEP 06.541-038</p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>C) A nota fiscal dever� estar na filial HB-TECH at� o
            �ltimo �til dia do m�s em curso e discriminar no corpo da mesma o seguinte:
            \"SERVI�OS PRESTADOS <st1:PersonName
            ProductID='EM APARELHOS DE SUA COMERCIALIZA��O' w:st='on'>EM APARELHOS DE SUA
             COMERCIALIZA��O</st1:PersonName>, SOB GARANTIA DURANTE O M�S DE\" (AS NOTAS
            FISCAIS RECEBIDAS AP�S 90 (NOVENTA) DIAS N�O SER�O PAGAS).<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>D) De posse da documenta��o a HB-TECH far� confer�ncia
            para averiguar poss�veis distor��es:<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><span style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'>(i) Pagamento das taxas de garantia ser�
            efetuado no quinto dia �til do m�s subseq�ente, para as NF recebidas at� o
            �ltimo dia �til do m�s anterior, em forma de cr�dito em conta corrente da
            pessoa jur�dica. Qualquer altera��o na conta corrente do servi�o autorizado deve
            ser comunicado previamente � HB-TECH.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><span style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'>(ii) HB-TECH reserva-se o direito de efetuar
            dedu��es de d�bitos pendentes, duplicatas, despesas banc�rias e de protesto referentes
            a t�tulos n�o quitados, ordens de servi�o irregulares, pe�as trocadas em
            garantia e n�o devolvidas no prazo m�ximo de 60 (sessenta) dias, sem pr�via
            consulta ou permiss�o da AUTORIZADA.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>E) Valores inferiores a R\$ 20,00 (vinte Reais), ser�o
            acumulados at� o pr�ximo cr�dito e assim sucessivamente, at� que o valor
            acumulado ultrapasse o disposto nesta cl�usula.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>(i) Apenas ser�o aceitas ordens de servi�o do mesmo
            cliente cujo prazo entre atendimentos, para o mesmo defeito, for superior a 60
            (sessenta) dias, ap�s a retirada do produto.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><span style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'>(ii) Ordens de servi�o incompletas n�o ser�o
            aceitas.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><span style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'>(iii) A HB-TECH n�o se responsabiliza por
            atrasos de pagamento cuja causa seja de responsabilidade da AUTORIZADA.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>F) O PRAZO M�XIMO QUE A AUTORIZADA PODER� RETER AS
            ORDENS DE SERVI�O, AP�S A SA�DA DO PRODUTO, DE SUA EMPRESA, SER� DE 90 DIAS,
            EXCETUANDO-SE O M�S DESSA SA�DA. AP�S ESSE PRAZO, AS ORDENS DE SERVI�O NELE
            ENQUADRADAS PERDER�O O DIREITO AO CR�DITO DE TAXAS DE GARANTIA.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>G) A AUTORIZADA enviar�, quando solicitado, os
            componentes substitu�dos em garantia, devidamente identificados com as
            etiquetas fornecidas pela HB-TECH, para que seja efetuada a inspe��o do
            controle de qualidade e a devida reposi��o quando for o caso. O frete desta
            opera��o ser� por conta da HB-TECH.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>H) Os comprovantes de pagamento de sedex, quando
            antecipados pela AUTORIZADA, dever�o ser enviados � HB-TECH, juntamente com o
            movimento de O. S., em prazo n�o superior a 90 dias da data da emiss�o do
            mesmo. Comprovantes recebidos ap�s o per�odo retro citado n�o ser�o
            reembolsados.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>4 - DURA��O DO CONTRATO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>4.1. A validade do presente contrato � por tempo
            indeterminado e poder� ser rescindido por qualquer das partes, mediante um
            aviso pr�vio de 30 (trinta) dias, por escrito e protocolado. A autorizada
            obriga-se, neste prazo do aviso, a dar continuidade aos atendimentos dos
            produtos em seu poder.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>4.2. O cancelamento deste contrato com fulcro na
            cl�usula anterior n�o dar� direito a nenhuma das partes a indeniza��o, cr�dito
            ou reembolso, seja a que t�tulo, forma ou hip�tese for.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>4.3. O contrato ser� imediatamente rescindido caso
            seja constatada e comprovada irregularidade na cobran�a dos servi�os e pe�as
            prestados em equipamentos sob garantia da HB-TECH, transfer�ncia da empresa
            para novos s�cios, mudan�a de endere�o para �rea fora do interesse da HB-TECH,
            concordata, fal�ncia, liquida��o judicial ou extrajudicial.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>4.4. Observada qualquer situa��o prevista nesta
            cl�usula, o representante indicado pela HB-TECH ter� plena autonomia para
            interceder junto � AUTORIZADA, no sentido de recolher incontinenti, as
            documenta��es, materiais, luminosos e tudo aquilo que de qualquer forma, for de
            origem, relacionar ou pertencer ao patrim�nio da HB-TECH e em perfeito estado
            de conserva��o e uso, sob pena de submeter a ent�o AUTORIZADA ao processo de
            indeniza��o na forma da lei.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>4.5. No caso de rescis�o contratual, a AUTORIZADA se
            obriga a devolver � HB-TECH toda documenta��o t�cnica e administrativa cedida
            para seu uso enquanto CREDENCIADA.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>4.6. Fica expressamente estipulado que este contrato
            n�o cria, sob hip�tese alguma, vinculo empregat�cio, direitos ou obriga��es
            previdenci�rias ou secund�rias entre as partes, ficando a cargo exclusivo da
            AUTORIZADA todos impostos taxas e encargos de qualquer natureza, incidentes
            sobre suas atividades.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>5 - �REA DE ATUA��O DA AUTORIZADA<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>5.1. A presta��o de servi�os ser� exercida pela
            AUTORIZADA na �rea que lhe for destinada, cujos limites poder�o ser modificados
            com o tempo, desde que tal medida se fa�a necess�ria para melhorar o
            atendimento aos consumidores de aparelhos comercializados pela HB-TECH.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>6 - MARCAS E PROPRIEDADE INDUSTRIAL<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'>6.1.
            As marcas, s�mbolos, nomes, identifica��o visual e direitos autorais que s�o de
            titularidade exclusiva da HB-TECH dever�o ser preservados, sendo que a
            AUTORIZADA reconhece e aceita a propriedade das mesmas, comprometendo-se e
            obrigando-se a preservar todas as suas caracter�sticas e reputa��o.</p>

            <p class=MsoBodyTextIndent style='margin-left:0cm;mso-line-height-alt:8.0pt'><span
            style='font-family: Times New Roman'>6.2. A reputa��o das marcas e produtos da
            HB-TECH dever�o ser preservadas, <u>constituindo-se infra��o grav�ssima ao
            presente contrato, bem como � legisla��o de propriedade industrial e penal
            brasileira vigente</u>, a ofensa � integridade, qualidade, conformidade,
            estabilidade e reputa��o, dentre outros quesitos, por parte da AUTORIZADA, seus
            s�cios e/ou funcion�rios e colaboradores.<o:p></o:p></span></p>

            <p class=MsoBodyTextIndent style='margin-left:0cm;mso-line-height-alt:8.0pt'><span
            style='font-family: Times New Roman'>6.2.1. Considera-se, igualmente, como
            infra��es nos termos do item 6.2. acima, difama��es e outras pr�ticas
            envolvendo marcas e produtos da HB-TECH por parte<span
            style='mso-spacerun:yes'>� </span>da AUTORIZADA, seus s�cios e/ou funcion�rios
            e colaboradores, seja perante outras autorizadas, outros fabricantes,
            representantes e inclusive, o p�blico consumidor. <o:p></o:p></span></p>

            <p class=MsoBodyTextIndent style='margin-left:0cm;mso-line-height-alt:8.0pt'><span
            style='font-family: Times New Roman'>6.2.2. Nestes termos do item 6.2.1. A
            HB-TECH poder� ter consultores de campo e auditores para averiguar e apurar
            eventuais irregularidades, enviando aos postos autorizados profissionais com ou
            sem identifica��o, que ser�o posteriormente alocados como testemunhas para
            todos os efeitos civis e criminais.<o:p></o:p></span></p>

            <p class=MsoBodyTextIndent style='margin-left:0cm;mso-line-height-alt:8.0pt'><span
            style='font-family: Times New Roman'>6.3. <span style='mso-tab-count:1'>���� </span>Os
            sinais distintivos da HB-TECH n�o poder�o ser livremente utilizados pela
            AUTORIZADA, mas t�o somente no que diga respeito, estritamente, ao desempenho
            de suas atividades aqui ajustadas. <o:p></o:p></span></p>

            <p class=MsoBodyTextIndent style='margin-left:0cm;mso-line-height-alt:8.0pt'><span
            style='font-family: Times New Roman'>6.4. As marcas, desenhos ou quaisquer
            sinais distintivos n�o poder�o sofrer qualquer altera��o da AUTORIZADA,
            inclusive quanto a cores, propor��es dos tra�os, sonoridade etc.<o:p></o:p></span></p>

            <p class=MsoBodyTextIndent style='margin-left:0cm;mso-line-height-alt:8.0pt'><span
            style='font-family: Times New Roman'>6.5. � vedado o uso de qualquer sinal
            distintivo ou refer�ncia ao nome da HB-TECH quando n�o expressamente autorizado
            ou determinado por esta �ltima. <o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'>6.6.
            Al�m das obriga��es j� assumidas, a AUTORIZADA se compromete e se obriga,
            durante o prazo do presente Contrato, e mesmo ap�s seu t�rmino ou rescis�o, a:
            (i) n�o utilizar, manusear ou possuir de qualquer forma, direta ou
            indiretamente, a marca, ou qualquer outro termo, express�o ou s�mbolo com o
            mesmo significado, que seja semelhante, ou que possa confundir o consumidor com
            as marcas da HBTECH; (ii) n�o utilizar a marca como parte da raz�o social de
            qualquer empresa que detenha qualquer participa��o, atualmente ou no futuro,
            ainda que como nome fantasia, no Cadastro Nacional de Pessoas Jur�dicas - CNPJ
            - do Minist�rio da Fazenda - Secretaria da Receita Federal; (iii)<span
            style='mso-tab-count:1'>������� </span>n�o registrar ou tentar registrar marca
            id�ntica ou semelhante, quer direta ou indiretamente, seja no Brasil ou <st1:PersonName
            ProductID='em qualquer outro Pa�s' w:st='on'>em qualquer outro Pa�s</st1:PersonName>
            ou territ�rio.</p>

            <p class=MsoBodyTextIndent style='margin-left:0cm;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='font-family: Times New Roman;mso-bidi-font-family:
            Arial'>6.7.<b> </b><span style='mso-bidi-font-weight:bold'><span
            style='mso-tab-count:1'></span></span></span><span style='font-family:
            Times New Roman'>Igualmente integram as obriga��es assumidas pela AUTORIZADA
            todas as obriga��es de sigilo, confidencialidade, n�o transmiss�o, cess�o ou
            outras formas de prote��o da tecnologia, <i>know-how</i>, desenvolvimentos, </span><span
            style='font-family: Times New Roman ;mso-fareast-language:#00FF;mso-bidi-language:
            #00FF'>desenhos t�cnicos, dados t�cnicos da HB-TECH. Nestas obriga��es
            incluem-se todas as prote��es da legisla��o brasileira vigente, especialmente
            as da Lei de Propriedade Industrial.<o:p></o:p></span></p>

            <p class=MsoBodyText style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:
            justify;mso-line-height-alt:8.0pt'>6.8. <span style='mso-tab-count:1'>���� </span><span
            style='mso-bidi-font-family:Arial;mso-bidi-font-weight:bold'>Qualquer
            transgress�o das normas aqui estabelecidas acarretar� � AUTORIZAD</span><span
            style='mso-bidi-font-family:Arial'>A e seus s�cios<span style='mso-bidi-font-weight:
            bold'>, n�o obstante a responsabilidade de seus funcion�rios, al�m da rescis�o
            deste instrumento e pagamento de perdas e danos, as san��es previstas na
            legisla��o especial de marcas e patentes, e legisla��o penal vigente.<o:p></o:p></span></span></p>

            <p class=MsoBodyText style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:
            justify;mso-line-height-alt:8.0pt'><b><o:p>&nbsp;</o:p></b></p>

            <p class=MsoBodyText style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:
            justify;mso-line-height-alt:8.0pt'><b>7 - SIGILO E N�O-CONCORR�NCIA<o:p></o:p></b></p>

            <p class=MsoBodyText style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:
            justify;mso-line-height-alt:8.0pt'>7.1.<b> </b><span style='mso-tab-count:
            1'>���� </span>Obriga-se a AUTORIZADA a manter sigilo quanto ao conte�do dos
            manuais,<span style='mso-spacerun:yes'>� </span>treinamentos, tecnologia ou de
            quaisquer outras informa��es que vier a receber da HB-TECH, ou que tomar
            conhecimento, em virtude da presente contrata��o, devendo no caso de t�rmino ou
            rescis�o da mesma, ser efetuada inspe��o e invent�rio sob supervis�o da HBTECH
            e/ou empresa parceira ou indicada para tal, ficando a AUTORIZADA, neste caso,
            obrigado a devolver imediatamente todo o material recebido e em seu poder.</p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-language:#00FF;mso-bidi-language:#00FF'>7.1.1.<b> </b>S�o
            consideradas confidenciais, para fins desta cl�usula, todas e quaisquer informa��es
            que digam respeito aos neg�cios, desenhos t�cnicos, treinamentos, estrat�gia de
            neg�cios, f�rmulas, marcas, registros, dados comerciais, financeiros e
            estrat�gicos, bem como todos e quaisquer dados relativos �s atividades externas
            e internas das partes, sobre os produtos e marcas, informa��es estas
            fornecidas, a respeito das quais as partes venham a tomar conhecimento em
            virtude do presente contrato.<o:p></o:p></span></p>

            <p class=MsoBodyText style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:
            justify;mso-line-height-alt:8.0pt'>7.2. <span style='mso-tab-count:1'>���� </span>A
            AUTORIZADA,<b> </b>seus s�cios, diretores, prepostos, colaboradores ou
            empregados, n�o poder�o fazer ou permitir que se fa�am c�pias dos manuais,
            sistema informatizado, material promocional ou qualquer outra informa��o
            caracterizada como confidencial fornecida pela HB-TECH. Qualquer comprovada
            viola��o ao sigilo ora pactuado, a qualquer tempo, por parte da AUTORIZADA,<b> </b>seus
            s�cios, diretores, prepostos, colaboradores, ou empregados, acarretar� o
            pagamento da indeniza��o prevista neste instrumento, sem preju�zo das demais
            disposi��es legais ou contratuais cab�veis.</p>

            <p class=MsoBodyText style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:
            justify;mso-line-height-alt:8.0pt'>7.3. <span style='mso-bidi-font-family:
            Tahoma'>Considerando as negocia��es efetuadas entre as partes, na fase
            pr�-contratual, � motivo de rescis�o imediata do presente contrato, com o
            imediato fechamento da \"unidade autorizada\", qualquer viola��o de sigilo deste
            contrato e da negocia��o efetuada, tendo em vista princ�pios de probidade e de
            boa-f�. Qualquer vazamento de informa��o ser� compreendido como ato de
            irresponsabilidade e m�-f�, acarretando os efeitos da responsabilidade por
            quebra de obriga��es contratuais e falta grave de viola��o de dever de sigilo,
            rescindindo este contrato, independentemente, da cobran�a de quaisquer
            indeniza��es por perdas e danos.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-right:.75pt;text-align:justify;mso-line-height-alt:
            10.0pt'><span style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:
            Tahoma;mso-fareast-language:#00FF;mso-bidi-language:#00FF'>7.4. A AUTORIZADA, </span>seus
            s�cios, diretores, prepostos, colaboradores ou empregados <span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>considerando este contrato,
            a negocia��o realizada e o disposto no item k) anterior, obrigam-se a: (i) n�o
            copiar, reproduzir, transferir, ceder, divulgar ou transmitir as informa��es
            confidenciais e dados da presente negocia��o, seja a que t�tulo for; (ii)
            abster-se de falar, comentar, expor ou induzir observa��es ou assuntos que
            possam fazer refer�ncia aos neg�cios da franquia, fora do �mbito do
            desenvolvimento de suas atividades envolvendo os neg�cios da empresa,
            incluindo-se conversas externas �s depend�ncias da </span><span
            style='mso-bidi-font-family:Tahoma'>\"unidade autorizada\"</span><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>, escrit�rios de advogados
            da HB-TECH e/ou da AUTORIZADA, tais como elevadores, escadas, halls, banheiros,
            restaurantes, bares, festas, dentre outros; (iii) abster-se de tratar de
            assuntos da Franquia com terceiros, amigos ou parceiros de outros neg�cios, em
            quaisquer locais privados e/ou p�blicos quando n�o na consecu��o de suas
            atividades, dentre eles sagu�es de aeroportos, rodovi�rias ou no interior de
            transportes p�blicos; (iv)<span style='mso-spacerun:yes'>� </span>n�o entregar
            por qualquer meio, dentre eles, fax, <i style='mso-bidi-font-style:normal'>email</i>,
            correio, qualquer material referente aos neg�cios da franquia, salvo com expressa
            autoriza��o por escrito da HB-TECH, com qualquer tipo de processo ou informa��o
            dos referidos neg�cios.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>8 - RESPONSABILIDADES<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-right:2.15pt;text-align:justify;mso-line-height-alt:
            10.0pt'><span style='mso-bidi-font-family:'New York';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>8.1.<b> </b>A AUTORIZADA assume integral
            responsabilidade pelo pagamento das remunera��es devidas a seus funcion�rios,
            pelo recolhimento de todas as contribui��es e tributos incidentes, bem como
            pelo cumprimento da legisla��o social, trabalhista, previdenci�ria e
            securit�ria aplic�vel. Igualmente, a HB-TECH assume integral responsabilidade
            pelo pagamento das remunera��es devidas a seus funcion�rios, pelo recolhimento
            de todas as contribui��es e tributos incidentes, bem como pelo cumprimento da
            legisla��o social, trabalhista, previdenci�ria e securit�ria aplic�vel.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-language:#00FF;mso-bidi-language:#00FF'>8.2.<b> </b>As
            partes responder�o, individualmente, por reivindica��es de seus funcion�rios
            que sejam indevidamente endere�ados � outra. A parte que der causa �
            reivindica��o dever�<span style='mso-spacerun:yes'>� </span>assumir ao a��es de
            defesa necess�rias, e, em �ltima inst�ncia, indenizar� a parte reclamada das
            eventuais condena��es que lhe venham a ser imputadas, inclusive das despesas e
            honor�rios advocat�cios.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-language:#00FF;mso-bidi-language:#00FF'>8.3.<b> </b>�
            expressamente vedado �s partes, sem que para tanto esteja previamente
            autorizada por escrito, contrair em nome da outra qualquer tipo empr�stimo ou
            assumir em seu nome qualquer obriga��o que implique na outorga de garantias.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-language:#00FF;mso-bidi-language:#00FF'>8.4.<b> </b>As
            partes n�o assumem qualquer v�nculo, exceto aqueles expressamente acordados
            atrav�s do presente instrumento, obrigando-se ao cumprimento da legisla��o
            social, trabalhista, previdenci�ria e securit�ria aplic�vel.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-language:#00FF;mso-bidi-language:#00FF'>8.5. As obriga��es e
            responsabilidades aqui assumidas pelas partes tem in�cio a partir da data da
            assinatura do presente instrumento, n�o se responsabilizando reciprocamente, em
            hip�tese alguma por erros, dolo, e qualquer outro motivo que possa recair sobre
            a administra��o das partes contratantes.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-language:#00FF;mso-bidi-language:#00FF'>8.6.<b> </b><span
            style='mso-tab-count:1'>���� </span>Em caso de quaisquer infra��es ao presente
            contrato, que possam implicar em perda de cr�dito,<span
            style='mso-spacerun:yes'>� </span>ou de alguma forma atingir a imagem da
            HB-TECH junto ao p�blico consumidor, a AUTORIZADA</span>,<b> </b>seus s�cios,
            diretores, prepostos, colaboradores ou empregados,<span style='mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'> poder� ser responsabilizada por meio de
            procedimento judicial pr�prio, inclusive podendo ser condenada em perdas e
            danos.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>8.7.<b> </b><span
            style='mso-tab-count:1'>���� </span>Em caso de a��es propostas por
            consumidores, que reste provada a culpa ou dolo da AUTORIZADA, </span>seus
            s�cios, diretores, prepostos, colaboradores ou empregados, <span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>esta concorda desde j� que
            dever� assumir e integrar o polo passivo das a��es judiciais que venham a ser
            demandadas contra a HB-TECH, isentando a mesma, e ressarcindo quaisquer valores
            que ela venha a ser condenada a pagar e/ou tenha pago.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9- DISPOSI��ES GERAIS<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.1. A AUTORIZADA, ap�s a regular aprova��o de seu
            credenciamento, passar� � condi��o de CREDENCIADA para presta��o de servi�os de
            assist�ncia t�cnica aos produtos comercializados pela HB-TECH.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.2. A AUTORIZADA declara neste ato, estar ciente que
            dever� manter, por sua conta e risco, seguro contra roubo e inc�ndio cujo valor
            da ap�lice seja suficiente para cobrir sinistro que possa ocorrer em seu
            estabelecimento, envolvendo patrim�nio pr�prio e/ou de terceiros. Caso n�o o
            fa�a assume total responsabilidade e responder� civil e criminalmente pela
            omiss�o, perante terceiros e a HB-TECH.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.3. A AUTORIZADA - Declara conhecer e se compromete a
            cumprir o disposto no C�digo de Defesa do Consumidor e assume a
            responsabilidade de \"in vigilando\" por seus funcion�rios para esta finalidade.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.4. A AUTORIZADA responder� por seus atos, caso
            terceiros prejudicados vierem a reclamar diretamente � HB-TECH. Esta exercer� o
            direito de regresso acrescido de custas, honor�rios advocat�cios, al�m de
            perdas e danos incidentes, inclusive danos punitivos.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.5. A HB-TECH fornecer� apoio t�cnico/administrativo,
            al�m de documenta��o e treinamento. Fica estabelecido para a AUTORIZADA o
            compromisso de sigilo referente � documenta��o recebida, ficando reservado
            �nica e exclusivamente � AUTORIZADA o uso da documenta��o t�cnica. Caso seja
            comprovada a quebra do sigilo ou a utiliza��o dos componentes fornecidos em
            garantia em outros equipamentos, n�o comercializados pela HB-TECH, esta ter� o
            direito de tomar as provid�ncias legais, podendo exigir repara��o por perdas e
            danos que vier a sofrer.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.6. Toda correspond�ncia (documenta��o, notas
            fiscais, comunicados, etc.) dever� ser enviada para o endere�o especificado no
            pre�mbulo deste contrato.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.7. Caso a AUTORIZADA tenha necessidade de enviar �
            HB-TECH placas, m�dulos ou equipamentos para conserto, dever� obter uma senha
            com o inspetor ou t�cnico de plant�o. O aparelho dever� estar acompanhado de
            nota fiscal de remessa para conserto, e da ficha t�cnica e em especial da c�pia
            da O.S., devidamente preenchidas.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.8. Os componentes solicitados para uma determinada
            O. S. s� poder�o ser usados para ela e dever�o constar na mesma. A aus�ncia
            dessa O. S. na HB-TECH, decorrido o prazo descrito no item 3.2 - 2 E, dar�
            direito � HB-TECH de fatur�-los contra a AUTORIZADA.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.9. A HB-TECH fornecer� � AUTORIZADA, tabela de
            pre�os de componentes com valores � vista.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.10. A HB-TECH fornecer�, como antecipa��o, os
            componentes para atender aparelhos na garantia, comercializados por ela, desde
            que seja mencionado, em pedido pr�prio, o n�mero da respectiva O.S.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.11. O atendimento descrito no item anterior ser�
            suspenso quando a AUTORIZADA, por falta de devolu��o de componentes defeituosos,
            ou causas correlatas, acumular um valor superior ao seu limite de cr�dito.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.12. Os Pedidos de venda ser�o atendidos, com
            desconto de 20% e frete por conta do comprador. Os itens que n�o estiverem
            dispon�veis em estoque ser�o cancelados. Este desconto � v�lido especificamente
            para os pedidos de venda, n�o sendo aplic�vel ao valor de pe�as n�o devolvidas.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.13. Os d�bitos n�o quitados no vencimento, ser�o
            descontados do primeiro movimento de ORDENS DE SERVI�O, ap�s esse vencimento,
            acrescidos de juros de mercado proporcionalmente aos dias de atraso. A HB-TECH
            poder� optar por outra forma de cobran�a que melhor lhe convier.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.14. </span><span style='mso-bidi-font-family:'New York';
            letter-spacing:-.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'>As
            partes declaram ter recebido o presente instrumento com anteced�ncia necess�ria
            para a correta e atenta leitura e compreens�o de todos os seus termos, direitos
            e obriga��es, bem como foram prestados mutuamente todos os esclarecimentos
            necess�rios e obrigat�rios, e a inda que entendem, reconhecem e concordam com
            os termos e condi��es aqui ajustadas, ficando assim caracterizada a probidade e
            boa-f� de todas as partes contratantes.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'>9.15.<span
            style='mso-tab-count:1'>��� </span>A eventual declara��o judicial de nulidade
            ou inefic�cia de qualquer das disposi��es deste contrato n�o prejudicar� a
            validade e efic�cia das demais cl�usulas, que ser�o integralmente cumpridas,
            obrigando-se as partes a envidar seus melhores esfor�os de modo a validamente
            alcan�arem os mesmos efeitos da disposi��o que tiver sido anulada ou tiver se
            tornado ineficaz.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'>9.16. <span
            style='mso-tab-count:1'>�� </span>O n�o exerc�cio ou a ren�ncia, por qualquer
            das partes, de direito, termo ou disposi��o previstos ou assegurados neste
            contrato, n�o significar� altera��o ou nova��o de suas disposi��es e condi��es,
            nem prejudicar� ou restringir� os direitos de tal parte, n�o impedindo o
            exerc�cio do mesmo direito em �poca subseq�ente ou em id�ntica ou an�loga
            ocorr�ncia posterior, nem isentando as demais partes do integral cumprimento de
            suas obriga��es conforme aqui previstas.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'>9.17. <span
            style='mso-tab-count:1'>�� </span>Este contrato cont�m o acordo integral e
            final das partes, com respeito �s mat�rias aqui tratadas, substituindo todos os
            entendimentos verbais e/ou escrito entre elas, com respeito �s opera��es aqui
            contempladas. Nenhuma altera��o ou modifica��o deste contrato tornar-se-�
            efetiva, saldo se for por escrito e assinada pelas partes.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'New York';mso-fareast-language:#00FF;mso-bidi-language:
            #00FF'>9.18. <span style='mso-tab-count:1'>�� </span>Este contrato obriga e beneficia
            as partes signat�rias e seus respectivos sucessores e representantes a qualquer
            t�tulo. A AUTORIZADA n�o pode transferir ou ceder qualquer dos direitos ou
            obriga��es aqui estabelecidas sem o pr�vio consentimento por escrito da
            HB-TECH.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'New York';mso-fareast-language:#00FF;mso-bidi-language:
            #00FF'>9.19. <span style='mso-tab-count:1'>�� </span>Este contrato � celebrado
            com a inten��o �nica e exclusiva de benef�cio das partes signat�rias e seus
            respectivos sucessores e representantes, e nenhuma outra pessoa ou entidade
            deve ter qualquer direito de se basear neste contrato para reivindicar ou adquirir
            qualquer benef�cio aqui previsto.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'New York';mso-fareast-language:#00FF;mso-bidi-language:
            #00FF'>9.20. <span style='mso-tab-count:1'>�� </span>As disposi��es constantes
            no pre�mbulo deste contrato constituem parte integrante e insepar�vel do mesmo
            para todo os fins de direito, devendo subsidiar e orientar, seja na esfera
            judicial ou extrajudicial, qualquer diverg�ncia ou porventura venha a existir
            com rela��o ao aqui pactuado.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>10 - FORO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Estando de pleno acordo com todas as cl�usulas e
            condi��es aqui expostas, elegem as partes contratantes o Foro da Comarca da
            Cidade de S�o Paulo, para dirimir e resolver toda e qualquer quest�o,
            proveniente do presente contrato, com expressa renuncia de qualquer outro, por
            mais privilegiado que seja.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify'><span style='mso-fareast-font-family:
            'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>E, por estarem assim justas e acertadas, firmam o
            presente instrumento, em duas vias de igual teor e forma, juntamente com as
            testemunhas abaixo indicadas.<o:p></o:p></span></p>

            <p class=MsoNormal align=center style='text-align:center;mso-line-height-alt:
            10.0pt'><span style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:
            Tahoma;mso-fareast-language:#00FF;mso-bidi-language:#00FF'>S�o Paulo, $data_contrato <o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></p>

            <table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
             style='margin-left:2.75pt;border-collapse:collapse;mso-padding-alt:2.75pt 2.75pt 2.75pt 2.75pt'>
             <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes;mso-yfti-lastrow:yes'>
              <td width=265 valign=top style='width:198.8pt;padding:2.75pt 2.75pt 2.75pt 2.75pt'>
              <p class=MsoNormal align=center style='text-align:center;mso-line-height-alt:
              10.0pt;layout-grid-mode:char'>HB ASSIST�NCIA T�CNICA LTDA.</p>
              </td>
              <td width=302 valign=top style='width:226.3pt;padding:2.75pt 2.75pt 2.75pt 2.75pt'>
              <p class=MsoNormal align=center style='text-align:center;mso-line-height-alt:
              10.0pt;layout-grid-mode:char'>$posto_nome.</p>
              </td>
             </tr>
            </table>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><o:p>&nbsp;</o:p></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b
            style='mso-bidi-font-weight:normal'><span style='mso-fareast-font-family:'Lucida Sans Unicode';
            mso-bidi-font-family:Tahoma;mso-fareast-language:#00FF;mso-bidi-language:#00FF'>Testemunhas:<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>________________________________
            <span style='mso-tab-count:1'>����� </span>_______________________________<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>Nome: <span style='mso-tab-count:
            6'>���������������������������������������������������������� </span>Nome:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>RG: <span style='mso-tab-count:
            6'>��������������������������������������������������������������� </span>RG:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>CPF: <span
            style='mso-tab-count:6'>������������������������������������������������������������� </span>CPF:<o:p></o:p></span></p>

            <p class=MsoNormal align=center style='text-align:center;mso-line-height-alt:
            10.0pt'>ANEXO 1<o:p></o:p></b></p>

            <p class=MsoNormal align=left style='text-align:left;mso-line-height-alt:
            10.0pt'>�<br><b>$posto_nome<o:p></o:p></b></p>

            <p class=MsoNormal align=left style='text-align:left;mso-line-height-alt:
            10.0pt'><b>Ref.: Proposta Comercial de Assist�ncia T�cnica<o:p></o:p></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Prezados Senhores,<br><br>Inicialmente, agradecemos o 
            contato aberto e teremos prazer t�-los como parceiros da HB FLEX.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� <u>CONSIDERA��ES INICIAIS</u><o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� </span>Considerando que a HB FLEX desenvolveu uma 
            metodologia comercial e novo neg�cio atrav�s da marca HBFLEX, sob a qual vender� 
            produtos eletro-eletr�nicos, sendo que sem preju�zo de outros produtos poss�veis,
            poder�o integrar os seguintes: i. DVD Players; ii. DVR Players; iii. MP4; iv. Maquinas
            de Lavar Roupas residenciais; v. Notebooks; vi. Desktops; vii. Ar Condicionado Splits; 
            viii. TVs LCD; e ix. Monitores LC. A HB FLEX ser� respons�vel pela fabrica��o dos 
            referidos produtos no territ�rio nacional e, posterior venda no mercado brasileiro 
            e/ou mercado externo, sendo necess�ria a contrata��o de empresa especializada para a 
            presta��o de servi�os de assist�ncia t�cnica e manuten��o dos produtos.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� <u>OBJETO DA PROPOSTA</u><o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� </span>Assim, concluindo nossos recentes entendimentos, 
            vimos formalizar nossa proposta comercial para a contrata��o e execu��o dos servi�os 
            de assist�ncia t�cnica, a serem realizados por V.Sas., consoantes termos fixados em 
            contrato de presta��o de servi�os que dever� ser assinado, considerando V.Sas. terem 
            assegurado que possuem o know how e tecnologia necess�ria.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� </span>Os servi�os dever�o englobar todas as etapas 
            necess�rias para a consecu��o da assist�ncia t�cnica nos produtos expressamente 
            abaixo indicados, sem preju�zo de outros que venham a ser contratados, ficando 
            claramente fixado que a HB FLEX remunerar� V.Sas. apenas nas hip�teses que os 
            produtos estejam dentro da garantia originalmente fornecida (12 meses), ou dentro 
            do prazo de garantia estendida (12 meses mais 6 meses). Fora destas condi��es, a 
            HB FLEX apenas dever� ser respons�vel pelo fornecimento de pe�as de reposi��o 
            (cobradas), devendo V.Sas. cobrarem pelos trabalhos realizados individualmente.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� <u>REMUNERA��O:</u><o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� </span>O pre�o desta proposta para os servi�os 
            que envolvam especificamente a assist�ncia t�cnica e manuten��o dos produtos 
            � a seguinte:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>������ <b>1. DVD Player - qualquer modelo produzido pela HB FLEX.<br>
            <span
            style='mso-tab-count:6'>���������� - R$ 20,00 ( vinte reais ), para qualquer reparo.</b><o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>������ <b>2. MP4 Player - qualquer modelo produzido pela HB FLEX.<br>
            <span
            style='mso-tab-count:6'>���������� - R$ 10,00 ( dez reais), para qualquer reparo.</b><o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� <u>PRAZO DE DURA��O:</u><o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� </span>O contrato ter� prazo de validade indeterminado.
            <o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� <u>VALIDADE DA PROPOSTA:</u><o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� </span>Este proposta tem validade de 20 (vinte) dias, 
            vinculando as partes se aceita. Representa, ainda, o �nico e integral acordo entre 
            as partes, superando todos e quaisquer outros entendimentos havidos anteriormente.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� </span>Caso V. Sas. estejam de acordo com o teor desta 
            proposta, e aja confirma��o da aceita��o desta, solicitamos a devolu��o de uma via 
            com o seu 'de acordo', e a devida rubrica em todas as p�ginas, passando a presente 
            aven�a a produzir seus regulares efeitos, sendo a mesma formalizada por interm�dio 
            de contrato de presta��o de servi�os.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� Atenciosamente,<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:center;mso-line-height-alt:8.0pt' align='center'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� HB ASSIST�NCIA T�CNICA LTDA.<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� <u>De Acordo:</u><o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� Assinatura:_______________________________________<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� Nome:_____________________________________________<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� Empresa:__________________________________________<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>��� CNPJ:_____________________________________________<o:p></o:p></span></b></p>

            </div>
            ";
            if($tipo_arquivo=='pdf'){
                echo `mkdir /tmp/hbtech`;
                echo `chmod 777 /tmp/hbtech`;
                echo `rm /tmp/hbtech/contrato_$posto.htm`;
                echo `rm /tmp/hbtech/contrato_$posto.pdf`;
                echo `rm /var/www/assist/www/credenciamento/contrato/contrato_$posto.pdf`;


                if(strlen($msg_erro) == 0){
                    $abrir = fopen("/tmp/hbtech/contrato_$posto.htm", "w");
                    if (!fwrite($abrir, $conteudo)) {
                        $msg_erro = "Erro escrevendo no arquivo ($filename)";
                    }
                    fclose($abrir); 
                }


                //gera o pdf
                echo `htmldoc --webpage --no-duplex --no-embedfonts --header ... --permissions no-modify,no-copy --fontsize 8.5 --no-title -f /tmp/hbtech/contrato_$posto.pdf /tmp/hbtech/contrato_$posto.htm`;
                echo `mv  /tmp/hbtech/contrato_$posto.pdf /var/www/assist/www/credenciamento/contrato/contrato_hbtech.pdf`;
            }
            if($tipo_arquivo=='doc'){
                echo `mkdir /tmp/hbtech`;
                echo `chmod 777 /tmp/hbtech`;
                echo `rm /tmp/hbtech/contrato_$posto.htm`;
                echo `rm /tmp/hbtech/contrato_$posto.doc`;
                echo `rm /var/www/assist/www/credenciamento/contrato/contrato_$posto.doc`;


                if(strlen($msg_erro) == 0){
                    $abrir = fopen("/tmp/hbtech/contrato_$posto.htm", "w");
                    if (!fwrite($abrir, $conteudo)) {
                        $msg_erro = "Erro escrevendo no arquivo ($filename)";
                    }
                    fclose($abrir); 
                }


                //gera o doc
                echo `htmldoc --webpage --no-duplex --no-embedfonts --header ... --permissions no-modify,no-copy --fontsize 8.5 --no-title -f /tmp/hbtech/contrato_$posto.doc /tmp/hbtech/contrato_$posto.htm`;
                echo `mv  /tmp/hbtech/contrato_$posto.doc /var/www/assist/www/credenciamento/contrato/contrato_hbtech.doc`;
            }
                echo "<table align='center'>";
                echo "<tr>";
                    echo "<td><img src='http://posvenda.telecontrol.com.br/assist/credenciamento/hbtech/superior.jpg'></td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>";
                    echo "<p align='center'>Foi gerado contrato da F�brica HBtech para posto $posto_nome, cujo cnpj � $cnpj.<br>";
                    echo "<a href='http://posvenda.telecontrol.com.br/assist/credenciamento/contrato/download_contrato.php?id=$id&key=$key&tipo_arquivo=$tipo_arquivo' >Clique aqui para baixar o contrato.</a></p>";
                echo "</td>";
                echo "<tr>";
                    echo "<td><img src='http://posvenda.telecontrol.com.br/assist/credenciamento/hbtech/inferior.jpg'></td>";
                echo "</tr>";
                echo "</table>";
            }

            if($fabrica==47){
                    $conteudo .= "

            <p class=MsoNormal align=center style='text-align:center;mso-line-height-alt:
            10.0pt'><b>CONTRATO DE CREDENCIAMENTO DE ASSIST�NCIA T�CNICA<o:p></o:p></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'>Pelo
            presente instrumento particular,</p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><b>ZUQUI IMPORTA�AO 
            E EXPORTA�AO LTDA</b>, sociedade empresarial com escrit�rio administrativo na Rua Nilo 
            Pe�anha, 1032, Curitiba - PR CEP - 80520-000, inscrita no CNPJ sob n� 08.607951/0001-35, 
            neste ato representada por seu diretor ao final assinado, doravante denominada &quot;CROWN FERRAMENTAS EL�TRICAS DO BRASIL&quot;, e<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b>$posto_nome.</b>, sociedade empresarial com sede na $endereco,
            $numero $complemento, na cidade de $cidade, $estado, CEP $cep, inscrita no CNPJ sob n�
            $cnpj, neste ato representada por seu administrador, ao final
            assinado, doravante denominada &quot;AUTORIZADA&quot;,</p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>1- OBJETIVO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>1.1. O objetivo do presente contrato � a presta��o, pela AUTORIZADA, 
            em sua sede social, do servi�o de assist�ncia t�cnica aos produtos comercializados pela CROWN 
            FERRAMENTAS EL�TRICAS DO BRASIL.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>1.2. Os servi�os que ser�o prestados pela AUTORIZADA, consistem em 
            manuten��o corretiva e preventiva, quando os custos ser�o cobertos pela CROWN FERRAMENTAS EL�TRICAS DO BRASIL, 
            atrav�s de taxas de garantia, fornecimento de pe�as e informa��o t�cnica.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>2- DA EXECU��O DOS SERVI�OS DURANTE A GARANTIA<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>2.1. O prazo e condi��es de garantia dos produtos comercializados 
            pela CROWN FERRAMENTAS EL�TRICAS DO BRASIL, s�o especificados no certificado de garantia, cujo in�cio � 
            contado a partir da data emiss�o da nota fiscal de compra do produto pelo primeiro 
            usu�rio.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Para consertos efetuados em aparelhos no per�odo de garantia, 
            a CROWN FERRAMENTAS EL�TRICAS DO BRASIL,  pagar� � AUTORIZADA , no m�s subseq�ente � apresenta��o das O.S. 
            os seguintes valores de Taxa de M�o de Obra:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>3- PRE�O E CONDI��ES DE PAGAMENTO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Para consertos efetuados em aparelhos no per�odo de garantia, 
            a CROWN FERRAMENTAS EL�TRICAS DO BRASIL,  pagar� � AUTORIZADA , no m�s subseq�ente � apresenta��o das O.S. 
            os seguintes valores de Taxa de M�o de Obra:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>(i) Ferramentas at� 1.000 Watts - R$ 15,00<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>(ii) Ferramentas acima de 1.000 Watts at� 2.000 Watts - R$ 25,00<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>(iii) Ferramentas acima de 2.000 Watts - R$ 30,00<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>4 - DURA��O DO CONTRATO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>A validade do presente contrato � por tempo indeterminado e 
            poder� ser rescindido por qualquer das partes, mediante um aviso pr�vio de 30 
            (trinta) dias, por escrito. A autorizada obriga-se, neste prazo do aviso, a dar 
            continuidade aos atendimentos dos produtos em seu poder.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>5 - RESPONSABILIDADES<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>5.1. A AUTORIZADA assume responsabilidade pelo pagamento das 
            remunera��es devidas a seus funcion�rios, pelo recolhimento de todas as contribui��es e 
            tributos incidentes, bem como pelo cumprimento da legisla��o social, trabalhista, 
            previdenci�ria e securit�ria aplic�vel.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>5.2. Em caso de a��es propostas por consumidores, 
            que reste provada a culpa ou dolo da AUTORIZADA, concorda desde j� que dever� 
            responder pelo  passivo das a��es judiciais que venham a ser demandadas contra a 
            CROWN FERRAMENTAS EL�TRICAS DO BRASIL.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>6- DISPOSI��ES GERAIS<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>6.1. A AUTORIZADA Declara conhecer e se compromete a 
            cumprir o disposto no C�digo de Defesa do Consumidor e assume a responsabilidade 
            de 'in vigilando' por seus funcion�rios para esta finalidade.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>6.2. Os componentes solicitados para uma determinada O.S. 
            s� poder�o ser usados para ela e dever�o constar na mesma. A aus�ncia dessa O. S. 
            na CROWN FERRAMENTAS EL�TRICAS DO BRASIL, decorrido o prazo regular , dar� direito � CROWN FERRAMENTAS EL�TRICAS DO BRASIL 
            de fatur�-los contra a AUTORIZADA. As pe�as utilizadas em garantia dever�o ser mantidas 
            por 90 dias antes do descarte.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>6.3. Os d�bitos n�o quitados no vencimento, ser�o descontados 
            do primeiro movimento de ORDENS DE SERVI�O, ap�s esse vencimento, acrescidos de 
            juros de mercado proporcionalmente aos dias de atraso.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>6.4.   Este contrato obriga e beneficia as partes signat�rias 
            e seus respectivos sucessores e representantes a qualquer t�tulo. A AUTORIZADA n�o 
            pode transferir ou ceder qualquer dos direitos ou obriga��es aqui estabelecidas sem 
            o pr�vio consentimento por escrito da CROWN FERRAMENTAS EL�TRICAS DO BRASIL.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>7 - FORO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Estando de pleno acordo com todas as cl�usulas e condi��es 
            aqui expostas, elegem as partes contratantes o Foro da Comarca da Cidade de Curitiba, 
            para dirimir e resolver toda e qualquer quest�o, proveniente do presente contrato, 
            com expressa renuncia de qualquer outro, por mais privilegiado que seja. E por estarem 
            assim contratados, firmam o presente em duas vias do mesmo teor e para um s� efeito, 
            na presen�a de duas testemunhas.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify' align='justify'><span style='mso-fareast-font-family:
            'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>E, por estarem assim justas e acertadas, firmam o presente 
            instrumento, em duas vias de igual teor e forma, juntamente com as testemunhas abaixo 
            indicadas.<o:p></o:p></span></p>

            <p class=MsoNormal align=center style='text-align:center;mso-line-height-alt:
            10.0pt'><span style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:
            Tahoma;mso-fareast-language:#00FF;mso-bidi-language:#00FF'>Curitiba, $data_contrato <o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></p>

            <table class=MsoNormalTable border=0 cellspacing=0 cellpadding=0
             style='margin-left:2.75pt;border-collapse:collapse;mso-padding-alt:2.75pt 2.75pt 2.75pt 2.75pt'>
             <tr style='mso-yfti-irow:0;mso-yfti-firstrow:yes;mso-yfti-lastrow:yes'>
              <td width=265 valign=top style='width:198.8pt;padding:2.75pt 2.75pt 2.75pt 2.75pt'>
              <p class=MsoNormal align=center style='text-align:center;mso-line-height-alt:
              10.0pt;layout-grid-mode:char'>CROWN FERRAMENTAS EL�TRICAS DO BRASIL.</p>
              </td>
              <td width=302 valign=top style='width:226.3pt;padding:2.75pt 2.75pt 2.75pt 2.75pt'>
              <p class=MsoNormal align=center style='text-align:center;mso-line-height-alt:
              10.0pt;layout-grid-mode:char'>$posto_nome.</p>
              </td>
             </tr>
            </table>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><o:p>&nbsp;</o:p></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b
            style='mso-bidi-font-weight:normal'><span style='mso-fareast-font-family:'Lucida Sans Unicode';
            mso-bidi-font-family:Tahoma;mso-fareast-language:#00FF;mso-bidi-language:#00FF'>Testemunhas:<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>________________________________
            <span style='mso-tab-count:1'>����� </span>_______________________________<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>Nome: <span style='mso-tab-count:
            6'>���������������������������������������������������������� </span>Nome:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>RG: <span style='mso-tab-count:
            6'>��������������������������������������������������������������� </span>RG:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>CPF: <span
            style='mso-tab-count:6'>��������������������������������������� </span>CPF:<o:p></o:p></span></p>
        ";
        if($tipo_arquivo=='pdf'){
            echo `mkdir /tmp/crown`;
            echo `chmod 777 /tmp/crown`;
            echo `rm /tmp/crown/contrato_$posto.htm`;
            echo `rm /tmp/crown/contrato_$posto.pdf`;
            echo `rm /var/www/assist/www/credenciamento/contrato/contrato_$posto.pdf`;


            if(strlen($msg_erro) == 0){
                $abrir = fopen("/tmp/crown/contrato_$posto.htm", "w");
                if (!fwrite($abrir, $conteudo)) {
                    $msg_erro = "Erro escrevendo no arquivo ($filename)";
                }
                fclose($abrir); 
            }


            //gera o pdf
            echo `htmldoc --webpage --no-duplex --no-embedfonts --header ... --permissions no-modify,no-copy --fontsize 8.5 --no-title -f /tmp/crown/contrato_$posto.pdf /tmp/crown/contrato_$posto.htm`;
            echo `mv  /tmp/crown/contrato_$posto.pdf /var/www/assist/www/credenciamento/contrato/contrato_crown.pdf`;
        }
        if($tipo_arquivo=='doc'){
            echo `mkdir /tmp/crown`;
            echo `chmod 777 /tmp/crown`;
            echo `rm /tmp/crown/contrato_$posto.htm`;
            echo `rm /tmp/crown/contrato_$posto.doc`;
            echo `rm /var/www/assist/www/credenciamento/contrato/contrato_$posto.doc`;


            if(strlen($msg_erro) == 0){
                $abrir = fopen("/tmp/crown/contrato_$posto.htm", "w");
                if (!fwrite($abrir, $conteudo)) {
                    $msg_erro = "Erro escrevendo no arquivo ($filename)";
                }
                fclose($abrir); 
            }


            //gera o pdf
            echo `htmldoc --webpage --no-duplex --no-embedfonts --header ... --permissions no-modify,no-copy --fontsize 8.5 --no-title -f /tmp/crown/contrato_$posto.doc /tmp/crown/contrato_$posto.htm`;
            echo `mv  /tmp/crown/contrato_$posto.doc /var/www/assist/www/credenciamento/contrato/contrato_crown.doc`;

        }
                echo "<br><br><table align='center'>";
                echo "<tr>";
                    echo "<td align='center'><img src='http://posvenda.telecontrol.com.br/assist/credenciamento/crown/contrato_topo.jpg'></td>";
                echo "</tr>";
                echo "<tr>";
                    echo "<td align='center'>&nbsp;</td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>";
                    echo "<p align='center'>Foi gerado o contrato para posto $posto_nome, o CNPJ deste posto � $cnpj.<br>";
                    echo "<a href='http://posvenda.telecontrol.com.br/assist/credenciamento/contrato/download_contrato.php?id=$id&key=$key&tipo_arquivo=$tipo_arquivo' >Clique aqui para baixar o contrato.</a></p>";
                echo "</td>";
                echo "</tr>";
                echo "<tr>";
                    echo "<td align='center'>&nbsp;</td>";
                echo "</tr>";
                echo "<tr>";
                    echo "<td align='center'><img src='http://posvenda.telecontrol.com.br/assist/credenciamento/crown/contrato_rodape.jpg'></td>";
                echo "</tr>";
                echo "</table>";
            }

            if(in_array($fabrica, array(10,81,114,122,123,125,128,136,187,188))){
                $data_extrato = "01";
                if($fabrica == 81){
                    $nome_fabrica = "bestway";
                    $data_extrato = "21";
                }else if($fabrica == 114){
                    $nome_fabrica = "cobimex";
                    $data_extrato = "01";
                }else if($fabrica == 122){
                    $nome_fabrica = "wurth";
                    $data_extrato = "01";
                }else if($fabrica == 123){
                    $nome_fabrica = "positec";
                    $data_extrato = "01";
                }else if($fabrica == 125){
                    $nome_fabrica = "saint-gobain";
                    $data_extrato = "01";
                }else if($fabrica == 128){
                    $nome_fabrica = "unilever";
                    $data_extrato = "25";
                }else if($fabrica == 136){
                    $nome_fabrica = "ello";
					$data_extrato = "01";
				}else if($fabrica == 187){
                    $nome_fabrica = "cuisinart";
                    $data_extrato = "20";
                }else if($fabrica == 188){
                    $nome_fabrica = "ingco";
                    $data_extrato = "01";

                }else if ($fabrica == 198) {
                    $nome_fabrica = "frigelar";
                    $data_extrato = "01";
                } else {
                    $nome_fabrica = "telecontrol";
                }
                

            $conteudo .= " <div class='Section1'>";  

	if($fabrica == 188){
		            $conteudo .="
            <p class=MsoNormal align=center style='margin-left:1pt;;text-align:center;mso-line-height-alt:
            9.0pt;font-size:11pt'><b>CONTRATO DE CREDENCIAMENTO DE POSTO AUTORIZADO DE ASSIST�NCIA T�CNICA<o:p></o:p></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>CONTRATANTE: <b>KELLATOR SOLU��ES EM EQUIPAMENTOS LTDA.</b>, doravante chamada <b>KELLATOR</b><o:p></o:p></span></p>

	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'>CNPJ n�: 30.388.338/0001-23</p>
	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'>ENDERE�O: Av. Jo�o Paccola, 1389, Vila Antonieta II, Len��is Paulista, S.P.</p>
	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'>REPRESENTANTE LEGAL: RODRIGO CAPELARI VICTAGLIANO</p>

	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'>CONTRATADA/POSTO: <b style='text-transform:uppercase;'>".strtoupper($posto_nome)."</b>, doravante denominado simplesmente AUTORIZADA</p>

	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'>CNPJ n�: $cnpj</p>

	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'>ENDERE�O: $endereco, $numero $complemento, $cidade, $estado</p>

	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'>REPRESENTANTE LEGAL: ___________________________________________________</p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>1- OBJETIVO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>O Objetivo do presente contrato � presta��o, pela AUTORIZADA, em sua sede social, do servi�o de assist�ncia t�cnica, das marcas representadas pela CONTRATANTE.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>2- DA GEST�O DO SISTEMA DE GARANTIAS E ASSIST�NCIA T�CNICA<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>2.1. A CONTRATANTE poder�, a seu crit�rio, contratar uma terceira entidade gestora de seus processos de garantia e de assist�ncia t�cnica. Essa terceira entidade, que poder� ser a chamada de TELECONTROL, ou outra que, a crit�rio da CONTRATANTE venha substitu�-la, ter� liberdade de interagir diretamente com a AUTORIZADA, devendo esta acatar e proceder com as ordens de servi�o a ela endere�adas, como se a pr�pria CONTRATANTE as tivesse enviado.<o:p></o:p></span></p>

	     <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>2.2. A AUTORIZADA dever� se adequar ao sistema da TELECONTROL, para que possa receber as ordens de servi�o, registrar os servi�os prestados e imprimir os extratos para fins de emiss�o de nota fiscal de servi�os.<o:p></o:p></span></p>
	    
	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>3- PAGAMENTO DA TAXA DE M�O-DE-OBRA<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Os servi�os executados em ORDENS DE SERVI�O (garantia) dever�o ser registrados no site da Telecontrol. No dia 01 de cada m�s ser� gerado um extrato com todas as ordens de servi�os finalizadas. A AUTORIZADA dever� imprimir o extrato e enviar nota fiscal de presta��o de servi�os para a  TELECONTROL e para a CONTRATANTE, conforme orienta��es contidas no site da TELECONTROL. Dentro de 10 dias ap�s o recebimento da nota fiscal de m�o de obra, o dep�sito ser� realizado na conta banc�ria da AUTORIZADA.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>4- DURA��O DO CONTRATO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>A validade do presente contrato � por tempo indeterminado e poder� ser rescindido por qualquer das partes, mediante um aviso pr�vio de 30 (trinta) dias, por escrito. A AUTORIZADA obriga-se, neste prazo de aviso, a dar continuidade aos atendimentos dos produtos em seu poder.<o:p></o:p></span></p>


            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>5- RESPONSABILIDADES<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>5.1. A AUTORIZADA se compromete a seguir normas de procedimento expressamente vinculadas pela TELECONTROL e pela CONTRATANTE;<o:p></o:p></span></p>

	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
             style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
             mso-bidi-language:#00FF'>5.2. Em caso de a��es judiciais propostas por consumidores, em que restem provadas a culpa ou dolo da AUTORIZADA, seus s�cios, diretores, gestores, prepostos, colaboradores ou empregados, esta concorda desde j� que dever� assumir e integrar o polo passivo das a��es judiciais, reclama��es em Procon, dentre outros �rg�os que venham a ser demandadas contra a TELECONTROL ou a CONTRATANTE<o:p></o:p></span></p>
            
            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>6- DISPOSI��ES GERAIS<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>A AUTORIZADA declara conhecer e se compromete a cumprir o disposto no C�digo de Defesa do Consumidor e assume a responsabilidade �in vigilando� e �in elegendo� pelos atos de seus funcion�rios para esta finalidade.<o:p></o:p></span></p>

	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>7- FORO<o:p></o:p></span></b></p>


            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
	    mso-bidi-language:#00FF'>Estando de pleno acordo com todas as cl�usulas e condi��es aqui expostas, elegem as partes contratantes o Foro da Comarca de Len��is Paulista, S.P., para dirimir e resolver todas e quaisquer quest�es proveniente do presente contrato, com expressa ren�ncia de qualquer outro, por mais privilegiado que seja.<o:p></o:p></span></p>

	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
             style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
             mso-bidi-language:#00FF'>E, por estarem assim justas e acertadas, firmam o presente instrumento, em duas vias de igual teor e forma, juntamente com as testemunhas abaixo.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Len��is Paulista, ".date(d)." de ".$array_meses[intval($mes)]." de ".date(Y).". <o:p></o:p></span></p>

     <table width=100% >
	<tr >
		<td width=10%>&nbsp;</td>
      		<td width=80% rowspan=2 align=left nowrap>
        		<img src='https://ww2.telecontrol.com.br/assist/credenciamento/assinatura_contrato_ingco.jpeg' height=130 />   
        	</td>
          	<td width=10%>&nbsp;</td>
	</tr>
	</table><br><br>
    <center>
	   <p class=MsoNormal style='mso-line-height-alt:7.0pt;font-size:14pt;align='center'><b><span style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;mso-bidi-language:#00FF'>TESTEMUNHAS<o:p></o:p></span></b></p>
    </center><br>
	<table width=100%>
	 <tr >
        	<td width=10%>&nbsp;</td>
		<td  width='10%' colspan='100%' align=left nowrap>
			<br><br>
			<img src='https://ww2.telecontrol.com.br/assist/credenciamento/assinatura_testemunhas_contrato_ingco.jpeg' height=130 />
	     </td>
		<td width=10%>&nbsp;</td>
         </tr>
   </table>
</div>
";		
	}else{		

            $conteudo .="
            <p class=MsoNormal align=center style='margin-left:1pt;;text-align:center;mso-line-height-alt:
            9.0pt;font-size:11pt'><b>CONTRATO DE CREDENCIAMENTO DE ASSIST�NCIA T�CNICA<o:p></o:p></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:9.0pt;font-size:11pt'>Pelo
            presente instrumento particular,</p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><b>TELECONTROL NETWORKING LTDA</b>, sociedade empresarial com escrit�rio administrativo na Av. Carlos Art�ncio, 420 A - Bairro Fragata C, CEP 17.519-255, na cidade de Mar�lia, estado de S�o Paulo, inscrita no CNPJ sob n� 04.716.427/0001-41, 
            neste ato representada por seu diretor ao final assinado, doravante denominada simplesmente &quot;TELECONTROL NETWORKING&quot;, e<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b style='text-transform:uppercase;'>".strtoupper($posto_nome)."</b>, sociedade empresarial com sede na $endereco,
            $numero $complemento, na cidade de $cidade, $estado, CEP $cep, inscrita no CNPJ sob n�
            $cnpj, neste ato representada por seu administrador, ao final
            assinado, doravante denominada &quot;AUTORIZADA&quot;,</p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>1- OBJETIVO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>1.1. O objetivo do presente contrato � a presta��o, pela AUTORIZADA, 
            em sua sede social, do servi�o de assist�ncia t�cnica das empresas que contratarem a TELECONTROL para administrar o p�s-venda.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>2- PAGAMENTO DA TAXA DE M�O-DE-OBRA<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>2.1. Os servi�os executados dever�o ser registrados no site da TELECONTROL. No dia $data_extrato de cada m�s ser� gerado um extrato com todas as ordens de servi�os finalizadas. A AUTORIZADA dever� imprimir o extrato e enviar nota fiscal de presta��o de servi�os para a TELECONTROL, conforme orienta��es no site, e dentro de 10 dias ap�s o recebimento da nota na TELECONTROL o dep�sito ser� realizado na conta banc�ria da AUTORIZADA.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>3- DURA��O DO CONTRATO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>3.1. A validade do presente contrato � por tempo indeterminado e 
            poder� ser rescindido por qualquer das partes, mediante um aviso pr�vio de 30 
            (trinta) dias, por escrito. A autorizada obriga-se, neste prazo do aviso, a dar 
            continuidade aos atendimentos dos produtos em seu poder.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>4- RESPONSABILIDADES<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>4.1. A AUTORIZADA se compromete a seguir as normas de procedimento expressamente veiculadas pela TELECONTROL;<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>4.2. Em caso de a��es propostas por consumidores, que reste provada a culpa ou dolo da AUTORIZADA, seus s�cios, diretores, prepostos, colaboradores ou empregados, esta concorda desde j� que dever� assumir e integrar o polo passivo das a��es judiciais que venham a ser demandadas contra a TELECONTROL e a empresas propriet�rias dos produtos em quest�o.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>5- DISPOSI��ES GERAIS<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>5.1. A AUTORIZADA declara conhecer e se compromete a 
            cumprir o disposto no C�digo de Defesa do Consumidor e assume a responsabilidade 
            de 'in vigilando' por seus funcion�rios para esta finalidade.<o:p></o:p></span></p>

            
            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>6- FORO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Estando de pleno acordo com todas as cl�usulas e condi��es aqui expostas, elegem as partes contratantes o Foro da Comarca de Mar�lia, estado de S�o Paulo, para dirimir e resolver toda e qualquer quest�o, proveniente do presente contrato, com expressa renuncia de qualquer outro, por mais privilegiado que seja.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>E, por estarem assim justas e acertadas, firmam o presente 
            instrumento, em duas vias de igual teor e forma, juntamente com as testemunhas abaixo 
            indicadas.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Mar�lia, ".date(d)." de ".$array_meses[intval($mes)]." de ".date(Y).". <o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></p>


            <table width=100% >
        <tr >
        <td width=45% rowspan=2 align=left nowrap>
        <img src='https://ww2.telecontrol.com.br/assist/credenciamento/assinatura_contrato.jpg' height=300 />   
        </td>
          <td width=10%>&nbsp;</td>
          <td width=45% align=left valign=top nowrap>
            <br><br><br>
            <hr width=100% size=1 color=#000 />
        <span style='text-transform:uppercase;'>".strtoupper($posto_nome)."</span><br>
        Autorizada
        </td>
        </tr>
         <tr >
        <td width=10%>&nbsp;</td>
        <td width=45% align=left nowrap>
        <br><br><br><br>
        <hr width=100% size=1 color=#000 />
          Nome:<br>
          RG:
              </td>
             </tr>
            </table>
            </div>
";
	}
        
        if($_GET['fabrica']){
            echo "<table style='width:880px' align='center'>";
            echo "<tr><td>";
            echo $conteudo;
            echo "</tr></td>";
            echo "</table>";
        }

        if($tipo_arquivo=='pdf'){
            echo `mkdir -m 777 /tmp/$nome_fabrica`;
            echo `rm /tmp/$nome_fabrica/contrato_$posto.htm`;
            echo `rm /tmp/$nome_fabrica/contrato_$posto.pdf`;
            echo `rm /var/www/assist/www/credenciamento/contrato/contrato_$posto.pdf`;
            //echo `rm /var/www/chamados/hd-968655/credenciamento/contrato/contrato_$posto.pdf`;
            

            if(strlen($msg_erro) == 0){
                $abrir = fopen("/tmp/$nome_fabrica/contrato_$posto.htm", "w");
                if (!fwrite($abrir, $conteudo)) {
                    $msg_erro = "Erro escrevendo no arquivo ($filename)";
                }
                fclose($abrir); 
            }
                
                if(file_exists("/tmp/$nome_fabrica/contrato_$posto.htm") ){
                //gera o pdf
                echo `htmldoc --webpage --no-duplex --no-embedfonts --header ... --permissions no-modify,no-copy --fontsize 11.8 --no-title -f /tmp/$nome_fabrica/contrato_$posto.pdf /tmp/$nome_fabrica/contrato_$posto.htm`;
                echo `mv  /tmp/$nome_fabrica/contrato_$posto.pdf /var/www/assist/www/credenciamento/contrato/contrato_$nome_fabrica.pdf`;
                //echo `mv  /tmp/$nome_fabrica/contrato_$posto.pdf /var/www/chamados/hd-968655/credenciamento/contrato/contrato_$nome_fabrica.pdf`;
                }
            
        }
        if($tipo_arquivo=='doc'){
            echo `mkdir -m 777 /tmp/$nome_fabrica`;
            echo `rm /tmp/$nome_fabrica/contrato_$posto.htm`;
            echo `rm /tmp/$nome_fabrica/contrato_$posto.doc`;
            echo `rm /var/www/assist/www/credenciamento/contrato/contrato_$posto.doc`;
            //echo `rm /var/www/chamados/hd-968655/credenciamento/contrato/contrato_$posto.doc`;
            
            if(strlen($msg_erro) == 0){
                $abrir = fopen("/tmp/$nome_fabrica/contrato_$posto.htm", "w");
                if (!fwrite($abrir, $conteudo)) {
                    $msg_erro = "Erro escrevendo no arquivo ($filename)";
                }
                fclose($abrir); 
            }


            //gera o doc
            echo `htmldoc --webpage --no-duplex --no-embedfonts --header ... --permissions no-modify,no-copy --fontsize 8.5 --no-title -f /tmp/$nome_fabrica/contrato_$posto.doc /tmp/$nome_fabrica/contrato_$posto.htm`;
            echo `mv  /tmp/$nome_fabrica/contrato_$posto.doc /var/www/assist/www/credenciamento/contrato/contrato_$nome_fabrica.doc`;
            //echo `mv  /tmp/$nome_fabrica/contrato_$posto.doc /var/www/chamados/hd-968655/credenciamento/contrato/contrato_$nome_fabrica.doc`;
            
        }
                echo "<br><br><table align='center' id='download'>";
                    echo "<td align='center'>&nbsp;</td>";
                echo "</tr>";

                if(in_array($fabrica, array(81,114,122,123,125,128,136,187,188,198))){
                    echo "<tr>";
                    echo "<td>";
                    echo "<div id='erro' style='margin:auto;width:700px;display:none;'></div><br>";
                    echo "<center>
                            <!-- <input type='button' id='print' value='Imprimir contrato' onclick='javascript: imprimir();'> --> 
                            <input type='button' value='Enviar E-mail' onclick=\"javascript: mensagemEmail();\">
                            <input type='button' value='Download Contrato' onclick=\"javascript: window.open('contrato/download_contrato.php?id=$id&key=$key&tipo_arquivo=$tipo_arquivo');\">
                           </center>";
                    echo "</td>";
                    echo "</tr>";
                    echo "<tr id='mensagem_email' style='display:none;'>";
                    echo "<td>";
                    
                    echo "Escreva a mensagem do E-mail <br>";
                    echo "<textarea name='mensagem' id='mensagem' rows='5' cols='80'>$texto</textarea>";
                    echo "<br><center><input type='button' value='Enviar' onclick=\"javascript: enviaEmail();\"></center><br>";
                    echo "<input type='hidden' name='fabrica' id='fabrica' value='$fabrica'>
                          <input type='hidden' name='tipo' id='tipo' value='$tipo_arquivo'>
                          <input type='hidden' name='email_dest' id='email_dest' value='$email'>";
                    echo "</td>";
                    echo "</tr>";

                }
                if($fabrica == 10){
                    echo "<tr>";
                    echo "<td>";
                    
                        echo "<p align='center'>Foi gerado o contrato para posto $posto_nome, o CNPJ deste posto � $cnpj.<br>";
                        echo "<a href='http://www.telecontrol.com.br/assist/credenciamento/contrato/download_contrato.php?id=$id&key=$key&tipo_arquivo=$tipo_arquivo' >Clique aqui para baixar o contrato.</a></p>";
                        
                    echo "</td>";
                    echo "</tr>";
                }
                echo "<tr>";
                    echo "<td align='center'>&nbsp;</td>";
                echo "</tr>";
                echo "</table>";


            }

        }
    }else{
        $msg="N�o foi encontrado nenhum posto com este email ou cnpj";
    }
    if(strlen($msg) > 0) {
        echo $msg;
    }
}


?>

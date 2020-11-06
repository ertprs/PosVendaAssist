
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
                        "Março",
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

                        <h3>CONTRATO DE CREDENCIAMENTO DE ASSISTÊNCIA TÉCNICA</h3>

                        <p>Pelo presente instrumento particular,</p>

                        <p>
                            </strong>FABRICANTE DISTRIBUIDOR,</strong> <strong>FRIGELAR COMERCIO E INDUSTRIA LTDA .</strong>, pessoa jurídica de direito privado, inscrita no CNPJ sob o nº 92.660.406/0001-19, com sede em  Porto Alegre/RS na Av. Pernambuco, 2285, Navegantes, doravante denominada simplesmente <strong>CONTRATANTE e;</strong>
                        </p>

                        <p>
                            <strong>POSTO</strong> {$posto_nome}, com sede em {$cidade} / {$estado}, {$endereco}, {$numero}, {$cep}, inscrita no CNPJ: {$cnpj}, Inscrição Estadual {$ie},, doravante denominado simplesmente de <strong>CONTRATADA,</strong> 
                        </p>

                        <strong><h4>1 – OBJETIVO</h4></strong>
                        <p>
                            O presente <strong>CONTRATO</strong> tem como objetivo a prestação de serviço pela <strong>CONTRATADA</strong> em sua sede social como Assistência Técnica Autorizada das marcas representadas pela <strong>CONTRATANTE</strong>.
                            A <strong>CONTRATADA</strong> declara que possui estrutura física para atendimento ao consumidor e aptidões técnicas para desmonte, testes, avaliações, diagnósticos e conserto da (s) linha (s) de produtos <strong>".implode(",", $linhasAtende)." </strong>da <strong>CONTRATATANTE</strong>.
                        </p>

                        <strong><h4>2 – RESPONSABILIDADES</h4></strong>
                        <p>
                            2.1 A <strong>CONTRATADA</strong> se compromete a seguir  fielmente as normas e procedimento expressamente vinculadas pela Telecontrol e pela <strong>CONTRATANTE</strong>;
                        </p>
                        <p>
                            2.2 Em caso de ações propostas por consumidores, que reste provada a culpa ou dolo da <strong>CONTRATADA</strong>, seus sócios, diretores, gestores, prepostos, colaboradores ou funcionários, esta concorda desde já que deverá assumir integralmente o polo passivo das ações judiciais e extra judiciais, reclamações em Procon, dentre outros órgãos que venham a ser demandadas contra a Telecontrol ou a <strong>CONTRATANTE</strong>;
                        </p>
                        <p>
                            2.3 A <strong>CONTRATADA</strong> deverá manter em sigilo todas informações coletadas de consumidores e se compromete em hipótese alguma a não divulgar tais dados conforme elucida a Lei nº.13709/2018 Lei Geral de Proteção de Dados (LGPD). Ocorrendo vazamento destes dados e comprovado a culpa da <strong>CONTRATADA</strong>, esta assumirá toda e qualquer demanda judicial ou extrajudicial, bem como arcando com eventuais condenações, honorários advocatícios e demais despesas decorrentes, isentando desde já a <strong>CONTRATANTE</strong> e Telecontrol e, de plano isentando-as de qualquer responsabilidade, independentemente do desfecho da demanda;
                        </p>
                        <p>
                            2.4 A <strong>CONTRATADA</strong> deverá seguir a recomendação da <strong>CONTRATANTE</strong> para realizar consertos, teste, diagnósticos e desmonte dos produtos contemplados por este CONTRATO;
                        </p>
                        <p>
                            2.5 A <strong>CONTRATADA</strong> se compromete a preencher corretamente todos os dados e informações do consumidor na ORDEM DE SERVIÇO TELECONTROL, seguindo as orientações e regras da <strong>CONTRATANTE</strong>;
                        </p>
                        <p>
                            2.6 A <strong>CONTRATADA</strong> se compromete a cumprir o que determina o Código de Defesa do Consumidor, contribuindo com a agilidade do processo de atendimento em GARANTIA.
                        </p>
                        <p>
                            2.7 Ônus decorrentes de legislação especial, trabalhista e previdenciária, relativos aos funcionários da <strong>CONTRATADA</strong> são de sua inteira responsabilidade, ficando desde já estabelecido que, ocorrendo reclamação trabalhista ou qualquer contenda cível por parte de qualquer funcionário, ex-funcionários ou contratado da <strong>CONTRATADA</strong> que recaia sobre a <strong>CONTRATANTE</strong> ou Telecontrol, deverá a CONTRATADA  assumir e arcar com eventuais condenações, honorários advocatícios e demais despesas decorrentes, isentando, desde já, a <strong>CONTRATANTE</strong> e  Telecontrol e, de plano concordar com sua exclusão de qualquer responsabilidade, independentemente do desfecho da demanda, sendo todos eles de inteira responsabilidade da <strong>CONTRATADA</strong>
                        </p>

                        <strong><h4>3- PAGAMENTO DA TAXA DE MÃO-DE-OBRA</h4></strong>
                        <p>
                            Os serviços executados em ORDENS DE SERVIÇO (garantia) deverão ser obrigatoriamente registrados no Sistema Telecontrol. Todo dia 20 (vinte) de cada mês a <strong>CONTRATANTE</strong> gerará um extrato com todas as Ordens de Serviços (OS) finalizadas naquele período. A CONTRATADA deverá conferir o extrato e estando de acordo enviar Nota Fiscal de Prestação de Serviços para a <strong>CONTRATANTE</strong> até o dia 05 (cinco) do mês seguinte anexando-a no Sistema Telecontrol, conforme orientações contidas no site da Telecontrol. Dentro de 10 (dez) dias após o recebimento da Nota Fiscal de Prestação de Serviço, o depósito será realizado na conta bancária da <strong>CONTRATADA</strong>.
                        </p>

                        <strong><h4>4- DURAÇÃO DO CONTRATO</h4></strong>
                        <p>
                            O presente CONTRATO é válido por tempo indeterminado e poderá ser rescindido por qualquer das partes, mediante aviso prévio de 30 (trinta) dias, por escrito. A <strong>CONTRATADA</strong> obriga-se, a dar continuidade aos atendimentos dos produtos em seu poder durante o período de aviso de rescisão contratual.
                        </p>

                        <strong><h4>5 – DISPOSIÇÕES GERAIS</h4></strong>
                        <p>
                            A <strong>CONTRATADA</strong> declara conhecer e se compromete a cumprir o disposto no Código de Defesa do Consumidor e assume a responsabilidade de “in vigilando” por seus funcionários para esta finalidade.
                        </p>

                        <strong><h4>6 – FORO</h4></strong>
                        <p>
                            Estando de pleno acordo com todas as cláusulas e condições aqui expostas, elegem as partes contratantes o Foro da Comarca de ( Porto Alegre – RS), para dirimir e resolver toda e qualquer questão proveniente do presente contrato, com expressa renúncia de qualquer outro, por mais privilegiado que seja.
                        </p>
                        <p>
                            E por estarem assim justas e acordadas, firmam o presente instrumento, em duas vias de igual teor e forma, juntamente com as testemunhas abaixo.
                        </p>
                        <p>
                            <center>(Porto Alegre  – RS), ".date("d")." de ".$array_meses[intval(date("m"))]." de ".date("Y")."</center>
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

                        <h3>CONTRATO DE ASSISTÊNCIA TÉCNICA</h3>

                        <p>
                            Pelo presente instrumento particular, de um lado <strong>FERRAGENS NEGRÃO COMERCIAL LTDA.</strong>, pessoa jurídica de direito privado, inscrita no CNPJ sob o nº 76.639.285/0001-77, Inscriçao estadual 90529723-69, com sede em Curitiba - PR na Rua Algacyr Munhoz Mader, 2800, denominada simplesmente <strong>CONTRATANTE</strong> e, de outro lado a 
                            empresa {$posto_nome}, com sede em {$cidade} / {$estado}, {$endereco}, {$numero}, {$cep}, inscrita no CNPJ: {$cnpj}, Inscrição Estadual {$ie}, denominada simplesmente <strong>AUTORIZADA</strong>, tem justo e contratado a prestação de serviços e assistência técnica, que se regerá de conformidade com o disposto nas seguintes cláusulas:
                        </p>

                        <p>
                            <strong>CLÁUSULA 1ª:</strong> Devidamente credenciada, a AUTORIZADA passará a executar serviços de assistência técnica que estiverem em garantia, conforme o ANEXO 1 , a todo e qualquer cliente, consumidor casual ou industrial, independente do município.
                        </p>

                        <p>
                            <strong>CLÁUSULA 2ª:</strong> A <strong>AUTORIZADA</strong>, devidamente credenciada pela <strong>CONTRATANTE</strong> com a assinatura deste contrato, exercerá suas atividades na cidade de {$cidade} / {$estado}, vedada à abertura de filiais com a finalidade objeto deste instrumento em outros municípios, sem expressa concordância da <strong>CONTRATANTE</strong>.
                        </p>

                        <p>
                            <strong>CLÁUSULA 3ª:</strong> A <strong>CONTRATANTE</strong> reserva-se o direito de prestar diretamente assistência técnica dos Produtos Worker, bem como contratar uma ou mais assistências técnicas no mesmo ou em diversos municípios, não sendo, em razão deste fato, devida qualquer indenização à <strong>AUTORIZADA</strong>.
                        </p>

                        <p>
                            <strong>CLÁUSULA 4ª:</strong> Compromete-se a <strong>AUTORIZADA</strong> a manter pessoal, equipamento e instalações condizentes com a atividade ora ajustada, garantindo a eficiência nos serviços.
                        </p>

                        <p>
                            <strong>CLÁUSULA 5ª:</strong> Compromete-se a <strong>AUTORIZADA</strong> a manter pasta de arquivo técnico sempre atualizado com as últimas informações fornecidas pela <strong>CONTRATANTE</strong>.
                        </p>

                        <p>
                            <strong>CLÁUSULA 6ª:</strong> Das funções básicas da <strong>AUTORIZADA</strong>: <br />
                            - Atender as normas estabelecidas pelo Código de Defesa do Consumidor. <br />
                            - Relatar e informar a CONTRATANTE de todas as deficiências encontradas nos produtos por ela distribuídos. <br />
                            - Quando da prestação dos serviços observar os procedimentos e rotinas técnicas compatíveis e/ou adequadas aos respectivos produtos.
                        </p>

                        <p>
                            <strong>CLÁUSULA 7ª:</strong> A <strong>AUTORIZADA</strong> deverá relacionar as peças defeituosas trocadas em garantia, no formulário \"SOLICITAÇÃO DE GARANTIA\", obrigando-se a deixá-las a disposição da <strong>CONTRATANTE</strong> por um período de 90 dias, sob pena de serem consideradas fora de garantia pelo inspetor da <strong>CONTRATANTE</strong> e conseqüentemente não lhe caber qualquer espécie de ressarcimento.
                        </p>

                        <p>
                            <strong>CLÁUSULA 8ª:</strong> Dentro do prazo de garantia as despesas com o transporte da <strong>CONTRATANTE</strong> até a assistência técnica das peças a serem substituídas em garantia correrá por conta da <strong>CONTRATANTE</strong> e fora deste prazo a <strong>AUTORIZADA</strong> arcará com o custo do fretamento.
                        <p>

                        <p>
                            <strong>CLÁUSULA 9ª:</strong> Se o exame técnico procedido nas peças defeituosas trocadas em garantia não constatar as irregularidades apontadas nos relatórios de assistência técnica fica facultado à <strong>CONTRATANTE</strong> debitar da autorizada o respectivo valor bem como a taxa de serviços incidentes.
                        </p>

                        <p>
                            <strong>CLÁUSULA 10ª:</strong> Compromete-se a <strong>CONTRATANTE</strong> fornecer à AUTORIZADA, tabelas de preços, informações de serviços e instruções em geral, modelo de \"SOLICITAÇÃO DE GARANTIA\" e outros que se fizerem necessários, tudo para uma perfeita e constante atualização não só comercial, mas também técnica. 
                        </p>

                        <p>
                            <strong>CLÁUSULA 11ª:</strong> Quando a <strong>CONTRATANTE</strong> promover encontros de aperfeiçoamento técnico, seja em forma de cursos estágios, treinamentos ou outros, será importante a participação de um ou mais técnicos da <strong>AUTORIZADA</strong>.
                        </p>

                        <p>
                            <strong>CLÁUSULA 12ª:</strong> Se a <strong>AUTORIZADA</strong> pretender efetuar publicidade em que envolva as marcas comercializadas pela <strong>CONTRATANTE</strong>, deverá antes, ser comunicada, para que manifeste de forma escrita sua concordância ou não.
                        </p>

                        <p>
                            <strong>CLÁUSULA 13ª:</strong> Se houver a rescisão deste contrato, a <strong>AUTORIZADA</strong> deverá: <br />
                            - Devolver todo material ou equipamento da <strong>CONTRATANTE</strong>, que se encontrem sob sua responsabilidade, instalado ou em uso. <br />
                            - Suspender imediatamente toda e qualquer propaganda, falada, escrita, televisada que envolva ou mencione as marcas comercializadas pela <strong>CONTRATANTE</strong>. <br />
                            - Tirar de circulação todos os impressos que a identifiquem como <strong>AUTORIZADA</strong> das marcas comercializadas pela <strong>CONTRATANTE</strong>.
                        </p>

                        <p>
                            <strong>CLÁUSULA 14ª:</strong> O presente contrato é celebrado pelo prazo de 12 MESES, iniciando-se na data de sua assinatura, podendo ser rescindido nos seguintes casos: <br />
                            - De comum acordo, expressando-se a vontade por escrito. <br />
                            - Por qualquer parte mediante notificação por escrito, com antecedência mínima de 30 dias, sem nenhum ônus ou penalidade. <br />
                            - Se ocorrer a venda ou transferência a terceiros, recuperação judicial ou falência da <strong>CONTRATANTE</strong> ou da <strong>AUTORIZADA</strong>.
                        </p>

                        <p>
                            <strong>CLÁUSULA 15ª:</strong> A <strong>AUTORIZADA</strong> não terá direito a qualquer indenização ou multa, nem será ressarcida pelo eventual estoque de peças das marcas comercializadas pela <strong>CONTRATANTE</strong>, se houver a rescisão deste contrato.
                        </p>

                        <p>
                            <strong>CLÁUSULA 16ª:</strong> O presente contrato não gerará qualquer relação de emprego entre as partes cabendo à <strong>AUTORIZADA</strong>o recolhimento de todos os impostos, taxas, emolumentos, contribuições fiscais, trabalhistas e outras que vierem a incidir direta ou indiretamente sobre os serviços prestados.
                        </p>

                        <p>
                            <strong>CLÁUSULA 17ª:</strong> A <strong>AUTORIZADA</strong> responderá civil e criminalmente por todo e qualquer dano que vier a causar com os serviços prestados, não cabendo à <strong>CONTRATANTE</strong> culpa alguma por eventuais ocorrências.
                        </p>

                        <p>
                            <strong>CLÁUSULA 18ª:</strong> Fica facultado à <strong>CONTRATANTE</strong> designar pessoal especializado para a qualquer momento proceder à vistoria nas instalações da <strong>AUTORIZADA</strong>.
                        </p>

                        <p>
                            <strong>CLÁUSULA 19ª:</strong> Todos os produtos das marcas comercializadas pela <strong>CONTRATANTE</strong> possuem garantia de acordo com o especificado em seu manual e certificado de garantia do produto, salvaguardadas as situações que caracterizem a perda de garantia. <br />
                            - O prazo de garantia é contado a partir da data de emissão da nota fiscal de venda ao consumidor. <br />
                            - Para ter direito a garantia é imprescindível a apresentação da nota fiscal de venda e o correto preenchimento do certificado de garantia.
                        </p>

                        <p>
                            <strong>CLÁUSULA 20ª:</strong> A <strong>CONTRATANTE</strong> pagará à <strong>AUTORIZADA</strong> pelos serviços prestados as importâncias descritas no ANEXO I. <br />
                            - Os valores previstos no ANEXO I, estarão sujeitos, anualmente, à correção monetária calculado pela variação do IGPM ou outro índice que vier a substituí-lo. <br />
                            - Os valores definidos no ANEXO I, serão conhecidos através de relatório de volume de produtos efetivamente consertados, apresentado pela <strong>AUTORIZADA</strong> até o último dia útil de cada mês, para conferência e aprovação pela <strong>CONTRATANTE</strong>, que tem o prazo de dois dias úteis para a liberação deste relatório, para que, a <strong>AUTORIZADA</strong> emita a Nota Fiscal de Prestação de Serviços (PESSOA JURÍDICA) realizados no período constante do relatório. <br />
                            - Os pagamentos definidos no ANEXO I, serão feitos a <strong>CONTRATADA</strong> no 5º (quinto) dia útil de cada mês.
                        </p>

                        <p>
                            <strong>CLÁUSULA 21ª:</strong> Não terão direito á garantia: <br />
                            - Produtos que estiverem fora do prazo de garantia especificado em seu manual. <br />
                            - Produtos que apresentarem sinais de terem sido usados inadequadamente por motivos diversos (imperícia, imprudência ou negligência). <br />
                            - Produtos que apresentarem sinais de má conservação, desgaste natural, de terem sido violados alterados em suas características originais. <br />
                            - A <strong>CONTRATANTE</strong> não pagará por serviços e nem por peças aplicadas em produtos que se encontrarem fora do período de garantia. 
                        </p>

                        <p>
                            <strong>CLÁUSULA 22ª:</strong> Todas as peças substituídas nos produtos que estivem dentro do período de garantia ou não, deverão obrigatoriamente ser originais de fábrica, sob pena de rescisão contratual.
                        </p>

                        <p>
                            <table class='tabela-meses' border='1' cellspacing='0' cellspacing='0'>
                                <tr>
                                    <th colspan='2' align='left'>GARANTIAS:</th>
                                </tr>
                                <tr>
                                    <td>LAVADORA DE ALTA PRESSÃO:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                                <tr>
                                    <td>UMIDIFICADOR:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                                <tr>
                                    <td>BOMBAS CENTRÍFUGAS, PERIFÉRICAS E AUTO ASPIRANTE:</td>
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
                                    <td>MÁQUINAS DE SOLDA MIG MAG:</td>
                                    <td align='right'>12 MESES</td>
                                </tr>
                                <tr>
                                    <td>PNEUMÁTICOS:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                                <tr>
                                    <td>CARREGADORES DE BATERIA:</td>
                                    <td align='right'>6 MESES</td>
                                </tr>
                            </table>
                        </p>

                        <p>
                            <strong>CLÁUSULA 23ª:</strong> Este instrumento torna sem efeito, acordos firmados anteriormente à assinatura deste contrato.
                        </p>

                        <p>
                            <strong>CLÁUSULA 24ª:</strong> A <strong>AUTORIZADA</strong> concorda com a divulgação do seu endereço na relação das assistências técnicas autorizadas dos produtos comercializados pela CONTRATANTE, a qual tem afinidade de informar aos consumidores os locais onde é prestada a assistência técnica objeto deste contrato.
                        </p>

                        <p>
                            <strong>CLÁUSULA 25ª:</strong> A <strong>CONTRATANTE</strong> deverá reembolsar a <strong>AUTORIZADA</strong>, pagamentos dos impostos referentes à Notas Fiscais de peças e produtos enviados, estando dentro da garantia ou não, mediante o <strong>comprovante do imposto em questão. (Para os Estados onde há este regime de imposto diferenciado).</strong>
                        </p>

                        <p>
                            E por estarem certos e ajustados entre si firmam o presente contrato em duas vias de igual teor, elegendo o Fórum de Curitiba, com renúncia a qualquer outro, por mais privilegiado que seja, para dirimir eventuais dúvidas ou questões relativas a este contrato.
                        </p>

                        <br /> <br /> <br />

                        <strong>__________________________________________________ , ".date("d")." de ".$array_meses[intval(date("m"))]." de ".date("Y")."</strong>

                        <br /> <br /> <br /> <br /> <br />

                        <p style='text-align: center;'>
                            <strong>
                                __________________________________________________ <br />
                                FERRAGENS NEGRÃO COMERCIAL LTDA.
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

                        <h3>CONTRATO DE CREDENCIAMENTO DE ASSISTÊNCIA TÉCNICA</h3>

                        <p>
                            Pelo presente instrumento particular,
                        </p>

                        <p>
                            <strong>TELECONTROL SISTEMA LTDA</strong>, sociedade empresarial com escritório administrativo na Av. Carlos Artêncio, 420 A, Bairro Fragata C, CEP 17.519-255, na cidade de Marília, estado de São Paulo, inscrita no CNPJ sob nº 04.716.427/0001-41, neste ato representada por seu diretor ao final assinado, doravante denominada simplesmente \"TELECONTROL NETWORKING\", e
                        </p>

                        <p>
                            <strong> {$posto_nome} </strong>, sociedade empresarial com sede na {$endereco}, {$numero}, na cidade de {$cidade}, {$estado}, CEP {$cep}, inscrita no CNPJ sob nº {$cnpj}, neste ato representada por seu administrador, ao final assinado, doravante denominada \"AUTORIZADA\",
                        </p>

                        <p>
                            <strong>1 - OBJETIVO</strong>
                        </p>

                        <p> 
                            1.1. O objetivo do presente contrato é a prestação, pela AUTORIZADA, em sua sede social, do serviço de assistência técnica das empresas que contratarem a TELECONTROL para a dministrar o pós-venda.
                        </p>

                        <p>
                            <strong>2 - PAGAMENTO DA TAXA DE MÃO-DE-OBRA</strong>
                        </p>

                        <p>
                            Os serviços executados deverão ser registrados no site da TELECONTROL. No dia 1 de cada mês será gerado um extrato com todas as ordens de serviços finalizadas. A AUTORIZADA deverá imprimir o extrato e enviar nota fiscal de prestação de serviços para a TELECONTROL, conforme orientações no site, no último dia útil do mês, o depósito será realizado na conta bancária da AUTORIZADA.
                        </p>

                        <p>
                            <strong>3 - DURAÇÃO DO CONTRATO</strong>
                        </p>

                        <p>
                            3.1. A validade do presente contrato é por tempo indeterminado e poderá ser rescindido por qualquer das partes, mediante um aviso prévio de 30 (trinta) dias, por escrito. A autorizada obriga-se, neste prazo do aviso, a dar continuidade aos atendimentos dos produtos em seu poder.
                        </p>

                        <p>
                            <strong>4 - RESPONSABILIDADES</strong>
                        </p>

                        <p>
                            4.1 A AUTORIZADA se compromete a seguir as normas de procedimento expressamente veiculadas pela TELECONTROL;
                        </p>

                        <p>
                            4.2 Em caso de ações propostas por consumidores, que reste provada a culpa ou dolo da AUTORIZADA, seus sócios, diretores, prepostos, colaboradores ou empregados, esta concorda desde já que deverá assumir e integrar o polo passivo da sações judiciais que venham a ser demandadas contra a TELECONTROL e a empresas proprietárias dos produtos em questão.
                        </p>

                        <p>
                            <strong>5- DISPOSIÇÕES GERAIS</strong>
                        </p>

                        <p>
                            5.1. A AUTORIZADA declara conhecer e se compromete a cumprir o disposto no Código de Defesa do Consumidor e assume a responsabilidade de 'in vigilando' por seus funcionários para esta finalidade.
                        </p>

                        <p>
                            <strong>6- FORO</strong>
                        </p>

                        <p>
                            Estando de pleno acordo com todas as cláusulas e condições aqui expostas, elegem as partes contratantes o Foro da Comarca de Marília, estado de São Paulo, para dirimir e resolver toda e qualquer questão, proveniente do presente contrato, com expressa renuncia de qualquer outro, por mais privilegiado que seja.
                        </p>

                        <p>
                            E, por estarem assim justas e acertadas, firmam o presente instrumento, em duas vias de igual teor e forma, juntamente com as testemunhas abaixo indicadas.
                        </p>

                        <p>
                            Marília, ".date("d")." de ".$array_meses[intval(date("m"))]." de ".date("Y")."
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
                        <h4>CONTRATO DE CREDENCIAMENTO E PRESTAÇÃO DE SERVIÇOS DE ASSISTÊNCIA TÉCNICA</h4>

                        <p>
                            Pelo presente instrumento particular, as Partes
                        </p>

                        <p>
                            <b>CONTRATANTE: ÂNCORA CHUMBADORES LTDA.</b>, pessoa jurídica de direito privado, com sede na cidade de Vinhedo, Estado de São Paulo, na Avenida Benedito Storani nº 1345, bairro Santa Rosa, CEP 13289-004, com inscrição no CNPJ/MF sob o nº 67.647.412/0004-31; e
                        </p>

                        <p>
                            <b> CONTRATADA: {$posto_nome} </b>, pessoa jurídica de direito privado, com sede na {$endereco}, {$numero}, bairro {$bairro}, na cidade de {$cidade}, {$estado}, CEP {$cep}, com inscrição no CNPJ/MF sob nº {$cnpj}, representadas na forma de seus respectivos atos societários por seus representantes abaixo assinados; e,
                        </p>

                        <p>
                            CONSIDERANDO QUE:
                        </p>

                        < <p>(a) a CONTRATANTE tem por atividade o comércio, a importação e a distribuição de ferramentas e equipamentos, tais como furadeiras elétricas, lixadeiras, máquinas de solda, equipamentos de jardinagem, compressores, lavadoras de alta pressão, serras elétricas e bombas d´água;</p>

                        <p>(b) a CONTRATADA, no exercício de suas atividades, declara ter as condições técnicas para integrar a Rede de Assistência Técnica Credenciada da CONTRATANTE, por ser uma empresa especializada no ramo de manutenção e conserto de ferramentas e equipamentos;</p>

                        <p>têm entre si justo e acordado firmar o presente Contrato de Credenciamento e de Prestação de Serviços de Assistência Técnica (doravante designado ¨Contrato¨), o qual será regido pelas seguintes cláusulas e condições:</p>
                        <p></p>
                        <p></p>
                        <p class='tac'>
                            <b>CAPÍTULO I - DO CREDENCIAMENTO</b>
                        </p>

                        <p>
                            <b>CLÁUSULA PRIMEIRA: </b> A CONTRATANTE, detentora exclusiva dos direitos de uso no Brasil do nome e da marca <b>EINHELL</b>, devidamente registrada no Instituto Nacional de Propriedade Industrial - INPI, neste ato nomeia a CONTRATADA, <u>sem exclusividade</u>, como <b>Assistência Técnica Autorizada</b> dos produtos pela primeira comercializados, com o objetivo de obter êxito e qualidade na prestação
                         de serviços de assistência aos consumidores e revendedores, durante e após os períodos de garantia legal e/ou contratual dos respectivos produtos. </p>
                        <p>
                            <b>CLÁUSULA SEGUNDA: </b> A CONTRATADA é responsável pelo bom e adequado atendimento aos consumidores e revendedores dos produtos da marca EINHELL, de acordo com as estipulações do presente instrumento, Manuais e Termos de Garantia dos produtos EINHELL e, sobretudo, de acordo com as especificações do Código de Defesa e Proteção do Consumidor (Lei 8.078, de 11.9.90).
                        </p>
                        <p></p>
                        <p class='tac'><img width='300' src='einhell/logo_en2.jpg'/></p>
                        <p></p>
                        <p></p>
                        <p class='tar'><img width='140' src='einhell/logo_en1.jpg'/></p>
                        <p>
                            <b>CLÁUSULA TERCEIRA:</b> Qualquer alteração a ser realizada no Contrato Social da CONTRATADA deverá ser precedida de prévia ciência à CONTRATANTE com prazo mínimo de 10 (dez) dias de antecedência.
                        </p>

                        <p>
                            <b>CLÁUSULA QUARTA:</b> A CONTRATADA é obrigada a fazer periodicamente a atualização dos seus dados e documentos cadastrais, sendo de sua exclusiva responsabilidade perda ou extravio de quaisquer materiais, documentos, peças, etc., decorrentes da ausência de comunicação prévia acerca de quaisquer mudanças que venha a sofrer.
                        </p>
                        <p>
                            <b>CLÁUSULA QUINTA:</b> A CONTRATANTE e a CONTRATADA são pessoas jurídicas independentes e distintas, estando, portanto, a CONTRATADA, obrigada a cumprir todas as cláusulas do presente instrumento, e estando a CONTRATANTE isenta de toda e qualquer responsabilidade por encargos e obrigações contraídas pela CONTRATADA, seja de ordem civil, tributária, providenciaria, trabalhista, penal ou quaisquer outras decorrentes do exercício do objeto do presente contrato.
                        </p>

                        <p>
                            <b>CLÁUSULA SEXTA:</b> Durante a vigência do presente instrumento e enquanto a CONTRATADA integrar a Rede Credenciada de Assistência Técnica Autorizada da CONTRATANTE está obrigada a:
                        </p>
                        <p></p>

                        <p>I. Preservar a imagem, o nome, a marca e o bom conceito dos produtos da CONTRATANTE.</p>


                        <p>II. Possuir em seus quadros funcionais, profissionais com capacidade e conhecimento técnico para prestar assistência, atender e resolver as reclamações e solicitações dos Consumidores, independente de os produtos estarem dentro do período de garantia legal ou contratual.</p>

                        <p>III. Somente utilizar a expressÃ£o <b>¨REDE CREDENCIADA EINHELL¨</b> ou <b><u>Assistência Técnica Autorizada EINHELL</u></b>, durante a vigência do presente instrumento, sendo expressamente vedado o nome e a marca EINHELL para quaisquer outras finalidades. </p>

                        <p>IV. Disponibilizar em local de fácil visualização todo material que lhe for fornecido com a identificação visual da CONTRATANTE, assumindo todo e qualquer custo de exposição que possa ser exigido em virtude de leis federais, estaduais e/ou municipais.</p>

                        <p>V. Manter o prédio e o interior do seu estabelecimento em perfeito estado de conservação, limpeza e organização.</p>
                        <p class='tac'>
                            <b>CAPÍTULO II - DA PRESTAÇÃO DOS SERVIÇOS DE ASSISTÊNCIA TÉCNICA</b>
                        </p>
                        <p>
                            <b>CLÁUSULA SÉTIMA:</b> A CONTRATADA se obriga a realizar serviÃ§os de assistência técnica nos produtos comercializados pela CONTRATANTE, sempre que solicitado por consumidores e revendedores, compreendendo a substituição de peças e serviços de mão-de-obra, durante e após os respectivos períodos de garantia legal ou contratual, observadas as condições do presente contrato.
                        </p>
                        <p></p>
                        <p></p>
                        <p class='tac'><img width='300' src='einhell/logo_en2.jpg'/></p>
                        <p></p>
                        <p></p>
                        <p class='tar'><img width='140' src='einhell/logo_en1.jpg'/></p>
                        <p>
                            <b>CLÁUSULA OITAVA:</b> Os serviços de assistência técnica solicitados pelos consumidores e revendedores, deverão ser concluídos pela CONTRATADA no prazo máximo de <u>20 (vinte) dias corridos</u>, contados da data da recebimento do(s) produto(s) para reparo, a qual deve ser devidamente documentada por meio de ordem de serviço ou outro documento que registre a data de entrada.
                        </p>
                        <p class='paragrafo'>
                            <b>Parágrafo Primeiro:</b> A CONTRATADA obriga-se a respeitar o prazo acima e, quando for necessária sua dilação para a solução do problema detectado no produto, deverá notificar formalmente o Suporte da CONTRATANTE, de forma obter o acompanhamento da CONTRATANTE ao atendimento ao consumidor. Seja qual for o problema, a CONTRATADA jamais poderá ultrapassar o prazo máximo de 30 (trinta) dias, a contar da solicitação do serviço de assistência técnica pelo consumidor, para a resolução do problema, conforme prevê o parágrafo 1º do artigo 18 e demais disposições do Código de Defesa do Consumidor.
                        </p>
                        <p class='paragrafo'>
                            <b>Parágrafo Segundo:</b> O descumprimento dos prazos acima, implicará na responsabilização da    CONTRATADA, sendo-lhe debitado o valor do novo produto que venha a ser fornecido ao consumidor ou revendedor em substituição àquele deixado para conserto, sem prejuízo de a CONTRATADA responder, direta ou regressivamente, pelas perdas e danos e/ou prejuízos a que der causa à CONTRATANTE e/ou o consumidor/revendedor, incluídos honorários advocatícios e custas processuais.
                        </p>
                        <p>
                            <b>CLÁUSULA NONA:</b> Pelos serviços de mão de obra de assistência técnica executados nos produtos, a CONTRATANTE pagará à CONTRATADA, os valores constantes da Tabela de Taxas de Serviços respectiva, emitida pela CONTRATANTE, vigente à época do processamento das Ordens de Serviços.
                            Estes serviços serão pagos mensalmente, no prazo de 10 (dez) dias contados da data do envio do 'Formulário dos Serviços Prestados acompanhado' das respectivas Ordens de Serviços e Nota Fiscal de Prestação de Serviços.
                        </p>
                        <p>
                            <b>CLÁUSULA DÉCIMA:</b> O fornecimento e a distribuição de peças originais a serem utilizadas nos serviços de assistência técnica, serão realizados exclusivamente pela CONTRATANTE, ou por outra parte designada pela CONTRATANTE.
                        </p>  
                        <p>
                            <b>CLÁUSULA DÉCIMA PRIMEIRA:</b> Nos serviços prestados durante e após o prazo de garantia, a CONTRATADA empregará, exclusivamente peças originais fornecidas pela CONTRATANTE, responsabilizando-se integralmente pela qualidade dos serviços prestados, como previsto no presente instrumento.
                        </p>
                        <p>
                            <b>CLÁUSULA DÉCIMA SEGUNDA:</b> A CONTRATADA não é obrigada a manter um estoque mínimo permanente de peças que permita o atendimento eficiente aos revendedores, consumidores, entretanto, responderá, nos termos da Cláusula Oitava, pelo atraso decorrente da demora na solicitação de peças à CONTRATANTE.
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
                            <b>CLÁUSULA DÉCIMA TERCEIRA:</b> A CONTRATANTE enviará sem qualquer ônus para a CONTRATADA, as peças defeituosas dos produtos consertados durante a garantia (legal ou contratual), desde que tais peças estejam abrangidas pelas condições constantes do Certificado de Garantia respectivo, sendo de responsabilidade da CONTRATADA a utilização de peças originais fornecidas pela CONTRATANTE ou por quem esta indicar, sob pena de submeter o produto à perda de garantia e responder civil e criminalmente à CONTRATANTE, sem prejuízo das perdas e danos a que der causa.
                        </p>
                        <p>
                            <b>CLÁUSULA DÉCIMA QUARTA:</b> A solicitação de peças pela CONTRATADA se dará mediante a colocação de pedidos por comunicação via sistema telecontrol.
                        </p>
                        <p class='paragrafo'>
                            <b>Parágrafo Primeiro:</b> A CONTRATANTE remeterá as peças por Correio ou transportadora, no prazo de 48 horas, contadas do recebimento da solicitação da CONTRATADA.
                        </p>
                        <p class='paragrafo'>
                            <b>Parágrafo Segundo:</b> O pagamento pela aquisição das peças utilizadas em produtos que estejam fora do prazo de garantia, será feito por boleto bancário enviado pela CONTRATANTE, ou pelo seu preposto, à CONTRATADA, juntamente com as peças, nos respectivos valores e vencimentos previamente acordados.
                        </p>
                        <p></p>
                        <p></p>
                        <p class='tac'>
                            <b>CAPÍTULO III - DAS CONDIÇÕES GERAIS</b>
                        </p>
                        <p>
                            <b>CLÁUSULA DÉCIMA QUINTA:</b> O presente Contrato terá prazo de 12 (doze) meses a contar da data de sua assinatura e poderá ser renovado, mediante comunicação escrita entre as partes com, no mínimo, 30 (trinta) dias da antecedência do seu término, cabendo as partes, à época, estabelecerem, se for o caso, novas condições contratuais, modificando dessa forma as cláusulas correspondentes. 
                        </p>
                        <p>
                            <b>CLÁUSULA DÉCIMA SEXTA:</b> Este Contrato poderá ser rescindido imotivadamente, por qualquer das partes, a qualquer tempo e sem qualquer ônus para qualquer delas, mediante comunicação escrita enviada por uma à outra com antecedência mínima de 30 (trinta) dias. A rescisão imotivada deste Contrato não eximirá nenhuma das partes das responsabilidades e obrigações por elas contraídas inerentes a este Contrato.
                        </p>
                        <p class='paragrafo'>
                            <b>Parágrafo Primeiro:</b> Em caso de inadimplemento ou descumprimento de quaisquer das disposições contidas neste Contrato, a parte prejudicada notificará a outra para, no prazo de 15 (quinze) dias a contar do recebimento da notificação, sanar e remediar a falha ou omissão. Permanecendo a falha ou omissão, independentemente de novo aviso ou notificação, o presente Contrato estará automaticamente rescindido de pleno direito, sem prejuízo do direito da parte prejudicada de exigir da parte infratora o ressarcimento por eventuais prejuízos apurados em decorrência do descumprimento contratual.
                        </p>
                        <p></p>
                        <p></p>
                        <p class='tac'><img width='300' src='einhell/logo_en2.jpg'/></p>
                        <p></p>
                        <p></p>
                        <p class='tar'><img width='140' src='einhell/logo_en1.jpg'/></p>
                        <p class='paragrafo'>
                            <b>Parágrafo Segundo:</b> Este Contrato poderá ser rescindido por ato unilateral da CONTRATANTE, que se dará independentemente de qualquer procedimento judicial ou extrajudicial, não sendo devido o pagamento de qualquer indenização à CONTRATADA, se ocorridos quaisquer dos eventos abaixo relacionados:
                        </p>
                        <p class='paragrafo'>
                            (i) A decretação de falência, de recuperação judicial, de dissolução judicial ou liquidação extrajudicial da CONTRATADA que possa pôr em risco o cumprimento deste Contrato; ou
                        </p>
                        <p class='paragrafo'>
                            (ii)    A alteração ou a modificação da finalidade ou da estrutura societária da CONTRATADA, que venha a prejudicar o cumprimento deste Contrato.
                        </p>
                        <p>
                            <b>CLÁUSULA DÉCIMA SÉTIMA:</b> Este Contrato representa o acordo integral celebrado entre as partes e os seus termos e disposições prevalecerão sobre quaisquer outros entendimentos ou acordos anteriores mantidos entre as Partes, expressos ou implícitos, referentes às condições neles estabelecidas. Dessa forma, todos os documentos, minutas, cartas ou notas que possam existir até esta data, além de todas as declarações e garantias que possam ter sido feitas por ou a favor de cada uma das partes, tornam-se nulas e sem efeito.
                        </p>
                        <p>
                            <b>CLÁUSULA DÉCIMA OITAVA:</b> O disposto neste Contrato não poderá ser alterado ou emendado, a não ser por meio de termo aditivo formal ou epistolar, subscrito pelas partes. 
                        </p>
                        <p>
                            <b>CLÁUSULA DÉCIMA NONA:</b> Salvo expressa disposição em contrário, todos os prazos e condições do Contrato vencem independentemente de aviso, interpelação judicial ou extrajudicial.
                        </p>
                        <p>
                            <b>CLÁUSULA VIGÉSIMA:</b> O não exercício por qualquer das Partes de seus direitos ou a não exigência do cumprimento de obrigações contraídas pela parte contrária nos prazos convencionados, não importará em renúncia ou novação e não impedirá o seu exercício ou a sua exigência em qualquer tempo.
                        </p>
                        <p>
                            <b>CLÁUSULA VIGÉSIMA PRIMEIRA:</b> A CONTRATADA não está, sob nenhuma circunstância, autorizada a representar a CONTRATANTE perante qualquer empresa privada, pública e afins. A CONTRATADA, por si, seus sócios, administradores, empregados e prepostos, declaram-se cientes de que o uso de quaisquer meios não expressamente autorizados pela CONTRATADA, bem como meios antiéticos ou ilícitos por ventura utilizados com o intuito de facilitar negociações para e/ou com a CONTRATANTE, terá como conseqüência imediata, a rescisão do presente Contrato.
                        </p>

                        <p>
                            <b>CLÁUSULA VIGÉSIMA SEGUNDA:</b> A CONTRATADA manterá registros genuínos e exatos dos serviços prestados e de todas as transações a eles relacionadas. Todos os registros serão mantidos por um mínimo de 24 (vinte e quatro) meses a partir da data da execução dos serviços. A qualquer momento, durante a vigência deste Contrato ou durante 24 (vinte e quatro) meses contados da conclusão dos serviços, a CONTRATANTE poderá realizar uma auditoria nos registros da CONTRATADA passíveis de serem publicados e relevantes ao objeto deste Contrato, no que se refere aos serviços prestados e pagamentos efetuados.
                        </p>
                        <p></p>
                        <p></p>
                        <p class='tac'><img width='300' src='einhell/logo_en2.jpg'/></p>
                        <p></p>
                        <p></p>
                        <p class='tar'><img width='140' src='einhell/logo_en1.jpg'/></p>
                        <p>
                            <b>CLÁUSULA VIGÉSIMA TERCEIRA:</b> Nenhuma das partes poderá ceder ou transferir a terceiros, total ou parcialmente, este Contrato e/ou qualquer direito ou obrigação dele decorrente ou a ele relacionados, sem o consentimento prévio, por escrito, da outra parte.
                        </p>

                        <p>
                            <b>CLÁUSULA VIGÉSIMA QUARTA:</b> As partes elegem o foro da Comarca de Vinhedo, Estado de São Paulo, para dirimir quaisquer questões oriundas do presente contrato, com exclusão de qualquer outro por mais privilegiado que seja.
                        </p>
                        <p>
                            E, por estarem justas e contratadas, as partes assinam o presente contrato em 2 (duas) vias de igual teor e forma, na presença de 2 testemunhas.
                        </p>


                        <p>
                            Vinhedo/SP, ".date("d")." de ".$array_meses[intval(date("m"))]." de ".date("Y")."
                        </p>

                        <br /> <br /> <br />

                        <table width=100% >
                            <tr>
                                <td width=45% align=left nowrap><b>ÂNCORA CHUMBADORES LTDA.</b><br /><br /><br /></td>
                                <td width=10% align=left nowrap>&nbsp;</td>
                                <td width=45% align=left nowrap><b style='text-transform:uppercase;'>".strtoupper($posto_nome)."</b><br /><br /><br /></td>
                            </tr>
                            <tr>
                                <td width=45% align=left nowrap>
                                    <hr width=100% size=1 color=#000 />
                                    <b>José Roberto Bernardi - Diretor Comercial</b>
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
                'Tue' => 'Terça-Feira',
                'Wed' => 'Quarta-Feira',
                'Thu' => 'Quinta-Feira',
                'Fri' => 'Sexta-Feira',
                'Sat' => 'Sábado'
            );
            
            $mes_extenso = array(
                'Jan' => 'Janeiro',
                'Feb' => 'Fevereiro',
                'Mar' => 'Março',
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
                            <strong>CONTRATANTE: JCS BRASIL ELETRODOMÉSTICOS S.A.</strong>, sociedade anônima de capital fechado, inscrita no CNPJ sob o nº. 03.106.170/0002-24, situada a Avenida Takata, Km 101, n.º 3309, Bairro Nossa Senhora da Conceição, na cidade de Balneário Piçarras/SC, por seus representantes legais ao final assinados, nos termos do Estatuto Social, doravante denominada CONTRATANTE.<br>
                        </p>
                        <p>
                            <strong>CONTRATADA: {$posto_nome}, sociedade empresarial limitada, com sede na cidade de {$cidade}/{$estado}, na {$endereco}, $numero - {$bairro} - CEP {$cep}, inscrita no CNPJ/MF sob o nº {$cnpj}</strong>, por seu representante legal ao final assinado, nos termos do Contrato Social, doravante denominada CONTRATADA.<br>
                        </p>
                        <p>
                            <strong>I-CONSIDERAÇÕES PRELIMINARES</strong><br>
                        </p>
                        <p>
                            1.1 A CONTRATANTE é empresa do ramo de eletro portáteis, e que atua no mercado nacional sem qualquer mácula ou ofensa ao seu bom nome e imagem. A CONTRATANTE tem por princípios fabricar e comercializar produtos voltados a satisfação dos clientes e consumidores, prezando pela qualidade, inovação.<br>
                        </p>
                        <p>
                            1.2 A CONTRATANTE tem ciência que toda e qualquer atuação sua e de seus parceiros devem ser pautadas na ética, boa-fé, equidade, qualidade, clareza e segurança, atendendo, seja diretamente, ou através de seus parceiros, as especificações e determinações do Código de Defesa do Consumidor e legislações técnicas.<br>
                        </p>
                        <p>                            
                            1.3 A CONTRATADA tem ciência que em razão de sua condição de Serviço Técnico Autorizado deve respeitar as normas de segurança e qualidade e orientações de atendimento da CONTRATANTE, bem como, a obrigação de agir com lisura, correição e boa fé no trato com os clientes, além de zelar pelo nome e imagem da mesma no mercado e perante os clientes.<br>
                        </p>
                        <p>                            
                            1.4 A legalidade da representatividade legal deverá ser comprovada com a juntada da cópia de contrato social, ou qualquer outro instrumento legal que comprove a autonomia do representante, não sendo aceita a assinatura do presente instrumento por outra pessoa não autorizada nos moldes legais. As partes têm entre si, justo e acertado, o presente contrato de prestação de serviços de Assistência Técnica, regido pelas seguintes cláusulas:
                        </p>
                        <p>
                            <strong>II-DO OBJETO</strong><br>
                        </p>
                        <p>
                            2.1 As partes firmam o presente contrato que tem por objetivo credenciar a CONTRATADA como pessoa jurídica autorizada para a prestação de serviço técnico autorizado para os produtos industrializados e comercializados pela CONTRATANTE, nos casos de reparo dentro e fora do período determinado de garantia. Os preços relacionados aos serviços realizados dentro do prazo de garantia encontram-se em Tabela de Preço anexa ao presente instrumento.<br>
                        </p>
                        <p>                        
                            2.2 A CONTRATADA obriga-se a prestar serviços de assistência técnica e de reposição de peças defeituosas para os produtos comercializados pela CONTRATANTE em vários modelos, que podem ser das marcas Oster e Cadence.<br>
                        </p>
                        <p>
                            2.3 Os serviços de assistência técnica, previstos neste contrato, serão prestados no endereço da CONTRATADA, ou conforme a solicitação da CONTRATANTE, na condição de serviço externo com ressarcimento da quilometragem percorrida.
                        </p>
                        <p>
                            2.4 O presente contrato não impede que a CONTRATADA preste serviços de assistência técnica a outros produtos que não sejam do grupo JCS BRASIL, bem como, não impossibilita por parte da CONTRATANTE a contratação de outros prestadores de serviços ainda que na mesma localidade.<br>
                        </p>
                        <p>
                            2.5 A CONTRATANTE reserva-se o direito de efetuar serviços de Assistência Técnica na região de atuação da CONTRATADA, enviando profissional para que realize o atendimento diretamente ao cliente, bem como, nomeando para nela atuar mais de um prestador de Serviço Técnico Autorizado.<br>
                        </p>
                        <p>
                            <strong>III-DO PRAZO</strong><br>
                        </p>
                        <p>
                            3.1 O prazo de vigência do contrato é indeterminado, ficando assegurado às partes, o direito de, imotivadamente, a qualquer momento, rescindir unilateralmente o presente Contrato mediante aviso prévio e escrito à outra parte, com antecedência mínima de 30 (trinta) dias, sem pagamento de multa, ressalvadas as obrigações correspondentes aos serviços já iniciados.<br>
                        </p>
                        <p>
                            3.2 A CONTRATANTE deverá realizar o pagamento dos serviços realizados, conforme Tabela de Preços anexa, relativos às Ordens de Serviços atendidas neste período. A CONTRATADA se compromete a finalizar todos os atendimentos no prazo do aviso prévio, ou seja, no máximo 30 dias. Os serviços deverão ser concluídos nos termos desta avença. Importante ressaltar que a parte solicita o descredenciamento com 30 dias de antecedência e terá esse prazo para finalizar os atendimentos.<br>
                        </p>
                        <p>
                            3.3 O presente contrato poderá ser rescindido imediatamente, independente de notificação prévia, nos casos de descumprimento de qualquer uma das cláusulas previstas neste contrato, sem direito a qualquer indenização.<br>
                        </p>
                        <p>
                            <strong>IV-DO PAGAMENTO</strong><br>
                        </p>
                        <p>
                            4.1 Pela prestação de serviços realizada dentro do período de garantia do produto, a CONTRATADA receberá da CONTRATANTE valor relativo à mão-de-obra, conforme tabela de preços anexa, emitida pela CONTRATANTE. Ressalta-se que essa tabela poderá ter os valores reajustados de acordo com análise da CONTRATANTE. Os valores reajustados, bem como, os valores relativos a novos modelos de produtos serão informados por meio do sistema de gestão de Ordens de Serviço, ou estarão disponíveis para consulta no Sistema de gestão de Ordens de Serviço.<br>
                        </p>
                        <p>
                            4.2 Os serviços prestados pela CONTRATADA dentro do período de garantia do produto, deverão ser realizados no prazo máximo de 30 dias, contados a partir da data de recebimento do produto pela CONTRATADA, conforme previsto no Código de Defesa do Consumidor. Caso o atendimento não seja realizado no período de 30 dias, a CONTRATANTE reserva-se ao direito de cobrar da CONTRATADA os valores referentes à substituição do produto, restituição do valor pago pelo produto ao consumidor e demais valores oriundos de processos judiciais. Importante ressaltar que o tempo de atendimento será medido a partir da data da entrega do produto pelo consumidor à CONTRATADA, data de início, e finalizará na data de encerramento da Ordem de Serviço no Sistema de Gestão de Ordens de Serviço.<br>
                        </p>
                        <p>                            
                            4.3 As peças necessárias ao reparo dos produtos em garantia serão repostas à Assistência Técnica mediante solicitação, que deve ser realizada pela própria Ordem de Serviço através do Sistema de Gestão de Ordens de Serviço.<br>
                        </p>
                        <p>                            
                            4.4 Para poder usufruir dos serviços de assistência técnica gratuita, os adquirentes dos produtos deverão apresentar à CONTRATADA a respectiva nota fiscal de compra do produto, emitida pela CONTRATANTE, ou por seus revendedores, onde conste de uma forma precisa e detalhada a data da venda, a identificação do produto e demais informações pertinentes, quando se tratar de garantia de fábrica. A garantia de fábrica consta no Manual do Usuário que acompanha o produto.
                        </p>
                        <p>                            
                            4.5 Os serviços de assistência técnica gratuita, realizados durante o período de garantia, ficam inteiramente subordinados à observância dos termos da mesma e não incluem a substituição de peças gastas ou estragadas em decorrência de uso inadequado, ou danos decorrentes de quedas, ou uso em desacordo com o manual de instruções.<br>
                        </p>
                        <p>
                            4.6 Pelos serviços executados fora do período de garantia de fábrica, a CONTRATADA deverá cobrar do proprietário do produto consertado uma remuneração razoável e compatível com os serviços realizados, bem como, um valor adequado pelas peças utilizadas no conserto. O atendimento fora do prazo de garantia deverá ser realizado no período de até 30 dias.<br>
                        </p>
                        <p>
                            4.7 O frete referente às peças solicitadas para atendimentos de garantia de fábrica será de responsabilidade da CONTRATANTE.<br>
                        </p>
                        <p>                            
                            4.8 O frete referente às peças solicitadas para atendimentos fora do período de garantia de fábrica será de responsabilidade da CONTRATADA.<br>
                        </p>
                        <p>                            
                            4.9 Sempre que houver TROCA de produtos, o respectivo valor da Mão de Obra sofrerá redução, de acordo com a tabela anexa, inclusive os produtos de TROCA OBRIGATÓRIA, os quais são assim denominados a critério da CONTRATANTE. A CONTRATANTE poderá, a seu critério, alterar a classificação dos produtos de TROCA OBRIGATÓRIA e valor de Mão de Obra relativo a esses produtos que serão informados por meio do sistema de gestão de Ordens de Serviço ou estarão disponíveis para consulta no Sistema de gestão de Ordens de Serviço.<br>
                        </p>
                        <p>                            
                            4.10 A critério do CONTRATANTE, os valores de Mão de Obra poderão ser cancelados, caso o contratado: não cumpra o prazo mínimo legal de conserto; abra atendimento reincidente decorrente de ineficiência administrativa e/ou técnica; abra atendimentos não cobertos pela garantia legal.<br>
                        </p>
                        <p>                            
                            4.11 Para os produtos pertencentes às Linhas de Atendimento COM DESLOCAMENTO, a CONTRATANTE pagará o valor de R$ 0,60 por KM rodado. O cálculo da distância, bem como, do respectivo valor, será realizado automaticamente pelo sistema e passará por auditoria. Fica a critério da CONTRATANTE aprovar, ou não, a distância indicada pela CONTRATADA.<br>
                        </p>
                        <p>                        
                            4.12 O pagamento do valor dos serviços prestados devido à CONTRATADA será realizado pela CONTRATANTE após o recebimento dos documentos e componentes a seguir descritos:
                                <ul style='list-style-type:none'>
                                    <li>Nota fiscal de prestação de serviços contendo o(s) numero(s) do(s) extrato(s), englobando totalidade dos serviços prestados;</li>
                                    <li>Nota fiscal de devolução com respectivas peças e produtos retornáveis (devolução obrigatória demanda pelo extrato, que é gerado através do Sistema de gestão de Ordens de Serviço);</li>
                                </ul>
                        </p>
                        <p>                                    
                            4.13 O pagamento será efetuado através de depósito bancário, sendo que os dados bancários (Banco, Agência e Conta Corrente) serão indicados pela CONTRATADA. A conta corrente informada deverá ser de titularidade da empresa contratada.<br>
                        </p>
                        <p>                            
                            4.14 O controle de ordens de serviço e solicitação de peças deverá ser realizado pela CONTRATADA via Sistema de Gestão de Ordens de Serviço. O Sistema de Gestão de Ordens de Serviço atualmente utilizado pela CONTRATANTE é o Telecontrol, que será utilizado pela CONTRATADA, ficando a critério da CONTRATANTE a alteração quando entender necessário, e sob a responsabilidade da CONTRATADA a utilização da nova plataforma.<br>
                        </p>
                        <p>                            
                            4.15 Na hipótese da CONTRATANTE ser credora da CONTRATADA de importâncias já vencidas, devidas em função do fornecimento de peças de fabricação da CONTRATANTE, a CONTRATADA desde já autoriza a CONTRATANTE compensar tais valores com àqueles devidos à CONTRATADA, fazendo-se os ajustes contábeis necessários.<br>
                        </p>
                        <p>                            
                            4.16 Na hipótese da CONTRATANTE ser credora da CONTRATADA de importâncias já vencidas e, não havendo a possibilidade de compensação conforme estabelecido na cláusula anterior, a CONTRATADA autoriza desde já a CONTRATANTE a ingressar com as medidas judiciais e extrajudiciais (Protesto) cabíveis para a sua cobrança.<br>
                        </p>
                        <p>
                            <strong>V-DOS ENCARGOS</strong><br>
                        </p>
                        <p>
                            5.1 A CONTRATADA é responsável por todos os encargos de natureza tributária, previdenciária , ou outros que venham a incidir sobre os valores dos serviços prestados, sendo permitido à CONTRATANTE efetuar as retenções e os recolhimentos previstos em lei.<br>
                        </p>
                        <p>                            
                            5.2 Havendo a necessidade da CONTRATADA realizar o pagamento de despesas adicionais relacionadas à prestação de serviço EM GARANTIA, poderá ser ressarcida dos valores devidamente pagos mediante a apresentação de NOTA DE DÉBITO, desde que haja a autorização prévia da CONTRATANTE e a comprovação do pagamento das despesas adicionais por parte da CONTRATADA.<br>
                        </p>
                        <p>
                            <strong>VI- DAS OBRIGAÇÕES DAS PARTES</strong><br>
                        </p>
                        <p>
                            6.1 A CONTRATADA compromete-se a manter a estrutura mínima necessária para a prestação dos serviços, como: instalações físicas, computador e impressora com acesso à internet, ferramentas de uso geral e especiais, técnico treinado e qualificado, conforme solicitação da CONTRATANTE.<br>
                        </p>
                        <p>                            
                            6.2 A CONTRATADA compromete-se a utilizar única e exclusivamente peças originais ou especificadas, fornecidas pela CONTRATANTE ou revendedores autorizados e a respeitar a garantia dos serviços prestados aos consumidores pelo prazo mínimo, conforme estabelece o Código de Defesa do Consumidor.<br>
                        </p>
                        <p>                            
                            6.3 A CONTRATADA compromete-se a tentar manter um estoque mínimo de peças que permita o atendimento imediato e satisfatório aos consumidores e em conformidade com os prazos divulgados pela CONTRATADA, não podendo tal prazo ultrapassar o período máximo de 30 (trinta) dias.<br>
                        </p>
                        <p>                            
                            6.4 A CONTRATADA compromete-se a não cobrar dos consumidores beneficiários dos serviços na condição \"GARANTIA\", qualquer valor, a qualquer título, sob pena de ressarcimento das importâncias cobradas indevidamente, além da rescisão automática deste contrato.<br>
                        </p>
                        <p>                            
                            6.5 A CONTRATADA compromete-se a executar os serviços em conformidade com os procedimentos e zelar pela perfeita utilização dos materiais.<br>
                        </p>
                        <p>                            
                            6.6 A CONTRATADA compromete-se a abrir Ordem de Serviço no Sistema de Gestão de Ordens de Serviço no momento que der entrada de um produto para atendimento em garantia e entregar uma via impressa da Ordem de Serviço ao consumidor.<br>
                        </p>
                        <p>                            
                            6.7 A CONTRATADA compromete-se a executar os serviços em conformidade com as orientações da CONTRATANTE, inclusive quanto à preservação dos aparelhos dos consumidores em seu poder, incluindo, mas não se limitando, os seus colaboradores e subcontratados, respondendo a CONTRATADA por eventuais danos causados à CONTRATANTE e a terceiros, por atos resultantes de dolo, negligência, imprudência ou imperícia.<br>
                        </p>
                        <p>                            
                            6.8 A CONTRATADA compromete-se a zelar pela perfeita utilização dos materiais cedidos pela CONTRATANTE para a realização dos serviços, tais como: materiais de Identificação visual, manuais técnicos, ferramentas, políticas, documentos, expositores, peças e produtos para composição de estoque antecipado, etc., devendo devolvê-los à CONTRATANTE quando esta exigir.<br>
                        </p>
                        <p>                            
                            6.9 A CONTRATADA é responsável por devolver as peças substituídas em garantia à CONTRATANTE sempre que solicitado.<br>
                        </p>
                        <p>
                            6.10 A CONTRATADA deve informar à CONTRATANTE sobre qualquer anomalia ou defeitos encontrados nos produtos submetidos à assistência técnica, mantendo sobre o fato o mais absoluto sigilo.<br>
                        </p>
                        <p>                            
                            6.11 A CONTRATADA deve comunicar a CONTRATANTE se houver qualquer alteração na sua razão social, endereço e nos documentos constitutivos, bem como, se ocorrer a venda, cessão, fusão ou incorporação do seu estabelecimento.<br>
                        </p>
                        <p>                            
                            6.12 A CONTRATADA garante a continuidade da prestação dos serviços objeto deste contrato, por pessoal técnico qualificado, de forma profissional, de acordo com os padrões da atividade e das instruções da CONTRATANTE, inclusive na hipótese de impossibilidade e/ou afastamento de qualquer dos funcionários da CONTRATADA, por qualquer motivo ou razão, incluindo, mas não se limitando, no caso de período de férias, demissões, subcontratações, etc.<br>
                        </p>
                        <p>                            
                            6.13 A CONTRATANTE se obriga a efetuar todos os pagamentos, conforme pactuado neste Contrato.<br>
                        </p>
                        <p>                            
                            6.14 A CONTRATANTE se obriga a prestar todas as informações necessárias para que a CONTRATADA realize os serviços ora acertados.<br>
                        </p>
                        <p>                            
                            6.15 A CONTRATANTE divulgará o nome da contratada na relação de estabelecimentos autorizados a prestar assistência técnica dos produtos.<br>
                        </p>
                        <p>                            
                            6.16 É obrigatório à CONTRATADA ter o competente Alvará de Licença de Funcionamento e manter as suas custas nas repartições públicas e nos órgãos competentes, todas as inscrições e registros necessários à prestação de serviços, em razão da importância do CNPJ e Inscrição Estadual para a emissão de Notas para o faturamento de peças enviadas à CONTRATADA. Havendo irregularidades em relação ao CNPJ e Inscrição Estadual da CONTRATADA, a mesma será informada, sendo concedido o prazo de 10 (dez) dias para a regularização.<br>
                        </p>
                        <p>                            
                            6.17 A CONTRATADA é obrigada a anexar a cópia da Nota Fiscal de Compra do Produto (apresentada pelo consumidor) à respectiva Ordem de Serviço gerada no Sistema de Gestão de Ordem de Serviço.<br>
                        </p>
                        <p>                            
                            <strong>VII-DA INFRAESTRUTURA NECESSÁRIA À PRESTAÇÃO DE SERVIÇOS</strong><br>
                        </p>
                        <p>                            
                            7.1 A CONTRATADA declara e garante que possui a habilidade, experiência, conhecimento técnico e infraestrutura necessária para a prestação dos serviços, bem como, os serviços serão prestados pontualmente por pessoal técnico qualificado, de forma profissional e de acordo com os padrões da atividade e das instruções descritas pela CONTRATANTE.<br>
                        </p>
                        <p>                            
                            7.2 A CONTRATADA obriga-se a fornecer aos seus empregados e prepostos todos os equipamentos e materiais necessários à execução dos serviços objeto do presente instrumento, obrigando-se, ainda, a fornecer apenas equipamentos e materiais que reconhecidamente não ofereçam risco potencial aos seus próprios empregados e prepostos, bem como, a terceiros.<br>
                        </p>
                        <p>                            
                            7.3 A CONTRATADA deverá cumprir as Normas Regulamentadoras do Ministério do Trabalho sobre segurança, higiene e medicina do trabalho, assim como as Normas e Procedimentos da Segurança.<br>
                        </p>
                        <p>                            
                            7.4 A CONTRATADA responsabilizar-se-á por todos os danos ou prejuízos materiais, corporais e morais, que vier causar à CONTRATANTE, seus empregados, terceiros e, principalmente, aos seus clientes e consumidores durante o processo de prestação de serviços ora contratados, por dolo ou culpa de seus empregados. Eventuais danos, avarias, sinistros ou inutilizações de objetos ou equipamentos, comprovadamente causados por empregados da CONTRATADA serão indenizados à CONTRATANTE, observando o artigo 412 do Código Civil, autorizando desde já a CONTRATANTE descontar do pagamento da CONTRATADA a importância correspondente aos prejuízos causados.<br>
                        </p>
                        <p>                            
                            7.5 A CONTRATADA responsabilizar-se-á pelos acidentes de empregados, equipamentos, prepostos e contratados derem causa durante a prestação dos serviços e indenizará a CONTRATANTE por quaisquer danos decorrentes de demanda ou reclamação movida por terceiros relacionados à lesão corporal ou morte de qualquer pessoa empregada ou não, incluindo clientes, autoridades e prestadores de serviços de um modo geral.<br>
                        </p>
                        <p>
                            <strong>VIII- DA INEXISTÊNCIA DE VÍNCULO EMPREGATÍCIO</strong><br>
                        </p>
                        <p>                             
                            8.1 Não se estabelece entre as partes qualquer forma de sociedade, associação, mandato, agência, consórcio, responsabilidade solidária e/ou vínculo empregatício, permanecendo a CONTRATADA responsável por todas as obrigações, ônus e encargos advindos da administração e operação de seu negócio.<br>
                        </p>
                        <p>                             
                            8.2 A celebração deste Contrato/Acordo não criará qualquer vínculo empregatício entre uma das Partes e os respectivos Representantes da outra Parte e de suas Afiliadas, eis que os mesmos continuarão hierárquica e funcionalmente subordinados a esta Parte e suas Afiliadas, de quem será a exclusiva responsabilidade pelo pagamento, conforme o caso, dos reembolsos/salários, encargos trabalhistas e previdenciários, impostos e outros acréscimos pertinentes relativos aos respectivos Representantes. A celebração deste Contrato/Acordo também não obriga as Partes no contexto da manutenção da prestação dos Serviços ou celebração de quaisquer outros negócios relacionados, devendo, neste caso, ser observado o disposto nos respectivos instrumentos contratuais que venham a ser celebrados entre as Partes para estes objetivos.<br>
                        </p>
                        <p>                             
                            8.3 Caso a existência do vínculo trabalhista venha a ser reconhecida, ainda que por decisão judicial, obriga-se a CONTRATADA a indenizar a CONTRATANTE por todos os valores despendidos em decorrência do reconhecimento deste vínculo, inclusive, custas judiciais e honorários advocatícios, obrigando-se a este pagamento, nas 24 (vinte e quatro) horas seguintes à data da notificação da CONTRATANTE para o cumprimento da decisão que determinar o pagamento. Da mesma forma, obriga-se a CONTRADADA a envidar os seus melhores esforços para, de pronto, excluir a CONTRATANTE da lide.<br>
                        </p>
                        <p>                             
                            8.4 Fica expressamente pactuado que se a CONTRATANTE sofrer condenação judicial em razão do não cumprimento, em época própria, de qualquer obrigação atribuível à CONTRATADA ou a seus subcontratados, originária deste contrato, seja de natureza fiscal, trabalhista, previdenciária, criminal ou de qualquer outra espécie, a CONTRATANTE, poderá reter os pagamentos devidos à CONTRATADA por força deste contrato ou de qualquer outro contrato firmado com a CONTRATADA, aplicando-os na satisfação da respectiva obrigação, liberando a CONTRATANTE da condenação.<br>
                        </p>
                        <p> 
                            8.5 A presente obrigação perdurará, mesmo após o término da vigência do presente instrumento, até prescreverem os direitos trabalhistas dos empregados, subcontratados e/ou terceiros a serviço da CONTRATADA, em relação a esta e ao previsto no presente Contrato, de acordo com a legislação em vigor.<br>
                        </p>
                        <p>                             
                            8.6 A CONTRATADA reembolsará a CONTRATANTE de todas as despesas que tiver decorrentes de:
                            <ul style='list-style-type:disc'>
                                <li> indenizações, em consequência de eventuais danos materiais, pessoais e morais causados a empregados, prepostos e/ou dirigentes da CONTRATANTE ou a terceiros, pela CONTRATADA ou seus prepostos na execução de suas atividades.
                                <li> indenização à CONTRATANTE por quaisquer despesas eventualmente realizadas em decorrência das hipóteses acima e honorários advocatícios, audiências e viagens necessárias ao acompanhamento de eventuais ações previstas acima.
                            </ul>
                        </p>
                        <p>
                            8.7 Estipulam as partes que, em caso de rescisão do contrato, a CONTRATADA autoriza a CONTRATANTE a compensar do pagamento devido à CONTRATADA, a importância equivalente a todos os valores despendidos pela CONTRATANTE em decorrência das ações interpostas por empregados utilizados para a execução do contrato, incluindo as custas judiciais e os honorários advocatícios.<br>
                        </p>
                        <p>
                            8.8 A CONTRATADA autoriza a CONTRATANTE a compensar do pagamento devido à CONTRATADA por ocasião da rescisão, os débitos existentes da CONTRATADA perante a CONTRATANTE, podendo reter os pagamentos para este fim.<br>
                        </p>
                        <p>                            
                            8.9 Caso o débito da CONTRATADA para com a CONTRATANTE exceda o valor que a CONTRATANTE tenha a obrigação de pagar, a CONTRATADA indenizará a CONTRATANTE, autorizando desde já a emissão de Nota de Débito pela CONTRATANTE para este fim.<br>
                        </p>
                        <p>                            
                            <strong>IX-DA CONFIDENCIALIDADE</strong><br>
                        </p>
                        <p>                            
                            9.1 O CONTRATADO, desde já, reconhece e concorda que as informações a que tiver acesso em decorrência do presente contrato tem relevante valor e que sua divulgação não autorizada poderá acarretar danos substanciais à CONTRATANTE. Desta forma, o CONTRATADO, salvo prévia e expressa autorização da CONTRATANTE, compromete-se a não divulgar tais informações mesmo após o término ou rescisão do presente contrato, sob pena de arcar com indenização advindas dos seus atos, na forma da Lei Civil.<br>
                        </p>
                        <p>                            
                            <strong>X- DA ANTICORRUPÇÃO</strong><br>
                        </p>
                        <p>                            
                            10.1 Nenhuma das partes poderá oferecer, dar ou se comprometer a dar a quem quer que seja, ou aceitar ou se comprometer a aceitar de quem quer que seja, tanto por conta própria quanto através de outrem, qualquer pagamento, doação, compensação, vantagens financeiras ou não financeiras ou benefícios de qualquer espécie que constituam prática ilegal ou de corrupção de que trata a Lei Anticorrupção n.º 12.846/2013 ou de quaisquer outras leis aplicáveis sobre o objeto do presente contrato, em especial o Foreign Corrupt Practices Act, - Act, 15 U.S.C. §§ 78dd-1 et seq. - ('FCPA') dos Estados Unidos da América do Norte (\"Regras Anticorrupção\"), seja de forma direta ou indireta quanto ao objeto deste contrato, ou de outra forma que não relacionada a este contrato, devendo garantir, ainda, que seus prepostos e colaboradores ajam da mesma forma.<br>
                        </p>
                        <p>                            
                            <strong>XI- DA PROPRIEDADE INTELECTUAL</strong><br>
                        </p>
                        <p>                            
                            11.1 A CONTRATADA não utilizará, exceto mediante prévia autorização, qualquer nome, marca, logotipo ou símbolo de propriedade da CONTRATANTE, nem fará qualquer declaração ou referência que indique a existência de qualquer vínculo ou relação contratual ou negocial entre as partes, exceto se referida declaração ou referência for autorizada previamente, por escrito, pela CONTRATANTE.<br>
                        </p>
                        <p>                            
                            11.2 Toda utilização da marca Contratante, pela Contratada, em propaganda, promoção ou publicidade, somente poderá ser realizada com autorização prévia da Contratante e seu custo total será de responsabilidade da Contratada.<br>
                        </p>
                        <p>                            
                            11.3 Terminada a vigência deste contrato, a Contratada cessará imediatamente o uso de qualquer impresso, propaganda ou exibição de nomes e símbolos pertencentes a Contratante, sob pena de incorrer nas sanções civis e criminais previstas na legislação vigente.<br>
                        </p>
                        <p>
                            <strong>XII-ANÁLISE DE DESEMPENHO</strong><br>
                        </p>
                        <p>                            
                            12.1 A CONTRATANTE poderá determinar critérios de avaliação para medir a performance do CONTRATADO nos atendimentos prestados aos clientes e consumidores, estando seus respectivos produtos dentro, ou fora do período de garantia.<br>
                        </p>
                        <p>                            
                            12.2 Caberá exclusivamente à CONTRATANTE a implantação, alteração e divulgação da metodologia de avaliação através do sistema de gestão de ordens de serviço, concedendo à CONTRATADA um período razoável de tempo para que se adeque ao cumprimento dos critérios mínimos de performance determinados.<br>
                        </p>
                        <p>
                            12.3 Os resultados da avaliação tornarão a CONTRATADA elegível a 1. gratificações, 2. ações de desenvolvimento ou 3. descredenciamento (pelo não cumprimento dos critérios mínimos de performance determinados pelo Contratante).
                        </p>
                        <p>
                            <strong>XIII-DISPOSIÇÕES GERAIS</strong><br>
                        </p>
                        <p>                            
                            13.1 Este Contrato/Acordo é firmado em caráter irretratável e irrevogável, configurando obrigações legais, válidas e vinculantes para as Partes, seus cessionários e sucessores a qualquer título, exequíveis de conformidade com os seus respectivos termos.<br>
                        </p>
                        <p>                            
                            13.2 Sujeitas aos termos e condições deste Contrato/Acordo, as Partes agirão de boa-fé e envidarão seus melhores esforços em condições razoáveis para tomar ou fazer com que sejam tomadas todas as providências e realizados todos os atos necessários, adequados ou aconselháveis para consumar e dar eficácia aos entendimentos aqui contemplados.<br>
                        </p>
                        <p>                            
                            13.3 Os direitos e obrigações decorrentes deste Contrato/Acordo somente poderão ser cedidos por qualquer das Partes mediante a anuência, por escrito, da outra Parte, exceto em caso de cessão, pela Parte Reveladora, para (a) sociedades controladas; (b) sociedades controladoras; ou (c) sociedades sob controle comum ao da Parte Reveladora. Qualquer cessão em violação ao disposto nesta Cláusula será nula e ineficaz.<br> 
                        </p>
                        <p>                            
                            13.4 Caso qualquer disposição deste Contrato/Acordo seja considerada inválida, ineficaz, inexequível ou nula, as demais disposições deste Contrato/Acordo deverão permanecer válidas, eficazes e vigentes, hipótese em que as Partes deverão substituir a disposição inválida, inexequível ou nula por uma disposição válida e exequível que corresponda, tanto quanto possível, ao espírito e objetivo da disposição substituída.<br>
                        </p>
                        <p>                            
                            13.5 Este Contrato/Acordo somente poderá ser alterado mediante instrumento assinado por todas as Partes e qualquer renúncia ou consentimento somente será válido se prestado por escrito.<br>
                        </p>
                        <p>                            
                            13.6 O fato de uma das Partes deixar de exigir a tempo o cumprimento de qualquer das disposições ou de quaisquer direitos relativos a este Contrato/Acordo ou não exercer quaisquer faculdades aqui previstas não será considerado uma renúncia a tais disposições, direitos ou faculdades, não constituirá novação e não afetará de qualquer forma a validade deste Contrato/Acordo.<br>
                        </p>
                        <p>                            
                            13.7 Este contrato substitui quaisquer entendimentos, acordos ou ajustes, verbais ou escritos, anteriormente havidos entre as partes, os quais são considerados sem qualquer efeito, mesmo para fins e efeitos de interpretação da vontade das partes, passando a reger-se o relacionamento, direitos e obrigações unicamente com as disposições aqui estabelecidas.<br>
                        </p>
                        <p>                            
                            13.8 As partes elegem por mútuo consenso o foro da Comarca de Balneário Piçarras/SC para dirimir quaisquer controvérsias acaso oriundas deste pacto, com renúncia de qualquer outro por mais privilegiado que possa vir a ser.<br>
                        </p>
                        <p><br><br><br><br>                        
			 E, por estarem justos e pactuados, assinam o presente em 2 (duas) vias de igual teor e forma, na presença de 2 (duas) testemunhas, para que surta seus jurídicos e legais efeitos.
			</p>
                        <p><br><br>
                        Balneário Piçarras/SC, $dataAssinatura.
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
                                    CNPJ nº.03.106.170/0002-24
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
                                    CNPJ nº.03.106.170/0002-24
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
                                    CNPJ nº. {$cnpj}<br>
                                    {$responsavel_social}<br>
                                    CPF nº. <strong>_______________________</strong>              
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
                                        Em parceria com a Telecontrol, estamos credenciando novas assistências técnicas para ingressar em nossa rede.
                                        <br /><br />
                                        &bull; Os contratos e a gestão da rede serão realizados diretamente com a Hitachi.
                                        <br /><br />
                                        <strong>
                                            <font color='red'>Obs.</font>
                                            Somente o termo de adesão deverá ser preenchido, assinado e devolvido para a Telecontrol.
                                        </strong>
                                    </font>
                                </p>
                                <p align='left'>
                                    <font face='Verdana, Arial, Helvetica, sans-serif' size='3'>
                                        Envio feito através dos Correios, enviar para o endereço: <br />
                                        Av. Carlos Artêncio, 420-B CEP: 17.519-255 - Bairro Fragata Marília, SP - Brasil
                                        <br /><br />
                                        Envio feito através de E-mail, enviar para: <br />
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
                    <p style='padding-top:170px;margin-left:480px;font-weight:bold;'>Marília, ".date(d)." de ".$array_meses[intval($mes)]." de ".date(Y).".</p>

                    <p style='font-size:16px;font-weight:bold;margin-left:50px;'>
                    Prezado Assistente T&eacute;cnico,
                    </p>
                    <br>
                    <p style='margin-left:50px;'>
                    Nós da TELECONTROL estamos muito satisfeitos em poder contar com essa parceria e temos certeza que nossos clientes serão sempre muito bem atendidos.
                    </p>
                    <p style='text-align:justify;margin-left:50px'>
                    Para realizar o primeiro acesso ao sistema acesse o site www.telecontrol.com.br e clique no link <b>\"Primeiro Acesso\"</b> logo abaixo dos campos de login e senha.
                    </p>

                    <p style='text-align:justify;margin-left:50px'>
                    Colocamos a disposição de vocês toda nossa equipe de profissionais através dos telefones abaixo ou pelo endereço eletrônico suporte@telecontrol.com.br. Todos estão devidamente treinados e preparados para atender a quaisquer dúvidas.
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
                    Pedimos gentilmente que providencie a assinatura do contrato em anexo, imprescindível para esta união, remetendo-o para o seguinte endereço:
                    </p>

                    <p style='margin-left:150px;text-align:justify;'>
                    <b>
                    TELECONTROL SISTEMA LTDA <br>
                    A/C Sr. Luis Carlos dos Santos Martins <br>
                    Av. Carlos Artêncio, 420 A - Bairro Fragata C <br>
                    Marilia / SP <br>
                    CEP 17.519-255 <br>
                    </b>
                    </p>
                    <p style='margin-left:50px'>
                    Seja bem vindo a maior rede de postos autorizados do país. Agradecemos mais uma vez a credibilidade e confiança.
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
                                Você está recebendo o Contrato de Prestação de Serviços, juntamente com nossa tabela de mão de obra.
                                </p>
                                <p style='text-align:justify;margin-left:10px'>
                                A partir desta data você tem um prazo de 10 dias úteis para nos retornar o arquivo digital, devidamente assinado e com firma reconhecida, conforme instruções abaixo.
                                </p>
                                <p style='text-align:justify;margin-left:10px'>
                                A via original deve ser postada via correios, imediatamente após o envio digital. O prazo máximo para recebimento da via original assinada e com firma reconhecida será de 30 dias a partir desta data.
                                </p><br>
                                <p style='font-size:16px;font-weight:bold;margin-left:10px;'>
                                Orienta&ccedil;&otilde;es de preenchimento e assinatura
                                </p> 
                                <p style='text-align:justify;margin-left:10px'>
                                1. Viste todas as páginas e assine a última página, informando o nº do CPF do Representante Legal devidamente identificado nos dados da Contratada.
                                </p>
                                <p style='text-align:justify;margin-left:10px'>
                                2. Reconhecer firma na assinatura de uma cópia do contrato e enviar digitalizado (em um único arquivo), via chamado Help desk no Telecontrol.
                                </p>
                                <p style='text-align:justify;margin-left:10px'>
                                3. Coletar assinatura de uma (01) testemunha. Não sendo necessário reconhecer firma da assinatura da testemunha. A outra testemunha será por parte da CONTRATANTE.
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
                                $titulo_comunicado_contrato = "Contrato de Prestação de Serviços de Assistência Técnica";
                                $msg_comunicado_contrato = "Já está disponível nos informes administrativos o Contrato de Prestação de Serviços de Assistência Técnica. Este contrato assegura às partes os direitos e obrigações distintas, garantindo o fiel cumprimento do acordado.<br>Pedimos que faça o Download e leia atentamente as cláusulas que regem nossa parceria.<br>Após assinado, reconheça firma na assinatura do Representante Legal e anexe a cópia do contrato no Sistema Telecontrol.<br>É necessário envio, via correios, de 2 vias com assinatura reconhecida.";

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

$array_meses = array('','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro');
$mes = date('m');

echo "&nbsp;";
$texto = "<div style='width:700px;height:990px;background:url(http://posvenda.telecontrol.com.br/assist/admin/imagens_admin/contrato.jpg) no-repeat;'>
<p style='padding-top:170px;margin-left:480px;font-weight:bold;'>Marília, ".date(d)." de ".$array_meses[intval($mes)]." de ".date(Y).".</p>

<p style='font-size:16px;font-weight:bold;margin-left:50px;'>
Prezado Assistente T&eacute;cnico,
</p>
<br>
<p style='margin-left:50px;'>
Nós da TELECONTROL estamos muito satisfeitos em poder contar com essa parceria e temos certeza que nossos clientes serão sempre muito bem atendidos.
</p>
<p style='text-align:justify;margin-left:50px'>
Para realizar o primeiro acesso ao sistema acesse o site www.telecontrol.com.br e clique no link <b>\"Primeiro Acesso\"</b> logo abaixo dos campos de login e senha.
</p>

<p style='text-align:justify;margin-left:50px'>
Colocamos a disposição de vocês toda nossa equipe de profissionais através dos telefones abaixo ou pelo endereço eletrônico suporte@telecontrol.com.br. Todos estão devidamente treinados e preparados para atender a quaisquer dúvidas.
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
Pedimos gentilmente que providencie a assinatura do contrato em anexo, imprescindível para esta união, remetendo-o para o seguinte endereço:
</p>

<p style='margin-left:150px;text-align:justify;'>
<b>
TELECONTROL SISTEMA LTDA <br>
A/C Sr. Luis Carlos dos Santos Martins <br>
Av. Carlos Artêncio, 420 A - Bairro Fragata C <br>
Marilia / SP <br>
CEP 17.519-255 <br>
</b>
</p>
<p style='margin-left:50px'>
Seja bem vindo a maior rede de postos autorizados do país. Agradecemos mais uma vez a credibilidade e confiança.
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
<caption nowrap class='Titulo'>Gerar o contrato para Fábrica</caption>
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
<td align='center' class='Conteudo'>Fábrica
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
        $msg="Selecione a fábrica a ser gerado o contrato.";
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
            10.0pt'><b>CONTRATO DE CREDENCIAMENTO DE ASSISTÊNCIA TÉCNICA<o:p></o:p></b></p>

            <p class=MsoNormal style='mso-line-height-alt:8.0pt'><o:p>&nbsp;</o:p></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'>Pelo
            presente instrumento particular,</p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><o:p>&nbsp;</o:p></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b>HB
            ASSISTÊNCIA TÉCNICA LTDA</b>., sociedade empresarial com escritório
            administrativo na Av. Yojiro Takaoka, 4.384 - Loja 17 - Conj. 2083 - Alphaville
            - Santana de Parnaíba, SP, CEP 06.541-038, inscrita no CNPJ sob nº
            08.326.458/0001-47, neste ato representada por seu diretor ao final assinado,
            doravante denominada<span style='mso-spacerun:yes'> 
            </span>&quot;HB-TECH&quot;, e</p>


            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b>$posto_nome.</b>, sociedade empresarial com sede na $endereco,
            $numero $complemento, na cidade de $cidade, $estado, CEP $cep, inscrita no CNPJ sob nº
            $cnpj, neste ato representada por seu administrador, ao final
            assinado, doravante denominada &quot;AUTORIZADA&quot;,</p>


            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b
            style='mso-bidi-font-weight:normal'><span style='mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Considerando que:<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:35.45pt;text-align:justify;mso-line-height-alt:
            10.0pt;mso-pagination:none'><b><span style='mso-fareast-language:\#00FF;
            mso-bidi-language:#00FF'>(i) </span></b><span style='mso-fareast-language:\#00FF;
            mso-bidi-language:#00FF'><span style='mso-tab-count:1'>       </span>a HBTECH desenvolveu 
            uma metodologia comercial e novo negócio através da marca HBFLEX, ou HBTECH, dentre outras 
            possíveis, sob a qual venderá produtos com componentes elétricos, eletrônicos e mecânicos;<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><b><span style='mso-fareast-language:#00FF;mso-bidi-language:#00FF'>(ii)
            </span></b><span style='mso-fareast-language:#00FF;mso-bidi-language:#00FF'><span
            style='mso-tab-count:1'>      </span>a AUTORIZADA declara expressamente que possui 
            conhecimento, habilidade, tecnologia e know how de manutenção e assistência técnica 
            destes produtos;<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><b style='mso-bidi-font-weight:normal'><span style='mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'>(iii) </span></b><span style='mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'><span style='mso-tab-count:1'>      </span>a linha de produtos estará sempre 
            definida no Sistema &quot;Assist Telecontrol&quot;, conforme a especialidade da AUTORIZADA;<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><b style='mso-bidi-font-weight:normal'><span style='mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'>(iv) </span></b><span style='mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'><span style='mso-tab-count:1'>      </span>a HBTECH, sempre que julgar conveniente e nos termos 
            de suas políticas estratégicas, poderá introduzir novos produtos no mercado. A AUTORIZADA 
            deverá optar ou não pelo cadastramento para atendimento no Sistema &quot;Assist Telecontrol&quot;;<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><b style='mso-bidi-font-weight:normal'><span style='mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'>(v) </span></b><span style='mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'><span style='mso-tab-count:1'>      </span>havendo Ordens de Serviços cadastradas pela AUTORIZADA 
            no Sistema &quot;Assist Telecontrol&quot;, esta assume todas as responsabilidades 
            descritas neste contrato da respectiva linha de produtos;<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'>têm
            entre si, justo e contratado, o seguinte:</p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>1.1. O objetivo do presente contrato é a prestação,
            pela AUTORIZADA, em sua sede social, do serviço de assistência técnica aos
            produtos comercializados pela HB-TECH, cuja relação consta na tabela de mão de
            obra, fornecida em anexo e faz parte integrante deste contrato.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>1.2. Os serviços que serão prestados pela AUTORIZADA,
            junto aos clientes usuários dos produtos comercializados através da HB-TECH
            consistem em manutenção corretiva e preventiva, seja através de reparações a
            domicilio cujos custos serão por conta do consumidor, ou em sua oficina, quando
            os custos serão cobertos pela HB-TECH através de taxas de garantia.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>1.3. A HB-TECH</span><span style='mso-fareast-font-family:
            'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'> fornecerá à AUTORIZADA todos os elementos necessários
            e indispensáveis à boa prestação dos serviços em alusão, desde que sejam de sua
            responsabilidade, especialmente no tocante à qualificações e especificações
            técnicas dos produtos, quando for o caso, tudo previamente autorizado (p.ex.
            desenhos técnicos, peças de reposição para produtos em garantia, treinamento,
            quando necessários, dentre outras hipóteses) .<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>2- DA EXECUÇÃO DOS SERVIÇOS DURANTE A GARANTIA<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>2.1. O prazo e condições de garantia dos produtos
            comercializados pela HB-TECH, são especificados no certificado de garantia,
            cujo início é contado a partir da data emissão da nota fiscal de compra do
            produto pelo primeiro usuário.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>2.2. Se no período de garantia os equipamentos
            apresentarem defeitos de fabricação, a AUTORIZADA providenciará o reparo
            utilizando exclusivamente peças originais sem qualquer ônus.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>2.3. Para atendimento <st1:PersonName
            ProductID='em garantia a AUTORIZADA' w:st='on'>em garantia a AUTORIZADA</st1:PersonName>
            exigirá, do cliente usuário, a apresentação da NOTA FISCAL DE COMPRA.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>2.4. A ordem de serviço utilizada pela AUTORIZADA para
            consumidores, deverá ser individual e conter:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>- NÚMERO DE SÉRIE<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>- DATA DA CHEGADA NA AUTORIZADA<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>- NÚMERO DA NOTA FISCAL<o:p></o:p></span></p>

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
            mso-bidi-language:#00FF'>- ENDEREÇO COMPLETO DO CLIENTE<o:p></o:p></span></p>

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
            mso-bidi-language:#00FF'>3- PREÇO E CONDIÇÕES DE PAGAMENTO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>3.1. Para consertos efetuados em aparelhos no período
            de garantia, a HB-TECH pagará à AUTORIZADA os valores de taxas de acordo com a
            tabela fornecida em anexo, a qual faz parte integrante deste contrato.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>3.2. O pagamento dos serviços prestados em garantia
            será efetuado da seguinte forma:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>A) A AUTORIZADA deverá encaminhar até o dia 07 (sete)
            de cada mês subseqüente ao atendimento: <o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><span style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'>(i) Ordens de serviço individuais devidamente
            preenchidas (item 4.7), ACOMPANHADAS DAS RESPECTIVAS CÓPIAS DA N.F. DE VENDA AO
            CONSUMIDOR.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><span style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'>(ii) Ordens de serviço coletivas devidamente
            preenchidas ACOMPANHADAS DAS RESPECTIVAS CÓPIAS DAS NOTAS FISCAIS DE ENTRADA E SAÍDA.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>B) Depois de efetuado o cálculo pela HB-TECH, será
            solicitada a Nota fiscal de serviços, (original) emitida contra:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'>HB ASSISTÊNCIA TÉCNICA LTDA.</p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'>Av. Yojiro Takaoka, 4.384 - Loja 17 - Conj. 2083 - Alphaville - </p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'>Santana de Parnaíba, SP, CEP 06.541-038</p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'>CNPJ 08.326.458/0001-47</p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>E DEVERÁ ENVIAR A MESMA PARA O ENDEREÇO ABAIXO:<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'>HB ASSISTÊNCIA TÉCNICA LTDA.</p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'>Av. Yojiro Takaoka, 4.384 - Loja 17 - Conj. 2083 - Alphaville - </p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'>Santana de Parnaíba, SP, CEP 06.541-038</p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>C) A nota fiscal deverá estar na filial HB-TECH até o
            último útil dia do mês em curso e discriminar no corpo da mesma o seguinte:
            \"SERVIÇOS PRESTADOS <st1:PersonName
            ProductID='EM APARELHOS DE SUA COMERCIALIZAÇÃO' w:st='on'>EM APARELHOS DE SUA
             COMERCIALIZAÇÃO</st1:PersonName>, SOB GARANTIA DURANTE O MÊS DE\" (AS NOTAS
            FISCAIS RECEBIDAS APÓS 90 (NOVENTA) DIAS NÃO SERÃO PAGAS).<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>D) De posse da documentação a HB-TECH fará conferência
            para averiguar possíveis distorções:<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><span style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'>(i) Pagamento das taxas de garantia será
            efetuado no quinto dia útil do mês subseqüente, para as NF recebidas até o
            último dia útil do mês anterior, em forma de crédito em conta corrente da
            pessoa jurídica. Qualquer alteração na conta corrente do serviço autorizado deve
            ser comunicado previamente à HB-TECH.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><span style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'>(ii) HB-TECH reserva-se o direito de efetuar
            deduções de débitos pendentes, duplicatas, despesas bancárias e de protesto referentes
            a títulos não quitados, ordens de serviço irregulares, peças trocadas em
            garantia e não devolvidas no prazo máximo de 60 (sessenta) dias, sem prévia
            consulta ou permissão da AUTORIZADA.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>E) Valores inferiores a R\$ 20,00 (vinte Reais), serão
            acumulados até o próximo crédito e assim sucessivamente, até que o valor
            acumulado ultrapasse o disposto nesta cláusula.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>(i) Apenas serão aceitas ordens de serviço do mesmo
            cliente cujo prazo entre atendimentos, para o mesmo defeito, for superior a 60
            (sessenta) dias, após a retirada do produto.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><span style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'>(ii) Ordens de serviço incompletas não serão
            aceitas.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:35.4pt;text-align:justify;mso-line-height-alt:
            10.0pt'><span style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'>(iii) A HB-TECH não se responsabiliza por
            atrasos de pagamento cuja causa seja de responsabilidade da AUTORIZADA.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>F) O PRAZO MÁXIMO QUE A AUTORIZADA PODERÁ RETER AS
            ORDENS DE SERVIÇO, APÓS A SAÍDA DO PRODUTO, DE SUA EMPRESA, SERÁ DE 90 DIAS,
            EXCETUANDO-SE O MÊS DESSA SAÍDA. APÓS ESSE PRAZO, AS ORDENS DE SERVIÇO NELE
            ENQUADRADAS PERDERÃO O DIREITO AO CRÉDITO DE TAXAS DE GARANTIA.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>G) A AUTORIZADA enviará, quando solicitado, os
            componentes substituídos em garantia, devidamente identificados com as
            etiquetas fornecidas pela HB-TECH, para que seja efetuada a inspeção do
            controle de qualidade e a devida reposição quando for o caso. O frete desta
            operação será por conta da HB-TECH.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>H) Os comprovantes de pagamento de sedex, quando
            antecipados pela AUTORIZADA, deverão ser enviados à HB-TECH, juntamente com o
            movimento de O. S., em prazo não superior a 90 dias da data da emissão do
            mesmo. Comprovantes recebidos após o período retro citado não serão
            reembolsados.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>4 - DURAÇÃO DO CONTRATO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>4.1. A validade do presente contrato é por tempo
            indeterminado e poderá ser rescindido por qualquer das partes, mediante um
            aviso prévio de 30 (trinta) dias, por escrito e protocolado. A autorizada
            obriga-se, neste prazo do aviso, a dar continuidade aos atendimentos dos
            produtos em seu poder.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>4.2. O cancelamento deste contrato com fulcro na
            cláusula anterior não dará direito a nenhuma das partes a indenização, crédito
            ou reembolso, seja a que título, forma ou hipótese for.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>4.3. O contrato será imediatamente rescindido caso
            seja constatada e comprovada irregularidade na cobrança dos serviços e peças
            prestados em equipamentos sob garantia da HB-TECH, transferência da empresa
            para novos sócios, mudança de endereço para área fora do interesse da HB-TECH,
            concordata, falência, liquidação judicial ou extrajudicial.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>4.4. Observada qualquer situação prevista nesta
            cláusula, o representante indicado pela HB-TECH terá plena autonomia para
            interceder junto à AUTORIZADA, no sentido de recolher incontinenti, as
            documentações, materiais, luminosos e tudo aquilo que de qualquer forma, for de
            origem, relacionar ou pertencer ao patrimônio da HB-TECH e em perfeito estado
            de conservação e uso, sob pena de submeter a então AUTORIZADA ao processo de
            indenização na forma da lei.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>4.5. No caso de rescisão contratual, a AUTORIZADA se
            obriga a devolver à HB-TECH toda documentação técnica e administrativa cedida
            para seu uso enquanto CREDENCIADA.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>4.6. Fica expressamente estipulado que este contrato
            não cria, sob hipótese alguma, vinculo empregatício, direitos ou obrigações
            previdenciárias ou secundárias entre as partes, ficando a cargo exclusivo da
            AUTORIZADA todos impostos taxas e encargos de qualquer natureza, incidentes
            sobre suas atividades.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>5 - ÁREA DE ATUAÇÃO DA AUTORIZADA<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>5.1. A prestação de serviços será exercida pela
            AUTORIZADA na área que lhe for destinada, cujos limites poderão ser modificados
            com o tempo, desde que tal medida se faça necessária para melhorar o
            atendimento aos consumidores de aparelhos comercializados pela HB-TECH.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>6 - MARCAS E PROPRIEDADE INDUSTRIAL<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'>6.1.
            As marcas, símbolos, nomes, identificação visual e direitos autorais que são de
            titularidade exclusiva da HB-TECH deverão ser preservados, sendo que a
            AUTORIZADA reconhece e aceita a propriedade das mesmas, comprometendo-se e
            obrigando-se a preservar todas as suas características e reputação.</p>

            <p class=MsoBodyTextIndent style='margin-left:0cm;mso-line-height-alt:8.0pt'><span
            style='font-family: Times New Roman'>6.2. A reputação das marcas e produtos da
            HB-TECH deverão ser preservadas, <u>constituindo-se infração gravíssima ao
            presente contrato, bem como à legislação de propriedade industrial e penal
            brasileira vigente</u>, a ofensa à integridade, qualidade, conformidade,
            estabilidade e reputação, dentre outros quesitos, por parte da AUTORIZADA, seus
            sócios e/ou funcionários e colaboradores.<o:p></o:p></span></p>

            <p class=MsoBodyTextIndent style='margin-left:0cm;mso-line-height-alt:8.0pt'><span
            style='font-family: Times New Roman'>6.2.1. Considera-se, igualmente, como
            infrações nos termos do item 6.2. acima, difamações e outras práticas
            envolvendo marcas e produtos da HB-TECH por parte<span
            style='mso-spacerun:yes'>  </span>da AUTORIZADA, seus sócios e/ou funcionários
            e colaboradores, seja perante outras autorizadas, outros fabricantes,
            representantes e inclusive, o público consumidor. <o:p></o:p></span></p>

            <p class=MsoBodyTextIndent style='margin-left:0cm;mso-line-height-alt:8.0pt'><span
            style='font-family: Times New Roman'>6.2.2. Nestes termos do item 6.2.1. A
            HB-TECH poderá ter consultores de campo e auditores para averiguar e apurar
            eventuais irregularidades, enviando aos postos autorizados profissionais com ou
            sem identificação, que serão posteriormente alocados como testemunhas para
            todos os efeitos civis e criminais.<o:p></o:p></span></p>

            <p class=MsoBodyTextIndent style='margin-left:0cm;mso-line-height-alt:8.0pt'><span
            style='font-family: Times New Roman'>6.3. <span style='mso-tab-count:1'>     </span>Os
            sinais distintivos da HB-TECH não poderão ser livremente utilizados pela
            AUTORIZADA, mas tão somente no que diga respeito, estritamente, ao desempenho
            de suas atividades aqui ajustadas. <o:p></o:p></span></p>

            <p class=MsoBodyTextIndent style='margin-left:0cm;mso-line-height-alt:8.0pt'><span
            style='font-family: Times New Roman'>6.4. As marcas, desenhos ou quaisquer
            sinais distintivos não poderão sofrer qualquer alteração da AUTORIZADA,
            inclusive quanto a cores, proporções dos traços, sonoridade etc.<o:p></o:p></span></p>

            <p class=MsoBodyTextIndent style='margin-left:0cm;mso-line-height-alt:8.0pt'><span
            style='font-family: Times New Roman'>6.5. É vedado o uso de qualquer sinal
            distintivo ou referência ao nome da HB-TECH quando não expressamente autorizado
            ou determinado por esta última. <o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'>6.6.
            Além das obrigações já assumidas, a AUTORIZADA se compromete e se obriga,
            durante o prazo do presente Contrato, e mesmo após seu término ou rescisão, a:
            (i) não utilizar, manusear ou possuir de qualquer forma, direta ou
            indiretamente, a marca, ou qualquer outro termo, expressão ou símbolo com o
            mesmo significado, que seja semelhante, ou que possa confundir o consumidor com
            as marcas da HBTECH; (ii) não utilizar a marca como parte da razão social de
            qualquer empresa que detenha qualquer participação, atualmente ou no futuro,
            ainda que como nome fantasia, no Cadastro Nacional de Pessoas Jurídicas - CNPJ
            - do Ministério da Fazenda - Secretaria da Receita Federal; (iii)<span
            style='mso-tab-count:1'>        </span>não registrar ou tentar registrar marca
            idêntica ou semelhante, quer direta ou indiretamente, seja no Brasil ou <st1:PersonName
            ProductID='em qualquer outro País' w:st='on'>em qualquer outro País</st1:PersonName>
            ou território.</p>

            <p class=MsoBodyTextIndent style='margin-left:0cm;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='font-family: Times New Roman;mso-bidi-font-family:
            Arial'>6.7.<b> </b><span style='mso-bidi-font-weight:bold'><span
            style='mso-tab-count:1'></span></span></span><span style='font-family:
            Times New Roman'>Igualmente integram as obrigações assumidas pela AUTORIZADA
            todas as obrigações de sigilo, confidencialidade, não transmissão, cessão ou
            outras formas de proteção da tecnologia, <i>know-how</i>, desenvolvimentos, </span><span
            style='font-family: Times New Roman ;mso-fareast-language:#00FF;mso-bidi-language:
            #00FF'>desenhos técnicos, dados técnicos da HB-TECH. Nestas obrigações
            incluem-se todas as proteções da legislação brasileira vigente, especialmente
            as da Lei de Propriedade Industrial.<o:p></o:p></span></p>

            <p class=MsoBodyText style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:
            justify;mso-line-height-alt:8.0pt'>6.8. <span style='mso-tab-count:1'>     </span><span
            style='mso-bidi-font-family:Arial;mso-bidi-font-weight:bold'>Qualquer
            transgressão das normas aqui estabelecidas acarretará à AUTORIZAD</span><span
            style='mso-bidi-font-family:Arial'>A e seus sócios<span style='mso-bidi-font-weight:
            bold'>, não obstante a responsabilidade de seus funcionários, além da rescisão
            deste instrumento e pagamento de perdas e danos, as sanções previstas na
            legislação especial de marcas e patentes, e legislação penal vigente.<o:p></o:p></span></span></p>

            <p class=MsoBodyText style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:
            justify;mso-line-height-alt:8.0pt'><b><o:p>&nbsp;</o:p></b></p>

            <p class=MsoBodyText style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:
            justify;mso-line-height-alt:8.0pt'><b>7 - SIGILO E NÃO-CONCORRÊNCIA<o:p></o:p></b></p>

            <p class=MsoBodyText style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:
            justify;mso-line-height-alt:8.0pt'>7.1.<b> </b><span style='mso-tab-count:
            1'>     </span>Obriga-se a AUTORIZADA a manter sigilo quanto ao conteúdo dos
            manuais,<span style='mso-spacerun:yes'>  </span>treinamentos, tecnologia ou de
            quaisquer outras informações que vier a receber da HB-TECH, ou que tomar
            conhecimento, em virtude da presente contratação, devendo no caso de término ou
            rescisão da mesma, ser efetuada inspeção e inventário sob supervisão da HBTECH
            e/ou empresa parceira ou indicada para tal, ficando a AUTORIZADA, neste caso,
            obrigado a devolver imediatamente todo o material recebido e em seu poder.</p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-language:#00FF;mso-bidi-language:#00FF'>7.1.1.<b> </b>São
            consideradas confidenciais, para fins desta cláusula, todas e quaisquer informações
            que digam respeito aos negócios, desenhos técnicos, treinamentos, estratégia de
            negócios, fórmulas, marcas, registros, dados comerciais, financeiros e
            estratégicos, bem como todos e quaisquer dados relativos às atividades externas
            e internas das partes, sobre os produtos e marcas, informações estas
            fornecidas, a respeito das quais as partes venham a tomar conhecimento em
            virtude do presente contrato.<o:p></o:p></span></p>

            <p class=MsoBodyText style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:
            justify;mso-line-height-alt:8.0pt'>7.2. <span style='mso-tab-count:1'>     </span>A
            AUTORIZADA,<b> </b>seus sócios, diretores, prepostos, colaboradores ou
            empregados, não poderão fazer ou permitir que se façam cópias dos manuais,
            sistema informatizado, material promocional ou qualquer outra informação
            caracterizada como confidencial fornecida pela HB-TECH. Qualquer comprovada
            violação ao sigilo ora pactuado, a qualquer tempo, por parte da AUTORIZADA,<b> </b>seus
            sócios, diretores, prepostos, colaboradores, ou empregados, acarretará o
            pagamento da indenização prevista neste instrumento, sem prejuízo das demais
            disposições legais ou contratuais cabíveis.</p>

            <p class=MsoBodyText style='margin-bottom:0cm;margin-bottom:.0001pt;text-align:
            justify;mso-line-height-alt:8.0pt'>7.3. <span style='mso-bidi-font-family:
            Tahoma'>Considerando as negociações efetuadas entre as partes, na fase
            pré-contratual, é motivo de rescisão imediata do presente contrato, com o
            imediato fechamento da \"unidade autorizada\", qualquer violação de sigilo deste
            contrato e da negociação efetuada, tendo em vista princípios de probidade e de
            boa-fé. Qualquer vazamento de informação será compreendido como ato de
            irresponsabilidade e má-fé, acarretando os efeitos da responsabilidade por
            quebra de obrigações contratuais e falta grave de violação de dever de sigilo,
            rescindindo este contrato, independentemente, da cobrança de quaisquer
            indenizações por perdas e danos.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-right:.75pt;text-align:justify;mso-line-height-alt:
            10.0pt'><span style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:
            Tahoma;mso-fareast-language:#00FF;mso-bidi-language:#00FF'>7.4. A AUTORIZADA, </span>seus
            sócios, diretores, prepostos, colaboradores ou empregados <span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>considerando este contrato,
            a negociação realizada e o disposto no item k) anterior, obrigam-se a: (i) não
            copiar, reproduzir, transferir, ceder, divulgar ou transmitir as informações
            confidenciais e dados da presente negociação, seja a que título for; (ii)
            abster-se de falar, comentar, expor ou induzir observações ou assuntos que
            possam fazer referência aos negócios da franquia, fora do âmbito do
            desenvolvimento de suas atividades envolvendo os negócios da empresa,
            incluindo-se conversas externas às dependências da </span><span
            style='mso-bidi-font-family:Tahoma'>\"unidade autorizada\"</span><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>, escritórios de advogados
            da HB-TECH e/ou da AUTORIZADA, tais como elevadores, escadas, halls, banheiros,
            restaurantes, bares, festas, dentre outros; (iii) abster-se de tratar de
            assuntos da Franquia com terceiros, amigos ou parceiros de outros negócios, em
            quaisquer locais privados e/ou públicos quando não na consecução de suas
            atividades, dentre eles saguões de aeroportos, rodoviárias ou no interior de
            transportes públicos; (iv)<span style='mso-spacerun:yes'>  </span>não entregar
            por qualquer meio, dentre eles, fax, <i style='mso-bidi-font-style:normal'>email</i>,
            correio, qualquer material referente aos negócios da franquia, salvo com expressa
            autorização por escrito da HB-TECH, com qualquer tipo de processo ou informação
            dos referidos negócios.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>8 - RESPONSABILIDADES<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-right:2.15pt;text-align:justify;mso-line-height-alt:
            10.0pt'><span style='mso-bidi-font-family:'New York';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>8.1.<b> </b>A AUTORIZADA assume integral
            responsabilidade pelo pagamento das remunerações devidas a seus funcionários,
            pelo recolhimento de todas as contribuições e tributos incidentes, bem como
            pelo cumprimento da legislação social, trabalhista, previdenciária e
            securitária aplicável. Igualmente, a HB-TECH assume integral responsabilidade
            pelo pagamento das remunerações devidas a seus funcionários, pelo recolhimento
            de todas as contribuições e tributos incidentes, bem como pelo cumprimento da
            legislação social, trabalhista, previdenciária e securitária aplicável.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-language:#00FF;mso-bidi-language:#00FF'>8.2.<b> </b>As
            partes responderão, individualmente, por reivindicações de seus funcionários
            que sejam indevidamente endereçados à outra. A parte que der causa à
            reivindicação deverá<span style='mso-spacerun:yes'>  </span>assumir ao ações de
            defesa necessárias, e, em última instância, indenizará a parte reclamada das
            eventuais condenações que lhe venham a ser imputadas, inclusive das despesas e
            honorários advocatícios.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-language:#00FF;mso-bidi-language:#00FF'>8.3.<b> </b>É
            expressamente vedado às partes, sem que para tanto esteja previamente
            autorizada por escrito, contrair em nome da outra qualquer tipo empréstimo ou
            assumir em seu nome qualquer obrigação que implique na outorga de garantias.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-language:#00FF;mso-bidi-language:#00FF'>8.4.<b> </b>As
            partes não assumem qualquer vínculo, exceto aqueles expressamente acordados
            através do presente instrumento, obrigando-se ao cumprimento da legislação
            social, trabalhista, previdenciária e securitária aplicável.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-language:#00FF;mso-bidi-language:#00FF'>8.5. As obrigações e
            responsabilidades aqui assumidas pelas partes tem início a partir da data da
            assinatura do presente instrumento, não se responsabilizando reciprocamente, em
            hipótese alguma por erros, dolo, e qualquer outro motivo que possa recair sobre
            a administração das partes contratantes.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-language:#00FF;mso-bidi-language:#00FF'>8.6.<b> </b><span
            style='mso-tab-count:1'>     </span>Em caso de quaisquer infrações ao presente
            contrato, que possam implicar em perda de crédito,<span
            style='mso-spacerun:yes'>  </span>ou de alguma forma atingir a imagem da
            HB-TECH junto ao público consumidor, a AUTORIZADA</span>,<b> </b>seus sócios,
            diretores, prepostos, colaboradores ou empregados,<span style='mso-fareast-language:
            #00FF;mso-bidi-language:#00FF'> poderá ser responsabilizada por meio de
            procedimento judicial próprio, inclusive podendo ser condenada em perdas e
            danos.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>8.7.<b> </b><span
            style='mso-tab-count:1'>     </span>Em caso de ações propostas por
            consumidores, que reste provada a culpa ou dolo da AUTORIZADA, </span>seus
            sócios, diretores, prepostos, colaboradores ou empregados, <span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>esta concorda desde já que
            deverá assumir e integrar o polo passivo das ações judiciais que venham a ser
            demandadas contra a HB-TECH, isentando a mesma, e ressarcindo quaisquer valores
            que ela venha a ser condenada a pagar e/ou tenha pago.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><o:p>&nbsp;</o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9- DISPOSIÇÕES GERAIS<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.1. A AUTORIZADA, após a regular aprovação de seu
            credenciamento, passará à condição de CREDENCIADA para prestação de serviços de
            assistência técnica aos produtos comercializados pela HB-TECH.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.2. A AUTORIZADA declara neste ato, estar ciente que
            deverá manter, por sua conta e risco, seguro contra roubo e incêndio cujo valor
            da apólice seja suficiente para cobrir sinistro que possa ocorrer em seu
            estabelecimento, envolvendo patrimônio próprio e/ou de terceiros. Caso não o
            faça assume total responsabilidade e responderá civil e criminalmente pela
            omissão, perante terceiros e a HB-TECH.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.3. A AUTORIZADA - Declara conhecer e se compromete a
            cumprir o disposto no Código de Defesa do Consumidor e assume a
            responsabilidade de \"in vigilando\" por seus funcionários para esta finalidade.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.4. A AUTORIZADA responderá por seus atos, caso
            terceiros prejudicados vierem a reclamar diretamente à HB-TECH. Esta exercerá o
            direito de regresso acrescido de custas, honorários advocatícios, além de
            perdas e danos incidentes, inclusive danos punitivos.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.5. A HB-TECH fornecerá apoio técnico/administrativo,
            além de documentação e treinamento. Fica estabelecido para a AUTORIZADA o
            compromisso de sigilo referente à documentação recebida, ficando reservado
            única e exclusivamente à AUTORIZADA o uso da documentação técnica. Caso seja
            comprovada a quebra do sigilo ou a utilização dos componentes fornecidos em
            garantia em outros equipamentos, não comercializados pela HB-TECH, esta terá o
            direito de tomar as providências legais, podendo exigir reparação por perdas e
            danos que vier a sofrer.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.6. Toda correspondência (documentação, notas
            fiscais, comunicados, etc.) deverá ser enviada para o endereço especificado no
            preâmbulo deste contrato.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.7. Caso a AUTORIZADA tenha necessidade de enviar à
            HB-TECH placas, módulos ou equipamentos para conserto, deverá obter uma senha
            com o inspetor ou técnico de plantão. O aparelho deverá estar acompanhado de
            nota fiscal de remessa para conserto, e da ficha técnica e em especial da cópia
            da O.S., devidamente preenchidas.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.8. Os componentes solicitados para uma determinada
            O. S. só poderão ser usados para ela e deverão constar na mesma. A ausência
            dessa O. S. na HB-TECH, decorrido o prazo descrito no item 3.2 - 2 E, dará
            direito à HB-TECH de faturá-los contra a AUTORIZADA.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.9. A HB-TECH fornecerá à AUTORIZADA, tabela de
            preços de componentes com valores à vista.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.10. A HB-TECH fornecerá, como antecipação, os
            componentes para atender aparelhos na garantia, comercializados por ela, desde
            que seja mencionado, em pedido próprio, o número da respectiva O.S.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.11. O atendimento descrito no item anterior será
            suspenso quando a AUTORIZADA, por falta de devolução de componentes defeituosos,
            ou causas correlatas, acumular um valor superior ao seu limite de crédito.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.12. Os Pedidos de venda serão atendidos, com
            desconto de 20% e frete por conta do comprador. Os itens que não estiverem
            disponíveis em estoque serão cancelados. Este desconto é válido especificamente
            para os pedidos de venda, não sendo aplicável ao valor de peças não devolvidas.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.13. Os débitos não quitados no vencimento, serão
            descontados do primeiro movimento de ORDENS DE SERVIÇO, após esse vencimento,
            acrescidos de juros de mercado proporcionalmente aos dias de atraso. A HB-TECH
            poderá optar por outra forma de cobrança que melhor lhe convier.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>9.14. </span><span style='mso-bidi-font-family:'New York';
            letter-spacing:-.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'>As
            partes declaram ter recebido o presente instrumento com antecedência necessária
            para a correta e atenta leitura e compreensão de todos os seus termos, direitos
            e obrigações, bem como foram prestados mutuamente todos os esclarecimentos
            necessários e obrigatórios, e a inda que entendem, reconhecem e concordam com
            os termos e condições aqui ajustadas, ficando assim caracterizada a probidade e
            boa-fé de todas as partes contratantes.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'>9.15.<span
            style='mso-tab-count:1'>    </span>A eventual declaração judicial de nulidade
            ou ineficácia de qualquer das disposições deste contrato não prejudicará a
            validade e eficácia das demais cláusulas, que serão integralmente cumpridas,
            obrigando-se as partes a envidar seus melhores esforços de modo a validamente
            alcançarem os mesmos efeitos da disposição que tiver sido anulada ou tiver se
            tornado ineficaz.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'>9.16. <span
            style='mso-tab-count:1'>   </span>O não exercício ou a renúncia, por qualquer
            das partes, de direito, termo ou disposição previstos ou assegurados neste
            contrato, não significará alteração ou novação de suas disposições e condições,
            nem prejudicará ou restringirá os direitos de tal parte, não impedindo o
            exercício do mesmo direito em época subseqüente ou em idêntica ou análoga
            ocorrência posterior, nem isentando as demais partes do integral cumprimento de
            suas obrigações conforme aqui previstas.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'>9.17. <span
            style='mso-tab-count:1'>   </span>Este contrato contém o acordo integral e
            final das partes, com respeito às matérias aqui tratadas, substituindo todos os
            entendimentos verbais e/ou escrito entre elas, com respeito às operações aqui
            contempladas. Nenhuma alteração ou modificação deste contrato tornar-se-á
            efetiva, saldo se for por escrito e assinada pelas partes.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'New York';mso-fareast-language:#00FF;mso-bidi-language:
            #00FF'>9.18. <span style='mso-tab-count:1'>   </span>Este contrato obriga e beneficia
            as partes signatárias e seus respectivos sucessores e representantes a qualquer
            título. A AUTORIZADA não pode transferir ou ceder qualquer dos direitos ou
            obrigações aqui estabelecidas sem o prévio consentimento por escrito da
            HB-TECH.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'New York';mso-fareast-language:#00FF;mso-bidi-language:
            #00FF'>9.19. <span style='mso-tab-count:1'>   </span>Este contrato é celebrado
            com a intenção única e exclusiva de benefício das partes signatárias e seus
            respectivos sucessores e representantes, e nenhuma outra pessoa ou entidade
            deve ter qualquer direito de se basear neste contrato para reivindicar ou adquirir
            qualquer benefício aqui previsto.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'New York';mso-fareast-language:#00FF;mso-bidi-language:
            #00FF'>9.20. <span style='mso-tab-count:1'>   </span>As disposições constantes
            no preâmbulo deste contrato constituem parte integrante e inseparável do mesmo
            para todo os fins de direito, devendo subsidiar e orientar, seja na esfera
            judicial ou extrajudicial, qualquer divergência ou porventura venha a existir
            com relação ao aqui pactuado.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>10 - FORO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Estando de pleno acordo com todas as cláusulas e
            condições aqui expostas, elegem as partes contratantes o Foro da Comarca da
            Cidade de São Paulo, para dirimir e resolver toda e qualquer questão,
            proveniente do presente contrato, com expressa renuncia de qualquer outro, por
            mais privilegiado que seja.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify'><span style='mso-fareast-font-family:
            'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>E, por estarem assim justas e acertadas, firmam o
            presente instrumento, em duas vias de igual teor e forma, juntamente com as
            testemunhas abaixo indicadas.<o:p></o:p></span></p>

            <p class=MsoNormal align=center style='text-align:center;mso-line-height-alt:
            10.0pt'><span style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:
            Tahoma;mso-fareast-language:#00FF;mso-bidi-language:#00FF'>São Paulo, $data_contrato <o:p></o:p></span></p>

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
              10.0pt;layout-grid-mode:char'>HB ASSISTÊNCIA TÉCNICA LTDA.</p>
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
            <span style='mso-tab-count:1'>      </span>_______________________________<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>Nome: <span style='mso-tab-count:
            6'>                                                           </span>Nome:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>RG: <span style='mso-tab-count:
            6'>                                                                </span>RG:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>CPF: <span
            style='mso-tab-count:6'>                                                              </span>CPF:<o:p></o:p></span></p>

            <p class=MsoNormal align=center style='text-align:center;mso-line-height-alt:
            10.0pt'>ANEXO 1<o:p></o:p></b></p>

            <p class=MsoNormal align=left style='text-align:left;mso-line-height-alt:
            10.0pt'>À<br><b>$posto_nome<o:p></o:p></b></p>

            <p class=MsoNormal align=left style='text-align:left;mso-line-height-alt:
            10.0pt'><b>Ref.: Proposta Comercial de Assistência Técnica<o:p></o:p></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Prezados Senhores,<br><br>Inicialmente, agradecemos o 
            contato aberto e teremos prazer tê-los como parceiros da HB FLEX.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    <u>CONSIDERAÇÕES INICIAIS</u><o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    </span>Considerando que a HB FLEX desenvolveu uma 
            metodologia comercial e novo negócio através da marca HBFLEX, sob a qual venderá 
            produtos eletro-eletrônicos, sendo que sem prejuízo de outros produtos possíveis,
            poderão integrar os seguintes: i. DVD Players; ii. DVR Players; iii. MP4; iv. Maquinas
            de Lavar Roupas residenciais; v. Notebooks; vi. Desktops; vii. Ar Condicionado Splits; 
            viii. TVs LCD; e ix. Monitores LC. A HB FLEX será responsável pela fabricação dos 
            referidos produtos no território nacional e, posterior venda no mercado brasileiro 
            e/ou mercado externo, sendo necessária a contratação de empresa especializada para a 
            prestação de serviços de assistência técnica e manutenção dos produtos.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    <u>OBJETO DA PROPOSTA</u><o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    </span>Assim, concluindo nossos recentes entendimentos, 
            vimos formalizar nossa proposta comercial para a contratação e execução dos serviços 
            de assistência técnica, a serem realizados por V.Sas., consoantes termos fixados em 
            contrato de prestação de serviços que deverá ser assinado, considerando V.Sas. terem 
            assegurado que possuem o know how e tecnologia necessária.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    </span>Os serviços deverão englobar todas as etapas 
            necessárias para a consecução da assistência técnica nos produtos expressamente 
            abaixo indicados, sem prejuízo de outros que venham a ser contratados, ficando 
            claramente fixado que a HB FLEX remunerará V.Sas. apenas nas hipóteses que os 
            produtos estejam dentro da garantia originalmente fornecida (12 meses), ou dentro 
            do prazo de garantia estendida (12 meses mais 6 meses). Fora destas condições, a 
            HB FLEX apenas deverá ser responsável pelo fornecimento de peças de reposição 
            (cobradas), devendo V.Sas. cobrarem pelos trabalhos realizados individualmente.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    <u>REMUNERAÇÃO:</u><o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    </span>O preço desta proposta para os serviços 
            que envolvam especificamente a assistência técnica e manutenção dos produtos 
            é a seguinte:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>       <b>1. DVD Player - qualquer modelo produzido pela HB FLEX.<br>
            <span
            style='mso-tab-count:6'>           - R$ 20,00 ( vinte reais ), para qualquer reparo.</b><o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>       <b>2. MP4 Player - qualquer modelo produzido pela HB FLEX.<br>
            <span
            style='mso-tab-count:6'>           - R$ 10,00 ( dez reais), para qualquer reparo.</b><o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    <u>PRAZO DE DURAÇÃO:</u><o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    </span>O contrato terá prazo de validade indeterminado.
            <o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    <u>VALIDADE DA PROPOSTA:</u><o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    </span>Este proposta tem validade de 20 (vinte) dias, 
            vinculando as partes se aceita. Representa, ainda, o único e integral acordo entre 
            as partes, superando todos e quaisquer outros entendimentos havidos anteriormente.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt;
            tab-stops:0cm'><span style='mso-bidi-font-family:'New York';letter-spacing:
            -.15pt;mso-fareast-language:#00FF;mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    </span>Caso V. Sas. estejam de acordo com o teor desta 
            proposta, e aja confirmação da aceitação desta, solicitamos a devolução de uma via 
            com o seu 'de acordo', e a devida rubrica em todas as páginas, passando a presente 
            avença a produzir seus regulares efeitos, sendo a mesma formalizada por intermédio 
            de contrato de prestação de serviços.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    Atenciosamente,<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:center;mso-line-height-alt:8.0pt' align='center'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    HB ASSISTÊNCIA TÉCNICA LTDA.<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    <u>De Acordo:</u><o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    Assinatura:_______________________________________<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    Nome:_____________________________________________<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    Empresa:__________________________________________<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><span
            style='mso-tab-count:6'>    CNPJ:_____________________________________________<o:p></o:p></span></b></p>

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
                    echo "<p align='center'>Foi gerado contrato da Fábrica HBtech para posto $posto_nome, cujo cnpj é $cnpj.<br>";
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
            10.0pt'><b>CONTRATO DE CREDENCIAMENTO DE ASSISTÊNCIA TÉCNICA<o:p></o:p></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'>Pelo
            presente instrumento particular,</p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><b>ZUQUI IMPORTAÇAO 
            E EXPORTAÇAO LTDA</b>, sociedade empresarial com escritório administrativo na Rua Nilo 
            Peçanha, 1032, Curitiba - PR CEP - 80520-000, inscrita no CNPJ sob nº 08.607951/0001-35, 
            neste ato representada por seu diretor ao final assinado, doravante denominada &quot;CROWN FERRAMENTAS ELÉTRICAS DO BRASIL&quot;, e<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b>$posto_nome.</b>, sociedade empresarial com sede na $endereco,
            $numero $complemento, na cidade de $cidade, $estado, CEP $cep, inscrita no CNPJ sob nº
            $cnpj, neste ato representada por seu administrador, ao final
            assinado, doravante denominada &quot;AUTORIZADA&quot;,</p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>1- OBJETIVO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>1.1. O objetivo do presente contrato é a prestação, pela AUTORIZADA, 
            em sua sede social, do serviço de assistência técnica aos produtos comercializados pela CROWN 
            FERRAMENTAS ELÉTRICAS DO BRASIL.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>1.2. Os serviços que serão prestados pela AUTORIZADA, consistem em 
            manutenção corretiva e preventiva, quando os custos serão cobertos pela CROWN FERRAMENTAS ELÉTRICAS DO BRASIL, 
            através de taxas de garantia, fornecimento de peças e informação técnica.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>2- DA EXECUÇÃO DOS SERVIÇOS DURANTE A GARANTIA<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>2.1. O prazo e condições de garantia dos produtos comercializados 
            pela CROWN FERRAMENTAS ELÉTRICAS DO BRASIL, são especificados no certificado de garantia, cujo início é 
            contado a partir da data emissão da nota fiscal de compra do produto pelo primeiro 
            usuário.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Para consertos efetuados em aparelhos no período de garantia, 
            a CROWN FERRAMENTAS ELÉTRICAS DO BRASIL,  pagará à AUTORIZADA , no mês subseqüente à apresentação das O.S. 
            os seguintes valores de Taxa de Mão de Obra:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>3- PREÇO E CONDIÇÕES DE PAGAMENTO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Para consertos efetuados em aparelhos no período de garantia, 
            a CROWN FERRAMENTAS ELÉTRICAS DO BRASIL,  pagará à AUTORIZADA , no mês subseqüente à apresentação das O.S. 
            os seguintes valores de Taxa de Mão de Obra:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>(i) Ferramentas até 1.000 Watts - R$ 15,00<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>(ii) Ferramentas acima de 1.000 Watts até 2.000 Watts - R$ 25,00<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>(iii) Ferramentas acima de 2.000 Watts - R$ 30,00<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>4 - DURAÇÃO DO CONTRATO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>A validade do presente contrato é por tempo indeterminado e 
            poderá ser rescindido por qualquer das partes, mediante um aviso prévio de 30 
            (trinta) dias, por escrito. A autorizada obriga-se, neste prazo do aviso, a dar 
            continuidade aos atendimentos dos produtos em seu poder.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>5 - RESPONSABILIDADES<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>5.1. A AUTORIZADA assume responsabilidade pelo pagamento das 
            remunerações devidas a seus funcionários, pelo recolhimento de todas as contribuições e 
            tributos incidentes, bem como pelo cumprimento da legislação social, trabalhista, 
            previdenciária e securitária aplicável.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>5.2. Em caso de ações propostas por consumidores, 
            que reste provada a culpa ou dolo da AUTORIZADA, concorda desde já que deverá 
            responder pelo  passivo das ações judiciais que venham a ser demandadas contra a 
            CROWN FERRAMENTAS ELÉTRICAS DO BRASIL.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>6- DISPOSIÇÕES GERAIS<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>6.1. A AUTORIZADA Declara conhecer e se compromete a 
            cumprir o disposto no Código de Defesa do Consumidor e assume a responsabilidade 
            de 'in vigilando' por seus funcionários para esta finalidade.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>6.2. Os componentes solicitados para uma determinada O.S. 
            só poderão ser usados para ela e deverão constar na mesma. A ausência dessa O. S. 
            na CROWN FERRAMENTAS ELÉTRICAS DO BRASIL, decorrido o prazo regular , dará direito à CROWN FERRAMENTAS ELÉTRICAS DO BRASIL 
            de faturá-los contra a AUTORIZADA. As peças utilizadas em garantia deverão ser mantidas 
            por 90 dias antes do descarte.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>6.3. Os débitos não quitados no vencimento, serão descontados 
            do primeiro movimento de ORDENS DE SERVIÇO, após esse vencimento, acrescidos de 
            juros de mercado proporcionalmente aos dias de atraso.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>6.4.   Este contrato obriga e beneficia as partes signatárias 
            e seus respectivos sucessores e representantes a qualquer título. A AUTORIZADA não 
            pode transferir ou ceder qualquer dos direitos ou obrigações aqui estabelecidas sem 
            o prévio consentimento por escrito da CROWN FERRAMENTAS ELÉTRICAS DO BRASIL.<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>7 - FORO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Estando de pleno acordo com todas as cláusulas e condições 
            aqui expostas, elegem as partes contratantes o Foro da Comarca da Cidade de Curitiba, 
            para dirimir e resolver toda e qualquer questão, proveniente do presente contrato, 
            com expressa renuncia de qualquer outro, por mais privilegiado que seja. E por estarem 
            assim contratados, firmam o presente em duas vias do mesmo teor e para um só efeito, 
            na presença de duas testemunhas.<o:p></o:p></span></p>

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
              10.0pt;layout-grid-mode:char'>CROWN FERRAMENTAS ELÉTRICAS DO BRASIL.</p>
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
            <span style='mso-tab-count:1'>      </span>_______________________________<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>Nome: <span style='mso-tab-count:
            6'>                                                           </span>Nome:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>RG: <span style='mso-tab-count:
            6'>                                                                </span>RG:<o:p></o:p></span></p>

            <p class=MsoNormal style='text-align:justify;mso-line-height-alt:8.0pt'><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>CPF: <span
            style='mso-tab-count:6'>                                        </span>CPF:<o:p></o:p></span></p>
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
                    echo "<p align='center'>Foi gerado o contrato para posto $posto_nome, o CNPJ deste posto é $cnpj.<br>";
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
            9.0pt;font-size:11pt'><b>CONTRATO DE CREDENCIAMENTO DE POSTO AUTORIZADO DE ASSISTÊNCIA TÉCNICA<o:p></o:p></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>CONTRATANTE: <b>KELLATOR SOLUÇÕES EM EQUIPAMENTOS LTDA.</b>, doravante chamada <b>KELLATOR</b><o:p></o:p></span></p>

	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'>CNPJ nº: 30.388.338/0001-23</p>
	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'>ENDEREÇO: Av. João Paccola, 1389, Vila Antonieta II, Lençóis Paulista, S.P.</p>
	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'>REPRESENTANTE LEGAL: RODRIGO CAPELARI VICTAGLIANO</p>

	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'>CONTRATADA/POSTO: <b style='text-transform:uppercase;'>".strtoupper($posto_nome)."</b>, doravante denominado simplesmente AUTORIZADA</p>

	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'>CNPJ nº: $cnpj</p>

	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'>ENDEREÇO: $endereco, $numero $complemento, $cidade, $estado</p>

	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'>REPRESENTANTE LEGAL: ___________________________________________________</p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>1- OBJETIVO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>O Objetivo do presente contrato é prestação, pela AUTORIZADA, em sua sede social, do serviço de assistência técnica, das marcas representadas pela CONTRATANTE.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>2- DA GESTÃO DO SISTEMA DE GARANTIAS E ASSISTËNCIA TÉCNICA<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>2.1. A CONTRATANTE poderá, a seu critério, contratar uma terceira entidade gestora de seus processos de garantia e de assistência técnica. Essa terceira entidade, que poderá ser a chamada de TELECONTROL, ou outra que, a critério da CONTRATANTE venha substituí-la, terá liberdade de interagir diretamente com a AUTORIZADA, devendo esta acatar e proceder com as ordens de serviço a ela endereçadas, como se a própria CONTRATANTE as tivesse enviado.<o:p></o:p></span></p>

	     <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>2.2. A AUTORIZADA deverá se adequar ao sistema da TELECONTROL, para que possa receber as ordens de serviço, registrar os serviços prestados e imprimir os extratos para fins de emissão de nota fiscal de serviços.<o:p></o:p></span></p>
	    
	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>3- PAGAMENTO DA TAXA DE MÃO-DE-OBRA<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Os serviços executados em ORDENS DE SERVIÇO (garantia) deverão ser registrados no site da Telecontrol. No dia 01 de cada mês será gerado um extrato com todas as ordens de serviços finalizadas. A AUTORIZADA deverá imprimir o extrato e enviar nota fiscal de prestação de serviços para a  TELECONTROL e para a CONTRATANTE, conforme orientações contidas no site da TELECONTROL. Dentro de 10 dias após o recebimento da nota fiscal de mão de obra, o depósito será realizado na conta bancária da AUTORIZADA.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>4- DURAÇÃO DO CONTRATO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>A validade do presente contrato é por tempo indeterminado e poderá ser rescindido por qualquer das partes, mediante um aviso prévio de 30 (trinta) dias, por escrito. A AUTORIZADA obriga-se, neste prazo de aviso, a dar continuidade aos atendimentos dos produtos em seu poder.<o:p></o:p></span></p>


            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>5- RESPONSABILIDADES<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>5.1. A AUTORIZADA se compromete a seguir normas de procedimento expressamente vinculadas pela TELECONTROL e pela CONTRATANTE;<o:p></o:p></span></p>

	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
             style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
             mso-bidi-language:#00FF'>5.2. Em caso de ações judiciais propostas por consumidores, em que restem provadas a culpa ou dolo da AUTORIZADA, seus sócios, diretores, gestores, prepostos, colaboradores ou empregados, esta concorda desde já que deverá assumir e integrar o polo passivo das ações judiciais, reclamações em Procon, dentre outros órgãos que venham a ser demandadas contra a TELECONTROL ou a CONTRATANTE<o:p></o:p></span></p>
            
            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>6- DISPOSIÇÕES GERAIS<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>A AUTORIZADA declara conhecer e se compromete a cumprir o disposto no Código de Defesa do Consumidor e assume a responsabilidade ¿in vigilando¿ e ¿in elegendo¿ pelos atos de seus funcionários para esta finalidade.<o:p></o:p></span></p>

	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>7- FORO<o:p></o:p></span></b></p>


            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
	    mso-bidi-language:#00FF'>Estando de pleno acordo com todas as cláusulas e condições aqui expostas, elegem as partes contratantes o Foro da Comarca de Lençóis Paulista, S.P., para dirimir e resolver todas e quaisquer questões proveniente do presente contrato, com expressa renúncia de qualquer outro, por mais privilegiado que seja.<o:p></o:p></span></p>

	    <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
             style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
             mso-bidi-language:#00FF'>E, por estarem assim justas e acertadas, firmam o presente instrumento, em duas vias de igual teor e forma, juntamente com as testemunhas abaixo.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Lençóis Paulista, ".date(d)." de ".$array_meses[intval($mes)]." de ".date(Y).". <o:p></o:p></span></p>

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
            9.0pt;font-size:11pt'><b>CONTRATO DE CREDENCIAMENTO DE ASSISTÊNCIA TÉCNICA<o:p></o:p></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:9.0pt;font-size:11pt'>Pelo
            presente instrumento particular,</p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'><b>TELECONTROL NETWORKING LTDA</b>, sociedade empresarial com escritório administrativo na Av. Carlos Artêncio, 420 A - Bairro Fragata C, CEP 17.519-255, na cidade de Marília, estado de São Paulo, inscrita no CNPJ sob nº 04.716.427/0001-41, 
            neste ato representada por seu diretor ao final assinado, doravante denominada simplesmente &quot;TELECONTROL NETWORKING&quot;, e<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b style='text-transform:uppercase;'>".strtoupper($posto_nome)."</b>, sociedade empresarial com sede na $endereco,
            $numero $complemento, na cidade de $cidade, $estado, CEP $cep, inscrita no CNPJ sob nº
            $cnpj, neste ato representada por seu administrador, ao final
            assinado, doravante denominada &quot;AUTORIZADA&quot;,</p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>1- OBJETIVO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>1.1. O objetivo do presente contrato é a prestação, pela AUTORIZADA, 
            em sua sede social, do serviço de assistência técnica das empresas que contratarem a TELECONTROL para administrar o pós-venda.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>2- PAGAMENTO DA TAXA DE MÃO-DE-OBRA<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>2.1. Os serviços executados deverão ser registrados no site da TELECONTROL. No dia $data_extrato de cada mês será gerado um extrato com todas as ordens de serviços finalizadas. A AUTORIZADA deverá imprimir o extrato e enviar nota fiscal de prestação de serviços para a TELECONTROL, conforme orientações no site, e dentro de 10 dias após o recebimento da nota na TELECONTROL o depósito será realizado na conta bancária da AUTORIZADA.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-fareast-font-family:'Lucida Sans Unicode';mso-bidi-font-family:Tahoma;
            mso-fareast-language:#00FF;mso-bidi-language:#00FF'>3- DURAÇÃO DO CONTRATO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>3.1. A validade do presente contrato é por tempo indeterminado e 
            poderá ser rescindido por qualquer das partes, mediante um aviso prévio de 30 
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
            mso-bidi-language:#00FF'>4.2. Em caso de ações propostas por consumidores, que reste provada a culpa ou dolo da AUTORIZADA, seus sócios, diretores, prepostos, colaboradores ou empregados, esta concorda desde já que deverá assumir e integrar o polo passivo das ações judiciais que venham a ser demandadas contra a TELECONTROL e a empresas proprietárias dos produtos em questão.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>5- DISPOSIÇÕES GERAIS<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>5.1. A AUTORIZADA declara conhecer e se compromete a 
            cumprir o disposto no Código de Defesa do Consumidor e assume a responsabilidade 
            de 'in vigilando' por seus funcionários para esta finalidade.<o:p></o:p></span></p>

            
            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><b><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>6- FORO<o:p></o:p></span></b></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Estando de pleno acordo com todas as cláusulas e condições aqui expostas, elegem as partes contratantes o Foro da Comarca de Marília, estado de São Paulo, para dirimir e resolver toda e qualquer questão, proveniente do presente contrato, com expressa renuncia de qualquer outro, por mais privilegiado que seja.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>E, por estarem assim justas e acertadas, firmam o presente 
            instrumento, em duas vias de igual teor e forma, juntamente com as testemunhas abaixo 
            indicadas.<o:p></o:p></span></p>

            <p class=MsoNormal style='margin-left:1pt;;text-align:justify;mso-line-height-alt:7.0pt;font-size:11pt' align='justify'><span
            style='mso-bidi-font-family:'DejaVu Sans';mso-fareast-language:#00FF;
            mso-bidi-language:#00FF'>Marília, ".date(d)." de ".$array_meses[intval($mes)]." de ".date(Y).". <o:p></o:p></span></p>

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
                    
                        echo "<p align='center'>Foi gerado o contrato para posto $posto_nome, o CNPJ deste posto é $cnpj.<br>";
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
        $msg="Não foi encontrado nenhum posto com este email ou cnpj";
    }
    if(strlen($msg) > 0) {
        echo $msg;
    }
}


?>

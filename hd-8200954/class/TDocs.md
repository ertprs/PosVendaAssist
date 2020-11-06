
[tdocsDoc]: https://docs.tdocs.apiary.io/ "Documentação TDocs"

# Classe **TDocs** para o Sistema de PósVenda
O serviço **TDocs** da API Telecontrol é um repositório de arquivos, que serve a todas as aplicações que o solicitarem. Permite o envio, recuperação e inativação de anexos para todo o sistema de PósVenda.

Os arquivos enviados ao TDocs são armazenados no AWS S3, indexados em uma tabela da API para sua fácil recuperação por um _hash_ fornecido pelo serviço no momento do _upload_. Com este _hash_ é possível recuperar o arquivo, ou excluir ele.

Para informações específicas do **TDocs**, consulte a [documentação do TDocs][tdocsDoc] no **APIary**.

Para enviar um arquivo como anexo do Pós Venda, algumas informações complementares são necessárias, como o contexto (OS, help desk, pedido etc) e o ID do processo/objeto ao qual o arquivo está associado

Para efeitos de histórico, os anexos não são excluídos realmente, apenas inativados no banco de dados, e mantido um _log_ de acesso no próprio registro.

Para acompanhar a documentação os nomes das variáveis informam o tipo de informação que deve ser repassada.

Variável  | Tipo | Exemplo | Conteúdo
----------|------|---|---
`$tDocs`  | int  | 800 | PK da tabela `tbl_tdocs`
`$tDocsID`|String (64)| _hash_ | Valor retornado pela API TDocs, é um _hash_ de 32 bytes (64 caracteres),<br /> identifica o documento
`$ref`    | int  | 123456 | PK do campo de referência. Se o contexto é 'os',<br />`$ref` seria o valor do campo `tbl_os.os`

Quando um método pode receber vários IDs diferentes (ref, tDocs, tDocsID), o nome da variável será `$id`.

## Métodos da classe
A classe tem as seguintes funcionalidades:

### Inicialização
Como no caso de implementações anteriores, é necessário informar o fabricante (`$login_fabrica`) e o tipo de anexo, que deveria ser o contexto ou sub contexto, mas, para facilitar a integração, pode ser algum dos "tipos" usados hoje ('os', 'lt', 'co', 've', etc.).

A grande diferença é que também deve ser injetado um <u>recurso de conexão</u>. Assim, a inicialização típica, seria:

```php
    $s3 = new TDocs($con, $login_fabrica);
```

Com isso, teremos uma instância que poderá ser utilizada para procurar, subir ou "excluir" anexos de comunicados para a fábrica que está rodando ao script nesse momento. Para isso, a classe oferece os seguintes métodos e atributos:

### Estabelecer ou alterar o contexto (ou sub contexto)
 `TDocs::setContext(string $contexto[, string $subcontexto])`

Define o contexto e sub contexto do arquivo, tornando possível a reutilização do objeto por _pseudo_ polimorfismo. Para facilitar o uso, o primeiro parâmetro pode ser diretamente o sub contexto, desde que este não exista em dois contextos diferentes. Assim, `setContext('os', 'item')` e `setContext('item')` são equivalentes, mas só porque nenhum outro contexto tem um sub contexto _item_.

### Consultar o contexto atual
 `TDocs::getContext()`<br />
Retorna o contexto atual. Serve para determinar se está no "ambiente" certo antes de iniciar um processo de pesquisa ou de upload. Retorna uma string com as informações separadas por ponto e vírgula `';'`. As informações são: _contexto_, _subcontexto_ e _campo referência_.

```php
    $s3->setContext('os_item')
       ->getContext(); // os;item;tbl_os_item.os_item
```

### Procurar documentos pela referência<a name='getDocRef'></a>
 `TDocs::getDocumentsByRef(mixed $tDocsId|$ref[, $contexto])`<br />
Procura todos os anexos desse contexto (se informado, se não, usa o último que foi definido) e ID. Se achou algum documento, armazena as informações de cada um em um `array` no atributo `attachListInfo` (ver exemplo abaixo). Também pode procurar por um arquivo específico se passado o `tDocsID` (_hash_ que identifica o objeto S3).

```php
        $s3->getdocumentsByRef(123456, 'help desk'); // 1
```

Procura anexos cujo identificador é o `tbl_hd_chamado_item.hd_chamado_item = 123456`

```php
        $s3->getdocumentsByRef('7c7d71d726e39abe47417817109b8ef9cb46776a9635d3b23e1877bec7141ee9'); // 1
```

Procura por o documento com esse _hash_, e portanto deve localizar apenas um documento. Retorna o próprio objeto, o que permite encadear uma chamada para outro método ou propriedade, permitindo recuperar um `boolean` ou `int` de forma rápida:

```php
	$s3->getDocumentsByRef(123456, 'hd'); // retorna o próprio objeto, podemos então

	$s3->getDocumentsByRef(123456, 'hd')->hasAttachment; // retorna TRUE
	$s3->getDocumentsByRef(123456, 'hd')->attachCount;   // retorna 1

	$s3->getDocumentsByRef(123456, 'hd')->attachListInfo; // retorna o array com as informações
    7 => [
        'tdocs_id'      => '7c7d71d726e39abe47417817109b8ef9cb46776a9635d3b23e1877bec7141ee9',
        'contexto'      => 'helpdesk',
        'referencia'    => 'tbl_hd_chamado_item.hd_chamado_item',
        'referencia_id' => 123456,
        'filename'      => 'documento.pdf',
        'filesize'      => 568472,
        'link'          => 'https://api2.telecontrol.com.br/tdocs/document/id/7c7d71d726e...bec7141ee9',
        'extra' => [
            'acao'     => 'anexar',
            'filename' => 'documento.pdf',
            'date'     => '2016-08-01T09:00:00',
            'fabrica'  => 10,
            'usuario' => [
                'admin'   => 1375,
                'login'   => 'manuel'
            ],
            'page'      => 'os_cadastro_tudo.php',
            'access_IP' => '189.32.64.194'
        ]
    ]
```

### Procurar documentos pelo nome do arquivo
 `TDocs::getDocumentsByName(string $nome[, string $contexto])`<br />
Muitas implementações de gerenciamento de arquivos no S3 do PósVenda utilizam um nome específico para diferenciar o subtipo de anexo. Assim, este método permite a localização de um anexo pelo nome do arquivo ou parte do nome, como se usasse uma máscara `nome*.*`, da mesma forma que é implementado nas classes anteriores o método/função `s3glob()`.

O retorno é o mesmo que o método `getDocumentsByRef()`, assim pode ser usado com uma mínima alteração.

### Recuperar informações sobre o arquivo
 `TDocs::getDocumentInfo(int $tDocs[, int $history])`<br />
Este método devolve informações do arquivo apontado pelo **ID da tabela**. As informações incluem, além de _hash, link_, nome do arquivo e ID (`tbl_tdocs.tdocs`), quem subiu ou alterou o arquivo, quantos elementos tem o histórico do registro e qual foi a última ação (anexar, substituir, renomear...). Opcionalmente, pode solicitar um elemento específico do histórico do arquivo, se não é informado, devolve as informações mais recentes.


```php
	$s3->getDocumentInfo(7);

	// retorno:
	array(
		'InsertDate' => '2016-08-01 10:00:00',    // data da criação do registro na tabela
		'Context' => 'help desk;hd_chamado_item', // Contexto[;subcontexto];campo de referência
		'ReferenciaId' => 123456,                 // valor do campo de referência
		'LastModified' => '2016-08-01 10:00:00',  // Data da ação que está sendo retornada
		'fileName' => 'documento.pdf',            // nome do arquivo, ou 'NONAME' se foi excluído
		'Situacao' => 'ativo',
		'user' => array(                          // informações do usuário que realizou a ação
		    'accessType' => 'admin',
		    'login' => 'manuel',
		    'admin' => 1375
		)
	);
```
### Recuperar o histórico do arquivo
 `TDocs::getDocumentHistory(int $tdocs[, int $max = null])`<br />
Este método consulta o ID da tabela, recuperando o JSON com o histórico do arquivo, e devolve um _array_ com todos os arquivos desse registro. Comunicados, por exemplo, tem a opção de subir um arquivo substituindo o já existente.

O parâmetro opcional `$max` permite informar o limite de elementos do histórico. Uma futura implementação poderia receber uma ou duas datas (limite inferior ou intervalo do histórico).

Ao contrário da maioria dos métodos desta classe, não temos um parâmetro para o contexto ou referência, pois está recebendo o ID da tabela, sendo desnecessário o filtro por contexto, _hash_, referência e inclusive por ID do objeto.

Com este método fica mais fácil ter uma lista dos arquivos que já foram vinculados com o objeto de referência (como um comunicado ou uma foto de um produto), podendo recuperar arquivos excluídos por engano, ou que precisam ser consultados por algum outro motivo.

```php
    $historico = $tDocs->getDocumentHistory(7000); // ID da tabela!
    echo array2table($historico);
```
Devolveria:

| tdocs_id          | date                | link                       | filename    | filesize |
| ---               | :---:               | ---                        | ---         | ---:     |
| 1296967e...fc1f3b | 2016-08-16T10:52:49 | https://api2.tele..f3d.png | com_f3d.png | 8456832  |
| d0fb230f...646c43 | 2016-08-15T17:45:18 | https://api2.tele..old.png | com_old.png | 8456832  |


### Recuperar o endereço (URL) do arquivo<a name='getDocLoc'></a>
 `TDocs::getDocumentLocation(int $tDocs)`<br />
Procura o elemento com base ao **ID da tabela**, e retorna a URL do arquivo. é mais direto que o `getDocumentsByRef()`, pois este aceita exclusivamente o ID da tabela, e não o tDocsID (o _hash_), podendo acessar unicamente a **um** documento.

A URL retornada é formada pela URL da API, mais o _TDocs **ID**_, o parâmetros `size` se foi solicitado o _thumbnail_ da imagem, e mais um parâmetro `file` (inexistente na API, portanto é ignorado) com o nome do arquivo armazenado no JSON `tbl_tdocs.obs`, o que permite que o *client* (o navegador, normalmente) interprete o endereço como um arquivo, com ajuda do `HEADER` enviado pela API. Seguindo o [exemplo do método getDocumentsByRef](#getDocRef), a chamada e retorno seriam:

```php
    $s3->getDocumentLocation(7);
    // https://api2.telecontrol.com.br/tdocs/document/id/1d726e...bec7141ee9/file/documento.pdf
```
### Subir um arquivo ao TDocs (sem relacionamento com o banco)
 `TDocs::sendFile(mixed $arquivo)`<br />
 Este método envia um arquivo à **API TDocs**, que, caso processe a requisição corretamente, irá retornar um _hash_ que permite recuperar o arquivo. O método `sendFile()` irá estabelecer o valor do atributo `TDocs::$sentData` com os dados do arquivo (nome, tamanho, tipo, _hash_) para posterior uso. O nome original do arquivo ($\_FILES['name']) é sanitizado de forma que não contenha caracteres não alfanuméricos, `.`, `-` ou `_`. Caracteres acentuados são convertidos para a correspondente letra sem acento.

 Este método retorna `FALSE` se houve algum erro (arquivo, conexão, API...), que será armazenado no atributo `TDocs::$error`, ou o `tDocsID` em caso de êxito no _upload_. Se por algum motivo este valor não é armazenado, o arquivo estará "perdido", pois é a única maneira de identificá-lo na API.

### Vincular um documento (TDocs) a um objeto do PósVenda
 `TDocs::setDocumentReference(array $fileInfo, int $refID[, string $acao, bool $overwrite, string $contexto])`<br />
 Se o método anterior permite subir um arquivo ao TDocs sem necessidade de vincular o mesmo a um registro de outra tabela, este método permite completar o processo, gravando um registro (ou atualizando um existente, se `$overwrite` for `TRUE`) na `tbl_tdocs` para vincular um documento do TDocs ao registro escolhido do **contexto**.

```php
    $tDocs->setDocumentReference(
        array(
            'tdocs_id' => '1d726e...bec7141ee9',
            'name' => 'exemplo.zip',
            'size' => 1048576,
        ),
        123456789,
        'anexar',
        TRUE,
        'comunicados'
    );
```

O código acima irá vincular o objeto do TDocs 1d72... ao registro 123456789 da `tbl_comunicado` (por causa do contexto). Este método **VALIDA O ID DO REGISTRO NA TABELA DE REFERÊNCIA!**, o que quer dizer que irá retornar `FALSE` se o registro não existe. Não valida a presencia do arquivo no TDocs, pois iria demorar desnecessariamente o processo (o acesso à API2 pode demorar até 2 segundos,dependendo do estado da rede e do servidor).

### Anexar um arquivo a um objeto Ref no TDocs
 `TDocs::uploadFileS3(mixed $path|$_FILE[index], int $refID[, bool $overwrite = true, string $contexto])`<br />
Este método grava o arquivo no S3 (usando o método `sendFile()`) usando a **API** ***TDocs*** e vincula o arquivo com uma referência a um registro de uma tabela do PósVenda.

Parâmetros:

1. Pode ser o _path_ do arquivo, ou um array tipo `$_FILES['arquivo']` (não o `_FILES` completo!).
2. Obrigatoriamente deverá receber também o ID da referência **ou o _hash_ do TDocs**, para poder localizá-lo posteriormente. Se conseguiu subir, retorna `TRUE`, se houve algum problema, retorna `FALSE` e o motivo estará no atributo `error` do objeto.
3. Opcional, informa se em caso de existir um anexo para o objeto informado, o novo arquivo deve substituir o existente (`TRUE`, valor padrão) ou criar um novo registro (`$overwrite = FALSE`).
4. Também é possível alterar o contexto e sub contexto no momento do upload, porém não é recomendado a não ser que seja um valor constante e conhecido.

```php
    $s3->uploadFileS3($arquivo, 12345678, false); // TRUE: está salvo!
```

Cria, substitui (_overwrite_ `TRUE`) ou adiciona (_overwrite_ `FALSE`) o arquivo apontado por `$arquivo` ao ID `123456` do contexto atual.

Este método utiliza os métodos `sendFile()` e `setDocumentReference()`, portanto o ID da tabela de referência **é validado e deve existir** antes de iniciar a ação.

#### Substituir um arquivo já existente
Quando um arquivo é enviado ao TDocs e vinculado com `$overwrite = true`, a classe substitui de forma autmática o documento anterior com o novo, deixando, é claro, as informações do anterior no **JSON** do registro.

Mas, quando um arquivo é anexado com `$overwrite = false`, um novo registro é gravado na tabela. Assim, como seria possível **substituir** um arquivo que já existe?

O método `setDocumentReference()` permite receber o `$objectID` (referência de os, peça, produto,extrato, etc.) **ou** o ***hash*** do anexo. Neste caso, o novo anexo substitui então o referenciado pelo _hash_, permitindo _sobrescrever_ um arquivo por um novo, mesmo usando `false` no parâmetro `$overwrite`.

```php
    $anterior = $tDocs->attachListInfo[0]['tdocs_id'];
    $anexou   = $tDocs->uploadFileS3($_FILES['anexo'], $anterior, false);
```
Este código sobrescreve o documento com `tdocs_id` `$anterior` pelo arquivo enviado pelo navegador, sem criar um novo registro.

### Renomear um Documento
 `TDocs::setDocumentFileName(int $id, string $nome)`<br />
Renomeia o arquivo apontado pelo **ID da tabela** `$id` ou _hash_ do **TDocs** para o novo `$nome`. Este método não acessa a API e nem substitui o arquivo, apenas cria uma nova chave no JSON do campo `obs`, com a ação _renomear_ e o novo nome, junto com os dados do usuário para efeitos de _log_.

Retorna o objeto ou `FALSE` se houve algum erro, com a mensagem no atributo `error`. A princípio apenas pode dar erro quando for atualizar o banco de dados, pois o nome do arquivo é "sanitizado" (todo caractere que não seja alfanumérico, hífen ou _undescore_ é substituído por esse último (`_`).

### Validação da referência do documento
 `TDocs::checkDocumentId(int $id)`<br />
Confere se existe o objeto (campo referência => ID referência) ao qual o documento está associado. Por exemplo:

```php
    $tDocs = new TDocs($con, $login_fabrica, 'comunicados');
    echo $tDocs->checkDocumentId(123456);
```

Confere se existe o registro `123456` na `tbl_comunicado`, simplesmente fazendo um `SELECT` na tabela com o campo de referência.

### Excluir documentos por referência
 `TDocs::removeDocumentsByType(int $ref, string $contexto[, $subcontexto])`<br />
Localiza **TODOS** os anexos do **ID / contexto / referência** informados e os exclui, usando o método a seguir.

### Excluir documento por ID
 `TDocs::removeDocumentById(mixed $tDocs)`<br />
Mais especificamente, marca como inativo (`tbl_tdocs.situacao = 'inativo'`) o registro. O registro pode ser referenciado pelo ID da tabela `tbl_tdocs` ou pelo _hash_ do arquivo, sempre deve apontar para um único registro.

### Excluir arquivo do S3
 `TDocs::deleteFileById(mixed $tDocs)`<br />
<p class='alert alert-danger'>
Este método ordena a exclusão definitiva do objeto (arquivo) do S3, no repositório do TDocs. Sua finalidade é se desfazer de arquivos temporários, da área de teste, etc., e **não é para excluir os arquivos de produção dos clientes**, salvo se ordenado ou autorizado préviamente.
</p>

> **Repetindo:** este método apenas deve ser usado com a autorização da Gerência de TI!

Para excluir um arquivo do S3, basta informar o ID da tabela ou o hash do arquivo. No primeiro caso, o registro na tabela `tbl_tdocs` deve existir; no segundo caso, será possível excluir um arquivo sabendo apenas seu `$tDocsID`. Se esse id existe na tabela, será 'excluído' (marcado inativo e a ação será 'deletar'), porém não é requisito para realizar a exclusão.

## Propriedades e atributos

A seguir, as propriedades e atributos disponíveis, e como adicionar um novo contexto ou alterar um já existente.

### `TDocs::$attTypes`
Esta propriedade contém as informações necessárias para configurar o objeto de acordo a um contexto e subcontexto. Trata-se de uma matriz de dados (`array` multidimensional) que define: tipo e campo de referência, subtipos e campos de referência, nomes alternativos para tipo e subtipo.

O primeiro nível tem uma chave, com quatro elementos, dois deles obrigatórios, que são:

- `contexto` (conforme o `ENUM` da `tbl_tdocs`)
- `referencia` o campo de referência desse contexto (tabela.nome_do_campo)
- `alternate` (opcional), um `array` com outros nomes para o `contexto`, o que flexibiliza o uso
- `subcontexto`, um `array` com subtipos ou subcontextos, que serve para acotar melhor a classe do objeto

Para cada subcontexto, **PODE** existir um elemento dentro do elemento `referencia`, para alterar o campo de referência. Se não existe, o campo de referência do subcontexto será o mesmo que o do contexto. O nome do elemento (a `key` do array) será gravada no campo `tbl_tdocs.referencia` para facilitar a filtragem e o tipo de objeto (diferenciar entre NF, extrato de OS e foto do produto da OS, por exemplo).

#### Alterando a validação da referência
A validação do campo e ID, usando o método `checkDocumentId()` é realizado usando o campo informado na `referencia` do contexto ou subcontexto, mas podem existir (e existem) casos onde se deve alterar o campo de referência da fábrica, ou recorrer a tabelas extras, por meio de `JOIN`. Nestes casos, será necessário alterar o método para acrescentar essas regras. Existem exemplos de como fazer essas alterações já no método.

Talvez, se isso for algo recorrente, a lógica seja mudada e as informações para as "exceções" passem também à matriz de configuração dos contextos em uma versão futura.

#### `TDocs::$attachListInfo`
Contém um vetor bidimensional de dados, com o Id do banco `tdocs` como índice principal, e os elementos básicos do arquivo, conforme descrito no método [`getDocumentsByRef()`](#getDocRef).

O fato de ter o índice com o ID da tabela é para poder obter facilmente esses IDs para poder trabalhar com eles. Por exemplo, para obter o primeiro ID basta usar `$id = key($tDocs->attachListInfo)`, e para obter todos eles, basta `$arrIDs = array_keys($tDocs->attachListInfo)`.

Atributo/Propriedade | Descrição
---|---
 `TDocs::$error` | Variável que contém a última mensagem de erro. é conveniente ler o conteúdo a cada passo importante, especialmente se algum método retornou `FALSE`, para saber se houve algum problema.
 `TDocs::$sentData` | Quando é enviado um arquivo ao TDocs, este atributo contém os dados do arquivo (o que teria um $\_FILES[]), mais o TDocsID.
 `TDocs::$sentFileName` | Quando é enviado um arquivo, este atributo contém o nome "original" do arquivo que foi enviado.
 `TDocs::$sqlError` | Se houve algum erro no acesso ao banco, este atributo deve conter (se é possível) o conteúdo do `pg_last_error()`, o que ajudará o desenvolvedor a localizar a origem do problema.
 `TDocs::$url` | URL completa do primeiro anexo encontrado pelos métodos `getDocuemntsById` e `getDocumentsByName`.
 `TDocs::$hasAttachment` | Retorna `TRUE` ou `FALSE` conforme o conteúdo de `attachListInfo`, para informar se existem ou não documentos. Útil para saber se um `get` localizou algum documento (ver [exemplo do método](#getDocRef) `getDocumentsByRef()`).
 `TDocs::$attachmentCount` | Retorno o número de elementos no array `attachListInfo`. Outros nomes para esta propriedade: `temAnexo`, `temAnexos`, `attachCount`.


##   Histórico
Alterações realizadas na Classe ***TDocs***:

| Data | Autor | Log
:---:|---|---
2017-08-22|MLG|Método `getDocumentHistory()` agora pode receber um "limite de registros do histórico" como segundo parámetro.<br>Atualizada documentação para refletir esta alteração, assim como a nova funcionalidade:<br> `setRDocumentReference()`, agora pode receber um _hash_ para substituir um anexo específico com base nele.
2016-11-25|MLG|Adicionando método de exclusão definitiva do arquivo no TDocs/S3. Quando usar este método, o arquivo será **excluído** no repositório TDocs do S3, ***não pode ser revertido***!<br>Novas funcionalidades à pedido do Túlio:<br>- recuperar a consulta SQL que é feita no banco para pesquisa<br>- recuperar o campo (`tabela.coluna`) de referência do contexto
206-11-08|MLG|Resolve problema ao setar o contexto durante a inicialização do objeto.
2016-09-28|MLG|- salva informação extra se estiver no DEVEL<br>- grava o subcontexto no campo referência e não o nome da tabela<br>- método para excluir um objeto do TDocs (API)<br>- método para 'limpar' atributos de contexto<br>- documentação atualizada<br>- adicionado subtipo `osserie` para o `HD 3032756`<br>- melhorada a detecção do contexto e subcontexto
2016-09-21|MLG|Tela de comunicado envia o TDocsID como parâmetro do método de exclusão, que espera a RefID (ID da tabela de referência). Alterado método para aceitar RefID e TDocsID.
2016-09-03|MLG|Alterado método que recupera o IP e outras infromações do usuário, adapando-o ao LoadBalance e as `COOKIES`.
2016-08-31|MLG|HD 3027304, 3028270<br>Erros nas funções temNF, dirNF, anexaNFDevolucao, e na própria tDocs, chamando um método inexistente.
2016-08-26|MLG|Última rodada de correções antes da Homologação.<br>Correções para AnefaNF(), detectando OS SEDEX.
2016-08-25|MLG|Atualização das classes da API2 para manipular strings UTF8/Latin-1 e outras pequenas correções.
2016-08-24|MLG|Classes `API2`, `AnexaSSS`, TcComm<br>Correção na detecção de strings UTF-8.

### Outros
**simpleREST**
- Adicionada validação em caso de `timeout` na conexão com o servidor,
  devolvendo um erro **`504` Gateway timeout**.
- Parametrização do _path_ para o arquivo de _log_.

**TDocs**
- corrigido problema com a ordem dos arquivos, estava com `DESC`, sendo
  que não precisava.
- alteração do tipo de dados no JSON de observações
- adicionados mais subtipos
- JSON: tamanho do arquivo adicionado
- refatoração do upload em dois novos métodos
  - `sendFile($arquivo)` e
  - `vincularAnexo()`
- novos métodos (ponto anterior) permitem subir e vincular separadamente
  um documento
- refatoração da consulta na tabela: todos os métodos getDocumentsBy* usam um
  único  método.
- documentação atualizada (`TDocs.md`)

### 2016-08-19
`mlg_funciones.php`
: retiradas linhas de debug obsoletas

`anexaNF_S3`
: Corrigido problema de ordenação dos anexos, e também do nome dos anexos quando o mesmo ID tem vários (erro causado parcialmente pelo problema de ordenação).
   
### 2016-08-19
`admin/os_press.php`
: Correção do `javascript` que exclui arquivos: o método `jQuery.live()`
: não está disponível na tela, mudei para `jQuery.on()`, juntamente com
: uma melhoria no PHP.

`admin/gera_zip_vista_explodida.php`
: melhoria: agora pode funcionar no devel, apenas mudando o diretório de
: destino dos temporários.

### 2016-08-15
Anexos de NF Devolução (LGR Britânia) refatorado para usar o TDocs. Ainda falta testar.
   
### 2016-08-11
Afinando a detecção do tipo de anexo
`anexaNF_SSS`
: versão anterior das funções "anexaNF", apenas para TDocs e api2:
: documentação das novas classes.
: histórico, poderá ser excluído em uma próxima revisão.

- Refatoração do AnexaNF_S3 para usar o TDocs.
- Correções na Abstract API2 (nome de variável)
- SimpleREST permite enviar arquivos usando multipart/form-data
- Documentação da TDocs
- Documentação da TcComm atualizada (anexos, blacklist)
- Documentação da SimpleREST atualizada (multipart/form-data)

`os_press`: muda include por include_once
`comunicado_produto`: retirado código repetido

### 2016-08-01
`AnexaS3`
: Renomeada para AnexaSSS e refatorada para extender a AnexaSSS, usando a TDocs para salvar os arquivos, e recuperar os do TDocs, ou do S3 usando o método existente antes da TDocs. Em tese, deve ser transparente ao resto do sistema, facilitando a implementação do TDocs como sistema de armazenamento de arquivos.
   
  *AmazonTC* também foi renomeada e extendida, mas por enquanto não foi refatorada até ter uma implementação que atenda ao uso no sistema.
   
**API2**
Criada nova classe abstrata API2. Sua função é fornecer propriedades e métodos comuns às classes que possam interagir com a API2.

**TcComm**
- Refatorada para extender a class API2.
- Novos métodos para interagir com a BlackList da API Communicator.

**TDocs**
- Criada nova classe para interagir com a API2, o serviõ de armazenamento de arquivos TDocs.
   Essencialmente, armazena qualquer tipo de arquivo no S3, indexado por um hash próprio. No PósVenda, gerencia as informações necessárias para a recuperação do arquivo, usando a tbl_tdocs.
   
**mlg_funciones**: Correções na função `getAttachLink`, para interpretar URLs com query string e sem.
   
**SimpleREST**: refatoração parcial, novos métodos privados auxiliares para melhorar a legibilidade e manutenção.

**sql_cmd**: correção da validação dos nomes dos campos para INSERT e UPDATE.

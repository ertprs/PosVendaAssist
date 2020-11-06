# SIMPLE REST CLIENT

Esta classe tem sido desenvolvida para facilitar chamadas "simples" para diferentes
**APIs**. O objetivo é criar requisições da forma mais rápida e clara possível, usando o
mínimo de métodos:

```
    $correios = new simpleREST('https://api.correios.com.br');

    $correios->addHeader(array(
            'Token' => sha1($senhaCorreios),
            'Client'=> 'Telecontrol'
         ))
        ->addParam(
            array(
                'cep' => $revenda_cep,
                'estado' => 'SP'
            )
        )->send();

        return ($correios->responseCode == 200) ?
            $correios->getResponse() :
            $correios->errorMessage();
```

Ainda, esse retorno poderia ser simplificado usando o método mágico:

    return $correios;

Pois o método `__toString()` seleciona o `responseText` ou o `errorMessage`, dependendo
do `responseCode`.

> Pena que o _WebService_ dos **Correios** não seja REST...

### Inicialização (constructor)
A classe permite criar um objeto já informando a URL e o método a ser enviado.  Assim,
para criar um novo objeto REST Client, seria como no exemplo acima:

    $client = new simpleREST(<URL do WebService>, 'POST');

Ambos parâmetros são opcionais, e tanto a URL quanto o método podem ser informados a
qualquer momento, antes do envio da requisição. Se o método não é informado, o padrão
é `GET`.  A classe oferece diversos recursos para adicionar HEADers, FORMs e BODY, e um método
para ler o retorno.

### CLIENT

#### Opções e configuração
O elemento principal é, obviamente, a URL do serviço, e ela PODE ser informada ao
instanciar o objeto, ou usando o método `setUrl(string $url)`. O segundo parâmetro (também
opcional, com valor _default_ `GET`) é o método a ser enviado.

##### addHeader(array $headers)
Detecta o tipo de array (vetor ou hash) e adiciona as informações segundo o tipo:

Se for um vetor simples (array com índices numéricos consecutivos, começando por `0`) irá
inserir cada valor como um header. Headers já inseridos **serão sobrescritos** pelos novos
**sem perguntar** ou informar.

Se for um _hash_, então irá compôr cada header com a key e o value. Se o value for `null`,
irá excluir o _header_.

##### removeHeader(array $headers)
O parâmetro é opcional. Se não é informado, este método **exclui todos os headers**.

Se é informado um array, o mesmo deve conter os nomes dos headers a serem excluídos.

##### setMethod(string $method)
O método pode ser informado de forma independente com este método, ou como parâmetro do
método `send()`.

    $client->setMethod('GET');

##### addParam(String|array $parameters)
Parâmetros a serem adicionados à requisição. Eles são armazenados como array até o momento em que
a requisição é preparada e enviada, portanto é possível alterar os parâmetros enviando a _key_ para
sobrescrever o valor. Se for `null` a chave será excluída (usa `array_merge()` e `array_filter('empty')`
para compôr o array).

##### addFile(String $filename, Optional String $param_name)
Permite adicionar o _path_ de um arquivo aos parâmetros (de fato, usa o método `addParam()` para
adicionar os arquivos). No momento do envio, os arquivos são lidos e adicionados ao corpo da
mensagem, e todos os parâmetros são enviados juntos como `multipart/form-data`.

Quando um arquivo é adicionado, o _header_ `Content-Type` é alterado ou adicionado com o
valor adequado (`multipart/form-data`). O método deverá ser `POST` ou `PUT`, e deve ser
configurado manualmente. Isto pode mudar em uma próxima versão para deixar `POST` como
padrão e permitir usar o método `PUT`.

##### setUrl(string $url)
Normalmente a URL do serviço é informada no construtor. Porém, uma mesma rotina pode
realizar várias requisições (autenticação + consulta + PUT, por exemplo), e este método
permite alterar a URL e assim, "reciclar" a variável para uma nova requisição.

<span style="color:darkred">ATENÇÃO</span>: Este método **limpa todos os valores** (parâmetros,
headers, body) do objeto, para evitar enviar dados esquecidos e que possam alterar o
comportamento do serviço.

##### setBody(mixed $body)
Estabelece o conteúdo do corpo da requisição. É possível usar um array como corpo, o que
facilita o envio de JSON ou formulários, conforme explicado no método `send()`.

##### addFile(string $fileName[, string $paramName])
Adiciona um parâmetro `$paramName` (ou 'arquivo', se não informado) cujo valor é o **nome do
arquivo** a ser enviado. Este método altera o **HEADER** `Content-Type` para "multipart/form-data".
Quando for processado o POST, é criado um novo `body`, no formato padrão `multipart`, para ser
enviado ao servidor.

Para envio de arquivos usando **JSON** como envelope, simplesmente criar o JSON e usar
o método `setBody()`.

##### send(string $url, string $method, mixed $params, mixed $body)
Prepara e executa uma requisição HTTP conforme os parâmetros inseridos; opcionalmente é
possível informar neste último momento a url, o método a ser usado e uma string, array ou
arquivo(s) (TODO) a ser(em) enviado(s).

Processamento do `body`:
- String é adicionada e enviada sem processar ou validar.
- Array é convertido para JSON se existe o header 'Content-type: application/json'.
  Se não, ele é convertido para `x-www-form-urlencoded` (parâmetros).
- (TO-DO) Se o array contém **apenas** uma chave `"files"`, os arquivos serão enviados
  usando `form-data`.

Assim, para enviar um arquivo via PUT, poderia ser assim:

``` php
    $uploader = new simpleREST();
    $uploader->send(
        'http://image.editor.com/upload/files',
        'PUT', null,
        array('files'=>array(
            '/tmp/apache.log',
            '/tmp/php.log'
        ))
    );
```

##### toString()
Retorna o corpo do retorno da requisição, se houver, ou a mensagem de erro que tiver
durante o processamento (servidor não encontrado ou algum outro não relacionado
diretamente com a requisição).

#### Propriedades
As seguintes propriedades estão disponíveis para leitura:

- `URL` (para o modo CLIENT)
- `response`  (reposta do servidor, um array com as chaves statusCode, statusMsg e body)
- `statusCode`
- `reqHeaders`
- `statusMsg`
- `method`
- `error`  TRUE ou FALSE, conforme **se foi possível** realizar a requisição

# Nova interface para o Auditor _log_
Nova classe de acesso à **API** ***AuditorLog***, que registra um histórico de alterações
entre dois grupos de valores.

A motivação principal para a criação desta _class_ é a simplificação do uso do
**AuditorLog**, que hoje exige consultar o banco, salvar o resultado _e a consulta_,
consultar novamente com _a mesma_ consulta, verificar se houve alterações e enviar para a
API se efetivamente algum registro foi alterado.

Foram acrescentadas novas funcionalidades à **classe** ***AbstractAPI2*** para conversão
de codificações entre UTF-8 e Latin-1, e mais _encodings_ aceitos pelo **PHP**. Estas
classes facilitam a conversão dos dados do banco (Latin-1) para enviar à API2 (UTF-8),
e também para mostrar os dados em tela, pois o sistema de PósVenda trabalha com
o _encoding_ `ISO-8859-1`.

Este envio recebe hoje pelo menos **cinco** parâmetros, sendo que a definição da função
está com erro mas, como sempre precisa do último parâmetro, não estava acusando problema.

Também, a consulta do _log_ pode ser realizada usando esta _class_.

## Classe `AuditorLog`
Esta nova classe utiliza a `simpleREST` e a `AbstractAPI2`, para se comunicar com a API2.

O arquivo está localizado no diretório `class`, o mesmo diretório onde estão localizadas as dependências. Outras dependências são: conexão ao banco, que obtém e valida desde o construtor, e o arquivo de biblioteca de funções `helpdesk/mlg_funciones.php` (mas não precisa incluir, pois ele já é incluso desde o arquivo da classe `simpleREST`).

### O que oferece esta classe?

Com esta classe temos à disposição **dois** métodos para criar os dados a serem logados, um para enviar esses dados à API2 e um para recuperar esses dados. Além dos métodos do objeto, temos um método estático para comparar o _antes_ e o _depois_ e determinar se houve alteração e, também, recuperar os campos que são diferentes.

### Inicialização (`__construct`)*
Durante a inicialização do novo objeto, a conexão global ao banco (variável global `$con`) é copiada para o objeto (é um _resource_ e, portanto, é como se fosse passada a conexão por referência), e é determinado o endereço IP do usuário, conforme o exposto pelo servidor e o PHP na _superglobal_ `$_SERVER`. Esta informação faz parte dos dados a serem salvos no _log_.

* Um caso especial é quando vai ser registrada **uma inserção**, pois neste caso não existe um registro anterior a ser lido e deve-se informar no ato de instanciar o objeto:

```php
	$Aud = new AuditorLog('insert');
```

Neste caso, não é necessário invocar o método `retornaDadosTabela()` ou `retornaDadosSelect()` 
antes da alteração pois, não há nada a ler e armazenar.

### Métodos para criar o _log_
Existem dois métodos para obter os dados a serem gravados no _Auditor_:

#### `retornaDadosTabela($tabela = '', $where = null, $campos_ignorar = '')`
Com este método podemos obter os dados atuais de um registro ou registros de uma tabela específica:

```php
	$Aud = new AuditorLog;
	$Aud->retornaDadosTabela('tbl_admin', array('admin'=>1000, 'fabrica'=>$login_fabrica));
```

Armazena todos os campos do registro apontado pela cláusula `WHERE` (segundo parâmetro) no objeto como _"antes"_.

O parâmetro opcional `$ignorar` aceita um _array_ ou _string_ com os campos do registro a serem ignorados. Isto é necessário às vezes quando uma tabela tem um campo que é alterado, mesmo que os dados gravados sejam iguais, como um `data_alteracao` preenchido por uma _trigger_. Estes campos serão ignorados para a comparação.

```php
	$Aud->retornaDadosTabela(
		'tbl_admin',
		array(
			'admin'=>1000,
			'fabrica'=>$login_fabrica
		), 'data_alteracao'
	);
	$Aud->retornaDadosTabela(
		'tbl_admin',
		array('admin'=>1000, 'fabrica'=>$login_fabrica),
		array('data_alteracao','data_input')
	);
```

Após realizar as operações solicitadas pelo usuário, se chamarmos o **mesmo** método do mesmo **objeto** (no caso do exemplo, `$Aud`), repete-se a consulta anterior, sem necessidade de passar novamente os parâmetros:

```php
	$Aud->retornaDadosTabela();
```

No caso de uma operação de **inserção**, como não foi informada anteriormente a tabela ou a consulta SQL a ser usada, deve-se informar neste momento:

```php
	$Aud->retornaDadosTabela('tbl_admin', array('admin'=>$novo_admin, 'fabrica'=>$login_fabrica));
```

Armazena os dados do registro apontado pelo `WHERE`, mas agora como _"depois"_. Com isso temos as informações básicas para gerar o _log_ de alteração.

#### Operações de _log_ mais complexas: `retornaDadosSelect($sql)`
Este método dá opção ao desenvolvedor de usar uma consulta específica para gerar o _log_, por exemplo, quando os requisitos do _log_ fazem necessário o uso de mais de uma tabela, ou restringir o _log_ à apenas uns campos.

```php
	$Aud->retornaDadosSelect(
		'SELECT *
		   FROM tbl_posto_fabrica
		   JOIN tbl_posto USING(posto)
		  WHERE tbl_posto_fabrica.fabrica = $login_fabrica
		    AND posto = $posto'
	);
```
**IMPORTANTE:** Quando utilizar este método, colocar como primeira coluna a chave primária da tabela ou um conjunto de campos que a tornem única (chave composta) e o mesmo deve ser **NUMÉRICO**

#### Enviando o _log_: `enviarLog()`
Já com os dados "em mãos", é hora de enviar todos os dados para o _Auditor_.

```php
	$Aud->enviarLog('update', 'tbl_admin', "$fabrica*$admin");
```

E esse é todo o processo para gravar uma atualização no ***AuditorLog***. Dados obrigatórios, como ambiente (_posto_ ou _admin_), programa que realizou a alteração ou o admin/posto responsável são informações gerais do sistema: programa e IP estão no `$_SERVER`, e os dados de fabricante e usuário nos dados de login (`$_COOKIE` e as variáveis globais que o PósVenda já utiliza para identificar o usuário).

#### Consultar diferenças geradas

Ao completar o envio do log é possível recuperar as diferenças geradas em um array.

	$Aud->diff;

#### Envio em pacotes de _log_
> 2017-11-23

Com esta funcionalidade, é possível agrupar logs para enviá-los de uma só vez, ou em grupos de uma determinada quantidade. A composição do _log_ em si não muda. Apenas é necessário acrescentar **duas** linhas a um _log_ já existente para que ele seja enviado em pacotes.

##### Inicializar o modo _bulk_
Após a inicialização do objeto, ele pode ser habilitado para trabalhar em modo _bulk_ informado-o de qual seria o tamanho (em registros) do pacote de dados:

```php
    $aud->setMultiple(100);
```
##### Enviando pacotes
Com essa chamada informamos que queremos trabalhar em modo _bulk_, com pacotes de 100 registros por cada requisição. O resto do código continua igual. Se quisermos que o mesmo objeto volte a enviar os _log_ um a um, é só chamar de novo o método com valor `1` ou `0`.

Ao finalizar o processo que está sendo _logado_, é provável que alguns registros estejam aguardando a serem enviados. Por isso, após a última iteração ou que seja, deve ser feito o envio do restante:

```php
    $aud->enviarLogMultiplo();
```

Seria o equivalente a um `flush()`, enviando os registros que ainda estiverem "na fila".

#### Recuperar dados de _log_: `getLog(mixed $tabela, string $PrimaryKey[,int $limit])`

Para consultar o histórico de alterações de um registro ou grupo de registros registrados, o método `AuditorLog::getLog()` consulta a API com os dados fornecidos e retorna os dados como um _array_ associativo, semelhante ao fornecido pela API, porém com algumas diferenças:

- Dados já vem em Latin-1 para mostrar em tela
- Mesmo que só vier um registro, ele está dentro de um primeiro nível, para facilitar o
  processamento

```php
	$log = Aud->getLog('tbl_admin', "$fabrica@$admin", 20);
```

O último parâmetro é a quantidade máxima de registros solicitada, e é opcional: se não é informado, a API _Auditor_ retorna **o último registro gravado**.

#### Comparação `VerificaIgualdade($antes, $depois, [$ignorar = null])`
Em tese, um registro de _log_ grava apenas alterações, ou seja, se o "antes" e o "depois" tiverem alguma diferença.

Este método, que é usado antes do envio para determinar a necessidade do mesmo, **não considera os espaços extra** como alteração. Isso pode ser alterado modificando o atributo estático `trimData`, usando o método `AuditorLog::trimData(bool)`, cujo valor inicial é `true`.

O parâmetro opcional `$ignorar` aceita um _array_ com os campos do registro a serem ignorados. Isto é necessário às vezes quando uma tabela tem um campo que é alterado, mesmo que os dados gravados sejam iguais, como um `data_alteracao` preenchido por uma _trigger_. Estes campos serão ignorados para a comparação.

## Arquivo `relatorio_log_alteracao_new.php`
A princípio cogitou-se refatorar o `relatorio_log_alteracao.php` (de fato, isso foi feito), mas com a necessidade de liberar o código, resolveu-se criar uma nova funcionalidade e não modificar as já existentes. Este programa foi criado pelos seguintes motivos:

- a visualização do _log_ fica visualmente melhor usando um _popup_ (como o _ShadowBox_)
- as regras para a adaptação de valores segundo o conteúdo era muito confuso
- acrescentar _logs_ novo implicaria uma confusão e complexidade ainda maiores
- o novo código será mais adaptável, e seu comportamento pode ser adaptado alterando
  apenas um _array_

### Parâmetros e acesso
Esta tela de relatório de _log_ não tem mais cabeçalho e nem rodapé, pois a ideia é mostrar o _log_ de alterações em um _popup_.

Os parâmetros obrigatórios ficaram igual que a tela completa, isso facilita a adaptação. Além desses parâmetros agora existe o `title`, que permite estabelecer um título para a tela do _log_ que é mostrada acima da tabela.

Um exemplo de invocação do _log_ seria assim:

```javascript
	$(".show-log").click(function() {
		var url = 'relatorio_log_alteracao_new.php?' +
			'parametro=tbl_' + $(this).data('object') +
			'&id=' + $(this).data('value');

		if ($(this).data('title'))
			url += "&titulo=" + $(this).data('title');

		Shadowbox.init();

		Shadowbox.open({
			content: url,
			player: "iframe",
			height: 600,
			width: 800
		});
	});
```

```html
	<a class="show-log" href="#"
     data-object="pedido"
      data-title="Log do Pedido"
      data-value="10*123456">Ver Log de Alteração</a>
```
### Retornar os dados do Log de uma tela específica
Quando existir a necessidade de listar os dados de uma tabela, que já esta sendo registrada do Auditor e os dados que devem ser listados são apenas referentes as alterações na tela, coloque o seguinte parâmetro:

```html
 data-program='NOME DA TELA'
```

Logo ficaria assim:

```html
	<a class="show-log" href="#"
     data-object="pedido"
      data-title="Log do Pedido"
      data-value="10*123456"
    data-program='NOME DA TELA'>Ver Log de Alteração</a>
```

### Dados de teste
Foi feito um esforço para ignorar os dados gravados desde os servidores de teste e, se bem estas informações não tem sido filtradas com total sucesso, a lógica será aprimorada em próximas iterações, se for necessário.

Na classe `AuditorLog` é usada a funcionalidade de "ambientes" da API2, foi habilitado o ambiente de "teste" e agora a classe irá gravar os dados de teste no _environment_ **DEVELOPMENT**, e assim, quando for executado nos servidores de produção, esses dados não serão mais acessados, e vice-versa.

### Variáveis de configuração
O programa foi projetado para que seu comportamento seja controlado por algumas variáveis, que contêm o _template_ HTML para usar com o _bootstrap_, e um _array_ de configuração que permite manipular alguns valores a serem mostrados, como por exemplo mostrar a referência do produto no lugar do seu `ID` do banco, que seria um dado inútil; ou unificar _logs_ relacionados. E, em último extremo, indicar um arquivo que seria incluso (`include()`) durante o processamento de cada registro. Ainda não houve a necessidade, mas a implementação já existe.

#### `$LOG_template`
Esta variável é um _array_ com três chaves:

ext
: HTML com o "envelope exterior" de cada registro (**antes** ou **depois**)

int
: HTML com o envelope de cada campo do registro, e será envolvido pelo anterior

CSS
: Código CSS extra que seja necessário para o uso do HTML das outras chaves

A rotina do programa  que prepara o conteúdo utiliza a função `sprintf()` para inserir os dados no _template_. Para o HTML `ext`, são passados dois parâmetros: o HTML do conteúdo do registro (todos os campos a serem mostrados), e uma classe CSS `alert` (que é do Bootstrap) para quando a informação é do teste.

O _template_ `int` irá receber dois valores: o nome do campo e seu valor.

Cada registro irá dentro de uma _tag_ `<td>` da tabela com os resultados.

#### `$LOG_config`
Este _array_ é o responsável de mudar o comportamento do programa dependendo do _log_ a ser mostrado, que vem determinado pelo nome da tabela, um dos parâmetros do `$_GET`.
Cada elemento da matriz tem como chave a tabela, e pode conter de 1 a três elementos no segundo nível:

##### ignorar
é um _array_ simples que relaciona os campos de cada registro que devem ser ignorados para **comparar registros** na hora de determinar se o **antes** e **depois** são iguais.

##### join
é um _array_ simples que contém a lista de outras tabelas, com o mesmo `id`, a serem incorporados ao _log_.

##### campos_chave
é um _array_ simples que contém os campos que deseja sempre mostrar, só não mostra se o campo não existir na tabela.

##### sql
Trata-se de um _array_ associativo que **DEVE** conter duas chaves: `sql` e `filtro`. Cada elemento é o nome de um campo do _log_, cujo valor será substituído pelo localizado no banco de dados usando uma consulta SQL.

Na primeira chave, `sql`, está a consulta ao banco que será usada para localizar o valor que irá substituir o original do registro do _log_.

Vamos supor que queremos mostrar a referência de uma peça no _log_ do pedido de peças, a descrição do status do pedido e a descrição da condição de pagamento:

```php
    $LOG_config = array(
        'tbl_pedido_item' => array(
	    'sql' => array(
	        'peca' => array( // peca é o ID, que queremos substituir
	            'sql' => 'SELECT referencia FROM tbl_peca WHERE fabrica = $1 AND peca = $2'
	        ),
	        'filtro' => array('login_fabrica', 'val')
	    ),
	    'status_pedido' => array(
	        'sql' => 'SELECT descricao FROM tbl_status_pedido WHERE status_pedido = $1',
	        'filtro' => array('val')
	    ),
	    'condicao' => array(
	        'sql' => 'SELECT descricao FROM tbl_condicao WHERE fabrica = $1 condicao = $2',
	        'filtro' => array('login_fabrica', 'val')
	    ),
	)
    );
```

O filtro é uma lista **de nomes de variáveis** que irão substituir, na ordem, os respectivos _placeholders_ da consulta SQL.

O programa vai gerar um plano de execução de cada consulta, e quando encontrar um campo com o nome "peca", "status_pedido" ou "condicao", irá executar essa consulta, passando o valor do _log_ (variável `$val`), e substituindo-o pelo resultado da consulta.

> NOTA:
> Cabe ao desenvolvedor escrever uma consulta que traga exatamente o valor necessário. A rotina irá recuperar o primeiro campo do primeiro registro da consulta.


&copy; 2017 Telecontrol

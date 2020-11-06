# Classe JSON

Objetivo: Facilitar a manipulação de objetos JSON dentro do PHP, usando uma classe que envolva o JSON e o estenda com métodos e acesso aos seus elementos.

## Classe Json
Incluir e instanciar uma variável do tipo `Json`, cujo construtor aceita um valor do tipo `Array`, `SimpleObject` ou um objeto serializado em **JSON**.

O construtor tem um segundo parâmetro opcional, `boolean`, que rege o comportamento do objeto caso haja algum erro: se este parâmetro é `TRUE` (valor padrão), em caso de erro irá jogar uma `Exception`; se for `FALSE`, uma mensagem de erro será colocada no atributo `Json::$last_error` e o método irá retornar `false`.

Uma vez instanciado o objeto, é possível acessar qualquer valor **do primeiro nível** do JSON usando a chave como atributo:

	$a = '{"estados":["AC","SP","RJ"],"telefone":"97346982378"}';
	$jo = new Json($a);

	echo $jo->telefone;  // 97346982378

Também aceita um `Array` como valor inicial:

	$jo = new Json(
		array(
			'estados' => array('AC', 'SP', 'RJ'),
			'telefone' => '97346982378'
		)
	);
	echo $jo; // {"estados":["AC","SP","RJ"],"telefone":"97346982378"}

Para acessar (leitura e **escrita**) níveis inferiores, a classe tem liberada a propriedade `data`, que contém uma representação em `Array` do **JSON**:

	$jo->estados[1];   // "SP"
	$jo->data['estados'][2] = 'RS';  // Agora {estados: [AC,SP,RS]}

Este é um acesso de "baixo nível", ou seja, não existe validação programática dos valores, é **responsabilidade do programador** validar as informações.

A classe tenta verificar sempre que os valores `string` sejam **UTF-8**, conforme as restrições do padrão JSON à respeito.


##  Métodos
Além do acesso pelos atributos e a propriedade `data`, a classe fornece alguns métodos de manipulação do JSON para adicionar, alterar e excluir, assim como converter.

# Magic Methods
Aproveitando a capacidade do PHP com os métodos mágicos, são usados os seguintes:

### __toString()
Sempre que for usar o objeto num contexto onde é esperada uma `string`, o objeto irá retornar a serialização dos dados em formato JSON:

	echo $jo;   // {"estados":["AC","SP","RS"],"telefone":"97346982378"}

Inclusive, é possível usar o objeto dentro de uma String, desde que seja com aspas duplas, como se fosse uma variável escalar de tipo `string` normal:

	$sql = "UPDATE tabela
			   SET campo_json = '$jo'
			 WHERE id = 1000"

Resulta em:

	$sql = "UPDATE tabela
			   SET campo_json = '{"estados":["AC","SP","RJ"],"telefone":"97346982378"}'
			 WHERE id = 1000"

Simplificando o processo de atualizar um campo no banco de dados, por exemplo.

### __set()
É o método mágico que permite ações como `$jo->novoCampo = "teste"` para adicionar um novo elemento, ou substituir um valor.

Se o valor do novo elemento é um JSON, ele será inserido como parte do objeto, e não como `string`:

	$jo->contato = '{"cidade":"Marilia","estado":"SP","telefone":"97346982378"}';

Irá adicionar um novo atributo `contato` com os valores informados:

	var_export($jo->contato);

Resultado:

	array (
		'cidade' => 'Marilia',
		'estado' => 'SP',
		'telefone' => '97346982378',
	);

### `__get()`
Para ler um valor de primeiro nível, este é o método que interpreta a solicitação.

### `__call()`
Para criar de maneira simples _aliases_ para os outros métodos.

## Métodos para manipulação
A seguir, os métodos disponíveis para trabalhar com um JSON:

### `set(array|JSON)`
> Aliases: `add`, `push`, `insert`, `append`

Aceita um array ou um JSON, o resultado é uma mistura (`merge`) dos valores atuais com os novos. Se o parâmetro é do tipo `string` mas não é um JSON válido, retorna uma Exceção (se estiverem habilitadas).

	$jo->add(array('test'=>'valor adicionado'));
	$jo->push('{"jsonStr":"Valor inserido desde um outro JSON (é como fazer um 'merge' de JSONs)"}')

### `removeItem(array|string, ..)`
> Aliases: `unset`, `delete`, `del`, `rm`, `remove`

Exclui o índice informado, ou os índices, caso o parâmetro seja um array com os nomes dos elementos a serem excluídos.

	$jo->unset('contato','maisum');

Exclui os índices, se existirem. Se não são localizados, não devolve erro. [Pode ser interessante implementar algum tipo de aviso, como um atributo 'Warning'...]

### `toArray()`
Retorna o conteúdo do JSON como um `array`. Seria o mesmo que `$jo->data`. Para quem gosta mais de chamar métodos.

### `toObject()`
Retorna Json::$data como objeto. Seria como usar `json_decode($str, false)`.

### `throwErrors(bool)`
> Aliases: `throw_errors`, `exceptions`

Permite alterar o comportamento do objeto, fazendo com que o mesmo lance exceções quando houver algum erro (normalmente de interpretação do JSON), ou apenas informe do erro no atributo `Json::$error`.

### `shift()`
Armazena no atributo `Json::$lastValue` o valor do primeiro elemento do JSON e exclui esse elemento. Também armazena o nome do índice no atributo `Json::$lastIndex`.

### `pop()`
Armazena no atributo `Json::$lastValue` o valor do último elemento do JSON e exclui esse elemento. Também armazena o nome do índice no atributo `Json::$lastIndex`.

### Static `Json::isJson(string) | Boolean`
Método estático que retorna `TRUE` se o texto do argumento é um JSON. Deve ser um JSON aceito pelo PHP, o que implica que deve estar em **UTF-8**.

## Atributos da Classe
Os seguintes atributos (além dos dinâmicos, que permitem o acesso aos elementos de primeiro nível do JSON) são disponibilizados pela classe:

Atributo | valor | Observações
---|---|---
last_error | Texto | Última mensagem de erro
lastIndex  | Texto | Nome do último índice do JSON excluído
lastValue  | Texto | Conteúdo do último índice do JSON excluído
encoding   | Texto | Codificação dos dados quando é solicitado o valor de alguma chave.  Quando o retornado é JSON, ele é semre **UTF-8**.



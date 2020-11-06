/*	Galer�a de funciones varias, sobre todo para que JavaScript sea algo m�s
	parecido al VisualBasic ('InStr(,n, str, char), p.e., en vez de un m�todo
	de la variable string...)
*/

function InStr(n, s1, s2){
	// Devuelve la posici�n de la primera ocurrencia de s2 en s1
	// Si se especifica n, se empezar� a comprobar desde esa posici�n
	// Sino se especifica, los dos par�metros ser�n las cadenas
	var numargs=InStr.arguments.length;	
	if(numargs<3)
		return n.indexOf(s1)+1;
	else
		return s1.indexOf(s2, n)+1;
}

function iif(condition, TrueVal, FalseVal) {
	//	Fun��o iif (boolean condi��o, var valor1, var valor2)
	//	Devolve o valor 1 se a condi��o for 'true' e o valor2 se a condi��o for 'false'

	return (condition) ? TrueVal : FalseVal ;
}
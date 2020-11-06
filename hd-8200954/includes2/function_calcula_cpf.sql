DROP FUNCTION CALCULA_CPF(text);
CREATE FUNCTION CALCULA_CPF(text)  RETURNS text AS '
DECLARE
	t_cpf		text;
	t_acum		integer;
	t_conta		integer;
	msg		text;
BEGIN
	t_cpf		:= so_numeros(trim($1));
	t_acum		:= 0;
	t_conta		:= 1;
	msg		:= '' '';
	
	IF LENGTH (t_cpf) < 11 THEN
		msg := ''CPF do CLIENTE deve ser maior ou igual a 11 caracteres'';
		raise exception ''%'' , msg;
	ELSE
		t_cpf := so_numeros(t_cpf);
		IF LENGTH (t_cpf) > 11 THEN
			msg := ''CPF do CLIENTE contém muitos caracteres'';
			raise exception ''%'' , msg;
		END IF;
	END IF;
	
	/*------------- Primeiro Digito -----------*/
	t_acum := 0;
	t_acum := substr(t_cpf,01,1)::integer * 1;
	t_acum := substr(t_cpf,02,1)::integer * 2 + t_acum;
	t_acum := substr(t_cpf,03,1)::integer * 3 + t_acum;
	t_acum := substr(t_cpf,04,1)::integer * 4 + t_acum;
	t_acum := substr(t_cpf,05,1)::integer * 5 + t_acum;
	t_acum := substr(t_cpf,06,1)::integer * 6 + t_acum;
	t_acum := substr(t_cpf,07,1)::integer * 7 + t_acum;
	t_acum := substr(t_cpf,08,1)::integer * 8 + t_acum;
	t_acum := substr(t_cpf,09,1)::integer * 9 + t_acum;
	
	t_conta := MOD (t_acum,11);
	t_conta := substr(''00'' || trim(t_conta),2)::integer;
	
	IF LENGTH (trim(t_conta)::varchar) = 2 THEN
		t_conta := substr(t_conta,0,1)::integer;
	ELSE
		t_conta := substr(t_conta,1)::integer;
	END IF;
	
	
	IF t_conta != substr(t_cpf,10,1)::integer THEN
		msg := ''CPF '' || t_cpf || '' invalido'';
		raise exception ''%'' , msg;
	END IF;
	
	/*------------- Segundo Digito -----------*/
	t_acum = 0;
	t_acum = substr(t_cpf,01,1)::integer * 0;
	t_acum = substr(t_cpf,02,1)::integer * 1 + t_acum;
	t_acum = substr(t_cpf,03,1)::integer * 2 + t_acum;
	t_acum = substr(t_cpf,04,1)::integer * 3 + t_acum;
	t_acum = substr(t_cpf,05,1)::integer * 4 + t_acum;
	t_acum = substr(t_cpf,06,1)::integer * 5 + t_acum;
	t_acum = substr(t_cpf,07,1)::integer * 6 + t_acum;
	t_acum = substr(t_cpf,08,1)::integer * 7 + t_acum;
	t_acum = substr(t_cpf,09,1)::integer * 8 + t_acum;
	t_acum = substr(t_cpf,10,1)::integer * 9 + t_acum;
	
	t_conta := MOD (t_acum,11);
	t_conta := substr(''00'' || trim(t_conta),2)::integer;
	
	IF LENGTH (trim(t_conta)::varchar) = 2 THEN
		t_conta := substr(t_conta,0,1)::integer;
	ELSE
		t_conta := substr(t_conta,1)::integer;
	END IF;
	
	IF t_conta != substr(t_cpf,11,1)::integer Then
		msg := ''CPF '' || t_cpf || '' invalido'';
		raise exception ''%'' , msg;
	END IF;
	
	return t_cpf;
END;'

language  'plpgsql';
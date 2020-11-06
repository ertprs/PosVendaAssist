DROP FUNCTION CALCULA_CNPJ(text);
CREATE FUNCTION CALCULA_CNPJ(text)  RETURNS text AS '
DECLARE
	t_cgc	text;
	t_xxx	text;
	t_aux	integer;
	t_acum	integer;
	t_loop	integer;
	msg	text;
BEGIN
	t_cgc	:= so_numeros(trim($1));
	t_loop	:= 0;
	t_acum	:= 0;
	msg	:= '' '';
	
	IF t_cgc = ''00000000000000'' THEN
		msg := ''CNPJ inválido'';
		raise exception ''%'' , msg;
	END IF;
	
	/*------------- Primeiro Digito -----------*/
	/*
	WHILE t_loop < 7 LOOP
		t_loop	:= t_loop + 1;
		t_aux	:= substr(t_cgc,t_loop,1);
		
		IF MOD (t_loop,2) <> 0 THEN
			t_aux := t_aux * 2;
		END IF;
		
		IF t_aux > 9 Then
			t_xxx = t_aux::text;
			t_aux = substr(t_xxx,2,1)::integer + substr(t_xxx,1,1)::integer;
		END IF;
		
		t_acum	:= t_acum + t_aux;
	END LOOP;
	
	t_loop := 0;
	
	WHILE MOD (t_acum,10) <> 0 LOOP
		t_loop := t_loop + 1;
		t_acum := t_acum + 1;
	END LOOP;
	
	t_loop := substr(''00'' || trim(t_loop),2);
	t_loop := substr(t_loop,1);
	
	IF t_loop != substr(t_cgc,8,1)::integer AND substr(t_cgc,1,1) > ''0'' THEN
		msg := ''CNPJ inválido'';
		raise exception ''%'' , msg;
	END IF;
	*/
	
	/*------------- Segundo Digito -----------*/
	t_acum := 0;
	t_acum := substr(t_cgc,1,1)::integer * 6;
	t_acum := substr(t_cgc,2,1)::integer * 7 + t_acum;
	t_acum := substr(t_cgc,3,1)::integer * 8 + t_acum;
	t_acum := substr(t_cgc,4,1)::integer * 9 + t_acum;
	t_acum := substr(t_cgc,5,1)::integer * 2 + t_acum;
	t_acum := substr(t_cgc,6,1)::integer * 3 + t_acum;
	t_acum := substr(t_cgc,7,1)::integer * 4 + t_acum;
	t_acum := substr(t_cgc,8,1)::integer * 5 + t_acum;
	t_acum := substr(t_cgc,9,1)::integer * 6 + t_acum;
	t_acum := substr(t_cgc,10,1)::integer * 7 + t_acum;
	t_acum := substr(t_cgc,11,1)::integer * 8 + t_acum;
	t_acum := substr(t_cgc,12,1)::integer * 9 + t_acum;
	
	t_loop := MOD (t_acum,11);
	t_loop := substr(''00'' || trim(t_loop),2);
	t_loop := substr(t_loop,1);
	
	IF t_loop != substr(t_cgc,13,1)::integer THEN
		msg := ''CNPJ inválido'';
		raise exception ''%'' , msg;
	END IF;
	
	/*------------- Terceiro Digito -----------*/
	t_acum = 0;
	t_acum = substr(t_cgc,1,1)::integer * 5;
	t_acum = substr(t_cgc,2,1)::integer * 6 + t_acum;
	t_acum = substr(t_cgc,3,1)::integer * 7 + t_acum;
	t_acum = substr(t_cgc,4,1)::integer * 8 + t_acum;
	t_acum = substr(t_cgc,5,1)::integer * 9 + t_acum;
	t_acum = substr(t_cgc,6,1)::integer * 2 + t_acum;
	t_acum = substr(t_cgc,7,1)::integer * 3 + t_acum;
	t_acum = substr(t_cgc,8,1)::integer * 4 + t_acum;
	t_acum = substr(t_cgc,9,1)::integer * 5 + t_acum;
	t_acum = substr(t_cgc,10,1)::integer * 6 + t_acum;
	t_acum = substr(t_cgc,11,1)::integer * 7 + t_acum;
	t_acum = substr(t_cgc,12,1)::integer * 8 + t_acum;
	t_acum = substr(t_cgc,13,1)::integer * 9 + t_acum;
	
	t_loop := MOD (t_acum,11);
	t_loop := substr(''00'' || trim(t_loop),2);
	t_loop := substr(t_loop,1);
	
	IF t_loop != substr(t_cgc,14,1)::integer Then
		msg := ''CNPJ inválido'';
		raise exception ''%'' , msg;
	END IF;
	
	return t_cgc;
END;'

language  'plpgsql';
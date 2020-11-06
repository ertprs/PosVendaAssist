DROP FUNCTION formata_data (text);
CREATE FUNCTION formata_data (text) RETURNS date AS '
DECLARE 
	m_data			text;
	m_data_fim		date;
	m_dia			integer;
	m_mes			integer;
	m_ano			integer;
	m_barra			integer;
	string			text;
	string_verifica		text;
	string_final		text;
	string_tamanho		integer;
	string_conta		integer;
	letra			text;
	msg			text;

BEGIN
	msg	:= '' '';
	m_data	:= $1;
	
	string_conta	:= 1;
	string_final	:= '''';
	string		:= trim(m_data);
	string_tamanho	:= LENGTH(string);
	
	WHILE string_conta <= string_tamanho LOOP
		string_verifica := substr(string,string_conta,1::text);
		letra := string_verifica;
		
		IF string_verifica = ''.'' THEN
			letra := ''/'';
		END IF;
		IF string_verifica = ''-'' THEN
			letra := ''/'';
		END IF;
		IF string_verifica = '','' THEN
			letra := ''/'';
		END IF;
		IF string_verifica = '';'' THEN
			letra := ''/'';
		END IF;
		IF string_verifica = '' '' THEN
			letra := ''/'';
		END IF;
		string_final := string_final || letra;
		string_conta = string_conta + 1;
	END LOOP;
	string_final := UPPER(string_final);
	m_data := string_final;
	
	m_barra := STRPOS (m_data,''/'');
	m_dia   := SUBSTR (m_data,1,m_barra - 1)::integer;
	m_data  := SUBSTR (m_data,m_barra+1,LENGTH (m_data)-m_barra);
	
	
	m_barra := STRPOS (m_data,''/'');
	m_mes   := SUBSTR (m_data,1,m_barra - 1)::integer;
	m_data  := SUBSTR (m_data,m_barra+1,LENGTH (m_data)-m_barra);
	
	m_ano   := m_data::INT8;
	
	IF m_ano < 40 THEN
		m_ano := m_ano + 2000;
	END IF;
	
	IF m_ano < 100 THEN
		m_ano := m_ano + 1900;
	END IF;
	
	IF m_dia > 31 THEN
		msg := ''Dia maior que 31'';
		raise exception ''%'' , msg;
	END IF;
	
	IF m_dia < 1 THEN
		msg := ''Dia menor que 1'';
		raise exception ''%'' , msg;
	END IF;
	
	IF m_mes > 12 THEN
		msg := ''`Mês maior que 12'';
		raise exception ''%'' , msg;
	END IF;
	
	IF m_mes < 1 THEN
		msg := ''Mês menor que 1'';
		raise exception ''%'' , msg;
	END IF;
	
	IF m_ano > 2050 THEN
		msg := ''Ano maior que 2050'';
		raise exception ''%'' , msg;
	END IF;
	
	IF m_ano < 1900 THEN
		msg := ''Ano menor que 1900'';
		raise exception ''%'' , msg;
	END IF;
	
	
	m_data := (m_ano::text || ''-'' || m_mes::text || ''-'' || m_dia::text);
	m_data_fim := m_data::date;
	
	
	RETURN m_data_fim;

END;' LANGUAGE 'plpgsql';

DROP   FUNCTION limpa_email (TEXT);
CREATE FUNCTION limpa_email (TEXT) RETURNS TEXT AS '
DECLARE
	m_letra			text;
	m_string		text;
	m_string_verifica	text;
	m_string_final		text;
	m_string_conta		integer;
BEGIN
	m_string_conta		:= 1;
	m_string_final		:= '''';
	m_string		:= trim($1);
	
	WHILE m_string_conta <= LENGTH(m_string) LOOP
		m_string_verifica := substr(m_string,m_string_conta,1)::text;
		m_letra := m_string_verifica;
		
		IF m_string_verifica = ''à'' OR
			m_string_verifica = ''ã'' OR
			m_string_verifica = ''á'' OR
			m_string_verifica = ''ä'' OR
			m_string_verifica = ''â'' OR
			m_string_verifica = ''À'' OR
			m_string_verifica = ''Ã'' OR
			m_string_verifica = ''Á'' OR
			m_string_verifica = ''Ä'' OR
			m_string_verifica = ''Â'' THEN
			m_letra := ''a'';
		END IF;
		
		IF m_string_verifica = ''è'' OR
			m_string_verifica = ''é'' OR
			m_string_verifica = ''ë'' OR
			m_string_verifica = ''ê'' OR
			m_string_verifica = ''È'' OR
			m_string_verifica = ''É'' OR
			m_string_verifica = ''Ë'' OR
			m_string_verifica = ''Ê'' THEN
			m_letra := ''e'';
		END IF;
		
		IF m_string_verifica = ''ì'' OR
			m_string_verifica = ''í'' OR
			m_string_verifica = ''ï'' OR
			m_string_verifica = ''î'' OR
			m_string_verifica = ''Ì'' OR
			m_string_verifica = ''Í'' OR
			m_string_verifica = ''Ï'' OR
			m_string_verifica = ''Î'' THEN
			m_letra := ''i'';
		END IF;
		
		IF m_string_verifica = ''ò'' OR
			m_string_verifica = ''õ'' OR
			m_string_verifica = ''ó'' OR
			m_string_verifica = ''ö'' OR
			m_string_verifica = ''ô'' OR
			m_string_verifica = ''Ò'' OR
			m_string_verifica = ''Õ'' OR
			m_string_verifica = ''Ó'' OR
			m_string_verifica = ''Ö'' OR
			m_string_verifica = ''Ô'' THEN
			m_letra := ''o'';
		END IF;
		
		IF m_string_verifica = ''ù'' OR
			m_string_verifica = ''ú'' OR
			m_string_verifica = ''ü'' OR
			m_string_verifica = ''û'' OR
			m_string_verifica = ''Ù'' OR
			m_string_verifica = ''Ú'' OR
			m_string_verifica = ''Ü'' OR
			m_string_verifica = ''Û'' THEN
			m_letra := ''u'';
		END IF;
		
		IF m_string_verifica = ''ç'' OR
			m_string_verifica = ''Ç'' THEN
			m_letra := ''c'';
		END IF;
		
		IF m_string_verifica = ''ñ'' OR
			m_string_verifica = ''Ñ'' THEN
			m_letra := ''n'';
		END IF;
		
		IF m_string_verifica = ''/'' THEN
			m_letra := '''';
		END IF;
		
		IF m_string_verifica = ''-'' THEN
			m_letra := '''';
		END IF;
		
		m_string_final := m_string_final || m_letra;
		m_string_conta := m_string_conta + 1;
	END LOOP;
	m_string_final := LOWER(m_string_final);
	
	RETURN m_string_final;
END;' LANGUAGE 'plpgsql';
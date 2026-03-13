import re

def convert_sql(input_file, output_file):
    with open(input_file, 'r', encoding='utf-8') as f:
        content = f.read()

    # Split into statements
    statements = content.split(';')
    converted_statements = []

    for stmt in statements:
        if not stmt.strip():
            continue
        
        # Remove MySQL specific table options
        stmt = re.sub(r'(?i)character set [a-zA-Z0-9_]+\s*', '', stmt)
        stmt = re.sub(r'(?i)collate [a-zA-Z0-9_]+\s*', '', stmt)
        stmt = re.sub(r'(?i)comment\s+\'[^\']*\'\s*', '', stmt)
        stmt = re.sub(r'(?i)engine=[a-zA-Z0-9_]+\s*', '', stmt)
        
        # Replace AUTO_INCREMENT primary keys
        stmt = re.sub(r'(?i)int\s+auto_increment\s+primary\s+key', 'INTEGER PRIMARY KEY AUTOINCREMENT', stmt)
        stmt = re.sub(r'(?i)bigint\s+auto_increment\s+primary\s+key', 'INTEGER PRIMARY KEY AUTOINCREMENT', stmt)
        
        # Handle separated auto_increment and primary key
        stmt = re.sub(r'(?i)int\s+auto_increment', 'INTEGER PRIMARY KEY AUTOINCREMENT', stmt)
        stmt = re.sub(r'(?i)bigint\s+auto_increment', 'INTEGER PRIMARY KEY AUTOINCREMENT', stmt)
        stmt = re.sub(r'(?i)constraint\s+[a-zA-Z0-9_]+\s+primary\s+key\s*\([^\)]+\)', '', stmt)
        stmt = re.sub(r'(?i)primary\s+key\s*\([^\)]+\)', '', stmt)
        
        # Clean up possible trailing commas before closing parenthesis
        stmt = re.sub(r',\s*\)', '\n)', stmt)
        
        # Or cases where AUTO_INCREMENT is separated from PRIMARY KEY
        # e.g.: id int auto_increment, ..., constraint pk primary key (id)
        # In install.sql practically all are `id int auto_increment primary key` or `id bigint auto_increment primary key`
        
        # Handle `ON DUPLICATE KEY UPDATE`
        # e.g., INSERT INTO card (...) VALUES (...) ON DUPLICATE KEY UPDATE name = VALUES(name), ...
        # SQLite uses: INSERT INTO card (...) VALUES (...) ON CONFLICT(...) DO UPDATE SET ...
        # But a simpler cross-compatible way for `ON DUPLICATE KEY UPDATE` if there's a unique constraint is `INSERT OR REPLACE INTO` or standard `ON CONFLICT` IF we know the conflict target.
        # Since `card` has `unique(name_en)`, it's usually `ON CONFLICT(name_en)` but in install.sql it's not explicitly stating the unique key in INSERT.
        # Let's check the INSERTs in install.sql manually:
        # INSERT INTO card (name, name_en, version, tips, src, url, `window`) VALUES (...) ON DUPLICATE KEY UPDATE
        if 'ON DUPLICATE KEY UPDATE' in stmt:
            # We can use INSERT OR REPLACE for these specific rows since we give all values.
            # But wait, `version`, `tips`, `src` etc are updated, meaning it's basically an upsert matching the unique constraint `name_en`.
            # Let's replace INSERT INTO with INSERT OR REPLACE INTO and remove ON DUPLICATE KEY UPDATE ...
            stmt = re.sub(r'(?i)INSERT\s+INTO', 'INSERT OR REPLACE INTO', stmt)
            stmt = re.sub(r'(?i)ON\s+DUPLICATE\s+KEY\s+UPDATE\s+.*$', '', stmt, flags=re.DOTALL)
        
        converted_statements.append(stmt)
        
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write(';'.join(converted_statements) + ';')

convert_sql('install.sql', 'install.sqlite.sql')

-- Runs once, when the volume is created. Tests use a separate database (phpunit.xml).
SELECT 'CREATE DATABASE ai_roadmap_testing OWNER ai_roadmap'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'ai_roadmap_testing')\gexec

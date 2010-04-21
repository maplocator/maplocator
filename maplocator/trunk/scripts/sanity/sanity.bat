@IF (%1) == (/?) GOTO USAGE

@IF (%1) == () GOTO USAGE
@IF (%2) == () GOTO USAGE

@echo Using DB: %1; DB user: %2

@REM sanity
@echo =============== Executing sanity SQL ================
psql -e -d %1 -U %2 -f sanity.sql

@echo =============== Executing sanity script ===============
python sanity.py %1 %2

@GOTO EOF

:USAGE
@echo Usage: %0 DBNAME DBUSER

:EOF (end-of-file)
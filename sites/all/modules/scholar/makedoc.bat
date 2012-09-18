@ECHO OFF

set DOCDIR=doc
RMDIR /Q /S %DOCDIR% 2>nul
MKDIR %DOCDIR%


call jsdoc -d=%DOCDIR%\js -a js

set TITLE=Scholar
set PACKAGES=Scholar

set PATH_FILE=scholar.module.php
set PATH_PROJECT=.

set PATH_DOCS=%DOCDIR%\php

set OUTPUTFORMAT=HTML

set CONVERTER=Smarty

set TEMPLATE=default

set PRIVATE=on

:: nie owijac PATH_PHPDOC w ciapki, bo wtedy nie dziala poprawnie %~dp0
echo phpdoc -f "%PATH_FILE%" -d "%PATH_PROJECT%" -t "%PATH_DOCS%" -ti "%TITLE%" -dn "%PACKAGES%" -o %OUTPUTFORMAT%:%CONVERTER%:%TEMPLATE% -pp %PRIVATE% -ue

phpdoc -f "%PATH_FILE%" -d "%PATH_PROJECT%" -t "%PATH_DOCS%" -ti "%TITLE%" -dn "%PACKAGES%" -o %OUTPUTFORMAT%:%CONVERTER%:%TEMPLATE% -pp %PRIVATE% -ue
:: pause

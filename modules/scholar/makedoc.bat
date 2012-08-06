@ECHO OFF

RMDIR /Q /S ..\..\docs\scholar 2>nul
MKDIR ..\..\docs\scholar


call jsdoc -d=..\..\docs\scholar\js -a .

set TITLE=Scholar
set PACKAGES=Scholar

set PATH_FILE=scholar.module.php
set PATH_PROJECT=.

set PATH_DOCS=..\..\docs\scholar\php

set OUTPUTFORMAT=HTML

set CONVERTER=Smarty

set TEMPLATE=default

set PRIVATE=on

:: nie owijac PATH_PHPDOC w ciapki, bo wtedy nie dziala poprawnie %~dp0
echo phpdoc -f "%PATH_FILE%" -d "%PATH_PROJECT%" -t "%PATH_DOCS%" -ti "%TITLE%" -dn "%PACKAGES%" -o %OUTPUTFORMAT%:%CONVERTER%:%TEMPLATE% -pp %PRIVATE% -ue

phpdoc -f "%PATH_FILE%" -d "%PATH_PROJECT%" -t "%PATH_DOCS%" -ti "%TITLE%" -dn "%PACKAGES%" \
-o %OUTPUTFORMAT%:%CONVERTER%:%TEMPLATE% -pp %PRIVATE% -ue
pause

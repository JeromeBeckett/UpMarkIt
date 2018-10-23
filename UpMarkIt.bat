@echo off
color 0a
title UpMarkIt

:inputMenu
cls
echo.
echo ------------------------------------------
echo.
echo                  UPMARKIT                 
ECHO.
echo        Developed by Jerome Beckett
echo.
echo ------------------------------------------
echo.
echo How would you like to input your markdown?
echo 1 - As string
echo 2 - From file
set /p inputMethod=
if %inputMethod% == 1 goto stringInput
if %inputMethod% == 2 goto fileInput
if not %inputMethod% == 1 if not %inputMethod% == 2 goto inputMenu

:stringInput
cls
echo.
echo Input your string:
set /p string=
goto outputMenu

:fileInput
cls
echo.
echo Enter the filepath (starts in UpMarkIt folder):
set /p filename=
goto outputMenu

:outputMenu
cls
echo.
echo How would you like to output the HTML?
echo 1 - As string
echo 2 - To file
set /p outputMethod=
if %outputMethod% == 1 goto styling
if %outputMethod% == 2 goto fileOutput
if not %outputMethod% == 1 if not %outputMethod% == 2 goto outputMenu

:fileOutput
cls
echo.
echo Enter the filepath (starts in UpMarkIt folder):
set /p outputFilename=
goto styling

:styling
cls
echo.
echo Include blockquote/code styling? [y/n]
set /p styling=
goto parse

:parse
cls
if %inputMethod% == 1 set inputArg=-s "%string%"
if %inputMethod% == 2 set inputArg=-f %filename%
if %outputMethod% == 2 set outputArg=-o=%outputFilename%
if %styling% == y set stylingArg=-c=true
if %styling% == n set stylingArg=-c=false
php UpMarkIt.php %inputArg% %outputArg% %stylingArg%
echo.
pause
goto inputMenu
@echo off
cd /d "%~dp0"
title Download photos - Cote d'Or Retreat
if not exist images mkdir images
if not exist video mkdir video
echo.
echo Downloading free photos from Pexels into the images folder...
echo Photos that are already in the folder are skipped, so your own photos are never replaced.
echo.
call :getphoto hero.jpg 31386176
call :getphoto intro-1.jpg 6151653
call :getphoto intro-2.jpg 4024137
call :getphoto intro-3.jpg 4932184
call :getphoto strip-1.jpg 30358268
call :getphoto strip-2.jpg 33669186
call :getphoto strip-3.jpg 2956457
call :getphoto strip-4.jpg 4034153
call :getphoto room-double.jpg 29000313
call :getphoto room-selfcatering.jpg 6186819
call :getphoto room-apartment.jpg 7019013
call :getphoto spa.jpg 35884499
call :getphoto story-2.jpg 6724313
call :getphoto restaurant.jpg 6127317
call :getphoto story-1.jpg 29346150
call :getphoto pool.jpg 14024023
call :getphoto beach.jpg 29773805
call :getphoto anse-lazio.jpg 29761680
call :getphoto moment-1.jpg 10352885
call :getphoto moment-2.jpg 3030283
call :getphoto moment-3.jpg 30703913
call :getphoto vallee-de-mai.jpg 15556726
call :getphoto room-double-2.jpg 19227243
call :getphoto room-double-3.jpg 7329929
call :getphoto room-double-4.jpg 12498873
call :getphoto room-selfcatering-2.jpg 7147288
call :getphoto room-selfcatering-3.jpg 6207947
call :getphoto room-selfcatering-4.jpg 19025564
call :getphoto room-apartment-2.jpg 29702286
call :getphoto room-apartment-3.jpg 6447384
call :getphoto room-apartment-4.jpg 3897529
echo.
if exist "video\hero.mp4" (
    echo   hero.mp4 already there, skipped
) else (
    echo Downloading the beach video, this can take a minute...
    curl -fsSL -A "Mozilla/5.0" --retry 2 -o "video\hero.mp4" "https://videos.pexels.com/video-files/3089895/3089895-hd_1920_1080_30fps.mp4" || curl -fsSL -A "Mozilla/5.0" --retry 2 -o "video\hero.mp4" "https://videos.pexels.com/video-files/3089895/3089895-uhd_2560_1440_30fps.mp4"
    if exist "video\hero.mp4" (echo   hero.mp4 OK) else (echo   hero.mp4 FAILED)
)
echo.
echo Finished. Refresh the website in your browser.
pause
exit /b

:getphoto
if exist "images\%1" (
    echo   %1 already there, skipped
    exit /b
)
curl -fsSL -A "Mozilla/5.0" --retry 2 -o "images\%1" "https://images.pexels.com/photos/%2/pexels-photo-%2.jpeg?auto=compress&cs=tinysrgb&w=1920"
if errorlevel 1 (
    echo   %1 FAILED
    if exist "images\%1" del "images\%1"
) else (
    echo   %1 OK
)
exit /b

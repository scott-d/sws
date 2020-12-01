@ECHO OFF
set arg1=%1
git commit -m "%arg1%"
git push sws master

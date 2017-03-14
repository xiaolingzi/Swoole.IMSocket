<?php
//项目根目录
define("ROOT_PATH", dirname($_SERVER['SCRIPT_NAME']));
//框架目录
define("FRAME_PATH", dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))));
//环境变量
define("ENVIRONMENT", "dev");
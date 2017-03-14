<?php
require_once 'Global.php';
require_once 'ClassLoader.php';

//用i作为执行脚本文件的参数名 php 文件路径 -i xx方式
$inputParamArr = getopt("i:");

//传入的i的参数值，即执行命令
$command = "";
//如果未传入i参数，或者i的参数值为空，则提示输入。
if(!array_key_exists("i", $inputParamArr) || (array_key_exists("i", $inputParamArr) && empty($inputParamArr["i"])))
{
    $isInput=false;
    while(!$command)
    {
        if(!$isInput)
        {
            fwrite(STDOUT, 'Please input the operation command[i]:');
            $isInput = true;
        }
        else
        {
            fwrite(STDOUT,'The operation command can not be empty, please input again[i]:');
        }
    
        $command = trim(fgets(STDIN));
    }
    $inputParamArr["i"]=$command;
}
else
{
    $command = $inputParamArr["i"];
}


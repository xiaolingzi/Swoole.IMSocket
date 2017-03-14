<?php
namespace Lib\Utility\IO;

use Lib\Utility\IO\FileHandler;

class ConfigHandler
{
    private static  $_commonConfigArr;
    private static  $_localConfigArr;
    
    /**
     * 读取公共配置
     * @param string $key
     * @return Ambigous <>
     */
	static public function getCommonConfigs($key)
	{
		self::getCommonConfigArr();
		return self::$_commonConfigArr[$key];
	}
	
	/**
	 * 读取项目配置
	 * @param string $key
	 * @return Ambigous <>
	 */
	static public function getLocalConfigs($key)
	{
	    self::getLocalConfigArr();
	    return self::$_localConfigArr[$key];
	}
	
	/**
	 * 读取公共配置
	 */
	static private function getCommonConfigArr()
	{
		if(empty(self::$_commonConfigArr))
		{
		    $filename=dirname(dirname(ROOT_PATH)).'/config/'.ENVIRONMENT.'/common_config.json';
		    self::$_commonConfigArr=FileHandler::getArrayFromJsonFile($filename);
		}
	}
	
	/**
	 * 读取项目配置
	 */
	static private function getLocalConfigArr()
	{
	    if(empty(self::$_localConfigArr))
	    {
	        $filename=ROOT_PATH.'/config/'.ENVIRONMENT.'/common_config.json';
	        self::$_localConfigArr=FileHandler::getArrayFromJsonFile($filename);
	    }
	}
	
}
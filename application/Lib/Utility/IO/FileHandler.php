<?php
namespace Lib\Utility\IO;

class FileHandler
{
    static function deleteDir($dir)
    {
        if(!file_exists($dir))
        {
        	return;
        }
        // 先删除目录下的文件：
        $dh = opendir($dir);
        $file = readdir($dh);
        while($file!==false)
        {
            if($file != "." && $file != "..")
            {
                $fullpath = $dir . "/" . $file;
                if(! is_dir($fullpath))
                {
                    @unlink($fullpath);
                }
                else
                {
                    self::deleteDir($fullpath);
                }
            }
            $file = readdir($dh);
        }
        
        closedir($dh);
        // 删除当前文件夹：
        if(rmdir($dir))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    static function deleteFiles($dir)
    {
        if(!file_exists($dir))
        {
            return;
        }
        // 先删除目录下的文件：
        $dh = opendir($dir);
        $file = readdir($dh);
        while($file!==false)
        {
            if($file != "." && $file != "..")
            {
                $fullpath = $dir . "/" . $file;
                if(! is_dir($fullpath))
                {
                    unlink($fullpath);
                }
                else
                {
                    self::deleteDir($fullpath);
                }
            }
            $file = readdir($dh);
        }
    
        closedir($dh);
    }
    
    /*
     * 获取文件列表
     */
    static function getFiles($dir)
    {
    	$files = array();
        $dirpath = realpath($dir);
        $filenames = scandir($dir);
    
        foreach ($filenames as $filename)
        {
            if ($filename=='.' || $filename=='..')
            {
                continue;
            }
    
            $file = $dirpath . DIRECTORY_SEPARATOR . $filename;
             
            if (is_dir($file))
            {
                $files = array_merge($files, self::getFiles($file));
            }
            else
            {
                $files[] = $file;
            }
        }
    
        return $files;
    }
    
    static public function getContentFromFile($filename)
    {
        if(! file_exists($filename))
        {
            return null;
        }
        $content = file_get_contents($filename);
        return $content;
    }
    
    static public function getArrayFromJsonFile($filename)
    {
        $content=self::getContentFromFile($filename);
        if(!empty($content))
        {   
            return json_decode($content,true);
        }
        return array();
    }
    
    static public function writeArrayToJsonFile($filename,$arr)
    {   
        $filePath = dirname($filename);
        if(! file_exists($filePath))
        {
            mkdir($filePath, 0777, true);
        }
        $fp=fopen($filename, "w");
        fwrite($fp, json_encode($arr,true));
        fclose($fp);
    }
    
    
    static public function writeToFlie($content, $filename)
    {
        $filePath = dirname($filename);
        if(! file_exists($filePath))
        {
            mkdir($filePath, 0777, true);
        }
        $fp = fopen($filename, "w");
        fwrite($fp, $content);
        fclose($fp);
    }

    static public function writeLog($msg,$subDir="",$filename)
    {   
        if(!empty($subDir)){
            $filename=$filename."/".$subDir;
        }
        if(!file_exists($filename))
        {
            mkdir($filename,0777,true);
        }
        
        $fileName=$filename."/".date("Ymd",time()).".txt";;
        $fp=fopen($fileName, "a");
        fwrite($fp, $msg."\n\n");
        fclose($fp);
    
    }

    static public function json_return($res,$code = 'json')
    {
        if ($code == 'json') {
            $res = json_encode($res);
        }
        return $res;
    }

    static public function htmlDecode($str)
    {
        if(empty($str))
        {
            $str;
        }
        $result=str_replace(array(
                "&lt;",
                "&gt;",
                "&#39;",
                "&nbsp;",
                "&amp;"
        ),array(
                "<",
                ">",
                "'",
                " ",
                "&"
        ),  $str);
        return $result;
    }

    static function getAge($strTime)
    {
        $soureTime = strtotime($strTime); // int strtotime ( string $time [, int $now ] )
        $year = date('Y', $soureTime);
        if(($month = (date('m') - date('m', $soureTime))) < 0)
        {
            $year ++;
        }
        else if($month == 0 && date('d') - date('d', $soureTime) < 0)
        {
            $year ++;
        }
        $age = (date('Y') - $year) < 0 ? 0 :(date('Y') - $year) ;
        return $age;
    }


    
}
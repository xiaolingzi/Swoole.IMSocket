<?php

namespace Projects\IMSocket;

use Lib\BLL\IM\UserBLL;
use Lib\Utility\IO\ConfigHandler;

class User
{

    /**
     * 根据用户id获取用户信息
     * 
     * @param int $userId
     *            用户id
     * @return array
     */
    public function getUser($userId)
    {
        $filename = $this->getUserFileName($userId);
        if(file_exists($filename))
        {
            $content = file_get_contents($filename);
            $result = json_decode($content, true);
            if(! empty($result))
            {
                $result["userName"] = base64_decode($result["userName"]);
            }
            return $result;
        }
        return null;
    }

    /**
     * 根据连接id获取用户信息
     * 
     * @param int $fd
     *            连接id
     * @return array
     */
    public function getUserByFd($fd)
    {
        $userId = (new Connection())->getConnectorUserId($fd);
        return $this->getUser($userId);
    }

    /**
     * 保存当前连接的用户信息
     * 
     * @param int $fd
     *            连接id
     * @param int $userId
     *            用户id
     * @param int $connectionType
     *            连接类型
     * @return boolean
     */
    public function saveUser($fd, $userId, $connectionType)
    {
        $userBLL = new UserBLL();
        $userInfo = $userBLL->getUserInfo($userId);
        if(empty($userInfo))
        {
            echo "user not exist";
            return false;
        }
        
        $arr = array();
        $arr["userId"] = $userId;
        $arr["fd"] = $fd;
        $arr["userName"] = base64_encode($userInfo->userName);
        $arr["avatar"] = $userInfo->avatar;
        $arr["connectionType"] = $connectionType;
        
        $filename = $this->getUserFileName($userId);
        $fp = fopen($filename, "w");
        fwrite($fp, json_encode($arr));
        fclose($fp);
        return true;
    }

    /**
     * 根据用户id删除用户信息
     * 
     * @param int $userId
     *            用户id
     */
    public function removeUser($userId)
    {
        $filename = $this->getUserFileName($userId);
        if(file_exists($filename))
        {
            unlink($filename);
        }
    }

    /**
     * 获取用户信息文件路径
     * 
     * @param int $userId
     *            用户id
     * @return string
     */
    private function getUserFileName($userId)
    {
        $subDir = substr(strval($userId), 0, 1);
        $connectorDir = ConfigHandler::getLocalConfigs("connectorDir");
        $dir = $connectorDir . "/users/" . $subDir;
        if(! file_exists($dir))
        {
            mkdir($dir, 0755, true);
        }
        
        $filename = $dir . "/" . $userId;
        return $filename;
    }
}
<?php

namespace Projects\IMSocket;

use Lib\BLL\IM\ClubBLL;
use Lib\Utility\IO\ConfigHandler;

class Group
{

    /**
     * 将当前连接添加到该用户所在群组的通知池中
     * 
     * @param int $fd
     *            连接id
     */
    public function saveUserToGroups($fd)
    {
        $userId = (new Connection())->getConnectorUserId($fd);
        $clubBLL = new ClubBLL();
        $clubArr = $clubBLL->getUserClubList($userId);
        for($i = 0; $i < count($clubArr); $i ++)
        {
            $this->saveGroupParticipator($clubArr[$i]->clubId, $fd);
        }
    }

    /**
     * 将当前连接添加到群组通知池中
     * 
     * @param int $groupId
     *            群组id
     * @param int $fd
     *            连接id
     */
    public function saveGroupParticipator($groupId, $fd)
    {
        $filename = $this->getGroupFileName($groupId, $fd);
        if(! file_exists($filename))
        {
            touch($filename);
        }
    }

    /**
     * 将当前连接从所有群组中移除
     * 
     * @param int $fd
     *            连接id
     */
    public function removeUserFromGroups($fd)
    {
        $userId = (new Connection())->getConnectorUserId($fd);
        
        // 获取该用户的所有群组
        $clubBLL = new ClubBLL();
        $clubArr = $clubBLL->getUserClubList($userId);
        
        for($i = 0; $i < count($clubArr); $i ++)
        {
            $this->removeGroupParticipator($clubArr[$i]->clubId, $fd);
        }
    }

    /**
     * 将当前连接从群组通知池中移除
     * 
     * @param int $groupId
     *            群组id
     * @param int $fd
     *            连接id
     */
    public function removeGroupParticipator($groupId, $fd)
    {
        $filename = $this->getGroupFileName($groupId, $fd);
        if(file_exists($filename))
        {
            unlink($filename);
        }
    }

    /**
     * 获取连接在群组通知池的文件路径
     * 
     * @param int $groupId
     *            群组id
     * @param int $fd
     *            连接id
     * @return string
     */
    private function getGroupFileName($groupId, $fd)
    {
        $connectorDir = ConfigHandler::getLocalConfigs("connectorDir");
        $dir = $connectorDir . "/groups/" . $groupId;
        if(! file_exists($dir))
        {
            mkdir($dir, 0755, true);
        }
        $filename = $dir . "/" . $fd;
        return $filename;
    }

    /**
     * 获取群组在线人员
     * 
     * @param int $groupId
     *            群组id
     * @return array:
     */
    public function getGroupParticipators($groupId)
    {
        $filename = $this->getGroupFileName($groupId, 0);
        $dir = dirname($filename);
        $result = array();
        if(file_exists($dir))
        {
            $filenames = scandir($dir);
            foreach($filenames as $name)
            {
                if($name != "." && $name != "..")
                {
                    $name = basename($name);
                    array_push($result, $name);
                }
            }
        }
        return $result;
    }
}

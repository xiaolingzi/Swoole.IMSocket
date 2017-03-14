<?php

namespace Projects\IMSocket;

use Lib\Utility\Network\WebSocket;
use Lib\BLL\IM\MessageBLL;
use Lib\BLL\IM\ClubBLL;

class Message
{

    /**
     * websocket握手和消息分发
     *
     * @param swoole_server $serv            
     * @param int $fd            
     * @param string $data            
     */
    public function send($serv, $fd, $data)
    {
        if(! empty($data))
        {
            $connectionCls = new Connection();
            // websocket握手，如果是握手则直接返回
            if($this->wsHandShake($serv, $fd, $data))
            {
                echo "websocket handsake.\n";
                $connectionCls->saveConnector($fd, CommonDefine::CONNECTION_TYPE_WEBSOCKET);
                return;
            }
            
            // 判断客户端类型，对websocket的消息进行解包
            $connectionType = $connectionCls->getConnectionType($fd);
            if($connectionType == CommonDefine::CONNECTION_TYPE_WEBSOCKET)
            {
                echo "I am websocket.\n";
                $ws = new WebSocket();
                $data = $ws->unwrap($data);
            }
            echo $data . "\n";
            
            // 数据拆包
            $messageArr = (new MessageCache())->getSplitDataList($fd, $data);
            var_dump($messageArr);
            // 如果没有完整的消息，则直接返回，直到收到完整消息再处理
            if(empty($messageArr) && ! is_array($messageArr))
            {
                return;
            }
            // 将所有收到的所有完整消息进行投递处理
            for($i = 0; $i < count($messageArr); $i ++)
            {
                $this->sendMessage($serv, $fd, $messageArr[$i], $connectionType);
            }
        }
    }

    /**
     * 进行身份验证 根据消息类型进行不同的处理
     *
     * @param swoole_server $serv            
     * @param int $fd            
     * @param string $data            
     * @param int $connectionType            
     */
    private function sendMessage($serv, $fd, $data, $connectionType)
    {
        $msgArr = json_decode($data, true);
        
        // 如果data不是合法的json数据，则关闭连接
        if(! is_array($msgArr) || ! array_key_exists("infoType", $msgArr) || ! array_key_exists("data", $msgArr))
        {
            $serv->close($fd);
            return;
        }
        
        // 消息类型
        $infoType = intval($msgArr["infoType"]);
        // 连接类型
        $connectionType = intval($msgArr["connectionType"]);
        // 实际数据
        $dataArr = $msgArr["data"];
        
        $connectionCls = new Connection();
        $groupCls = new Group();
        
        // 如果该消息不是身份验证消息，而又没有进行过身份验证，则直接断开连接
        if($infoType != CommonDefine::INFO_TYPE_VALID && ! $connectionCls->isValidConnector($fd))
        {
            $serv->close($fd);
        }
        
        switch ($infoType)
        {
            case CommonDefine::INFO_TYPE_VALID:
                // 身份验证
                if(array_key_exists("token", $dataArr) && $connectionCls->validateConnector($dataArr["token"]))
                {
                    // 用户Id
                    $userId = intval($dataArr["userId"]);
                    // 添加到连接池中
                    $connectionCls->saveConnector($fd, $connectionType, $userId);
                    // 添加到群组通知列表中
                    $groupCls->saveUserToGroups($fd);
                    // 通知客户端身份校验成功
                    $serv->send($fd, $this->getResultJson("N00000", "身份校验成功", "", $infoType, $connectionType));
                    return;
                }
                break;
            case CommonDefine::INFO_TYPE_MESSAGE_USER:
                // 双人对话消息
                $this->sendUserMessage($serv, $fd, $dataArr);
                break;
            case CommonDefine::INFO_TYPE_MESSAGE_USER_CONFIRM:
                // 双人对话消息已读确认
                $this->updateUserMessageReadStatus($serv, $fd, $dataArr);
                break;
            case CommonDefine::INFO_TYPE_MESSAGE_CLUB:
                // 群组消息
                $this->sendGroupMessage($serv, $fd, $dataArr);
                break;
            case CommonDefine::INFO_TYPE_MESSAGE_CLUB_CONFIRM:
                // 群组消息已读确认
                $this->updateGroupMessageReadStatus($serv, $fd, $dataArr);
                break;
            default:
                break;
        }
    }

    /**
     * 发送双人对话消息
     *
     * @param swoole_server $serv            
     * @param int $fd            
     * @param array $dataArr            
     */
    private function sendUserMessage($serv, $fd, $dataArr)
    {
        echo "sending user message.\n";
        $toUserId = intval($dataArr["toUserId"]);
        $message = $dataArr["messageContent"];
        
        $userCls=new User();
        // 根据fd获取发送消息用户信息
        $fromUser = $userCls->getUserByFd($fd);
        $fromFd = intval($fromUser["fd"]);
        $fromConnectionType = intval($fromUser["connectionType"]);
        
        // 如果用户Id不合法或者消息为空，报异常
        if($toUserId <= 0 || empty($message))
        {
            $serv->send($fromFd, $this->getResultJson("N02000", "数据异常", "", CommonDefine::INFO_TYPE_MESSAGE_USER, $fromConnectionType));
        }
        
        // 根据用户Id获取被发送用户信息
        $toUser = $userCls->getUser($toUserId);
        if(! empty($toUser))
        {
            $toFd = $toUser["fd"];
            if(! empty($toFd) && $serv->exist($toFd))
            {
                if($fromFd != $toFd)
                {
                    $dataArr["userName"] = $fromUser["userName"];
                    $dataArr["avatar"] = $fromUser["avatar"];
                    $toConnectionType = intval($toUser["connectionType"]);
                    $serv->send($toFd, $this->getResultJson("N00000", "数据推送成功", $dataArr, CommonDefine::INFO_TYPE_MESSAGE_USER, $toConnectionType));
                }
            }
        }
        else
        {
            // 如果对方不在线，则返回不在线通知
            $serv->send($fromFd, $this->getResultJson("E02000", "对方不在线哦", "", CommonDefine::INFO_TYPE_WARNING, $fromConnectionType));
        }
    }

    /**
     * 更新双人对话消息已读状态
     *
     * @param swoole_server $serv            
     * @param int $fd            
     * @param array $dataArr            
     */
    private function updateUserMessageReadStatus($serv, $fd, $dataArr)
    {
        echo "update user message read status.\n";
        $userInfo = (new User())->getUserByFd($fd);
        if(! empty($userInfo))
        {
            $userId = intval($userInfo["userId"]);
            $fromUserId = intval($dataArr["fromUserId"]);
            $messageBLL = new MessageBLL();
            $messageBLL->updateMessageAsReadByUser($fromUserId, $userId);
        }
    }

    /**
     * 发送群组消息
     *
     * @param swoole_server $serv            
     * @param int $fd            
     * @param array $dataArr            
     */
    private function sendGroupMessage($serv, $fd, $dataArr)
    {
        echo "sending group message.\n";
        
        $fromUserId = intval($dataArr["userId"]);
        $message = $dataArr["messageContent"];
        $groupId = intval($dataArr["clubId"]);
        
        $userCls = new User();
        $groupCls = new Group();
        
        // 根据fd获取发送消息用户信息
        $fromUser = $userCls->getUserByFd($fd);
        $fromFd = intval($fromUser["fd"]);
        $fromConnectionType = intval($fromUser["connectionType"]);
        
        // 如果群组Id不合法或者消息为空，则返回异常
        if($groupId <= 0 || empty($message))
        {
            $serv->send($fromFd, $this->getResultJson("E02000", "数据异常", "", CommonDefine::INFO_TYPE_WARNING, $fromConnectionType));
        }
        
        // 获取当前群在线用户，广播消息
        $participatorArr = $groupCls->getGroupParticipators($groupId);
        if(! empty($participatorArr))
        {
            for($i = 0; $i < count($participatorArr); $i ++)
            {
                $toFd = $participatorArr[$i];
                
                // 确认用户在线，然后进行消息推送
                if(! empty($toFd) && $serv->exist($toFd))
                {
                    $toUser = $userCls->getUserByFd($toFd);
                    if(empty($toUser))
                    {
                        continue;
                    }
                    
                    if($fromFd != $toFd)
                    {
                        $dataArr["userName"] = $fromUser["userName"];
                        $dataArr["avatar"] = $fromUser["avatar"];
                        $toConnectionType = intval($toUser["connectionType"]);
                        $serv->send($toFd, $this->getResultJson("N00000", "数据推送成功", $dataArr, CommonDefine::INFO_TYPE_MESSAGE_CLUB, $toConnectionType));
                    }
                }
                else
                {
                    // 掉线则从群组通知池中移除
                    $groupCls->removeGroupParticipator($groupId, $toFd);
                }
            }
        }
    }

    /**
     * 更新群组消息已读状态
     *
     * @param swoole_server $serv            
     * @param int $fd            
     * @param array $dataArr            
     */
    private function updateGroupMessageReadStatus($serv, $fd, $dataArr)
    {
        echo "update group message read status.\n";
        $userInfo = (new User())->getUserByFd($fd);
        if(! empty($userInfo))
        {
            $userId = intval($userInfo["userId"]);
            $clubId = intval($dataArr["clubId"]);
            $lastClubMessageId = intval($dataArr["clubMessageId"]);
            $clubBLL = new ClubBLL();
            $clubBLL->updateLastReadClubMessageId($clubId, $userId, $lastClubMessageId);
        }
    }

    /**
     * websocket握手
     *
     * @param swoole_server $serv            
     * @param int $fd            
     * @param string $data            
     * @return boolean 如果为websocket连接则进行握手，握手成功返回true，否则返回false
     */
    private function wsHandShake($serv, $fd, $data)
    {
        // 判断客户端类型 通过websocket握手时的关键词进行判断
        if(strpos($data, "Sec-WebSocket-Key") > 0)
        {
            $ws = new WebSocket();
            $handShakeData = $ws->getHandShakeHeaders($data);
            $serv->send($fd, $handShakeData);
            return true;
        }
        return false;
    }

    /**
     * 生成发送的json数据
     * 
     * @param string $code
     *            状态码
     * @param string $message
     *            消息结果提示
     * @param array $data
     *            发送的数据
     * @param int $infoType
     *            消息类型
     * @param int $connectionType
     *            连接类型
     * @return string
     */
    private function getResultJson($code, $message, $data, $infoType = 0, $connectionType = CONNECTION_TYPE_SOCKET)
    {
        if(empty($message))
        {
            return $message;
        }
        
        $apiResult = array(
                "code" => $code,
                "message" => $message,
                "infoType" => $infoType,
                "data" => $data 
        );
        
        $result = json_encode($apiResult) . CommonDefine::MESSAGE_END_FLAG;
        
        if($connectionType == CommonDefine::CONNECTION_TYPE_WEBSOCKET)
        {
            $ws = new WebSocket();
            $result = $ws->wrap($result);
        }
        
        return $result;
    }
}
<?php
namespace Lib\DAL\IM;

use Lib\Entity\IM\UserInfoEntity;

class UserDAL
{
    public function getUserInfo($userId)
    {
		// TODO 此处只应该从数据库获取用户信息 这里直接返回模拟数据
    	$result = new UserInfoEntity();
		$result->userId = $userId;
		$result->userName = "test".$userId;
		$result->avatar = "";
    	return $result;
    }
    

}
<?php
namespace Lib\BLL\IM;

use Lib\DAL\IM\UserDAL;

class UserBLL
{
    public function getUserInfo($userId)
    {
        $userDAL = new UserDAL();
        $result=$userDAL->getUserInfo($userId);
        return $result;
    }

}
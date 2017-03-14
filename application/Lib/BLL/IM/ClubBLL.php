<?php
namespace Lib\BLL\IM;

use Lib\DAL\IM\ClubDAL;

class ClubBLL
{
    public function getUserClubList($userId)
    {
    	$clubDAL=new ClubDAL();
    	$result = $clubDAL->getUserClubList($userId);
    	return $result;
    }
    
    public function updateLastReadClubMessageId($clubId,$userId,$clubMesssageId)
    {
		// TODO 根据实际情况做相应的操作
        return;
    }
    
}
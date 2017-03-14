<?php
namespace Lib\DAL\IM;

use Lib\Entity\IM\ClubEntity;

class ClubDAL
{
	public function getUserClubList($userId)
	{
		// TODO 此处只应该从数据库获取用户的群组列表 这里直接返回模拟数据
		$result = array();
		for($i=0; $i<6; $i++)
		{
			$entity = new ClubEntity();
			$entity->clubId = $i;
			$entity->clubName = "";
			array_push($result, $entity);
		}
		
		return $result;
	}

}
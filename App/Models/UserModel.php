<?php
/**
 * Created by PhpStorm.
 * User: hongchen
 * Date: 2020/6/12
 * Time: 15:46
 */

namespace App\Models;
use EasySwoole\ORM\AbstractModel;
use EasySwoole\ORM\Utility\Schema\Table;
use EasySwoole\ORM\DbManager;

class UserModel extends AbstractModel
{
    //选择连接的数据库
    protected $connectionName = 'mysql';
    //选择表
    protected $tableName = 'k_user';

    public function findAll()
    {
        $info = $this->all()->toArray();
        return $info;
    }

}
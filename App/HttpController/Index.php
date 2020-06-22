<?php


namespace App\HttpController;

use App\Models\UserModel;
use App\Base\BaseController;
use EasySwoole\Pool\Manager;


class Index extends BaseController
{
    /**
     * @var array 提交参数
     */
    protected $params;

    /**
     * orm 多数据库连接配置；返回数据格式
     * @throws \EasySwoole\Mysqli\Exception\Exception
     * @throws \EasySwoole\ORM\Exception\Exception
     * @throws \Throwable
     */
    public function index()
    {
        $time = $this->getRequestParam();
        var_dump($time);
        $res = UserModel::create()->get(2);
        $this->apiSuccess($res);
    }

    /**
     * 自动生成表结构
     * @throws \EasySwoole\ORM\Exception\Exception
     */
    public function sctable()
    {
        $user = new UserModel();
        $table = $user->schemaInfo();
        var_dump($table);
        $this->response()->write($table);
    }

    public function findAll()
    {
        $model = new UserModel();
        $info = $model->findAll();
        var_dump($info);
        var_dump(count($info));
    }

    /**
     * redis测试
     * @return bool
     * @throws \Throwable
     */
    public function redisOne()
    {
        //开启
        $redis=Manager::getInstance()->get('redis')->getObj();
        if(!$res=$redis->get("name")){
            $redis->set("name","zq");
            $redis->expire("name",10);
        }
        $res=$redis->get("name");
        //回收
        Manager::getInstance()->get('redis')->recycleObj($redis);
        return $this->apiSuccess($res);
    }

    //测试git

}
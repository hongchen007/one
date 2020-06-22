<?php
/**
 * Created by PhpStorm.
 * User: hongchen
 * Date: 2020/6/13
 * Time: 16:13
 */

namespace App\Base;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Http\Message\Status;

class BaseController extends Controller
{

    /**
     * 业务处理成功返回
     * @param null $data
     * @param string $msg
     * @return bool
     * @throws ConnectFail
     * @throws OrderByFail
     * @throws PrepareQueryFail
     * @throws Throwable
     */
    protected function apiSuccess($data = null, $msg = '操作成功!')
    {
        $this->sendJson(Status::CODE_OK, $msg, $data);
        return true;
    }

    /**
     * 返回给客户端
     * @param $code
     * @param $msg
     * @param $date
     * @return bool
     */
    public function sendJson($code,$msg,$date)
    {
        $result = array(
            'code'=>$code,
            'messsage'=>$msg
        );
        if($date){
            $result['date'] = $date;
        }
        $this->response()->write(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
        $this->response()->withStatus(intval($code));
        return true;
    }

    /**
     * 获取post参数(get不行)
     * @return array|mixed
     */
    protected function getRequestParam()
    {
        return $this->request()->getRequestParam();
    }

}
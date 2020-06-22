<?php
/**
 * Created by PhpStorm.
 * User: Tioncico
 * Date: 2019/9/24 0024
 * Time: 16:16
 */

namespace Test;

use EasySwoole\Redis\Client;
use EasySwoole\Redis\Config\RedisConfig;
use EasySwoole\Redis\Redis;
use PHPUnit\Framework\TestCase;
use Swoole\Coroutine;

class UnixSocketRedisTest extends TestCase
{
    /**
     * @var $redis Redis
     */
    protected $redis;

    protected function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->redis = new Redis(new RedisConfig([
            'unixSocket' => REDIS_UNIX_SOCKET,
        ]));
        $this->redis->connect();
        //测试原始rawCommand
        $redis = $this->redis;

        $data = $redis->rawCommand(['set','a','1']);
        $this->assertEquals('OK',$data->getData());
        $data = $redis->rawCommand(['get','a']);
        $this->assertEquals('1',$data->getData());
        $redis->del('a');

    }

    function testConnect()
    {
        $this->assertTrue($this->redis->connect());
    }

    public function testAuth()
    {
        if (!empty(REDIS_AUTH)) {
            $this->assertTrue($this->redis->auth(REDIS_AUTH));
        }
        $this->assertTrue(true);
    }

    /**
     * key值操作测试
     * testKey
     * @author Tioncico
     * Time: 10:02
     */
    function testKey()
    {
        $redis = $this->redis;
        $key = 'test123213Key';
        $redis->select(0);
        $redis->set($key, 123);
        $data = $redis->dump($key);
        $this->assertTrue(!!$data);

        $data = $redis->dump($key . 'x');
        $this->assertNull($data);

        $data = $this->redis->exists($key);
        $this->assertEquals(1, $data);

        $data = $this->redis->expire($key, 1);
        $this->assertEquals(1, $data);
        Coroutine::sleep(2);
        $this->assertEquals(0, $this->redis->exists($key));

        $redis->expireAt($key, 1 * 100);
        Coroutine::sleep(0.1);
        $this->assertEquals(0, $this->redis->exists($key));

        $redis->set($key, 123);
        $data = $redis->keys("{$key}");
        $this->assertEquals($key, $data[0]);

        $redis->select(1);
        $redis->del($key);
        $redis->select(0);
        $data = $redis->move($key, 1);
        $this->assertEquals(1, $data);
        $data = $redis->exists($key);
        $this->assertEquals(0, $data);
        $redis->select(0);

        $redis->set($key, 123);
        $data = $redis->expire($key, 1);
        $this->assertEquals(1, $data);
        $data = $redis->persist($key);
        $this->assertEquals(1, $data);

        $redis->expire($key, 1);
        $data = $redis->pTTL($key);
        $this->assertLessThanOrEqual(1000, $data);

        $data = $redis->ttl($key);
        $this->assertLessThanOrEqual(1, $data);

        $data = $redis->randomKey();
        $this->assertTrue(!!$data);
        $data = $redis->rename($key, $key . 'new');
        $this->assertTrue($data);
        $this->assertEquals(1, $redis->expire($key . 'new'));
        $this->assertEquals(0, $redis->expire($key));

        $data = $redis->renameNx($key, $key . 'new');
        $this->assertEquals(0, $data);
        $redis->renameNx($key . 'new', $key);
        $data = $redis->renameNx($key, $key . 'new');
        $this->assertEquals(1, $data);
        $data = $redis->type($key);
        $this->assertEquals('none', $data);
        $data = $redis->type($key . 'new');
        $this->assertEquals('string', $data);

        //del单元测试新增
        $keyArr = ['a1','a2','a3','a4'];
        $valueArr = ['a1','a2','a3','a4'];
        $redis->mSet([
            $keyArr[0]=>$valueArr[0],
            $keyArr[1]=>$valueArr[1],
            $keyArr[2]=>$valueArr[2],
            $keyArr[3]=>$valueArr[3],
        ]);
        $data = $redis->del($keyArr[0]);
        $this->assertEquals(1,$data);


        $data = $redis->del($keyArr[1],$keyArr[2],$keyArr[3]);
        $this->assertEquals(3,$data);

        $redis->mSet([
            $keyArr[0]=>$valueArr[0],
            $keyArr[1]=>$valueArr[1],
            $keyArr[2]=>$valueArr[2],
            $keyArr[3]=>$valueArr[3],
        ]);
        $data = $redis->del([$keyArr[1],$keyArr[2],$keyArr[3]]);
        $this->assertEquals(3,$data);



        //unlink命令新增
        $keyArr = ['a1','a2','a3','a4'];
        $valueArr = ['a1','a2','a3','a4'];
        $redis->mSet([
            $keyArr[0]=>$valueArr[0],
            $keyArr[1]=>$valueArr[1],
            $keyArr[2]=>$valueArr[2],
            $keyArr[3]=>$valueArr[3],
        ]);
        $data = $redis->unlink($keyArr[0]);
        $this->assertEquals(1,$data);

        $data = $redis->unlink($keyArr[0].'test');
        $this->assertEquals(0,$data);

        $data = $redis->unlink($keyArr[1],$keyArr[2],$keyArr[3]);
        $this->assertEquals(3,$data);

        $redis->mSet([
            $keyArr[0]=>$valueArr[0],
            $keyArr[1]=>$valueArr[1],
            $keyArr[2]=>$valueArr[2],
            $keyArr[3]=>$valueArr[3],
        ]);
        $data = $redis->unlink([$keyArr[1],$keyArr[2],$keyArr[3]]);
        $this->assertEquals(3,$data);


    }

    /**
     * 字符串单元测试
     * testString
     * @author tioncico
     * Time: 下午9:41
     */
    function testString()
    {
        $redis = $this->redis;
        $key = 'test';
        $value = 1;
        $data = $redis->del($key);
        $this->assertNotFalse($data);
        $data = $redis->set($key, $value);
        $this->assertTrue($data);

        $redis->set($key, $value);
        $data = $redis->get($key);
        $this->assertEquals($data, $value);
        //set的其他作用测试(超时作用)
        $keyTTL=$key.'ttl';
        $redis->set($keyTTL,$value,20);
        $this->assertGreaterThan(18,$redis->ttl($keyTTL));
        $redis->set($keyTTL,$value,['EX'=>20]);
        $this->assertGreaterThan(18,$redis->ttl($keyTTL));
        $redis->set($keyTTL,$value,['PX'=>20000]);
        $this->assertGreaterThan(18000,$redis->pTTL($keyTTL));
        $this->assertGreaterThan(18,$redis->ttl($keyTTL));
        //set的其他作用测试(存在以及不存在时候设置)
        $keyExist = $key.'exist';
        $redis->del($keyExist);
        $data = $redis->set($keyExist,'1','XX');
        $this->assertNull($data);
        $data = $redis->set($keyExist,'1','NX');
        $this->assertTrue($data);
        $this->assertEquals('1',$redis->get($keyExist));
        $data = $redis->set($keyExist,'1','NX');
        $this->assertNull($data);
        $data = $redis->set($keyExist,'2','XX');
        $this->assertTrue($data);
        $this->assertEquals('2',$redis->get($keyExist));
        //set组合测试
        $data = $redis->set($keyExist,'3',['XX','EX'=>10]);
        $this->assertTrue($data);
        $this->assertEquals('3',$redis->get($keyExist));
        $this->assertGreaterThan(8,$redis->ttl($keyTTL));
        $data = $redis->set($keyExist,'3',['NX','EX'=>10]);
        $this->assertNull($data);
        $redis->del($keyExist);;
        $redis->set($keyExist,'4',['NX','EX'=>20]);
        $this->assertEquals('4',$redis->get($keyExist));
        $this->assertGreaterThan(18,$redis->ttl($keyTTL));

        $data = $redis->exists($key);
        $this->assertEquals(1, $data);

        $data = $redis->set($key, $value);
        $this->assertTrue($data);
        $value += 1;
        $data = $redis->incr($key);
        $this->assertEquals($value, $data);

        $value += 10;
        $data = $redis->incrBy($key, 10);
        $this->assertEquals($value, $data);

        $value -= 1;
        $data = $redis->decr($key);
        $this->assertEquals($value, $data);

        $value -= 10;
        $data = $redis->decrBy($key, 10);
        $this->assertEquals($value, $data);

        $key = 'stringTest';
        $value = 'tioncico';
        $redis->set($key, $value);

        $data = $redis->getRange($key, 1, 2);
        $this->assertEquals('io', $data);

        $data = $redis->getSet($key, $value . 'a');
        $this->assertEquals($data, $value);
        $redis->set($key, $value);

        $bitKey = 'testBit';
        $bitValue = 10000;
        $redis->set($bitKey, $bitValue);
        $data = $redis->setBit($bitKey, 1, 0);
        $this->assertEquals(0, $data);
        $data = $redis->getBit($key, 1);
        $this->assertEquals(1, $data);


        $field = [
            'stringField1',
            'stringField2',
            'stringField3',
            'stringField4',
            'stringField5',
        ];
        $value = [
            1,
            2,
            3,
            4,
            5,
        ];
        $data = $redis->mSet([
            "{$field[0]}" => $value[0],
            "{$field[1]}" => $value[1],
            "{$field[2]}" => $value[2],
            "{$field[3]}" => $value[3],
            "{$field[4]}" => $value[4],
        ]);
        $this->assertTrue($data);
        $data = $redis->mGet([$field[3], $field[2], $field[1]]);
        $this->assertEquals([$value[3], $value[2], $value[1]], $data);


        $data = $redis->setEx($key, 1, $value[0] . $value[0]);
        $this->assertTrue($data);
        $this->assertEquals($value[0] . $value[0], $redis->get($key));

        $data = $redis->pSetEx($key, 1, $value[0]);
        $this->assertTrue($data);
        $this->assertEquals($value[0], $redis->get($key));


        $redis->del($key);
        $data = $redis->setNx($key, 1);
        $this->assertEquals(1, $data);


        $redis->del($field[0]);
        $data = $redis->mSetNx([
            "{$field[0]}" => $value[0],
            "{$field[1]}" => $value[1],
        ]);
        $this->assertEquals(0, $data);
        $this->assertEquals($value[1], $redis->get($field[1]));
        $redis->del($field[1]);
        $data = $redis->mSetNx([
            "{$field[0]}" => $value[0] + 1,
            "{$field[1]}" => $value[1] + 1,
        ]);
        $this->assertEquals(1, $data);
        $this->assertEquals($value[0] + 1, $redis->get($field[0]));


        $data = $redis->setRange($field[0], 1, 1);
        $this->assertEquals(2, $data);
        $this->assertEquals('2' . $value[0], $redis->get($field[0]));

        $data = $redis->strLen($field[0]);
        $this->assertEquals(2, $data);

        $redis->set($key, 1);
        $data = $redis->incrByFloat($key, 0.1);
        $this->assertEquals(1.1, $data);
        $data = $redis->appEnd($field[0], '1');
        $this->assertEquals($redis->strLen($field[0]), $data);
        $this->assertEquals('2' . $value[0] . '1', $redis->get($field[0]));

        //迭代测试
        $cursor = 0;
        $redis->flushAll();
        $redis->set('xxxa', '仙士可');
        $redis->set('xxxb', '仙士可');
        $redis->set('xxxc', '仙士可');
        $redis->set('xxxd', '仙士可');
        $data = [];
        do {
            $keys = $redis->scan($cursor, 'xxx*', 1);
            $data = array_merge($data, $keys);
        } while ($cursor);
        $this->assertEquals(4, count($data));
    }

    /**
     * testHash
     * @author Tioncico
     * Time: 11:54
     */
    function testHash()
    {
        $key = 'hKey';
        $field = [
            'hField1',
            'hField2',
            'hField3',
            'hField4',
            'hField5',
        ];
        $value = [
            1,
            2,
            3,
            4,
            5,
        ];

        $redis = $this->redis;
        $redis->del($key);
        $data = $redis->hSet($key, $field[0], $value[0]);
        $this->assertNotFalse($data);

        $data = $redis->hGet($key, $field[0]);
        $this->assertEquals($data, $value[0]);

        $data = $redis->hExists($key, $field[0]);
        $this->assertEquals(1, $data);

        $data = $redis->hDel($key, $field[0]);
        $this->assertEquals(1, $data, $redis->getErrorMsg());

        $data = $redis->hExists($key, $field[0]);
        $this->assertEquals(0, $data);

        $data = $redis->hMSet($key, [
            "{$field[0]}" => $value[0],
            "{$field[1]}" => $value[1],
            "{$field[2]}" => $value[2],
            "{$field[3]}" => $value[3],
            "{$field[4]}" => $value[4],
        ]);
        $this->assertTrue($data);
        $data = $redis->hValS($key);
        sort($data);
        $this->assertEquals($value, $data);

        $data = $redis->hGetAll($key);
        $keyTmp = array_keys($data);
        sort($keyTmp);
        $this->assertEquals($field, $keyTmp);
        $valueTmp = array_values($data);
        sort($valueTmp);
        $this->assertEquals($value, $valueTmp);
        $this->assertEquals($value, [
            $data[$field[0]],
            $data[$field[1]],
            $data[$field[2]],
            $data[$field[3]],
            $data[$field[4]],
        ]);

        $data = $redis->hKeys($key);
        sort($data);
        $this->assertEquals($field, $data);

        $data = $redis->hLen($key);
        $this->assertEquals(count($field), $data);

        $data = $redis->hMGet($key, [$field[0], $field[1], $field[2]]);
        $this->assertEquals([1, 2, 3], $data);

        $data = $redis->hIncrBy($key, $field[4], 1);
        $this->assertEquals($value[4] + 1, $data);

        $data = $redis->hIncrByFloat($key, $field[1], 1.1);
        $this->assertEquals($value[1] + 1.1, $data);

        $data = $redis->hSetNx($key, $field[0], 1);
        $this->assertEquals(0, $data);

        $data = $redis->hSetNx($key, $field[0] . 'a', 1);
        $this->assertEquals(1, $data);
        $this->assertEquals(1, $redis->hGet($key, $field[0] . 'a'));


        $cursor = 0;
        $redis->del('a');
        $redis->hMSet('a', [
            'a' => 'tioncico',
            'b' => 'tioncico',
            'c' => 'tioncico',
            'd' => 'tioncico',
            'e' => 'tioncico',
            'f' => 'tioncico',
            'g' => 'tioncico',
            'h' => 'tioncico',
        ]);

        $data = [];
        do {
            $keys = $redis->hScan('a', $cursor);
            $data = array_merge($data, $keys);
        } while ($cursor);
        $this->assertEquals(8, count($data));
    }

    /**
     * testList
     * @author tioncico
     * Time: 下午8:17
     */
    function testList()
    {
        $redis = $this->redis;
        $key = [
            'listKey1',
            'listKey2',
            'listKey3',
        ];
        $value = [
            'a', 'b', 'c', 'd'
        ];

        $redis->flushAll();

        //测试null的时候
        $data = $redis->bLPop([$key[0], $key[1]], 1);
        $this->assertNull($data);
        $data = $redis->lPush($key[0], $value[0], $value[1]);
        $this->assertEquals(2, $data);

        //测试null的时候
        $data = $redis->bLPop([$key[1]], 1);
        $this->assertNull($data);
        $data = $redis->bRPop([$key[1]], 1);
        $this->assertNull($data);

        $data = $redis->bLPop([$key[0], $key[1]], 1);
        $this->assertTrue(!!$data);

        $data = $redis->bRPop([$key[0], $key[1]], 1);
        $this->assertTrue(!!$data);

        $redis->del($key[0]);
        $redis->lPush($key[0], $value[0], $value[1]);
        $data = $redis->bRPopLPush($key[0], $key[1], 1);
        $this->assertEquals($value[0], $data);

        $redis->del($key[0]);
        $redis->lPush($key[0], $value[0], $value[1]);
        $data = $redis->rPopLPush($key[0], $key[1]);
        $this->assertEquals($value[0], $data);

        $redis->del($key[0]);
        $redis->lPush($key[0], $value[0], $value[1]);
        $data = $redis->lIndex($key[0], 1);
        $this->assertEquals($value[0], $data);
        $data = $redis->lLen($key[0]);
        $this->assertEquals(2, $data);

        $data = $redis->lInsert($key[0], true, 'b', 'c');
        $this->assertEquals($redis->lLen($key[0]), $data);
        $data = $redis->lInsert($key[0], true, 'd', 'c');
        $this->assertEquals(-1, $data);


        $redis->del($key[1]);
        $data = $redis->rPush($key[1], $value[0], $value[2], $value[1]);
        $this->assertEquals(3, $data);


        $data = $redis->lRange($key[1], 0, 3);
        $this->assertEquals(count($data), 3);

        $data = $redis->lPop($key[1]);
        $this->assertEquals($value[0], $data);

        $data = $redis->rPop($key[1]);
        $this->assertEquals($value[1], $data);

        $data = $redis->lPuShx($key[1], 'x');
        $this->assertEquals($redis->lLen($key[1]), $data);
        $this->assertEquals('x', $redis->lPop($key[1]));

        $data = $redis->rPuShx($key[1], 'z');
        $this->assertEquals($redis->lLen($key[1]), $data);
        $this->assertEquals('z', $redis->rPop($key[1]));

        $redis->del($key[1]);
        $redis->rPush($key[1], $value[0], $value[0], $value[0]);
        $data = $redis->lRem($key[1], 1, $value[0]);
        $this->assertEquals(1, $data);

        $data = $redis->lSet($key[1], 0, 'xx');
        $this->assertTrue($data);
        $this->assertEquals('xx', $redis->lPop($key[1]));

        $data = $redis->lTrim($key[1], 0, 2);
        $this->assertTrue($data);
        $this->assertEquals(1, $redis->lLen($key[1]));
    }

    /**
     * 集合测试
     * testMuster
     * @author Tioncico
     * Time: 9:10
     */
    function testMuster()
    {
        $redis = $this->redis;
        $key = [
            'muster1',
            'muster2',
            'muster3',
            'muster4',
            'muster5',
        ];
        $value = [
            '1',
            '2',
            '3',
            '4',
        ];

        $redis->del($key[0]);
        $redis->del($key[1]);
        $data = $redis->sAdd($key[0], $value[0], $value[1]);
        $this->assertEquals(2, $data);

        $data = $redis->sCard($key[0]);
        $this->assertEquals(2, $data);

        $redis->sAdd($key[1], $value[0], $value[2]);

        $data = $redis->sDiff($key[0], $key[1]);
        $this->assertEquals([$value[1]], $data);

        $data = $redis->sDiff($key[1], $key[0]);
        $this->assertEquals([$value[2]], $data);

        $data = $redis->sMembers($key[0]);
        $this->assertEquals([$value[0], $value[1]], $data);
        $data = $redis->sMembers($key[1]);
        $this->assertEquals([$value[0], $value[2]], $data);

        $data = $redis->sDiffStore($key[2], $key[0], $key[1]);
        $this->assertEquals(1, $data);
        $data = $redis->sMembers($key[2]);
        $this->assertEquals([$value[1]], $data);

        $data = $redis->sInter($key[0], $key[1]);
        $this->assertEquals([$value[0]], $data);

        $data = $redis->sInterStore($key[3], $key[0], $key[1]);
        $this->assertEquals(1, $data);
        $this->assertEquals([$value[0]], $redis->sMembers($key[3]));

        $data = $redis->sIsMember($key[0], $value[0]);
        $this->assertEquals(1, $data);
        $data = $redis->sIsMember($key[0], $value[3]);
        $this->assertEquals(0, $data);

        $data = $redis->sMove($key[0], $key[1], $value[1]);
        $this->assertEquals(1, $data);

        $data = $redis->sPop($key[0]);
        $this->assertEquals(1, $data);

        $redis->del($key[3]);
        $redis->sAdd($key[3], $value[0], $value[1], $value[2], $value[3]);
        $data = $redis->sRandMember($key[3], 4);
        $this->assertEquals(4, count($data));

        $data = $redis->sRandMember($key[3].rand(1,1111));
        $this->assertEquals(null, $data);

        $data = $redis->sRandMember($key[3]);
        $this->assertTrue(in_array($data,$value));

        $data = $redis->sRem($key[3], $value[0], $value[1], $value[2], $value[3]);
        $this->assertEquals(4, $data);
        $this->assertEquals([], $redis->sMembers($key[3]));

        $data = $redis->sUnion($key[0], $key[1]);
        $this->assertEquals([$value[0], $value[1], $value[2]], $data);

        $redis->del($key[1]);
        $redis->del($key[2]);
        $redis->del($key[3]);
        $redis->del($key[4]);
        $redis->sAdd($key[1], 1, 2, 3, 4);
        $redis->sAdd($key[2], 5);
        $redis->sAdd($key[3], 6, 7);
        $data = $redis->sUnIonStore($key[4], $key[1], $key[2], $key[3]);
        $this->assertEquals(7, $data);


        $cursor = 0;
        $redis->del('a');
        $redis->sAdd('a', 'a1', 'a2', 'a3', 'a4', 'a5');
        $data = [];
        do {
            $keys = $redis->sScan('a', $cursor, '*', 1);
            $data = array_merge($data, $keys);
        } while ($cursor);
        $this->assertEquals(5, count($data));
    }

    /**
     * 有序集合测试
     * testSortMuster
     * @author Tioncico
     * Time: 14:17
     */
    function testSortMuster()
    {
        $redis = $this->redis;

        $key = [
            'sortMuster1',
            'sortMuster2',
            'sortMuster3',
            'sortMuster4',
            'sortMuster5',
        ];
        $member = [
            'member1',
            'member2',
            'member3',
            'member4',
            'member5',
        ];
        $score = [
            1,
            2,
            3,
            4,
        ];
        $redis->del($key[0]);
        $data = $redis->zAdd($key[0], $score[0], $member[0], $score[1], $member[1]);

        $this->assertEquals(2, $data);

        $data = $redis->zCard($key[0]);
        $this->assertEquals(2, $data);

        $data = $redis->zCount($key[0], 0, 3);
        $this->assertEquals(2, $data);

        $data = $redis->zInCrBy($key[0], 1, $member[1]);
        $this->assertEquals($score[1] + 1, $data);

        $redis->del($key[0]);
        $redis->del($key[1]);
        $redis->zAdd($key[0], $score[0], $member[0], $score[1], $member[1]);
        $redis->zAdd($key[1], $score[0], $member[0], $score[3], $member[3]);
        $data = $redis->zInTerStore($key[2], [$key[0], $key[1]], [1, 2]);
        $this->assertEquals(1, $data);

        $data = $redis->zLexCount($key[0], '-', '+');
        $this->assertEquals(2, $data);

        $redis->del($key[0]);
        $redis->zAdd($key[0], $score[0], $member[0], $score[1], $member[1], $score[2], $member[2]);
        $data = $redis->zRange($key[0], 0, -1, true);
        $this->assertEquals([
            $member[0] => $score[0],
            $member[1] => $score[1],
            $member[2] => $score[2],
        ], $data);
        $data = $redis->zRange($key[0], 0, -1, false);
        $this->assertEquals([
            $member[0],
            $member[1],
            $member[2],
        ], $data);

        $data = $redis->zRangeByLex($key[0], '-', '+');
        $this->assertEquals(3, count($data));

        $data = $redis->zRangeByScore($key[0], 2, 3, ['withScores' => true, 'limit' => array(0, 2)]);

        $this->assertEquals([
            $member[1] => $score[1],
            $member[2] => $score[2],
        ], $data);

        $data = $redis->zRangeByScore($key[0], 2, 3, ['withScores' => false, 'limit' => array(0, 2)]);
        $this->assertEquals([
            $member[1],
            $member[2],
        ], $data);

        $data = $redis->zRank($key[0], $member[1]);
        $this->assertEquals(1, $data);

        $data = $redis->zRem($key[0], $member[1], $member[2]);
        $this->assertEquals(2, $data);

        $redis->del($key[0]);
        $redis->zAdd($key[0], $score[0], $member[0], $score[1], $member[1], $score[2], $member[2]);
        $data = $redis->zRemRangeByLex($key[0], '-', '+');
        $this->assertEquals(3, $data);

        $redis->del($key[0]);
        $redis->zAdd($key[0], $score[0], $member[0], $score[1], $member[1], $score[2], $member[2]);
        $data = $redis->zRemRangeByRank($key[0], 0, 2);
        $this->assertEquals(3, $data);

        $redis->del($key[0]);
        $redis->zAdd($key[0], $score[0], $member[0], $score[1], $member[1], $score[2], $member[2]);
        $data = $redis->zRemRangeByScore($key[0], 0, 3);
        $this->assertEquals(3, $data);


        $redis->del($key[0]);
        $redis->zAdd($key[0], $score[0], $member[0], $score[1], $member[1], $score[2], $member[2]);
        $data = $redis->zRevRange($key[0], 0, 3);
        $this->assertEquals([
            $member[2],
            $member[1],
            $member[0],
        ], $data);
        $redis->del($key[0]);
        $redis->zAdd($key[0], $score[0], $member[0], $score[1], $member[1], $score[2], $member[2]);
        $data = $redis->zRevRange($key[0], 0, 3, true);
        $this->assertEquals([
            $member[2] => $score[2],
            $member[1] => $score[1],
            $member[0] => $score[0],
        ], $data);


        $redis->del($key[0]);
        $redis->zAdd($key[0], $score[0], $member[0], $score[1], $member[1], $score[2], $member[2]);
        $data = $redis->zRevRangeByScore($key[0], 3, 0, ['withScores' => true, 'limit' => array(0, 3)]);

        $this->assertEquals([
            $member[2] => $score[2],
            $member[1] => $score[1],
            $member[0] => $score[0],
        ], $data);
        $redis->del($key[0]);
        $redis->zAdd($key[0], $score[0], $member[0], $score[1], $member[1], $score[2], $member[2]);
        $data = $redis->zReVRangeByScore($key[0], 3, 0, ['withScores' => false, 'limit' => array(0, 3)]);
        $this->assertEquals([
            $member[2],
            $member[1],
            $member[0],
        ], $data);

        $data = $redis->zRevRank($key[0], $member[0]);
        $this->assertEquals(2, $data);

        $data = $redis->zScore($key[0], $member[0]);
        $this->assertEquals($score[0], $data);

        $redis->del($key[0]);
        $redis->del($key[1]);
        $redis->del($key[2]);
        $redis->zAdd($key[0], $score[0], $member[0], $score[1], $member[1]);
        $redis->zAdd($key[1], $score[0], $member[0], $score[3], $member[3]);

        $data = $redis->zUnionStore($key[2], [$key[1], $key[0]]);
        $this->assertEquals(3, $data);


        $cursor = 0;
        $redis->del('a');
        $redis->zAdd('a', 1, 'a1', 2, 'a2', 3, 'a3', 4, 'a4', 5, 'a5');
        $data = [];
        do {
            $keys = $redis->zScan('a', $cursor, '*', 1);
            $data = array_merge($data, $keys);
        } while ($cursor);
        $this->assertEquals(5, count($data));
    }

    /**
     * 基数统计 测试
     * testHyperLog
     * @author Tioncico
     * Time: 14:59
     */
    function testHyperLog()
    {
        $redis = $this->redis;

        $key = [
            'hp1',
            'hp2',
            'hp3',
            'hp4',
            'hp5',
        ];
        $redis->del($key[0]);
        $redis->del($key[1]);
        $data = $redis->pfAdd($key[0], [1, 2, 2, 3, 3]);
        $this->assertEquals(1, $data);

        $redis->pfAdd($key[1], [1, 2, 2, 3, 3]);
        $data = $redis->pfCount([$key[0], $key[1]]);
        $this->assertEquals(3, $data);

        $data = $redis->pfMerge($key[2], [$key[0], $key[1]]);
        $this->assertEquals(1, $data);
    }

    /**
     * 发布订阅测试
     * testSubscribe
     * @author Tioncico
     * Time: 14:59
     */
    function testSubscribe()
    {
//                $this->assertEquals(1,1);
        go(function () {
            $redis = new Redis(new RedisConfig([
                'host' => REDIS_HOST,
                'port' => REDIS_PORT,
                'auth' => REDIS_AUTH
            ]));
            $redis->pSubscribe(function (Redis $redis, $pattern, $str) {

                $redis2 = new Redis(new RedisConfig([
                    'host' => REDIS_HOST,
                    'port' => REDIS_PORT,
                    'auth' => REDIS_AUTH
                ]));
                var_dump($redis2);
                var_dump($redis2->set('a',2133));
                var_dump($redis2->get('a'));
                $this->assertEquals('test', $str);
                $data = $redis->unsubscribe('test');
                $this->assertTrue(!!$data);
                $redis->setSubscribeStop(true);
            }, 'test', 'test1', 'test2');
        });

        go(function () {
            $redis = new Redis(new RedisConfig([
                'host' => REDIS_HOST,
                'port' => REDIS_PORT,
                'auth' => REDIS_AUTH
            ]));
            $redis->subscribe(function (Redis $redis, $pattern, $str) {
                $this->assertEquals('test', $str);
                $data = $redis->unsubscribe('test');
                $this->assertTrue(!!$data);
                $redis->setSubscribeStop(true);
            }, 'test', 'test1', 'test2');
        });

        $redis = $this->redis;

        $data = $redis->pubSub('CHANNELS');
        $this->assertIsArray($data);
        Coroutine::sleep(1);
        $data = $redis->publish('test2', 'test');
        $this->assertGreaterThan(0, $data);
//
        $data = $redis->pUnSubscribe('test');
        $this->assertTrue(!!$data);


    }

    /**
     * 事务测试
     * testTransaction
     * @author tioncico
     * Time: 下午5:40
     */
    function testTransaction()
    {

        $redis = $this->redis;

        $data = $redis->multi();
        $this->assertTrue($data);
        $this->assertEquals(true, $redis->getTransaction()->isTransaction());
        $redis->del('ha');
        $data = $redis->hset('ha', 'a', 1);
        $this->assertEquals('QUEUED', $data);
        $data = $redis->hset('ha', 'b', '2');
        $this->assertEquals('QUEUED', $data);
        $data = $redis->hset('ha', 'c', '3');
        $this->assertEquals('QUEUED', $data);
        $data = $redis->hGetAll('ha');
        $this->assertEquals('QUEUED', $data);
        $data = $redis->exec();
        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $data[4]);
        $this->assertEquals(false, $redis->getTransaction()->isTransaction());

        $redis->multi();
        $this->assertEquals(true, $redis->getTransaction()->isTransaction());
        $data = $redis->discard();
        $this->assertEquals(true, $data);
        $this->assertEquals(false, $redis->getTransaction()->isTransaction());
        $data = $redis->watch('a', 'b', 'c');
        $data = $redis->unwatch();
        $this->assertEquals(1, $data);
    }

    /**
     * 管道测试
     * testTransaction
     * @author tioncico
     * Time: 下午5:40
     */
    function testPipe()
    {
        $this->assertEquals(1, 1);

        $redis = $this->redis;
        $redis->get('a');
        $data = $redis->startPipe();
        $this->assertTrue($data);
        $this->assertEquals(true, $redis->getPipe()->isStartPipe());
        $redis->del('ha');
        $data = $redis->hset('ha', "a", "a\r\nb\r\nc");
        $this->assertEquals('PIPE', $data);
        $data = $redis->hset('ha', 'b', '2');
        $this->assertEquals('PIPE', $data);
        $data = $redis->hset('ha', 'c', '3');
        $this->assertEquals('PIPE', $data);
        $data = $redis->hGetAll('ha');
        $this->assertEquals('PIPE', $data);
        $data = $redis->execPipe();

        $this->assertEquals(['a' => "a\r\nb\r\nc", 'b' => 2, 'c' => 3], $data[4]);
        $this->assertEquals(false, $redis->getPipe()->isStartPipe());

        $redis->startPipe();
        $this->assertEquals(true, $redis->getPipe()->isStartPipe());
        $data = $redis->set("a", '1');
        $this->assertTrue($data);
        $this->assertEquals('Set', ($redis->getPipe()->getCommandLog()[0][0]));
        $data = $redis->discardPipe();
        $this->assertEquals(true, $data);
        $this->assertEquals(false, $redis->getPipe()->isStartPipe());

    }

    /**
     * 脚本执行测试
     * testScript
     * @author tioncico
     * Time: 下午5:41
     */
    function testScript()
    {
        $this->assertEquals(1, 1);

        $redis = $this->redis;

//        $data = $redis->eval('s','s','a','1','2','a');
//        $this->assertEquals(1,$data);
//
//        $data = $redis->evalsha('a','g','g','1','a','a');
//        $this->assertEquals(1,$data);
//
//        $data = $redis->scriptExists('a','f');
//        $this->assertEquals(1,$data);
//
//        $data = $redis->scriptFlush();
//        $this->assertEquals(1,$data);
//
//        $data = $redis->scriptKill();
//        $this->assertEquals(1,$data);
//
//        $data = $redis->scriptLoad('a');
//        $this->assertEquals(1,$data);
    }

    /**
     * 服务器命令测试
     * testServer
     * @author tioncico
     * Time: 下午5:41
     */
    function testServer()
    {

        $redis = $this->redis;

        $data = $redis->bgRewriteAof();
        $this->assertEquals('Background append only file rewriting started', $data);
        Coroutine::sleep(1);
        $data = $redis->bgSave();
        $this->assertEquals('Background saving started', $data);
        $data = $redis->clientList();
        $this->assertIsArray($data);

        $data = $redis->clientSetName('test');
        $this->assertTrue($data);
        $data = $redis->clientGetName();
        $this->assertEquals('test', $data);

        $data = $redis->clientPause(1);
        $this->assertEquals(1, $data);

        $data = $redis->command();
        $this->assertIsArray($data);

        $data = $redis->commandCount();
        $this->assertGreaterThan(0, $data);

        $data = $redis->commandGetKeys('MSET', 'a', 'b', 'c', 'd');
        $this->assertEquals(['a', 'c'], $data);

        $data = $redis->time();
        $this->assertIsArray($data);

        $data = $redis->commandInfo('get', 'set');
        $this->assertIsArray($data);

        $data = $redis->configGet('*max-*-entries*');
        $this->assertIsArray($data);


        $data = $redis->configSet('appendonly', 'yes');
        $this->assertTrue($data);
        $data = $redis->configRewrite();
        $this->assertTrue($data);

        $data = $redis->configResetStat();
        $this->assertTrue($data);

        $data = $redis->dBSize();
        $this->assertGreaterThanOrEqual(0, $data);

        $redis->set('a', 1);
        $data = $redis->debugObject('a');
        $this->assertIsString($data);

        $data = $redis->flushAll();
        $this->assertTrue($data);

        $data = $redis->flushDb();
        $this->assertTrue($data);

        $data = $redis->info();
        $this->assertIsArray($data);

        $data = $redis->lastSave();
        $this->assertNotFalse($data);

        go(function () {
            $redis = new Redis(new RedisConfig([
                'host' => REDIS_HOST,
                'port' => REDIS_PORT,
                'auth' => REDIS_AUTH
            ]));
            $redis->monitor(function (Redis $redis, $data) {
                $this->assertIsString($data);
                $redis->set('a', 1);
                $redis->setMonitorStop(true);
            });
        });

        go(function () {
            $redis = new Redis(new RedisConfig([
                'host' => REDIS_HOST,
                'port' => REDIS_PORT,
                'auth' => REDIS_AUTH
            ]));
            Coroutine::sleep(1);
            $redis->set('a', 1);
        });

        $data = $redis->save();
        $this->assertEquals(1, $data);

        $data = $redis->clientKill($data[0]['addr']);
        $this->assertTrue($data);
        $data = $redis->slowLog('get', 'a');
        var_dump($data,$redis->getErrorMsg());
        $this->assertTrue(!!$data);

    }

    /**
     * geohash测试
     * testGeohash
     * @author tioncico
     * Time: 下午6:11
     */
    function testGeohash()
    {
        $redis = $this->redis;
        $key = 'testGeohash';

        $redis->del($key);
        $data = $redis->geoAdd($key, [
            ['118.6197800000', '24.88849', 'user1',],
            ['118.6197800000', '24.88859', 'user2',],
            ['114.8197800000', '25.88849', 'user3'],
            ['118.8197800000', '22.88849', 'user4'],
        ]);
        $this->assertEquals(4, $data);

        $data = $redis->geoAdd($key, [
            ['118.6197800000', '24.88869', 'user5',],
        ]);

        $data = $redis->geoDist($key, 'user1', 'user2');
        $this->assertGreaterThan(10, $data);

        $data = $redis->geoHash($key, 'user1', 'user2');
        $this->assertIsArray($data);

        $data = $redis->geoPos($key, 'user1', 'user2');
        $this->assertIsArray($data);

        $data = $redis->geoRadius($key, '118.6197800000', '24.88849', 100, 'm', false, false, false, null,'desc');
        $this->assertEquals(['user5','user2', 'user1'], $data);

        $data = $redis->geoRadiusByMember($key, 'user1', 100, 'm', false, false, false, 2,'DESC');
        //限制为2,并且desc
        $this->assertEquals(['user5', 'user2'], $data);


        $data = $redis->geoRadiusByMember($key, 'user1', 100, 'm', false, false, false, null,'asc');
        //不限制并且asc
        $this->assertEquals(['user1', 'user2', 'user5'], $data);

    }

}

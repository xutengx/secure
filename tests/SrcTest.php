<?php
declare(strict_types = 1);

use PHPUnit\Framework\TestCase;
use Xutengx\Cache\Driver\{Redis};
use Xutengx\Cache\Manager;
use Xutengx\Contracts\Cache\Driver;
use Xutengx\Secure\Secure;

final class SrcTest extends TestCase {

	/**
	 * 实例化Redis缓存驱动
	 * @return Redis
	 */
	public function testMakeRedisDriver(): Redis {
		$host                 = '127.0.0.1';
		$port                 = 6379;
		$password             = '';
		$database             = 0;
		$persistentConnection = false;
		$this->assertInstanceOf(Redis::class,
			$driver = new Redis($host, $port, $password, $database, $persistentConnection));
		return $driver;
	}

	/**
	 * 实例化Manager
	 * @depends testMakeRedisDriver
	 * @param Driver $Redis
	 * @return Manager
	 */
	public function testCacheManagerWithRedisDriver(Driver $Redis) {
		$this->assertInstanceOf(Manager::class, $Manager = new Manager($Redis));
		$this->assertEquals('redis', $Manager->getDriverName(), '当前缓存类型');
		return $Manager;
	}

	/**
	 * 实例化Secure
	 * @depends testCacheManagerWithRedisDriver
	 * @param Manager $cache
	 */
	public function testSecure(Manager $cache) {
		$this->assertInstanceOf(Secure::class, $Secure = new Secure($cache));

		$this->assertEquals($testString = 'tes', $Secure->decrypt($Secure->encrypt($testString)));
		$this->assertEquals($testString = 'tes123', $Secure->base64Decode($Secure::base64Encode($testString)));

		$lockKey = 'lockKey';
		$this->assertTrue($Secure->lockup($lockKey, 1));
		$this->assertFalse($Secure->lockup($lockKey, 1));
		$this->assertFalse($Secure->lockup($lockKey, 1));
		$this->assertFalse($Secure->lockup($lockKey, 1));
		$this->assertFalse($Secure->lockup($lockKey, 1));
		sleep(1);
		$this->assertTrue($Secure->lockup($lockKey, 1));
		$this->assertTrue($Secure->unlock($lockKey), '解除锁定');
		$this->assertTrue($Secure->lockup($lockKey, 1));
		$this->assertTrue($Secure->unlock($lockKey), '解除锁定');
		$this->assertTrue($Secure->lock($lockKey, function() use (&$result) {
			$result = 123;
		}, 1));
		$this->assertEquals($result, 123);

		try {
			$this->assertTrue($Secure->lock($lockKey, function() use (&$result) {
				throw new \Exception('error');
				$result = 321;
			}, 5));
		} catch (\Exception $exception) {
			$this->assertEquals('error', $exception->getMessage());
		} finally {
			$this->assertTrue($Secure->lockup($lockKey, 1), '闭包中抛出异常, 也可以正常解锁');
		}
		$this->assertEquals($result, 123);
		$this->assertTrue($Secure->unlock($lockKey), '解除锁定');

		try {
			$this->assertTrue($Secure->lock($lockKey, function() use (&$result) {
				throw new \Error('error');
				$result = 321;
			}, 5));
		} catch (\Error $exception) {
			$this->assertEquals('error', $exception->getMessage());
		} finally {
			$this->assertTrue($Secure->lockup($lockKey, 1), '闭包中抛出错误, 也可以正常解锁');
		}
	}

}



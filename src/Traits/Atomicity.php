<?php

declare(strict_types = 1);
namespace Xutengx\Secure\Traits;

use Closure;
use Error;
use Exception;

/**
 * 原子性
 */
trait Atomicity {

	/**
	 * 锁定一个闭包
	 * 锁定失败将即时返回false
	 * @param string $lockKey
	 * @param Closure $callback
	 * @param int $lockTime 最大锁定时间, 以避免意外情况下的长期锁定, 业务上应该使用稍大于业务处理时间的值
	 * @return bool
	 * @throws Exception
	 */
	public function lock(string $lockKey, Closure $callback, int $lockTime = 5): bool {
		if ($this->lockup($lockKey, $lockTime)) {
			try {
				$callback();
			} catch (Exception | Error $exc) {
				throw $exc;
			} finally {
				$this->unlock($lockKey);
			}
			return true;
		}
		return false;
	}

	/**
	 * 加锁
	 * @param string $lockKey
	 * @param int $lockTime 最大锁定时间, 以避免意外情况下的长期锁定, 业务上应该使用稍大于业务处理时间的值
	 * @return bool
	 * @throws Exception
	 */
	public function lockup(string $lockKey, int $lockTime = 5): bool {
		if ($this->cache->getDriverName() !== 'redis') {
			throw new Exception('lock() is dependent on Redis of Cache ');
		}
		// 当前时刻
		$time = time();
		// 未来`过期时刻`
		$timeOut = $time + $lockTime;
		// 尝试获取锁(设置值)
		$res = $this->cache->setnx($lockKey, $timeOut);
		// 锁成功!
		if ($res) {
			return true;
		}
		// 锁失败, 接下来排除一些异常原因导致的锁没有被释放的问题, 其标志为`锁过期`
		else {
			// 得到原锁的`过期时刻`
			$time_in_redis_old_first = $this->cache->get($lockKey);
			// 原锁已经过期, 或者此刻原锁被删除
			if ((int)$time_in_redis_old_first <= $time) {
				// 重新尝试获取锁(设置值)
				$time_in_redis_old_second = $this->cache->getset($lockKey, $timeOut);
				// 判断是否成功
				if ($time_in_redis_old_second === $time_in_redis_old_first) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * 解锁
	 * @param string $lockKey
	 * @return bool
	 */
	public function unlock(string $lockKey): bool {
		return $this->cache->rm($lockKey);
	}

}

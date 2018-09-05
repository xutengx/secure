<?php

declare(strict_types = 1);
namespace Xutengx\Secure;

use Xutengx\Cache\Manager as Cache;
use Xutengx\Secure\Traits\{Atomicity, Encryption};

/**
 * Class Secure
 * @package Xutengx\Secure
 */
class Secure {

	use Encryption, Atomicity;

	/**
	 * 缓存对象
	 * @var Cache
	 */
	protected $cache;

	/**
	 * 盐
	 * @var string
	 */
	protected $salt;

	/**
	 * Secure constructor.
	 * @param Cache $cache
	 * @param string $salt
	 */
	public function __construct(Cache $cache, string $salt = 'salt') {
		$this->cache = $cache;
		$this->salt  = $salt;
	}

	/**
	 * md5
	 * @param string $string
	 * @return string
	 */
	public static function md5(string $string): string {
		return md5($string . md5($string));
	}

	/**
	 * 过滤特殊(删除)字符
	 * @param string $string
	 * @param bool $is_strict 严格模式下, 将过滤更多
	 * @return string
	 */
	public static function symbol(string $string, bool $is_strict = false): string {
		$risk = '~^<>`\'"\\';
		$is_strict and $risk .= '@!#$%&?+-*/={}[]()|,.:;';
		$risk = str_split($risk, 1);
		return str_replace($risk, '', $string);
	}

}

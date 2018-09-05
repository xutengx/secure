<?php

declare(strict_types = 1);
namespace Xutengx\Secure\Traits;

/**
 * Trait Encryption 可逆加解密
 * @package Xutengx\Secure\Traits
 */
trait Encryption {

	/**
	 * URL安全的字符串编码
	 * @param string $string
	 * @return string
	 */
	public static function base64Encode(string $string): string {
		$data = base64_encode($string);
		$data = str_replace(['+', '/', '='], ['-', '_', ''], $data);
		return $data;
	}

	/**
	 * URL安全的字符串编码的解码
	 * @param string $string
	 * @return string
	 */
	public static function base64Decode(string $string): string {
		$data = str_replace(['-', '_'], ['+', '/'], $string);
		$mod4 = strlen($data) % 4;
		if ($mod4) {
			$data .= substr('====', $mod4);
		}
		return base64_decode($data);
	}

	/**
	 * 加密
	 * @param string $string
	 * @param string $salt
	 * @return string
	 */
	public function encrypt(string $string, string $salt = null): string {
		$key    = $this->md5($salt ?? $this->salt);
		$j      = 0;
		$buffer = $data = '';
		$length = strlen($string);
		for ($i = 0; $i < $length; $i++) {
			if ($j === 32) {
				$j = 0;
			}
			$buffer .= $key[$j];
			$j++;
		}
		for ($i = 0; $i < $length; $i++) {
			$data .= $string[$i] ^ $buffer[$i];
		}
		return $this->base64Encode($data);
	}

	/**
	 * 解密
	 * @param string $string
	 * @param string $salt
	 * @return string
	 */
	public function decrypt(string $string, string $salt = null): string {
		$key    = $this->md5($salt ?? $this->salt);
		$string = $this->base64Decode($string);
		$j      = 0;
		$buffer = $data = '';
		$length = strlen($string);
		for ($i = 0; $i < $length; $i++) {
			if ($j === 32) {
				$j = 0;
			}
			$buffer .= substr($key, $j, 1);
			$j++;
		}
		for ($i = 0; $i < $length; $i++) {
			$data .= $string[$i] ^ $buffer[$i];
		}
		return $data;
	}

}

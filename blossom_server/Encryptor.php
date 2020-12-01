<?php

/*

Blossom Server 1.3
Copyright (C) 2011 Jacob Grahn <contact@jiggmin.com>
See http://blossom-server.com/ for more information

Blossom Server is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Blossom Server is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with Blossom Server.  If not, see <http://www.gnu.org/licenses/>.

*/

class Encryptor {
	
	private static $algorithm = MCRYPT_RIJNDAEL_128;
	private static $mode = MCRYPT_MODE_CBC;
	private static $binary_key;
	private static $binary_iv;
	private static $old_binary_iv;
	
	public static function init($key) {
		$iv = self::generate_iv();
		self::set_iv($iv);
		self::$old_binary_iv = self::$binary_iv;
		self::set_key($key);
	}
	
	public static function set_key($base64_key) {
		self::$binary_key = base64_decode($base64_key);
	}
	
	public static function get_iv() {
		$base64_iv = base64_encode(self::$binary_iv);
		return($base64_iv);
	}
	
	public static function generate_iv() {
		$binary_iv = mcrypt_create_iv(mcrypt_get_iv_size(self::$algorithm, self::$mode), MCRYPT_RAND);
		$base64_iv = base64_encode($binary_iv);
		return($base64_iv);
	}
	
	public static function set_iv($base64_iv) {
		$binary_iv = base64_decode($base64_iv);
		self::$old_binary_iv = self::$binary_iv;
		self::$binary_iv = $binary_iv;
	}
	
	public static function encrypt($string) {
		$binary_encrypted = mcrypt_encrypt(self::$algorithm, self::$binary_key, $string, self::$mode, self::$binary_iv);
		$base64_encrypted = base64_encode($binary_encrypted);
		return $base64_encrypted;
	}
	
	public static function decrypt($base64_encrypted, $use_old_iv=false) {
		if($use_old_iv) {
			$iv = self::$old_binary_iv;
		}
		else {
			$iv = self::$binary_iv;
		}
		
		$binary_encrypted = base64_decode($base64_encrypted);
		$string = mcrypt_decrypt(self::$algorithm, self::$binary_key, $binary_encrypted, self::$mode, $iv);
		$string = rtrim($string, "\0");
		return $string;
	}
}

?>
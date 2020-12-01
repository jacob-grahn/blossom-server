/*

Blossom Socket 1.2
Copyright (C) 2011 Jacob Grahn <contact@jiggmin.com>
See http://blossom-server.com/ for more information

Blossom Socket is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Blossom Socket is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public License
along with Blossom Socket.  If not, see <http://www.gnu.org/licenses/>.

*/


/*-------------------------------------------------------------------------------------------------------------------
-- Encrypts and decrypts data in a way the server can duplicate
-------------------------------------------------------------------------------------------------------------------*/


package com.jiggmin.blossomSocket {
	
	import flash.utils.ByteArray;
	import com.hurlant.crypto.symmetric.AESKey;
	import com.hurlant.crypto.symmetric.CBCMode;
	import com.hurlant.crypto.symmetric.IVMode;
	import com.hurlant.util.Base64;
	
	public class Encryptor {
		
		private var mode:CBCMode;
		
		
		//--- the key should be set before trying to send or recieve data from an encrypted socket ---
		public function setKey(stringKey:String):void {
			var binaryKey:ByteArray = stringToBinary(stringKey);
			var padding:ZeroPad = new ZeroPad();
			var key:AESKey = new AESKey(binaryKey);
			mode = new CBCMode(key, padding);
		}
		
		
		//--- the remote connection will provide the IV ---
		public function setIV(stringIV:String):void {
			var binaryIV:ByteArray = stringToBinary(stringIV);
			IVMode(mode).IV = binaryIV;
		}
		
	
		
		//--- encrypt a string ---
		public function encrypt(string:String):String {
			var binary:ByteArray = new ByteArray();
			binary.writeUTFBytes(string);
			mode.encrypt(binary);
			var encrypted:String = binaryToString(binary);
			return(encrypted);
		}
		
		
		//--- decrypt a string ---
		public function decrypt(string:String):String {
			var binary:ByteArray = stringToBinary(string);
			mode.decrypt(binary);
			binary.position = 0;
			var decrypted:String = binary.readUTFBytes(binary.bytesAvailable);
			return(decrypted);
		}
		
		
		//--- convert a byte aray into a string ---
		private function binaryToString(binary:ByteArray):String {
			var string:String = Base64.encodeByteArray(binary);
			return(string);
		}
		
		
		//--- convert a string into a byte array ---
		private function stringToBinary(string:String):ByteArray {
			var binary:ByteArray = Base64.decodeToByteArray(string);
			return(binary);
		}
		
		
		//--- clean up ---
		public function remove():void {
			mode = null;
		}
	}
}
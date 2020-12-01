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
-- pads the end of a byte array with null bytes to create a full block of data that can be encrypted
-------------------------------------------------------------------------------------------------------------------*/

package com.jiggmin.blossomSocket {
	
	import com.hurlant.crypto.symmetric.IPad;
	import flash.utils.ByteArray;
	
	public class ZeroPad implements IPad {
		private var blockSize:uint;
		private var char:String = String.fromCharCode(0);
		
		public function ZeroPad(blockSize:uint=0) {
			this.blockSize = blockSize;
		}
		
		public function pad(a:ByteArray):void {
			while(a.length % blockSize != 0){
				a.writeUTFBytes(char);
			}
		}
		public function unpad(a:ByteArray):void {
			a.position = 0;
			var string:String = a.readUTFBytes(a.bytesAvailable);
			string.split(char).join("");
			a.writeUTFBytes(string);
		}

		public function setBlockSize(bs:uint):void {
			blockSize = bs;
		}

	}
}
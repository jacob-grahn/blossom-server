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
-- Handles the formatting involved with sending and receiving messages with the server
-------------------------------------------------------------------------------------------------------------------*/


package com.jiggmin.blossomSocket {
	
	import flash.events.Event;
	import flash.events.ProgressEvent;
	import com.adobe.serialization.json.JSON;
	
	public class CommandSocket extends BasicSocket {
		
		private var encryptor:Encryptor;
		private var sendBuffer:Array = new Array();
		
		private var encrypt:Boolean = false;
		private var readNum:int = 0;
		private var writeNum:int = 0;
		
		private var readBuffer:String = "";
		private var EOL:String = String.fromCharCode(4);
		
		public var traceTraffic:Boolean = false;
		
		//--- set up ---
		public function CommandSocket():void {
			encryptor = new Encryptor();
			addEventListener(BlossomEvent.RECEIVE_IV, receiveIVHandler, false, 0, true);
			addEventListener(BlossomEvent.READY, readyHandler, false, 0, true);
		}
		
		
		protected override function connectHandler(e:Event):void {
			super.connectHandler(e);
			
			var obj:Object = new Object();
			obj.type = "confirm_connection";
			send(obj);
		}
		
		
		//--- set the iv for encryption ---
		private function receiveIVHandler(be:BlossomEvent):void {
			if(encrypt == true) {
				var obj:Object = new Object();
				obj.type = "confirm_iv";
				send(obj);
			}
			
			var iv:String = be.iv;
			encryptor.setIV(iv);
			encrypt = true;
		}
		
		
		//--- send any buffered messages ---
		private function readyHandler(be:BlossomEvent):void {
			for each(var obj:Object in sendBuffer) {
				send(obj);
			}
			sendBuffer = new Array();
		}
		
		
		//--- set the private key used for encryption ---
		public function setKey(stringKey:String):void {
			encryptor.setKey(stringKey);
		}
		
		
		//--- read data when you get it ---
		protected override function socketDataHandler(e:ProgressEvent):void {
			readBytesAvailable();
			super.socketDataHandler(e);
		}
		
		
		
		
		
		
		//--- convert the vars object into a string, encrypt it, and then send ---
		protected function send(obj:Object):void {
			if(connected) {
				writeNum++;
				obj.write_num = writeNum;
	
				var strObj:String = JSON.encode(obj);
	
				var sendStr:String;
				if(encrypt){
					sendStr = encryptor.encrypt(strObj);
				}
				else{
					sendStr = strObj;
				}
				sendStr += EOL;
	
				write(sendStr);
				
				if(traceTraffic) {
					trace("write: "+ strObj);
					//trace("write encrypted: "+sendStr);
					//trace("");
				}
			}
			else {
				sendBuffer.push(obj);
			}
		}
		
		
		
		//--- reads all of the info available, adds it to the read buffer, and passes along any full message received ---
		protected function readBytesAvailable():void {
			var str:String = readUTFBytes(bytesAvailable);
			if(str != null){
				readBuffer += str;
				
				var index:int;
				var command:String;			
				
				while(true && connected){
					index = readBuffer.indexOf(EOL);
					if(index == -1){
						break;
					}
					command = readBuffer.substring(0, index);
					readBuffer = readBuffer.substr(index+1);
					
					receiveCommand(command);
				}
			}
		}
		
		
		//--- dispatches a BlossomEvent with the received comnand ---
		protected function receiveCommand(command:String):void {
			readNum++;
			
			//seperate the command from the messageNum
			var spaceIndex:int = command.indexOf(" ");
			var remoteSendNum:int = int(command.substr(0, spaceIndex));
			command = command.substr(spaceIndex+1);
			
			//if the messageNum does not equal the readCount, either a message has been lost or this is a hacking attempt
			//both are pretty bad
			if(remoteSendNum != readNum){
				throw new Error("readCount does not match messageNum. readNum: "+readNum+", remoteSendNum: "+remoteSendNum);
			}
			
			//decrypt
			var stringVars:String;
			if(encrypt) {
				stringVars = encryptor.decrypt(command);
			}
			else {
				stringVars = command;
			}
			
			if(traceTraffic) {
				//trace("recieved encrypted: "+command);
				trace("recieved: "+stringVars);
				//trace("");
			}
			
			//turn the string into an object
			var obj:Object = JSON.decode(stringVars);
			
			//handle the message
			handleMessage(obj);
		}
		
		
		//handle a received message
		protected function handleMessage(obj:Object):void {
			//figure out which event to call
			var type:String;
			if(obj.type != null && obj.type != ''){
				type = obj.type;
			}
			else if(obj.t != null && obj.t != '') {
				type = obj.t;
			}
			else {
				type = BlossomEvent.RECEIVE_MESSAGE;
			}
			
			//dispatch an event
			var blossomEvent:BlossomEvent = new BlossomEvent(type, obj, null);
			dispatchEvent(blossomEvent);
		}
		
		
		//--- clean everything up ---
		public override function remove():void {
			removeEventListener(BlossomEvent.RECEIVE_IV, receiveIVHandler);
			removeEventListener(BlossomEvent.READY, readyHandler);
			if(encryptor != null){
				encryptor.remove();
				encryptor = null;
			}
			super.remove();
		}
	}
}
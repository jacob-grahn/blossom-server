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
-- Handles the basics: connecting, closing, writing, reading, and connection error handling
-------------------------------------------------------------------------------------------------------------------*/



package com.jiggmin.blossomSocket {
	
	import flash.net.Socket;
	import flash.events.Event;
	import flash.events.IOErrorEvent;
	import flash.events.SecurityErrorEvent;
	import flash.events.ProgressEvent;
	import flash.system.Security;
	import flash.errors.IOError;
	
	public class BasicSocket extends Socket {

		
		//--- connects to a remote host on the specified port ---
		public override function connect(url:String, port:int):void {
			trace("connect: "+url+" "+port);
			addListeners();
			super.connect(url, port);
		}
		
		
		//--- sends a string to the remote host that this socket is hopefully connected to ---
		protected function write(string:String):void {
			try {
				writeUTFBytes(string);
				flush();
			}
			catch (e:Error) {
				trace("Error writing to socket: " + e);
			}
		}
		
		
		
		
		//--- handlers for all o' the listeners ---
		protected function connectHandler(e:Event):void {
			trace("Connected.");
		}
		
		protected function closeHandler(e:Event):void {
			trace("Disconnected.");
			remove();
		}

		protected function ioErrorHandler(e:IOErrorEvent):void {
			trace("Could not connect. This could be because: A: The server is broken. B: The internet is broken. C: Evil aliens.");
		}

		protected function securityErrorHandler(e:SecurityErrorEvent):void {
			trace("Coulden't get permission to connect to server.");
		}
		
		protected function emptySecurityErrorHandler(e:SecurityErrorEvent):void {
			//flash throws an error several seconds after the socket closes. WTF flash!
			//add this event listener after you have closed the socket and are done with it.
			//otherwise you get an error about not having an event listener for the security error.
		}

		protected function socketDataHandler(e:ProgressEvent):void {
			//trace(e);
		}
		
		private function addListeners():void {
			removeListeners();
			addEventListener(Event.CLOSE, closeHandler, false, 0, true);
			addEventListener(Event.CONNECT, connectHandler, false, 0, true);
			addEventListener(IOErrorEvent.IO_ERROR, ioErrorHandler, false, 0, true);
			addEventListener(SecurityErrorEvent.SECURITY_ERROR, securityErrorHandler, false, 0, true);
			addEventListener(ProgressEvent.SOCKET_DATA, socketDataHandler, false, 0, true);
		}
		
		private function removeListeners():void {
			removeEventListener(Event.CLOSE, closeHandler);
			removeEventListener(Event.CONNECT, connectHandler);
			removeEventListener(IOErrorEvent.IO_ERROR, ioErrorHandler);
			removeEventListener(SecurityErrorEvent.SECURITY_ERROR, securityErrorHandler);
			removeEventListener(ProgressEvent.SOCKET_DATA, socketDataHandler);
		}
		
		//--- disconnects from the remote host ---
		public function remove():void {
			removeListeners();
			addEventListener(SecurityErrorEvent.SECURITY_ERROR, emptySecurityErrorHandler, false, 0, true);
			if(connected){
				super.close();
			}
		}		
	}
}

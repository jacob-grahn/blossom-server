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
-- Creating an instance of this class will automatically attempt to join the specified room on the server
-- handles room messaging, and maintians a list of people in the room
-------------------------------------------------------------------------------------------------------------------*/


package com.jiggmin.blossomSocket {
	
	import flash.display.Sprite;
	
	public class BlossomRoom extends Sprite {
		
		private var socket:BlossomSocket;
		
		private var _roomName:String;
		private var _userArray:Array = new Array();
		private var _vars:Object;
		private var autoJoin:Boolean;
		private var autoLeave:Boolean;
		
		public function BlossomRoom(socket:BlossomSocket, roomName:String, pass:String="", roomType:String="", autoJoin:Boolean=true, autoLeave:Boolean=true, note:String=""):void {
			this.socket = socket;
			this.autoJoin = autoJoin;
			this.autoLeave = autoLeave;
			_roomName = roomName;
			
			socket.addEventListener(BlossomEvent.ERROR + _roomName, errorHandler, false, 0, true);
			socket.addEventListener(BlossomEvent.RECEIVE_ROOM_VARS + _roomName, receiveRoomVarsHandler, false, 0, true);
			socket.addEventListener(BlossomEvent.USER_JOIN_ROOM + _roomName, userJoinRoomHandler, false, 0, true);
			socket.addEventListener(BlossomEvent.USER_LEAVE_ROOM + _roomName, userLeaveRoomHandler, false, 0, true);
			socket.addEventListener(BlossomEvent.RECEIVE_MESSAGE + _roomName, receiveMessageHandler, false, 0 ,true);
			
			if(autoJoin) {
				socket.joinRoom(_roomName, pass, roomType, note);
			}
		}
		
		
		
		//--- get ---
		public function get roomName():String {
			return(_roomName);
		}
		
		public function get userArray():Array {
			var array:Array = new Array();
			for each(var user:BlossomUser in _userArray) {
				if(user != null) {
					array.push(user);
				}
			}
			return(array);
		}
		
		public function get vars():Object {
			return(_vars);
		}
		
		public function getVars(varList:*):void {
			socket.getRoomVars(varList, _roomName);
		}
		
		public function socketIDToUser(socketID:int):BlossomUser {
			var user:BlossomUser = _userArray[socketID];
			return(user);
		}
		
		public function userIDToUser(userID:int):BlossomUser {
			var ret:BlossomUser;
			var array:Array = this.userArray;
			for each(var user:BlossomUser in array) {
				if(user.userID == userID) {
					ret = user;
					break;
				}
			}
			return(ret);
		}
		
		public function sendToRoom(data:*, sendToSelf:Boolean=true, type:String=null):void {
			var raw:Object = new Object();
			if(type != null) {
				raw.type = type;
			}
			raw.data = data;

			socket.sendToRoom(_roomName, raw, sendToSelf);
		}
		
		public function setPass(pass:String):void {
			socket.setRoomPass(_roomName, pass);
		}
		
		public function setVars(vars:Object) {
			socket.setRoomVars(vars, _roomName);
		}
		
		public function deleteVars(varList:*):void {
			socket.deleteRoomVars(varList, _roomName);
		}
		
		public function lockVars():void {
			socket.lockRoomVars(_roomName);
		}
		
		public function unlockVars():void {
			socket.unlockRoomVars(_roomName);
		}
	
		
		
		
		//--- remote handlers ---
		private function errorHandler(be:BlossomEvent):void {
			var error:String = be.error;
			dispatchEvent(new BlossomEvent(BlossomEvent.ERROR, be.raw));
		}
		
		private function receiveRoomVarsHandler(be:BlossomEvent):void {
			_vars = be.vars;
			dispatchEvent(new BlossomEvent(BlossomEvent.RECEIVE_ROOM_VARS, be.raw));
		}
		
		private function userJoinRoomHandler(be:BlossomEvent):void {
			var user:BlossomUser = new BlossomUser(socket, be.socketID, be.userID, be.userName, be.moderator, be.vars);
			_userArray[be.socketID] = user;
			
			dispatchEvent(new BlossomEvent(BlossomEvent.USER_JOIN_ROOM, be.raw, user));
		}
		
		private function userLeaveRoomHandler(be:BlossomEvent):void {
			var socketID:int = be.socketID;
			var user:BlossomUser = _userArray[socketID];
			_userArray[socketID] = null;
			
			if(user != null){
				dispatchEvent(new BlossomEvent(BlossomEvent.USER_LEAVE_ROOM, be.raw, user));
				user.remove();
			}
			
			//if you leave this room, kill it
			if(socketID == socket.socketID) {
				remove();
			}
		}
		
		private function receiveMessageHandler(be:BlossomEvent):void {
			var raw:Object = be.data;
			var socketID:int = be.socketID;
			var user:BlossomUser = _userArray[socketID];
			
			raw.socketID = socketID;
			
			var type:String;
			if(raw.type == null || raw.type == ''){
				type = BlossomEvent.RECEIVE_MESSAGE;
			}
			else{
				type = raw.type;
			}

			dispatchEvent(new BlossomEvent(type, raw, user));
		}
		
		
		
		
		//--- clean up ---
		public function leaveRoom():void {
			remove();
		}
		
		
		public function remove():void {
			if(socket != null) {
				if(autoLeave) {
					socket.leaveRoom(_roomName);
				}
				
				socket.removeEventListener(BlossomEvent.ERROR + _roomName, errorHandler);
				socket.removeEventListener(BlossomEvent.RECEIVE_ROOM_VARS + _roomName, receiveRoomVarsHandler);
				socket.removeEventListener(BlossomEvent.USER_JOIN_ROOM + _roomName, userJoinRoomHandler);
				socket.removeEventListener(BlossomEvent.USER_LEAVE_ROOM + _roomName, userLeaveRoomHandler);
				socket.removeEventListener(BlossomEvent.RECEIVE_MESSAGE + _roomName, receiveMessageHandler);
				
				socket = null;
			}
			_userArray = null;
		}
	}
}
# BlossomSocket
Package: `com.jiggmin.blossomSocket`\
Class: `public class BlossomSocket`\
Inheritance: `Socket -> BasicSocket -> CommandSocket -> BlossomSocket`\

### Public Properties

`connected : Boolean`\
[read-only] Indicates whether this `BlossomSocket` object is currently connected.

`traceTraffic : Boolean`\
If set to `true`, all traffic that is sent or received will be traced. Handy for debugging.

`socketID : int`\
[read-only] The socket id assigned to your connection by the remote host.

`pingTime : int`\
[read-only] The time in milliseconds it takes a message to travel from the client to the host and back again.

### Public Methods

`BlossomSocket(host:String, port:int, encryptionKey:String) : void`\
Creates a `BlossomSocket` object. A connection will automatically be established with the specified host.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");

socket.addEventListener(BlossomEvent.READY, readyHandler, false, 0, true);
socket.addEventListener(IOErrorEvent.IO_ERROR, ioErrorHandler, false, 0, true);
socket.addEventListener(SecurityErrorEvent.SECURITY_ERROR, securityErrorHandler, false, 0, true);

private function readyHandler(be:BlossomEvent):void {
	trace("Successful connection!");
}
private function ioErrorHandler(e:IOErrorEvent):void {
	trace("Could not connect.");
}
private function securityErrorHandler(e:SecurityErrorEvent):void {
	trace("Could not get permission to connect to the server.");
}
```

`register(userName:String, pass:String, email:String="") : void`\
Register a new account.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var userName:String = "William";
var pass:String = "1234";
var email:String = "2@2.com";

socket.register(userName, pass, email);

socket.addEventListener(BlossomEvent.REGISTER_SUCCESS, registerSuccessHandler, false, 0, true);
socket.addEventListener(BlossomEvent.REGISTER_ERROR, registerErrorHandler, false, 0, true);

private function registerSuccessHandler(be:BlossomEvent):void {
	trace("The account registration was a grand success!");
}
private function registerErrorHandler(be:BlossomEvent):void {
	trace("Error registering: " + be.error));
}
```

`login(userName:String, pass:String) : void`\
Log into an existing account. Any data previously saved to this account will automatically be loaded in.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var userName:String = "William";
var pass:String = "1234";

socket.login(userName, pass);

socket.addEventListener(BlossomEvent.LOGIN_SUCCESS, loginSuccessHandler, false, 0, true);
socket.addEventListener(BlossomEvent.LOGIN_ERROR, loginErrorHandler, false, 0, true);

private function loginSuccessHandler(be:BlossomEvent):void {
	var userID:int = be.userID;
	var name:String = be.name;
	var moderator:Boolean = be.moderator;
	var vars:Object = be.vars; //contains variables user saved to their account previously
	trace("Login successful! userID: " + userID + ", name: " + name + ", moderator: " + moderator);
}	
private function loginErrorHandler(be:BlossomEvent):void {
	trace("Error logging in: " + be.error));
}
```

`sendPing() : void`\
Ping the remote host. The host will ping back, updating the `pingTime` property.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");

socket.sendPing();

socket.addEventListener(BlossomEvent.PING, pingHandler, false, 0, true);

private function pingHandler(be:BlossomEvent):void {
	var sentTime:int = be.time;
	var curTime:int = new Date().time;
	var elapsedTime:int = curTime - sentTime;
	trace("Ping received! The round trip took " + elapsedTime + "ms.");
}
```

`getRooms() : void`\
Ask the remote host for a list of existing rooms.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");

socket.getRooms();

socket.addEventListener(BlossomEvent.RECEIVE_ROOMS, receiveRoomsHandler, false, 0, true);

private function receiveRoomsHandler(be:BlossomEvent):void {
	var roomArray:Array = be.roomList;
	var len:int = roomArray.length;
	var i:int;
	var room:Object;
	
	for(i=0; i<len; i++){
		room = roomArray[i];
		trace(room.roomName + " - " + room.members);
	}
}
```

`joinRoom(roomName:String, pass:String="") : void`\
Join a room. Any data users send to this room will now be sent to you as well. If the room is password protected, a password will be needed to successfully join. While you can call this function directly, I recommend you use an instance of the `BlossomRoom` class instead.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var roomName:String = "the Greatest Room Ever";

socket.joinRoom(roomName);
```

`leaveRoom(roomName:String) : void`\
Leave a room. You will no longer receive data sent to this room. While you can call this function directly, I recommend you use an instance of the `BlossomRoom` class instead.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var roomName:String = "the Greatest Room Ever";

socket.joinRoom(roomName);
socket.leaveRoom(roomName);
```

`setRoomPass(roomName:String, pass:String) : void`\
Password protect a room. This will have no effect if a password has already been set. While you can call this function directly, I recommend you use an instance of the `BlossomRoom` class instead.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var roomName:String = "the Greatest Room Ever";
var pass:String = "1234";

socket.joinRoom(roomName);
socket.setRoomPass(roomName, pass);
```

`sendToRoom(roomName:String, data:*, sendToSelf:Boolean=false) : void`\
Send some form of data to the other members of a room. If `sendToSelf` is `true`, the data will also be sent back to you. While you can call this function directly, I recommend you use an instance of the `BlossomRoom` class instead.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var roomName:String = "the Greatest Room Ever";
var data:* = "Hello everyone!";
var sendToSelf:Boolean = true;

socket.joinRoom(roomName);
socket.sendToRoom(roomName, data, sendToSelf);

socket.addEventListener(BlossomEvent.RECEIVE_MESSAGE, receiveMessageHandler, false, 0 ,true);

private function receiveMessageHandler(be:BlossomEvent):void {
	var data:* = be.data;
	trace("Received data: " + data);
}
```

`sendToUser(socketID:int, data:*) : void`\
Send some form of data to another user.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var socketID:int = 20;
var data:* = "That is uncanny.";

socket.sendToUser(socketID, data);
```

`setUserVars(variables:Object) : void`\
Store an object on the remote host. If you are logged in, this data will be saved to your account.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");

var vars:Object = new Object();
vars.favoriteMovie = "Shawshank Redemption";
vars.favoriteBand = "Imogen Heap";
vars.favoriteColor = "Green";

socket.setUserVars(vars);
```

`setRoomVars(variables:Object, roomName:String) : void`\
Store an object in the specified room. Other players can also access and update this object. While you can call this function directly, I recommend you use an instance of the `BlossomRoom` class instead.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var roomName:String = "A Room Lalala";

var vars:Object = new Object();
vars.roomColor = "blue";
vars.states = new Array("Alaska", "New Mexico");

socket.joinRoom(roomName);
socket.setRoomVars(vars, roomName);
```

`setGlobalVars(variables:Object) : void`\
Store an object that is accessible to every user.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");

var vars:Object = new Object();
vars.season = "Summer";
vars.language = "English";

socket.setGlobalVars(vars);
```

`getUserVars(varList:*, socketID:int) : void`\
Get the specified variables from the user specified by `socketID`.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var socketID:int = 20;
var varList:* = new Array("favoriteMovie", "favoriteBand", "favoriteColor");

socket.getUserVars(varList, socketID);

socket.addEventListener(BlossomEvent.RECEIVE_USER_VARS, receiveUserVarsHandler, false, 0, true);

private function receiveUserVarsHandler(be:BlossomEvent):void {
	var vars:Object = be.vars;
}
```

`getRoomVars(varList:*, roomName:String) : void`\
Get the specified variables from a room. While you can call this function directly, I recommend you use an instance of the `BlossomRoom` class instead.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var roomName:String = "Panda Central";
var varList:* = "*";

socket.joinRoom(roomName);
socket.getRoomVars(varList, roomName);

socket.addEventListener(BlossomEvent.RECEIVE_ROOM_VARS, receiveRoomVarsHandler, false, 0, true);

private function receiveRoomVarsHandler(be:BlossomEvent):void {
	var vars:Object = be.vars;
}
```

`getGlobalVars(varList:*) : void`\
Get the specified variables. `varList` can be either an array of variable names to retrieve, or the string `*` can be used to return all remote variables.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var varList:* = "*";

socket.getGlobalVars(varList, roomName);

socket.addEventListener(BlossomEvent.RECEIVE_GLOBAL_VARS, receiveGlobalVarsHandler, false, 0, true);

private function receiveGlobalVarsHandler(be:BlossomEvent):void {
	var vars:Object = be.vars;
}
```

`deleteUserVars(varList:*) : void`\
Delete variables the user has stored on their account.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var varList:* = new Array("favoriteBand", "favoriteMovie");

socket.deleteUserVars(varList);
```

`deleteRoomVars(varList:*, roomName:String) : void`\
Delete variables stored in a room. While you can call this function directly, I recommend you use an instance of the `BlossomRoom` class instead.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var varList:* = "*";
var roomName:String = "Wordz and Stuff";

socket.joinRoom(roomName);
socket.deleteRoomVars(varList, roomName);
```

`deleteGlobalVars(varList:*) : void`\
Delete variables stored globally.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var varList:* = "*";

socket.deleteGlobalVars(varList);
```

`lockUserVars() : void`\
Lock your variables. They can still be read, but they cannot be changed.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
socket.lockUserVars();
```

`lockRoomVars(roomName:String) : void`\
Lock a room's variables. They can still be read, but they cannot be changed. While you can call this function directly, I recommend you use an instance of the `BlossomRoom` class instead.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var roomName:String = "Mighty Mastermind";

socket.joinRoom(roomName);
socket.lockRoomVars(roomName);
```

`lockGlobalVars() : void`\
Lock the global variables. They can still be read, but they cannot be changed.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
socket.lockGlobalVars();
```

`unlockUserVars() : void`\
Unlock your variables.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
socket.unlockUserVars();
```

`unlockRoomVars(roomName:String) : void`\
Unlock a room's variables. While you can call this function directly, I recommend you use an instance of the `BlossomRoom` class instead.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
var roomName:String = "Bob's Hut";

socket.joinRoom(roomName);
socket.unlockRoomVars(roomName);
```

`unlockGlobalVars() : void`\
Unlock the global variables.

```as3
var socket:BlossomSocket = new BlossomSocket("208.78.96.138", 1192, "NmojVTk3NFVWNStRRDRrNA==");
socket.unlockGlobalVars();
```

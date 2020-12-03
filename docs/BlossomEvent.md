# BlossomEvent

Package: `com.jiggmin.blossomSocket`<br />
Class: `public class BlossomEvent`<br />
Inheritance: `Event` -> `BlossomEvent`<br />

### Public Properties
All public properties are read-only.

`raw`					: `Object`<sub>1</sub><br />
`fromUser`		: `BlossomUser`<br />
`socketID`		: `int`<br />
`roomName`		: `String`<br />
`data`				: `*`<br />
`vars`				: `Object`<br />
`error`				: `String`<br />
`roomList`		: `Array`<br />
`userName`		: `String`<br />
`userID`			: `int`<br />
`moderator`		: `Boolean`<br />
`iv`					: `String`<br />
`version`			: `Number`<br />
`time`				: `int`

<sub>1</sub> The data object recieved from the server. The data from the raw will be automatically transferred to the BlossomEvent properties.

### Public Methods

None

### Public Constants
All public constants are read-only.

`RECEIVE_SOCKET_ID`		: `String` = "receiveSocketID"<br />
`RECEIVE_USER_VARS`		: `String` = "receiveUserVars"<br />
`RECEIVE_ROOM_VARS`		: `String` = "receiveRoomVars"<br />
`RECEIVE_GLOBAL_VARS` : `String` = "receiveGdlobalVars"<br />
`RECEIVE_IV` 					: `String` = "receiveIV"<br />
`RECEIVE_VERSION`			: `String` = "receiveVersion"<br />
`RECEIVE_ROOMS`				: `String` = "receiveRooms"<br />
`RECEIVE_MESSAGE`			: `String` = "receiveMessage"<br />
`USER_JOIN_ROOM`			: `String` = "userJoinRoom"<br />
`USER_LEAVE_ROOM`			: `String` = "userLeaveRoom"<br />
`ERROR`								: `String` = "error"<br />
`READY`								: `String` = "ready"<br />
`REGISTER_SUCCESS`		: `String` = "registerSuccess"<br />
`REGISTER_ERROR`			: `String` = "registerError"<br />
`LOGIN_SUCCESS`				: `String` = "loginSuccess"<br />
`LOGIN_ERROR`					: `String` = "loginError"<br />
`PING`								: `String` = "ping"

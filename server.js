require('dotenv').config();

const express = require("express");
var app       = express();
var request   = require('request');
var cors      = require('cors');

const http         = require('http').Server(app);
const bodyParser   = require('body-parser');
const port         = process.env.SOCKET_PORT || 3000;
const socketUrl    = process.env.SOCKET_URL;
const broadCastUrl = process.env.SOCKET_BRODCASTURL;
const endUrl       = socketUrl + ":" + port
app.use(cors());
app.use(bodyParser.json());

const io = require('socket.io')(http, {
    allowEIO3: true,
    cors: {
        origin: "*",
        methods: ["GET", "POST"],
        credentials: true
    }
});


const onlineUsers = {};

// io.on('connection', (socket) => {
//     console.log('A user connected:', socket.id);
//     socket.on('register', (userId) => {
//         onlineUsers[userId] = socket.id;
//         console.log(`User ${userId} registered with socket ID ${socket.id}`);
//     });

//     socket.on('disconnect', () => {
//         for (const [userId, sockId] of Object.entries(onlineUsers)) {
//             if (sockId === socket.id) {
//                 delete onlineUsers[userId];
//                 console.log(`User ${userId} disconnected`);
//                 break;
//             }
//         }
//     });
// });
io.on('connection', (socket) => {
    socket.on("disconnect", (reason) => {
        if (socket?.id) {  socket.leave(socket.id); }
    })
    if (socket && socket.handshake && socket.handshake.query) {
        if ((socket.handshake.query.id)) {
        socket.join(parseInt(socket.handshake.query.id.toString()))
        // console.log('socket',socket.adapter.rooms)
        socket.leave(socket.id);
        }

    }
});
app.get('/message', (req, res) => {
       console.log('message called')
    const customMessage = JSON.parse(req.query.customMessage || '{}');
    const { from_id, to_id, message: body, project, dispute_id, project_id,chatAttachments, fromUserProfile} = customMessage;

    // const toSocketId = onlineUsers[to_id];

    const messagePayload = {
        from_id,
        to_id,
        message: body,
        project,
        dispute_id,
        project_id,
        chatAttachments,
        fromUserProfile
    };

    if (to_id) {
        // io.emit('disputechatchannel', messagePayload);
        io.to(parseInt(to_id)).emit('disputechatchannel', messagePayload);
        console.log(`Message sent to user ${to_id}`);
    } else {
        console.log(`User ${to_id} is not connected`);
    }

    return res.status(200).json({
        status: true,
        message: "Message processed",
        data: messagePayload
    });
});

function getStateMessage(state, customMessage = null) {
    if (customMessage) {
        return customMessage;
    }
    const messages = {
        0: "Invitation Sent",
    };
    return messages[state] || "Unknown state.";
}

app.get('/broadcast', async (req, res) => {
    console.log('broadcast called')
    const { channel, state, customMessage } = req.query;

    if (channel && (state || customMessage)) {
        let message;

        try {
            message = JSON.parse(customMessage);
        } catch (e) {
            message = getStateMessage(parseInt(state, 10), customMessage);
        }

        io.emit(channel, { state, message });

        res.status(200).json({
            status: true,
            message: 'Broadcast success',
            data: { state, message }
        });
    } else {
        res.status(400).json({
            status: false,
            message: 'Invalid request: channel and state are required.'
        });
    }
});

global.io = io;
http.listen(port, () => {
    console.log('Server is running. Port: ' + port);
    console.log('Server is running. Url: ' + socketUrl);
});

import express from 'express';
import { createServer } from 'http';
import { Server } from 'socket.io';

const app = express();
const httpServer = createServer(app);

const PORT = process.env.SOCKET_PORT || 6001;

const io = new Server(httpServer, {
    cors: {
        origin: '*',
        methods: ['GET', 'POST']
    }
});

app.get('/health', (req, res) => {
    res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// User socket map: userId -> Set(socket.id)
const userSockets = new Map();

io.on('connection', (socket) => {
    console.log(`[Socket.IO] Client connected: ${socket.id}`);

    // Register user to their private room
    socket.on('register-user', (userId) => {
        if (!userId) return;
        const uid = String(userId);
        const roomName = `user_${uid}`;

        socket.userId = uid;
        socket.join(roomName);

        if (!userSockets.has(uid)) {
            userSockets.set(uid, new Set());
        }
        userSockets.get(uid).add(socket.id);

        console.log(`[Socket.IO] User ${uid} registered on socket ${socket.id} in room ${roomName}`);
        socket.emit('registered', { userId: uid, room: roomName });
    });

    // Signaling: Outgoing call initiation
    socket.on('call-user', (data) => {
        const { receiverId, call, callerName, callerRole } = data;
        const recipientRoom = `user_${receiverId}`;
        console.log(`[Socket.IO] Relay call-user from ${socket.userId} to ${receiverId}`);

        io.to(recipientRoom).emit('incoming-call', {
            call,
            callerName,
            callerRole,
            callerId: socket.userId
        });
    });

    // Signaling: Accept call
    socket.on('accept-call', (data) => {
        const { callerId, call } = data;
        console.log(`[Socket.IO] Relay accept-call from ${socket.userId} to ${callerId}`);

        io.to(`user_${callerId}`).emit('call-accepted', {
            call,
            receiverId: socket.userId
        });
    });

    // Signaling: Reject call
    socket.on('reject-call', (data) => {
        const { callerId, call } = data;
        console.log(`[Socket.IO] Relay reject-call from ${socket.userId} to ${callerId}`);

        io.to(`user_${callerId}`).emit('call-rejected', {
            call,
            receiverId: socket.userId
        });
    });

    // Signaling: User busy
    socket.on('busy-call', (data) => {
        const { callerId, call } = data;
        console.log(`[Socket.IO] Relay busy-call from ${socket.userId} to ${callerId}`);

        io.to(`user_${callerId}`).emit('user-busy', {
            call,
            receiverId: socket.userId
        });
    });

    // Signaling: End call
    socket.on('end-call', (data) => {
        const { recipientId, call } = data;
        console.log(`[Socket.IO] Relay end-call from ${socket.userId} to ${recipientId}`);

        io.to(`user_${recipientId}`).emit('call-ended', {
            call,
            endedBy: socket.userId
        });
    });

    // WebRTC Offer
    socket.on('offer', (data) => {
        const { recipientId, offer, callId } = data;
        console.log(`[Socket.IO] Relay offer from ${socket.userId} to ${recipientId}`);

        io.to(`user_${recipientId}`).emit('offer-created', {
            offer,
            callId,
            senderId: socket.userId
        });
    });

    // WebRTC Answer
    socket.on('answer', (data) => {
        const { recipientId, answer, callId } = data;
        console.log(`[Socket.IO] Relay answer from ${socket.userId} to ${recipientId}`);

        io.to(`user_${recipientId}`).emit('answer-created', {
            answer,
            callId,
            senderId: socket.userId
        });
    });

    // WebRTC ICE Candidate
    socket.on('ice-candidate', (data) => {
        const { recipientId, candidate, callId } = data;
        console.log(`[Socket.IO] Relay ice-candidate from ${socket.userId} to ${recipientId}`);

        io.to(`user_${recipientId}`).emit('ice-candidate', {
            candidate,
            callId,
            senderId: socket.userId
        });
    });

    // Disconnect handling
    socket.on('disconnect', () => {
        console.log(`[Socket.IO] Client disconnected: ${socket.id}`);
        if (socket.userId && userSockets.has(socket.userId)) {
            userSockets.get(socket.userId).delete(socket.id);
            if (userSockets.get(socket.userId).size === 0) {
                userSockets.delete(socket.userId);
            }
        }
    });
});

httpServer.listen(PORT, () => {
    console.log(`[Socket.IO Server] Running on port ${PORT}`);
});

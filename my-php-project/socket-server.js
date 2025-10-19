const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const path = require('path');

const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"]
    }
});

// Store connected users
const connectedUsers = new Map();
const adminUsers = new Set();
const userRooms = new Map(); // Map userId to socket.id
const typingUsers = new Map(); // Map conversation_id to typing users

// Middleware
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

// Routes
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'socket-test.html'));
});

// Socket.IO connection handling
io.on('connection', (socket) => {
    console.log('User connected:', socket.id);

    // Handle user authentication
    socket.on('authenticate', (data) => {
        const { userId, userRole, userName } = data;
        
        // Store user info
        connectedUsers.set(socket.id, {
            userId,
            userRole,
            userName,
            socketId: socket.id,
            connectedAt: new Date()
        });

        // Add to admin set if user is admin
        if (userRole && [1, 2, 3, 4].includes(parseInt(userRole))) {
            adminUsers.add(socket.id);
            socket.join('admin_room');
            console.log('Admin user connected:', userName);
        }

        // Join user-specific room
        socket.join(`user_${userId}`);
        userRooms.set(userId, socket.id);
        
        // Update user online status
        socket.emit('update_online_status', { userId, isOnline: true });
        
        // Send confirmation
        socket.emit('authenticated', {
            success: true,
            message: 'Đã kết nối thành công',
            userId,
            userRole
        });

        // Notify admins about new user connection
        if (adminUsers.has(socket.id)) {
            socket.to('admin_room').emit('admin_notification', {
                type: 'user_connected',
                message: `${userName} đã kết nối`,
                timestamp: new Date()
            });
        }
    });

    // Handle event registration notifications
    socket.on('event_registered', (data) => {
        const { eventName, userName, eventId } = data;
        
        // Notify all admins
        io.to('admin_room').emit('new_event_registration', {
            type: 'new_event',
            message: `Sự kiện mới: ${eventName} từ ${userName}`,
            eventId,
            userName,
            eventName,
            timestamp: new Date()
        });

        console.log('New event registration:', eventName, 'from', userName);
    });

    // Handle event status updates
    socket.on('event_status_updated', (data) => {
        const { eventId, eventName, status, userName, adminName } = data;
        
        // Notify the user who registered the event
        io.to(`user_${data.userId}`).emit('event_status_change', {
            type: 'status_update',
            message: `Sự kiện "${eventName}" đã được ${status === 'approved' ? 'duyệt' : 'từ chối'}`,
            eventId,
            eventName,
            status,
            adminName,
            timestamp: new Date()
        });

        // Notify admins
        io.to('admin_room').emit('admin_notification', {
            type: 'status_updated',
            message: `${adminName} đã ${status === 'approved' ? 'duyệt' : 'từ chối'} sự kiện "${eventName}"`,
            eventId,
            eventName,
            status,
            adminName,
            timestamp: new Date()
        });

        console.log('Event status updated:', eventName, 'to', status);
    });

    // Handle admin comments
    socket.on('admin_comment_added', (data) => {
        const { eventId, eventName, comment, adminName, userId } = data;
        
        // Notify the user
        io.to(`user_${userId}`).emit('admin_comment', {
            type: 'admin_comment',
            message: `Admin đã thêm ghi chú cho sự kiện "${eventName}"`,
            eventId,
            eventName,
            comment,
            adminName,
            timestamp: new Date()
        });

        console.log('Admin comment added to event:', eventName);
    });

    // Handle join user room
    socket.on('join_user_room', (data) => {
        const { userId } = data;
        const userInfo = connectedUsers.get(socket.id);
        
        if (userInfo && userInfo.userId == userId) {
            socket.join(`user_${userId}`);
            userRooms.set(userId, socket.id);
            console.log(`User ${userId} joined their room`);
        }
    });

    // Handle new message - Enhanced for real-time sync
    socket.on('new_message', (data) => {
        const { conversation_id, message, user_id, user_name } = data;
        const userInfo = connectedUsers.get(socket.id);
        
        if (userInfo) {
            console.log(`💬 ${userInfo.userName}: ${message}`);
            
            // Broadcast to all users in the conversation room
            io.to(`conversation_${conversation_id}`).emit('new_message', {
                conversation_id,
                message,
                user_id: userInfo.userId,
                user_name: userInfo.userName,
                timestamp: new Date()
            });
            
            console.log(`📢 Message broadcasted to conversation ${conversation_id}`);
        }
    });

    // Handle typing indicator - Enhanced for real-time sync
    socket.on('typing', (data) => {
        const { conversation_id, user_id, user_name } = data;
        const userInfo = connectedUsers.get(socket.id);
        
        if (userInfo) {
            console.log(`⌨️ ${userInfo.userName} is typing in conversation ${conversation_id}`);
            
            // Broadcast to conversation participants (excluding sender)
            socket.to(`conversation_${conversation_id}`).emit('typing', {
                conversation_id,
                user_id: userInfo.userId,
                user_name: userInfo.userName
            });
        }
    });

    // Handle stop typing - Enhanced for real-time sync
    socket.on('stop_typing', (data) => {
        const { conversation_id, user_id } = data;
        const userInfo = connectedUsers.get(socket.id);
        
        if (userInfo) {
            console.log(`⏹️ ${userInfo.userName} stopped typing in conversation ${conversation_id}`);
            
            // Broadcast to conversation participants (excluding sender)
            socket.to(`conversation_${conversation_id}`).emit('stop_typing', {
                conversation_id,
                user_id: userInfo.userId
            });
        }
    });

    // Handle join conversation - Enhanced for real-time sync
    socket.on('join_conversation', (data) => {
        const { conversation_id } = data;
        socket.join(`conversation_${conversation_id}`);
        console.log(`🟢 User joined conversation ${conversation_id}`);
    });

    // Handle leave conversation
    socket.on('leave_conversation', (data) => {
        const { conversation_id } = data;
        socket.leave(`conversation_${conversation_id}`);
        console.log(`🔴 User left conversation ${conversation_id}`);
    });

    // Handle broadcast message instantly
    socket.on('broadcast_message', (data) => {
        const { conversation_id, message, userId, timestamp } = data;
        console.log(`📢 Broadcasting message in conversation ${conversation_id}`);
        
        // Broadcast to all users in the conversation
        io.to(`conversation_${conversation_id}`).emit('broadcast_message', {
            conversation_id,
            message,
            userId,
            timestamp
        });
    });

    // Handle message read status
    socket.on('message_read', (data) => {
        const { conversation_id, message_id, user_id } = data;
        console.log(`👁️ Message ${message_id} read by user ${user_id}`);
        
        // Notify other users in the conversation
        socket.to(`conversation_${conversation_id}`).emit('message_read', {
            conversation_id,
            message_id,
            user_id
        });
    });

    // Handle messages loaded event
    socket.on('messages_loaded', (data) => {
        const { conversation_id, userId } = data;
        console.log(`📥 Messages loaded for user ${userId} in conversation ${conversation_id}`);
        
        // Notify other users that messages were loaded
        socket.to(`conversation_${conversation_id}`).emit('messages_loaded', {
            conversation_id,
            userId
        });
    });

    // Handle real-time chat (optional)
    socket.on('chat_message', (data) => {
        const { message, userName, userRole } = data;
        const userInfo = connectedUsers.get(socket.id);
        
        if (userInfo) {
            // Broadcast to all users
            io.emit('chat_message', {
                message,
                userName: userInfo.userName,
                userRole: userInfo.userRole,
                timestamp: new Date()
            });
        }
    });

    // Handle typing indicators
    socket.on('typing_start', (data) => {
        const userInfo = connectedUsers.get(socket.id);
        if (userInfo) {
            socket.broadcast.emit('user_typing', {
                userName: userInfo.userName,
                isTyping: true
            });
        }
    });

    socket.on('typing_stop', (data) => {
        const userInfo = connectedUsers.get(socket.id);
        if (userInfo) {
            socket.broadcast.emit('user_typing', {
                userName: userInfo.userName,
                isTyping: false
            });
        }
    });

    // Handle disconnect
    socket.on('disconnect', () => {
        const userInfo = connectedUsers.get(socket.id);
        
        if (userInfo) {
            console.log('User disconnected:', userInfo.userName);
            
            // Remove from user rooms
            userRooms.delete(userInfo.userId);
            
            // Update user online status
            socket.broadcast.emit('update_online_status', { 
                userId: userInfo.userId, 
                isOnline: false 
            });
            
            // Remove from admin set
            if (adminUsers.has(socket.id)) {
                adminUsers.delete(socket.id);
                
                // Notify other admins
                socket.to('admin_room').emit('admin_notification', {
                    type: 'admin_disconnected',
                    message: `${userInfo.userName} đã ngắt kết nối`,
                    timestamp: new Date()
                });
            }
            
            // Remove from connected users
            connectedUsers.delete(socket.id);
        }
    });

    // Handle ping/pong for connection health
    socket.on('ping', () => {
        socket.emit('pong');
    });
});

// Broadcast system notifications
function broadcastSystemNotification(message, type = 'info') {
    io.emit('system_notification', {
        type,
        message,
        timestamp: new Date()
    });
}

// Get connected users count
function getConnectedUsersCount() {
    return connectedUsers.size;
}

// Get admin users count
function getAdminUsersCount() {
    return adminUsers.size;
}

// Start server
const PORT = process.env.PORT || 3000;
server.listen(PORT, () => {
    console.log(`Socket.IO server running on port ${PORT}`);
    console.log(`Web interface: http://localhost:${PORT}`);
});

// Export for use in other modules
module.exports = {
    io,
    broadcastSystemNotification,
    getConnectedUsersCount,
    getAdminUsersCount
};

const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const path = require('path');

const app = express();

// Phát hiện môi trường: localhost hoặc production (cPanel/Passenger)
const isLocalhost = (process.env.PORT === '3000' || process.env.PORT === undefined) 
    && !process.env.PASSENGER_APP_ENV 
    && !process.env.PASSENGER_BASE_URI;

// Hybrid: WebSocket chạy trên VPS riêng, không có base path
// Nếu dùng cPanel/Passenger, set APP_BASE_PATH='/nodeapp' trong env
const APP_BASE_PATH = process.env.APP_BASE_PATH 
    ? process.env.APP_BASE_PATH.replace(/\/$/, '')
    : '';  // Hybrid: Không có base path

// Socket.IO path: LUÔN dùng relative path '/socket.io'
// Socket.IO path option CHỈ nhận relative path từ server root
// Vấn đề: Passenger mount app tại /nodeapp, request đến /nodeapp/socket.io/...
// Giải pháp: Normalize path ở HTTP server level (trước khi Socket.IO xử lý)
// Nhưng Passenger tự tạo server, nên cần cách khác
// Thử: Dùng custom HTTP server handler để normalize TRƯỚC khi Socket.IO xử lý
const SOCKET_IO_PATH = '/socket.io';
// Hybrid: Thêm các domain cần kết nối WebSocket
const CORS_ORIGINS = (process.env.CORS_ORIGINS || 'https://sukien.info.vn,https://www.sukien.info.vn,http://localhost,http://localhost:80,http://localhost:3000,http://localhost:3001,http://127.0.0.1,http://127.0.0.1:80')
    .split(',')
    .map(s => s.trim())
    .filter(Boolean);

// ⚠️ QUAN TRỌNG: Với Passenger, cần tạo server và attach Socket.IO
// Passenger có thể tự tạo server từ app, nhưng chúng ta cần server để attach Socket.IO
// Tạo server đơn giản từ app
const server = http.createServer(app);

// Khởi tạo Socket.IO - attach vào server
// Socket.IO sẽ intercept requests matching path '/socket.io' TRƯỚC Express routes
const io = socketIo(server, {
    path: SOCKET_IO_PATH,
    cors: {
        origin: function (origin, callback) {
            if (!origin) {
                // Same-origin request (no origin header)
                return callback(null, true);
            }
            
            // Allow all subdomains of sukien.info.vn (including www)
            if (origin.includes('sukien.info.vn')) {
                console.log('✅ CORS: Allowed - sukien.info.vn subdomain:', origin);
                return callback(null, true);
            }
            
            // Check CORS_ORIGINS list
            if (CORS_ORIGINS.includes(origin)) {
                console.log('✅ CORS: Allowed - in CORS_ORIGINS:', origin);
                callback(null, true);
            } else if (origin.includes('localhost') || origin.includes('127.0.0.1')) {
                console.log('✅ CORS: Allowed - localhost:', origin);
                    callback(null, true);
                } else {
                console.log('❌ CORS: Rejected -', origin);
                    callback(new Error('Not allowed by CORS'));
            }
        },
        methods: ["GET", "POST"],
        credentials: true
    },
    allowEIO3: true,
    transports: ['polling', 'websocket']
});

// Log để confirm Socket.IO đã được khởi tạo
console.log('🔌 Socket.IO initialized with path:', SOCKET_IO_PATH);
console.log('🔌 Socket.IO attached to server');

// Lưu trữ users đã kết nối
const connectedUsers = new Map();
const adminUsers = new Set();
const userRooms = new Map(); // Map userId sang socket.id
const typingUsers = new Map(); // Map conversation_id sang typing users
const activeCalls = new Map(); // Map call_id sang {caller_id, receiver_id, call_type, status, startTime}
const userActiveCalls = new Map(); // Map userId sang call_id (để track user đang trong cuộc gọi nào)

// Middleware
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

// Debug middleware - Log requests
app.use((req, res, next) => {
    console.log(`📥 ${req.method} ${req.url} (original: ${req.originalUrl || req.url})`);
    next();
});

// ⚠️ QUAN TRỌNG: Normalize Socket.IO path TRƯỚC tất cả middleware khác
// Passenger mount app tại /nodeapp, request đến /nodeapp/socket.io/...
// Socket.IO cần path /socket.io (relative), nên normalize /nodeapp/socket.io → /socket.io
// Socket.IO xử lý request TRƯỚC Express middleware, nhưng middleware này sẽ normalize path
// để Socket.IO có thể match path đúng
app.use((req, res, next) => {
    const originalUrl = req.url;
    
    // Normalize Socket.IO path: /nodeapp/socket.io/... → /socket.io/...
    if (req.url && req.url.startsWith('/nodeapp/socket.io')) {
        req.url = req.url.replace(/^\/nodeapp\/socket\.io/, '/socket.io');
        
        if (req.originalUrl) {
            req.originalUrl = req.originalUrl.replace(/^\/nodeapp\/socket\.io/, '/socket.io');
        }
        
        console.log(`🔌 [Middleware] Socket.IO path normalized: ${originalUrl} → ${req.url}`);
    }
    
    next();
});

// Strip prefix /nodeapp/ cho các routes khác (không phải Socket.IO)
app.use((req, res, next) => {
    if (req.url.startsWith('/socket.io')) {
        return next();
    }
    
    if (req.url.startsWith('/nodeapp/')) {
        req.url = req.url.replace(/^\/nodeapp/, '');
        if (!req.url.startsWith('/')) {
            req.url = '/' + req.url;
        }
    } else if (req.url === '/nodeapp') {
        req.url = '/';
    }
    next();
});

// Routes
// ⚠️ QUAN TRỌNG: Route này chỉ handle requests không phải Socket.IO
// Socket.IO requests đã được xử lý bởi Socket.IO TRƯỚC khi đến Express routes
app.get('/', (req, res) => {
    // Bỏ qua Socket.IO requests - Socket.IO đã xử lý ở trên
    if (req.url && (req.url.startsWith('/socket.io') || req.originalUrl && req.originalUrl.startsWith('/nodeapp/socket.io'))) {
        // Request đã được Socket.IO xử lý, không cần response ở đây
        return;
    }
    
    // ⚠️ QUAN TRỌNG: cPanel kiểm tra health bằng cách so sánh content type
    // Phải trả về 'text/plain' để cPanel không báo lỗi
    // Không dùng res.type('text/html') vì sẽ thay đổi content type
    res.setHeader('Content-Type', 'text/plain; charset=utf-8');
    res.status(200).send('Socket.IO server is running');
});

app.get('/health', (req, res) => {
    res.setHeader('Content-Type', 'application/json; charset=utf-8');
    res.json({
        status: 'ok',
        timestamp: new Date().toISOString(),
        server: 'Socket.IO Server',
        path: SOCKET_IO_PATH,
        appBasePath: APP_BASE_PATH,
        environment: process.env.NODE_ENV || 'development',
        connectedUsers: connectedUsers.size,
        adminUsers: adminUsers.size,
        requestUrl: req.url,
        originalUrl: req.originalUrl,
        baseUrl: req.baseUrl,
        path: req.path
    });
});

// API endpoint cho PHP để emit Socket.IO events
app.post('/api/emit', express.json(), (req, res) => {
    try {
        const { event, data } = req.body;
        
        if (!event || !data) {
            return res.status(400).json({
                success: false,
                error: 'Missing event or data'
            });
        }
        
        console.log(`📡 PHP đang emit event: ${event}`, data);
        
        // Emit event đến các rooms phù hợp dựa trên loại event
        if (event === 'event_registered') {
            // Thông báo cho tất cả admins
            io.to('admin_room').emit('new_event_registration', {
                type: 'new_event',
                message: `Sự kiện mới: ${data.eventName} từ ${data.userName}`,
                eventId: data.eventId,
                userName: data.userName,
                eventName: data.eventName,
                timestamp: new Date()
            });
        } else if (event === 'event_status_updated') {
            // Thông báo cho user đã đăng ký sự kiện
            if (data.userId) {
                io.to(`user_${data.userId}`).emit('event_status_change', {
                    type: 'status_update',
                    message: `Sự kiện "${data.eventName}" đã được ${data.status === 'approved' ? 'duyệt' : 'từ chối'}`,
                    eventId: data.eventId,
                    eventName: data.eventName,
                    status: data.status,
                    adminName: data.adminName,
                    timestamp: new Date()
                });
            }
            
            // Thông báo cho admins
            io.to('admin_room').emit('admin_notification', {
                type: 'status_updated',
                message: `${data.adminName} đã ${data.status === 'approved' ? 'duyệt' : 'từ chối'} sự kiện "${data.eventName}"`,
                eventId: data.eventId,
                eventName: data.eventName,
                status: data.status,
                adminName: data.adminName,
                timestamp: new Date()
            });
        } else if (event === 'admin_comment_added') {
            // Thông báo cho user
            if (data.userId) {
                io.to(`user_${data.userId}`).emit('admin_comment', {
                    type: 'admin_comment',
                    message: `Admin đã thêm ghi chú cho sự kiện "${data.eventName}"`,
                    eventId: data.eventId,
                    eventName: data.eventName,
                    comment: data.comment,
                    adminName: data.adminName,
                    timestamp: new Date()
                });
            }
        } else if (event === 'system_notification') {
            // Broadcast đến tất cả users
            io.emit('system_notification', {
                type: data.type || 'info',
                message: data.message,
                timestamp: new Date()
            });
        } else {
            // Emit event tổng quát - broadcast đến tất cả
            io.emit(event, data);
        }
        
        res.json({
            success: true,
            message: `Event ${event} emitted successfully`
        });
    } catch (error) {
        console.error('Error emitting event:', error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// Xử lý kết nối Socket.IO
io.on('connection', (socket) => {
    console.log('User đã kết nối:', socket.id);

    // Xử lý xác thực user
    socket.on('authenticate', (data) => {
        const { userId, userRole, userName } = data;
        
        // Lưu thông tin user
        connectedUsers.set(socket.id, {
            userId,
            userRole,
            userName,
            socketId: socket.id,
            connectedAt: new Date()
        });

        // Thêm vào admin set nếu user là admin
        if (userRole && [1, 2, 3, 4].includes(parseInt(userRole))) {
            adminUsers.add(socket.id);
            socket.join('admin_room');
            console.log('Admin user đã kết nối:', userName);
        }

        // Tham gia room riêng của user
        socket.join(`user_${userId}`);
        userRooms.set(userId, socket.id);
        
        // Cập nhật trạng thái online của user
        socket.emit('update_online_status', { userId, isOnline: true });
        
        // Gửi xác nhận
        socket.emit('authenticated', {
            success: true,
            message: 'Đã kết nối thành công',
            userId,
            userRole
        });

        // Thông báo cho admins về user mới kết nối
        if (adminUsers.has(socket.id)) {
            socket.to('admin_room').emit('admin_notification', {
                type: 'user_connected',
                message: `${userName} đã kết nối`,
                timestamp: new Date()
            });
        }
    });

    // Xử lý thông báo đăng ký sự kiện
    socket.on('event_registered', (data) => {
        const { eventName, userName, eventId } = data;
        
        // Thông báo cho tất cả admins
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

    // Xử lý cập nhật trạng thái sự kiện
    socket.on('event_status_updated', (data) => {
        const { eventId, eventName, status, userName, adminName } = data;
        
        // Thông báo cho user đã đăng ký sự kiện
        io.to(`user_${data.userId}`).emit('event_status_change', {
            type: 'status_update',
            message: `Sự kiện "${eventName}" đã được ${status === 'approved' ? 'duyệt' : 'từ chối'}`,
            eventId,
            eventName,
            status,
            adminName,
            timestamp: new Date()
        });

        // Thông báo cho admins
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

    // Xử lý comment của admin
    socket.on('admin_comment_added', (data) => {
        const { eventId, eventName, comment, adminName, userId } = data;
        
        // Thông báo cho user
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

    // Xử lý tham gia room của user
    socket.on('join_user_room', (data) => {
        const { userId } = data;
        const userInfo = connectedUsers.get(socket.id);
        
        if (userInfo && userInfo.userId == userId) {
            socket.join(`user_${userId}`);
            userRooms.set(userId, socket.id);
            console.log(`User ${userId} joined their room`);
        }
    });

    // Xử lý tin nhắn mới - Tối ưu cho real-time sync
    socket.on('new_message', (data) => {
        const { conversation_id, message, user_id, user_name } = data;
        const userInfo = connectedUsers.get(socket.id);
        
        if (userInfo) {
            console.log(`💬 ${userInfo.userName}: ${message}`);
            
            // Broadcast đến tất cả users trong conversation room
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

    // Xử lý chỉ báo đang gõ - Tối ưu cho real-time sync
    socket.on('typing', (data) => {
        const { conversation_id, user_id, user_name } = data;
        const userInfo = connectedUsers.get(socket.id);
        
        if (userInfo) {
            console.log(`⌨️ ${userInfo.userName} đang gõ trong conversation ${conversation_id}`);
            
            // Broadcast đến các participants trong conversation (trừ người gửi)
            socket.to(`conversation_${conversation_id}`).emit('typing', {
                conversation_id,
                user_id: userInfo.userId,
                user_name: userInfo.userName
            });
        }
    });

    // Xử lý dừng gõ - Tối ưu cho real-time sync
    socket.on('stop_typing', (data) => {
        const { conversation_id, user_id } = data;
        const userInfo = connectedUsers.get(socket.id);
        
        if (userInfo) {
            console.log(`⏹️ ${userInfo.userName} đã dừng gõ trong conversation ${conversation_id}`);
            
            // Broadcast đến các participants trong conversation (trừ người gửi)
            socket.to(`conversation_${conversation_id}`).emit('stop_typing', {
                conversation_id,
                user_id: userInfo.userId
            });
        }
    });

    // Xử lý tham gia conversation - Tối ưu cho real-time sync
    socket.on('join_conversation', (data) => {
        const { conversation_id } = data;
        socket.join(`conversation_${conversation_id}`);
        console.log(`🟢 User đã tham gia conversation ${conversation_id}`);
    });

    // Xử lý rời conversation
    socket.on('leave_conversation', (data) => {
        const { conversation_id } = data;
        socket.leave(`conversation_${conversation_id}`);
        console.log(`🔴 User left conversation ${conversation_id}`);
    });

    // Xử lý broadcast message ngay lập tức
    socket.on('broadcast_message', (data) => {
        const { conversation_id, message, userId, timestamp } = data;
        console.log(`📢 Đang broadcast message trong conversation ${conversation_id}`);
        
        // Broadcast đến tất cả users trong conversation
        io.to(`conversation_${conversation_id}`).emit('broadcast_message', {
            conversation_id,
            message,
            userId,
            timestamp
        });
    });

    // Xử lý trạng thái đã đọc message
    socket.on('message_read', (data) => {
        const { conversation_id, message_id, user_id } = data;
        console.log(`👁️ Message ${message_id} đã được đọc bởi user ${user_id}`);
        
        // Thông báo cho các users khác trong conversation
        socket.to(`conversation_${conversation_id}`).emit('message_read', {
            conversation_id,
            message_id,
            user_id
        });
    });

    // Xử lý event messages đã được load
    socket.on('messages_loaded', (data) => {
        const { conversation_id, userId } = data;
        console.log(`📥 Messages đã được load cho user ${userId} trong conversation ${conversation_id}`);
        
        // Thông báo cho các users khác rằng messages đã được load
        socket.to(`conversation_${conversation_id}`).emit('messages_loaded', {
            conversation_id,
            userId
        });
    });

    // Xử lý real-time chat (tùy chọn)
    socket.on('chat_message', (data) => {
        const { message, userName, userRole } = data;
        const userInfo = connectedUsers.get(socket.id);
        
        if (userInfo) {
            // Broadcast đến tất cả users
            io.emit('chat_message', {
                message,
                userName: userInfo.userName,
                userRole: userInfo.userRole,
                timestamp: new Date()
            });
        }
    });

    // Xử lý chỉ báo đang gõ
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

    // Xử lý ngắt kết nối
    socket.on('disconnect', () => {
        const userInfo = connectedUsers.get(socket.id);
        
        if (userInfo) {
            console.log('User đã ngắt kết nối:', userInfo.userName);
            
            // QUAN TRỌNG: Cleanup tất cả calls của user này khi disconnect
            const userId = userInfo.userId;
            const userActiveCallId = userActiveCalls.get(userId);
            
            if (userActiveCallId) {
                console.log(`🧹 Cleaning up call ${userActiveCallId} for disconnected user ${userId}`);
                const call = activeCalls.get(userActiveCallId);
                
                if (call) {
                    // Thông báo cho người còn lại rằng call đã kết thúc
                    const otherUserId = call.caller_id == userId ? call.receiver_id : call.caller_id;
                    if (otherUserId) {
                        io.to(`user_${otherUserId}`).emit('call_ended', {
                            call_id: userActiveCallId,
                            caller_id: call.caller_id,
                            receiver_id: call.receiver_id,
                            ended_by: userId,
                            message: `${userInfo.userName} đã ngắt kết nối. Cuộc gọi đã kết thúc.`
                        });
                    }
                    
                    // Cleanup call
                    activeCalls.delete(userActiveCallId);
                }
                
                // Cleanup userActiveCalls
                userActiveCalls.delete(userId);
                
                // Cleanup các calls khác mà user này tham gia
                for (let [callId, call] of activeCalls.entries()) {
                    if (call.caller_id == userId || call.receiver_id == userId) {
                        console.log(`🧹 Cleaning up call ${callId} for disconnected user ${userId}`);
                        activeCalls.delete(callId);
                        if (userActiveCalls.get(call.caller_id) == callId) {
                            userActiveCalls.delete(call.caller_id);
                        }
                        if (userActiveCalls.get(call.receiver_id) == callId) {
                            userActiveCalls.delete(call.receiver_id);
                        }
                    }
                }
            }
            
            // Xóa khỏi user rooms
            userRooms.delete(userInfo.userId);
            
            // Cập nhật trạng thái online của user
            socket.broadcast.emit('update_online_status', { 
                userId: userInfo.userId, 
                isOnline: false 
            });
            
            // Xóa khỏi admin set
            if (adminUsers.has(socket.id)) {
                adminUsers.delete(socket.id);
                
                // Thông báo cho các admins khác
                socket.to('admin_room').emit('admin_notification', {
                    type: 'admin_disconnected',
                    message: `${userInfo.userName} đã ngắt kết nối`,
                    timestamp: new Date()
                });
            }
            
            // Xóa khỏi connected users
            connectedUsers.delete(socket.id);
        }
    });

    // Xử lý các events cuộc gọi
    socket.on('call_initiated', (data) => {
        const { call_id, caller_id, receiver_id, call_type, conversation_id } = data;
        const userInfo = connectedUsers.get(socket.id);
        
        console.log(`📞 Đã nhận event call initiated:`, {
            call_id,
            caller_id,
            receiver_id,
            call_type,
            conversation_id,
            socket_id: socket.id,
            user_info: userInfo
        });
        
        if (userInfo && userInfo.userId != caller_id) {
            console.warn(`⚠️ Call được khởi tạo bởi user sai. Mong đợi ${caller_id}, nhận được ${userInfo.userId}`);
        }
        
        // QUAN TRỌNG: Cleanup call cũ của receiver trước khi kiểm tra busy
        // Chỉ coi là "busy" nếu call đang thực sự active (accepted) hoặc đang ring (trong 5 giây gần nhất)
        const receiverActiveCallId = userActiveCalls.get(receiver_id);
        if (receiverActiveCallId && receiverActiveCallId !== call_id) {
            const activeCall = activeCalls.get(receiverActiveCallId);
            
            if (activeCall) {
                // Cleanup call cũ (initiated/ringing quá 5 giây)
                if (activeCall.status === 'initiated' || activeCall.status === 'ringing') {
                    const callAge = Date.now() - new Date(activeCall.startTime).getTime();
                    if (callAge > 5000) { // Quá 5 giây
                        console.log(`🧹 Cleaning up old call ${receiverActiveCallId} for receiver ${receiver_id} (age: ${callAge}ms)`);
                        userActiveCalls.delete(receiver_id);
                        activeCalls.delete(receiverActiveCallId);
                        // Không coi là busy, tiếp tục xử lý call mới
                    } else if (activeCall.status === 'ringing') {
                        // Call đang ring trong 5 giây gần nhất, coi là busy
                        console.log(`⚠️ Receiver ${receiver_id} đang bận trong cuộc gọi ${receiverActiveCallId} (đang ring)`);
                        
                        let receiverName = 'Người dùng';
                        for (let [socketId, user] of connectedUsers.entries()) {
                            if (user.userId == receiver_id) {
                                receiverName = user.userName || receiverName;
                                break;
                            }
                        }
                        
                        // Thông báo cho caller
                        io.to(`user_${caller_id}`).emit('call_busy', {
                            call_id,
                            receiver_id,
                            receiver_name: receiverName,
                            message: `${receiverName} đang trong cuộc gọi khác. Vui lòng thử lại sau.`,
                            busy_call_id: receiverActiveCallId
                        });
                        
                        io.to(`user_${receiver_id}`).emit('call_notification', {
                            type: 'missed_call_busy',
                            call_id,
                            caller_id,
                            caller_name: userInfo ? (userInfo.userName || 'Người gọi') : 'Người gọi',
                            message: `Bạn có cuộc gọi từ ${userInfo ? (userInfo.userName || 'Người gọi') : 'Người gọi'} nhưng đang bận`,
                            timestamp: new Date()
                        });
                        
                        return;
                    }
                } else if (activeCall.status === 'active') {
                    // Call đang active (accepted), chắc chắn busy
                    console.log(`⚠️ Receiver ${receiver_id} đang bận trong cuộc gọi ${receiverActiveCallId} (đang active)`);
                    
                    let receiverName = 'Người dùng';
                    for (let [socketId, user] of connectedUsers.entries()) {
                        if (user.userId == receiver_id) {
                            receiverName = user.userName || receiverName;
                            break;
                        }
                    }
                    
                    // Thông báo cho caller
                    io.to(`user_${caller_id}`).emit('call_busy', {
                        call_id,
                        receiver_id,
                        receiver_name: receiverName,
                        message: `${receiverName} đang trong cuộc gọi khác. Vui lòng thử lại sau.`,
                        busy_call_id: receiverActiveCallId
                    });
                    
                    io.to(`user_${receiver_id}`).emit('call_notification', {
                        type: 'missed_call_busy',
                        call_id,
                        caller_id,
                        caller_name: userInfo ? (userInfo.userName || 'Người gọi') : 'Người gọi',
                        message: `Bạn có cuộc gọi từ ${userInfo ? (userInfo.userName || 'Người gọi') : 'Người gọi'} nhưng đang bận`,
                        timestamp: new Date()
                    });
                    
                    return;
                }
            }
        }
        
        // QUAN TRỌNG: Cleanup call cũ của caller trước khi kiểm tra busy
        const callerActiveCallId = userActiveCalls.get(caller_id);
        if (callerActiveCallId && callerActiveCallId !== call_id) {
            const activeCall = activeCalls.get(callerActiveCallId);
            
            if (activeCall) {
                // Cleanup call cũ (initiated/ringing quá 5 giây)
                if (activeCall.status === 'initiated' || activeCall.status === 'ringing') {
                    const callAge = Date.now() - new Date(activeCall.startTime).getTime();
                    if (callAge > 5000) { // Quá 5 giây
                        console.log(`🧹 Cleaning up old call ${callerActiveCallId} for caller ${caller_id} (age: ${callAge}ms)`);
                        userActiveCalls.delete(caller_id);
                        activeCalls.delete(callerActiveCallId);
                        // Không coi là busy, tiếp tục xử lý call mới
                    } else if (activeCall.status === 'ringing') {
                        // Call đang ring trong 5 giây gần nhất, coi là busy
                        console.log(`⚠️ Caller ${caller_id} đang trong cuộc gọi ${callerActiveCallId} (đang ring), không thể gọi mới`);
                        
                        // Thông báo cho caller
                        io.to(`user_${caller_id}`).emit('call_notification', {
                            type: 'cannot_call',
                            message: 'Bạn đang trong cuộc gọi khác. Vui lòng kết thúc cuộc gọi hiện tại trước.',
                            timestamp: new Date()
                        });
                        
                        return; // Không gửi call_initiated event
                    }
                } else if (activeCall.status === 'active') {
                    // Call đang active (accepted), chắc chắn busy
                    console.log(`⚠️ Caller ${caller_id} đang trong cuộc gọi ${callerActiveCallId} (đang active), không thể gọi mới`);
                    
                    // Thông báo cho caller
                    io.to(`user_${caller_id}`).emit('call_notification', {
                        type: 'cannot_call',
                        message: 'Bạn đang trong cuộc gọi khác. Vui lòng kết thúc cuộc gọi hiện tại trước.',
                        timestamp: new Date()
                    });
                    
                    return; // Không gửi call_initiated event
                }
            }
        }
        
        // Lưu thông tin cuộc gọi
        activeCalls.set(call_id, {
            call_id,
            caller_id,
            receiver_id,
            call_type,
            conversation_id,
            status: 'ringing',
            startTime: new Date()
        });
        userActiveCalls.set(receiver_id, call_id);
        userActiveCalls.set(caller_id, call_id);
        
        console.log(`📞 Call ${call_id} initiated: caller=${caller_id}, receiver=${receiver_id}, status=ringing`);
        
        io.to(`user_${caller_id}`).emit('call_notification', {
            type: 'calling',
            call_id,
            receiver_id,
            message: 'Đang gọi...',
            timestamp: new Date()
        });
        
        // Broadcast đến receiver
        io.to(`user_${receiver_id}`).emit('call_initiated', {
            call_id,
            caller_id,
            receiver_id,
            call_type,
            conversation_id,
            caller_name: userInfo ? (userInfo.userName || 'Người gọi') : 'Người gọi'
        });
        
        if (conversation_id) {
            io.to(`conversation_${conversation_id}`).emit('call_initiated', {
                call_id,
                caller_id,
                receiver_id,
                call_type,
                conversation_id,
                caller_name: userInfo ? (userInfo.userName || 'Người gọi') : 'Người gọi'
            });
        }
        
        socket.broadcast.emit('call_initiated', {
            call_id,
            caller_id,
            receiver_id,
            call_type,
            conversation_id,
            caller_name: userInfo ? (userInfo.userName || 'Người gọi') : 'Người gọi'
        });
        
        // Timeout 30 giây
        setTimeout(() => {
            const call = activeCalls.get(call_id);
            if (call && call.status === 'ringing') {
                console.log(`⏰ Call ${call_id} timeout sau 30 giây`);
                
                call.status = 'timeout';
                activeCalls.set(call_id, call);
                
                userActiveCalls.delete(caller_id);
                userActiveCalls.delete(receiver_id);
                
                // Thông báo timeout
                io.to(`user_${caller_id}`).emit('call_timeout', {
                    call_id,
                    receiver_id,
                    message: 'Cuộc gọi không được trả lời sau 30 giây'
                });
                
                io.to(`user_${receiver_id}`).emit('call_timeout', {
                    call_id,
                    caller_id,
                    message: 'Cuộc gọi đã hết thời gian chờ'
                });
                
                setTimeout(() => {
                    activeCalls.delete(call_id);
                }, 5000);
            }
        }, 30000);
    });

    socket.on('call_accepted', (data) => {
        const { call_id, caller_id, receiver_id } = data;
        const userInfo = connectedUsers.get(socket.id);
        
        if (userInfo && userInfo.userId == receiver_id) {
            console.log(`✅ Call đã được chấp nhận: ${call_id} bởi ${receiver_id}`);
            
            const call = activeCalls.get(call_id);
            if (call) {
                call.status = 'active';
                activeCalls.set(call_id, call);
            }
            
            let receiverName = userInfo.userName || 'Người dùng';
            
            io.to(`user_${caller_id}`).emit('call_accepted', {
                call_id,
                caller_id,
                receiver_id,
                receiver_name: receiverName
            });
            
            io.to(`user_${receiver_id}`).emit('call_notification', {
                type: 'call_active',
                call_id,
                caller_id,
                message: 'Cuộc gọi đã được kết nối',
                timestamp: new Date()
            });
        }
    });

    socket.on('call_rejected', (data) => {
        const { call_id, caller_id, receiver_id } = data;
        const userInfo = connectedUsers.get(socket.id);
        
        if (userInfo && userInfo.userId == receiver_id) {
            console.log(`❌ Call đã bị từ chối: ${call_id} bởi ${receiver_id}`);
            
            const call = activeCalls.get(call_id);
            if (call) {
                call.status = 'rejected';
                activeCalls.set(call_id, call);
            }
            
            userActiveCalls.delete(caller_id);
            userActiveCalls.delete(receiver_id);
            
            let receiverName = userInfo.userName || 'Người dùng';
            
            io.to(`user_${caller_id}`).emit('call_rejected', {
                call_id,
                caller_id,
                receiver_id,
                receiver_name: receiverName,
                message: `${receiverName} đã từ chối cuộc gọi`
            });
            
            io.to(`user_${receiver_id}`).emit('call_notification', {
                type: 'call_rejected',
                call_id,
                caller_id,
                message: 'Bạn đã từ chối cuộc gọi',
                timestamp: new Date()
            });
            
            setTimeout(() => {
                activeCalls.delete(call_id);
            }, 5000);
        }
    });

    socket.on('call_ended', (data) => {
        const { call_id, caller_id } = data;
        const userInfo = connectedUsers.get(socket.id);
        
        if (userInfo) {
            console.log(`🔚 Call đã kết thúc: ${call_id} bởi user ${userInfo.userId}`);
            
            const call = activeCalls.get(call_id);
            if (call) {
                const actualCallerId = call.caller_id;
                const actualReceiverId = call.receiver_id;
                
                console.log(`📞 Call details: caller=${actualCallerId}, receiver=${actualReceiverId}, ended_by=${userInfo.userId}`);
                
                call.status = 'ended';
                call.endTime = new Date();
                activeCalls.set(call_id, call);
                
                // Cleanup user active calls
                userActiveCalls.delete(actualCallerId);
                userActiveCalls.delete(actualReceiverId);
                
                let endedByName = userInfo.userName || 'Người dùng';
                
                // QUAN TRỌNG: Gửi call_ended event cho CẢ 2 bên để đảm bảo cả 2 đều nhận được
                // Gửi cho receiver (nếu caller tắt)
                if (userInfo.userId == actualCallerId && actualReceiverId) {
                    console.log(`📞 Sending call_ended to receiver ${actualReceiverId}`);
                    io.to(`user_${actualReceiverId}`).emit('call_ended', {
                    call_id,
                        caller_id: actualCallerId,
                        receiver_id: actualReceiverId,
                        ended_by: actualCallerId,
                        ended_by_name: endedByName,
                        message: `${endedByName} đã kết thúc cuộc gọi`
                    });
                }
                // Gửi cho caller (nếu receiver tắt)
                else if (userInfo.userId == actualReceiverId && actualCallerId) {
                    console.log(`📞 Sending call_ended to caller ${actualCallerId}`);
                    io.to(`user_${actualCallerId}`).emit('call_ended', {
                        call_id,
                        caller_id: actualCallerId,
                        receiver_id: actualReceiverId,
                        ended_by: actualReceiverId,
                        ended_by_name: endedByName,
                        message: `${endedByName} đã kết thúc cuộc gọi`
                    });
                }
                // Fallback: Gửi cho cả 2 bên nếu không xác định được
                else {
                    console.log(`📞 Sending call_ended to both parties (fallback)`);
                    io.to(`user_${actualCallerId}`).emit('call_ended', {
                        call_id,
                        caller_id: actualCallerId,
                        receiver_id: actualReceiverId,
                        ended_by: userInfo.userId,
                        ended_by_name: endedByName,
                        message: `${endedByName} đã kết thúc cuộc gọi`
                    });
                    io.to(`user_${actualReceiverId}`).emit('call_ended', {
                        call_id,
                        caller_id: actualCallerId,
                        receiver_id: actualReceiverId,
                        ended_by: userInfo.userId,
                        ended_by_name: endedByName,
                        message: `${endedByName} đã kết thúc cuộc gọi`
                    });
                }
                
                // Gửi notification cho người tắt
                io.to(`user_${userInfo.userId}`).emit('call_notification', {
                    type: 'call_ended',
                    call_id,
                    message: 'Bạn đã kết thúc cuộc gọi',
                    timestamp: new Date()
                });
            } else {
                console.warn(`⚠️ Call ${call_id} not found in activeCalls, but still sending call_ended to both parties`);
                // Nếu không tìm thấy call, vẫn cố gắng gửi event dựa trên data
                if (caller_id && data.receiver_id) {
                io.to(`user_${caller_id}`).emit('call_ended', {
                    call_id,
                    caller_id,
                        receiver_id: data.receiver_id,
                        ended_by: userInfo.userId,
                        ended_by_name: userInfo.userName || 'Người dùng',
                        message: 'Cuộc gọi đã kết thúc'
                    });
                    io.to(`user_${data.receiver_id}`).emit('call_ended', {
                        call_id,
                        caller_id,
                        receiver_id: data.receiver_id,
                        ended_by: userInfo.userId,
                        ended_by_name: userInfo.userName || 'Người dùng',
                        message: 'Cuộc gọi đã kết thúc'
                    });
                }
            }
            
            // Cleanup sau 5 giây
            setTimeout(() => {
                activeCalls.delete(call_id);
                console.log(`🗑️ Cleaned up call ${call_id} from activeCalls`);
            }, 5000);
        } else {
            console.warn(`⚠️ call_ended received from unknown user (socket ${socket.id})`);
        }
    });

    // WebRTC Offer - Caller gửi offer cho receiver
    socket.on('webrtc_offer', (data) => {
        const { call_id, offer } = data;
        const userInfo = connectedUsers.get(socket.id);
        
        if (userInfo) {
            const call = activeCalls.get(call_id);
            if (call && call.caller_id == userInfo.userId) {
                console.log(`📞 Forwarding WebRTC offer for call ${call_id} from caller ${call.caller_id} to receiver ${call.receiver_id}`);
                // Forward offer to receiver
                io.to(`user_${call.receiver_id}`).emit('webrtc_offer', {
                    call_id,
                    offer
                });
            }
        }
    });

    // WebRTC Answer - Receiver gửi answer cho caller
    socket.on('webrtc_answer', (data) => {
        const { call_id, answer } = data;
        const userInfo = connectedUsers.get(socket.id);
        
        if (userInfo) {
            const call = activeCalls.get(call_id);
            if (call && call.receiver_id == userInfo.userId) {
                console.log(`📞 Forwarding WebRTC answer for call ${call_id} from receiver ${call.receiver_id} to caller ${call.caller_id}`);
                // Forward answer to caller
                io.to(`user_${call.caller_id}`).emit('webrtc_answer', {
                    call_id,
                    answer
                });
            }
        }
    });

    // ICE Candidate - Forward ICE candidates giữa caller và receiver
    socket.on('ice_candidate', (data) => {
        const { call_id, candidate } = data;
        const userInfo = connectedUsers.get(socket.id);
        
        if (userInfo) {
            const call = activeCalls.get(call_id);
            if (call) {
                // Forward ICE candidate to the other peer
                if (call.caller_id == userInfo.userId) {
                    // Caller sent candidate, forward to receiver
                    io.to(`user_${call.receiver_id}`).emit('ice_candidate', {
                        call_id,
                        candidate
                    });
                } else if (call.receiver_id == userInfo.userId) {
                    // Receiver sent candidate, forward to caller
                    io.to(`user_${call.caller_id}`).emit('ice_candidate', {
                        call_id,
                        candidate
                    });
                }
            }
        }
    });

    // Xử lý ping/pong để kiểm tra sức khỏe kết nối
    socket.on('ping', () => {
        socket.emit('pong');
    });
});

// Broadcast thông báo hệ thống
function broadcastSystemNotification(message, type = 'info') {
    io.emit('system_notification', {
        type,
        message,
        timestamp: new Date()
    });
}

// Lấy số lượng users đã kết nối
function getConnectedUsersCount() {
    return connectedUsers.size;
}

// Lấy số lượng admin users
function getAdminUsersCount() {
    return adminUsers.size;
}

// Khởi động server
const PORT = process.env.PORT || 3000;

console.log('='.repeat(60));
console.log('Socket.IO Server Configuration:');
console.log('='.repeat(60));
console.log(`Environment: ${process.env.NODE_ENV || 'development'}`);
console.log(`Is Localhost: ${isLocalhost}`);
console.log(`APP_BASE_PATH: ${APP_BASE_PATH}`);
console.log(`SOCKET_IO_PATH: ${SOCKET_IO_PATH}`);
console.log(`Full Socket.IO URL: ${APP_BASE_PATH}${SOCKET_IO_PATH}`);
console.log(`CORS Origins: ${CORS_ORIGINS.join(', ')}`);
console.log(`Port: ${PORT}`);
console.log(`Node.js Version: ${process.version}`);
console.log(`Passenger App Env: ${process.env.PASSENGER_APP_ENV || 'N/A'}`);
console.log(`Passenger Base URI: ${process.env.PASSENGER_BASE_URI || 'N/A'}`);
console.log('='.repeat(60));

// ⚠️ QUAN TRỌNG: Với Passenger, KHÔNG gọi server.listen()
// Passenger tự tạo và quản lý server từ Express app
// Chỉ gọi server.listen() trên localhost
if (isLocalhost) {
server.listen(PORT, () => {
        console.log('✅ Socket.IO server started successfully (localhost)!');
    console.log(`📡 Server running on port: ${PORT}`);
    console.log(`🔗 Socket.IO path: ${SOCKET_IO_PATH}`);
    console.log(`📦 App Base Path: ${APP_BASE_PATH}`);
    console.log(`🌐 Full Socket.IO URL: ${APP_BASE_PATH}${SOCKET_IO_PATH}`);
    console.log(`📅 Server started at: ${new Date().toISOString()}`);
    console.log('='.repeat(60));
    console.log('🚀 Server is ready to accept connections!');
    console.log('='.repeat(60));
});
} else {
    // Production (Passenger): Passenger tự quản lý server
    console.log('✅ Socket.IO server configured for Passenger');
    console.log(`📡 Passenger will manage the server`);
    console.log(`🔗 Socket.IO path: ${SOCKET_IO_PATH}`);
    console.log(`📦 App Base Path: ${APP_BASE_PATH}`);
    console.log(`🌐 Full Socket.IO URL: ${APP_BASE_PATH}${SOCKET_IO_PATH}`);
    console.log('='.repeat(60));
    console.log('🚀 Server is ready for Passenger!');
    console.log('='.repeat(60));
}

// ⚠️ QUAN TRỌNG: Với Passenger, cần export app (Passenger expect Express app)
// Vấn đề: Passenger tự tạo HTTP server từ app, và Socket.IO không được attach vào server đó
// Giải pháp: Override app.listen() để re-attach Socket.IO khi Passenger tạo server
//
// Passenger có thể gọi app.listen() hoặc tạo server trực tiếp
// Nếu Passenger gọi app.listen(), chúng ta có thể intercept và attach Socket.IO
module.exports = app;

// Lưu server và io vào app để có thể access sau
app.set('server', server);
app.set('io', io);

// ⚠️ QUAN TRỌNG: Với Passenger, cần đảm bảo Socket.IO hoạt động
// Vấn đề: Passenger có thể tự tạo server từ app, và Socket.IO không được attach vào server đó
// Giải pháp: 
// 1. Export app (Passenger expect Express app)
// 2. Lưu server và io vào app
// 3. Nếu Passenger tạo server mới, cần re-attach Socket.IO
//
// Thử: Override app.listen() để re-attach Socket.IO khi Passenger tạo server
if (!isLocalhost) {
    // Lưu reference đến original listen
    const originalListen = app.listen.bind(app);
    
    // Override app.listen() để intercept khi Passenger tạo server
    app.listen = function(...args) {
        console.log('🔧 app.listen() called - Passenger may be creating server');
        const passengerServer = originalListen(...args);
        
        // Re-attach Socket.IO vào server mà Passenger tạo
        if (passengerServer && !passengerServer._socketIoAttached) {
            console.log('🔧 Re-attaching Socket.IO to Passenger server...');
            
            try {
                const newIo = socketIo(passengerServer, {
                    path: SOCKET_IO_PATH,
                    cors: {
                        origin: function (origin, callback) {
                            if (!origin) {
                                // Same-origin request (no origin header)
                                return callback(null, true);
                            }
                            
                            // Allow all subdomains of sukien.info.vn (including www)
                            if (origin.includes('sukien.info.vn')) {
                                console.log('✅ CORS: Allowed - sukien.info.vn subdomain:', origin);
                                return callback(null, true);
                            }
                            
                            // Check CORS_ORIGINS list
                            if (CORS_ORIGINS.includes(origin)) {
                                console.log('✅ CORS: Allowed - in CORS_ORIGINS:', origin);
                                callback(null, true);
                            } else if (origin.includes('localhost') || origin.includes('127.0.0.1')) {
                                console.log('✅ CORS: Allowed - localhost:', origin);
                                callback(null, true);
                            } else {
                                console.log('❌ CORS: Rejected -', origin);
                                callback(new Error('Not allowed by CORS'));
                            }
                        },
                        methods: ["GET", "POST"],
                        credentials: true
                    },
                    allowEIO3: true,
                    transports: ['polling', 'websocket']
                });
                
                // Copy connection handlers từ io cũ
                io.on('connection', (socket) => {
                    // Forward to new io
                });
                
                passengerServer._socketIoAttached = true;
                app.set('io', newIo);
                app.set('passengerServer', passengerServer);
                
                console.log('✅ Socket.IO re-attached to Passenger server');
            } catch (error) {
                console.error('❌ Error re-attaching Socket.IO:', error);
            }
        }
        
        return passengerServer;
    };
    
    console.log('🔧 App exported for Passenger');
    console.log('🔧 app.listen() overridden to re-attach Socket.IO if needed');
}

/**
 * Stringee Helper Functions
 * Các hàm hỗ trợ cho Stringee Voice/Video Call
 */

// Global variables
let stringeeClient = null;
let stringeeCall = null;
let localVideoTrack = null;
let localAudioTrack = null;
let remoteVideoTrack = null;
let remoteAudioTrack = null;

/**
 * Đợi Stringee SDK load
 */
function waitForStringeeSDK() {
    return new Promise((resolve, reject) => {
        // Kiểm tra StringeeClient và StringeeCall (bắt buộc)
        // StringeeCall2 là optional (chỉ cho video call)
        if (typeof StringeeClient !== 'undefined' && typeof StringeeCall !== 'undefined') {
            console.log('✅ [Stringee] SDK loaded - StringeeClient and StringeeCall available');
            if (typeof StringeeCall2 !== 'undefined') {
                console.log('✅ [Stringee] StringeeCall2 also available for video calls');
            }
            resolve();
            return;
        }
        
        let checkCount = 0;
        const maxChecks = 100; // 10 giây
        
        const checkInterval = setInterval(() => {
            checkCount++;
            
            if (typeof StringeeClient !== 'undefined' && typeof StringeeCall !== 'undefined') {
                clearInterval(checkInterval);
                clearTimeout(timeout);
                console.log('✅ [Stringee] SDK loaded - StringeeClient and StringeeCall available');
                if (typeof StringeeCall2 !== 'undefined') {
                    console.log('✅ [Stringee] StringeeCall2 also available for video calls');
                }
                resolve();
                return;
            }
            
            if (checkCount >= maxChecks) {
                clearInterval(checkInterval);
                clearTimeout(timeout);
                reject(new Error('Stringee SDK không load được sau 10 giây'));
            }
        }, 100);
        
        const timeout = setTimeout(() => {
            clearInterval(checkInterval);
            reject(new Error('Stringee SDK timeout'));
        }, 12000);
    });
}

/**
 * Initialize Stringee Client
 */
async function initStringeeClient(token, serverAddrs) {
    try {
        await waitForStringeeSDK();
        
        if (!token || !serverAddrs || !Array.isArray(serverAddrs) || serverAddrs.length === 0) {
            throw new Error('Token hoặc server addresses không hợp lệ');
        }
        
        // Tạo client
        stringeeClient = new StringeeClient(serverAddrs);
        
        // QUAN TRỌNG: Setup WebSocket connection event handlers
        // Connection status monitoring
        stringeeClient.on('connect', function() {
            console.log('✅ [Stringee] WebSocket connected');
            if (typeof window.onStringeeConnected === 'function') {
                window.onStringeeConnected();
            }
        });
        
        stringeeClient.on('disconnect', function() {
            console.warn('⚠️ [Stringee] WebSocket disconnected');
            if (typeof window.onStringeeDisconnected === 'function') {
                window.onStringeeDisconnected();
            }
        });
        
        // Error handler
        stringeeClient.on('error', function(error) {
            console.error('❌ [Stringee] WebSocket error:', error);
            if (typeof window.onStringeeError === 'function') {
                window.onStringeeError(error);
            }
        });
        
        // Setup incoming call handler (voice call)
        stringeeClient.on('incomingcall', function(incomingCall) {
            console.log('📞 [Stringee] Incoming voice call received');
            stringeeCall = incomingCall;
            setupStringeeCallEvents();
            
            // Trigger global callback
            if (typeof window.onStringeeIncomingCall === 'function') {
                window.onStringeeIncomingCall(incomingCall);
            }
        });
        
        // Setup incoming call handler (video call) - nếu SDK hỗ trợ
        if (typeof StringeeCall2 !== 'undefined') {
            stringeeClient.on('incomingcall2', function(incomingCall2) {
                console.log('📞 [Stringee] Incoming video call received');
                stringeeCall = incomingCall2;
                setupStringeeCallEvents();
                
                // Trigger global callback
                if (typeof window.onStringeeIncomingCall === 'function') {
                    window.onStringeeIncomingCall(incomingCall2);
                }
            });
        }
        
        // Connect với token
        console.log('🔄 [Stringee] Connecting to WebSocket servers:', serverAddrs);
        await new Promise((resolve, reject) => {
            let authenticated = false;
            
            // Authentication handler
            stringeeClient.on('authen', function(res) {
                if (res.r === 0) {
                    authenticated = true;
                    console.log('✅ [Stringee] Authenticated successfully');
                    console.log('✅ [Stringee] User ID:', res.userId || 'N/A');
                    resolve();
                } else {
                    const errorMsg = res.m || res.message || 'Unknown authentication error';
                    console.error('❌ [Stringee] Authentication failed:', errorMsg);
                    reject(new Error('Authentication failed: ' + errorMsg));
                }
            });
            
            // Connect
            console.log('🔄 [Stringee] Calling stringeeClient.connect()...');
            stringeeClient.connect(token);
            
            // Timeout handler
            setTimeout(() => {
                if (!authenticated) {
                    console.error('❌ [Stringee] Connection timeout after 10 seconds');
                    reject(new Error('Connection timeout'));
                }
            }, 10000);
        });
        
        return stringeeClient;
        
    } catch (error) {
        console.error('❌ [Stringee] Error initializing client:', error);
        throw error;
    }
}

/**
 * Make Stringee Call
 * Theo documentation: Voice call dùng StringeeCall, Video call dùng StringeeCall2
 */
async function makeStringeeCall(fromUserId, toUserId, callType = 'voice', callId = null) {
    try {
        console.log('📞 [Stringee] Making call:', { fromUserId, toUserId, callType, callId });
        
        await waitForStringeeSDK();
        
        if (!stringeeClient) {
            throw new Error('Stringee client chưa được khởi tạo');
        }
        
        // Tạo call theo documentation
        const isVideoCall = callType === 'video';
        
        // Voice call: new StringeeCall(client, from, to) - 3 parameters
        // Video call: new StringeeCall2(client, from, to, true) - 4 parameters
        if (isVideoCall && typeof StringeeCall2 !== 'undefined') {
            // Video call với StringeeCall2
            stringeeCall = new StringeeCall2(stringeeClient, fromUserId, toUserId, true);
            console.log('📹 [Stringee] Using StringeeCall2 for video call');
        } else {
            // Voice call hoặc video call với StringeeCall (fallback)
            if (isVideoCall) {
                // Video call với StringeeCall (4 parameters)
                stringeeCall = new StringeeCall(stringeeClient, fromUserId, toUserId, true);
                console.log('📹 [Stringee] Using StringeeCall for video call (StringeeCall2 not available)');
            } else {
                // Voice call với StringeeCall (3 parameters)
                stringeeCall = new StringeeCall(stringeeClient, fromUserId, toUserId);
                console.log('📞 [Stringee] Using StringeeCall for voice call');
            }
        }
        
        // QUAN TRỌNG: Set customData với callId để Stringee callback có thể query database
        if (callId && stringeeCall.setCustomData) {
            try {
                stringeeCall.setCustomData(JSON.stringify({ callId: callId }));
                console.log('✅ [Stringee] Set customData with callId:', callId);
            } catch (e) {
                console.warn('⚠️ [Stringee] setCustomData not available:', e);
            }
        }
        
        // Setup events
        setupStringeeCallEvents();
        
        // Start call
        await new Promise((resolve, reject) => {
            stringeeCall.makeCall(function(res) {
                if (res.r === 0) {
                    console.log('✅ [Stringee] Call initiated');
                    
                    // QUAN TRỌNG: Lưu stringee_call_id vào database sau khi makeCall() thành công
                    // Stringee callId có thể lấy từ stringeeCall.callId hoặc res.callId
                    const stringeeCallId = stringeeCall.callId || res.callId;
                    if (stringeeCallId && callId) {
                        // Gọi API để update stringee_call_id
                        updateStringeeCallId(callId, stringeeCallId).catch(err => {
                            console.warn('⚠️ [Stringee] Failed to update stringee_call_id:', err);
                        });
                    }
                    
                    resolve();
                } else {
                    const errorMsg = res.m || res.message || 'Failed to make call';
                    console.error('❌ [Stringee] Call failed:', errorMsg);
                    reject(new Error(errorMsg));
                }
            });
        });
        
        return stringeeCall;
        
    } catch (error) {
        console.error('❌ [Stringee] Error making call:', error);
        throw error;
    }
}

/**
 * Answer Stringee Call
 */
async function answerStringeeCall(call) {
    try {
        console.log('📞 [Stringee] Answering call...');
        
        if (!call) {
            throw new Error('Call object không hợp lệ');
        }
        
        stringeeCall = call;
        setupStringeeCallEvents();
        
        // Answer call
        await new Promise((resolve, reject) => {
            stringeeCall.answer(function(res) {
                if (res.r === 0) {
                    console.log('✅ [Stringee] Call answered');
                    resolve();
                } else {
                    const errorMsg = res.m || res.message || 'Failed to answer call';
                    console.error('❌ [Stringee] Answer failed:', errorMsg);
                    reject(new Error(errorMsg));
                }
            });
        });
        
    } catch (error) {
        console.error('❌ [Stringee] Error answering call:', error);
        throw error;
    }
}

/**
 * Setup Stringee Call Events
 * Hỗ trợ cả StringeeCall (voice) và StringeeCall2 (video)
 */
function setupStringeeCallEvents() {
    if (!stringeeCall) return;
    
    // Kiểm tra xem là StringeeCall2 hay StringeeCall
    const isStringeeCall2 = stringeeCall.constructor.name === 'StringeeCall2' || 
                           (typeof StringeeCall2 !== 'undefined' && stringeeCall instanceof StringeeCall2);
    
    // Signaling state changed (theo documentation - dùng cho cả StringeeCall và StringeeCall2)
    stringeeCall.on('signalingstate', function(state) {
        console.log('📞 [Stringee] Signaling state:', state);
        
        // Xử lý các trạng thái cuộc gọi
        if (state.code === 6) { // Call ended
            console.log('📞 [Stringee] Call ended');
            if (typeof window.onCallEnded === 'function') {
                window.onCallEnded();
            }
        } else if (state.code === 5) { // Busy
            console.log('📞 [Stringee] Call busy');
            if (typeof window.onCallBusy === 'function') {
                window.onCallBusy();
            }
        } else if (state.code === 2) { // Answered
            console.log('✅ [Stringee] Call answered');
            if (typeof window.onCallAnswered === 'function') {
                window.onCallAnswered();
            }
        } else if (state.code === 1) { // Ringing
            console.log('🔔 [Stringee] Call ringing');
            if (typeof window.onCallRinging === 'function') {
                window.onCallRinging();
            }
        }
        
        if (typeof window.onCallSignalingState === 'function') {
            window.onCallSignalingState(state);
        }
    });
    
    // Media state changed (theo documentation - dùng cho cả StringeeCall và StringeeCall2)
    stringeeCall.on('mediastate', function(state) {
        console.log('📞 [Stringee] Media state:', state);
        
        if (typeof window.onCallMediaState === 'function') {
            window.onCallMediaState(state);
        }
    });
    
    // StringeeCall2 dùng addlocaltrack/addremotetrack
    // StringeeCall dùng addlocalstream/addremotestream
    if (isStringeeCall2) {
        // StringeeCall2 events (video call)
        stringeeCall.on('addlocaltrack', function(track) {
            console.log('📹 [Stringee] Local track added (StringeeCall2):', track.kind);
            
            if (track.kind === 'video') {
                localVideoTrack = track;
                const videoElement = document.getElementById('localVideo');
                if (videoElement) {
                    track.attach(videoElement);
                }
            } else if (track.kind === 'audio') {
                localAudioTrack = track;
            }
            
            if (typeof window.onStringeeLocalStreamAdded === 'function') {
                window.onStringeeLocalStreamAdded(track);
            }
        });
        
        stringeeCall.on('addremotetrack', function(track) {
            console.log('📹 [Stringee] Remote track added (StringeeCall2):', track.kind);
            
            if (track.kind === 'video') {
                remoteVideoTrack = track;
                const videoElement = document.getElementById('remoteVideo');
                if (videoElement) {
                    track.attach(videoElement);
                }
            } else if (track.kind === 'audio') {
                remoteAudioTrack = track;
                const audioElement = document.getElementById('remoteAudio');
                if (audioElement) {
                    track.attach(audioElement);
                }
            }
            
            if (typeof window.onStringeeRemoteStreamAdded === 'function') {
                window.onStringeeRemoteStreamAdded(track);
            }
        });
        
        stringeeCall.on('removeremotetrack', function(track) {
            console.log('📹 [Stringee] Remote track removed (StringeeCall2):', track.kind);
            track.detachAndRemove();
        });
        
        stringeeCall.on('removelocaltrack', function(track) {
            console.log('📹 [Stringee] Local track removed (StringeeCall2):', track.kind);
            track.detachAndRemove();
        });
    } else {
        // StringeeCall events (voice call hoặc video call với StringeeCall)
    stringeeCall.on('addlocalstream', function(stream) {
            console.log('📞 [Stringee] Local stream added (StringeeCall)');
            
            // Get video track
            if (stream.getVideoTracks && stream.getVideoTracks().length > 0) {
                localVideoTrack = stream.getVideoTracks()[0];
                const videoElement = document.getElementById('localVideo');
                if (videoElement) {
                    videoElement.srcObject = stream;
                    videoElement.play().catch(err => console.error('Error playing local video:', err));
                }
            }
            
            // Get audio track
            if (stream.getAudioTracks && stream.getAudioTracks().length > 0) {
                localAudioTrack = stream.getAudioTracks()[0];
            }
            
            if (typeof window.onStringeeLocalStreamAdded === 'function') {
                window.onStringeeLocalStreamAdded(stream);
        }
    });
    
    stringeeCall.on('addremotestream', function(stream) {
            console.log('📞 [Stringee] Remote stream added (StringeeCall)');
            
            // Get video track
            if (stream.getVideoTracks && stream.getVideoTracks().length > 0) {
                remoteVideoTrack = stream.getVideoTracks()[0];
                const videoElement = document.getElementById('remoteVideo');
                if (videoElement) {
                    videoElement.srcObject = stream;
                    videoElement.play().catch(err => console.error('Error playing remote video:', err));
                }
            }
            
            // Get audio track
            if (stream.getAudioTracks && stream.getAudioTracks().length > 0) {
                remoteAudioTrack = stream.getAudioTracks()[0];
                const audioElement = document.getElementById('remoteAudio');
                if (audioElement) {
                    audioElement.srcObject = stream;
                    audioElement.play().catch(err => console.error('Error playing remote audio:', err));
                }
            }
            
            if (typeof window.onStringeeRemoteStreamAdded === 'function') {
                window.onStringeeRemoteStreamAdded(stream);
            }
        });
    }
    
    // Call info (theo documentation - dùng cho cả StringeeCall và StringeeCall2)
    stringeeCall.on('info', function(info) {
        console.log('📞 [Stringee] Call info:', info);
        
        if (typeof window.onCallInfo === 'function') {
            window.onCallInfo(info);
        }
    });
    
    // Call error (dùng cho cả StringeeCall và StringeeCall2)
    stringeeCall.on('error', function(error) {
        console.error('❌ [Stringee] Call error:', error);
        
        if (typeof window.onStringeeCallError === 'function') {
            window.onStringeeCallError(error);
        }
    });
    
    // Other device (theo documentation - dùng cho cả StringeeCall và StringeeCall2)
    stringeeCall.on('otherdevice', function(data) {
        console.log('📞 [Stringee] Other device:', data);
        
        if (typeof window.onCallOtherDevice === 'function') {
            window.onCallOtherDevice(data);
        }
    });
}

/**
 * Enable Camera and Microphone
 */
async function enableCameraAndMicrophone() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: true
        });
        
        if (stringeeCall) {
            stringeeCall.setLocalMediaStream(stream);
        }
        
        return stream;
    } catch (error) {
        console.error('❌ [Stringee] Error enabling camera/microphone:', error);
        throw error;
    }
}

/**
 * Enable Microphone Only
 */
async function enableMicrophone() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: false,
            audio: true
        });
        
        if (stringeeCall) {
            stringeeCall.setLocalMediaStream(stream);
        }
        
        return stream;
    } catch (error) {
        console.error('❌ [Stringee] Error enabling microphone:', error);
        throw error;
    }
}

/**
 * Toggle Mute
 */
function toggleMute() {
    if (localAudioTrack) {
        localAudioTrack.enabled = !localAudioTrack.enabled;
        return !localAudioTrack.enabled;
    }
    return false;
}

/**
 * Toggle Camera
 */
function toggleCamera() {
    if (localVideoTrack) {
        localVideoTrack.enabled = !localVideoTrack.enabled;
        return !localVideoTrack.enabled;
    }
    return false;
}

/**
 * End Stringee Call
 */
function endStringeeCall() {
        if (stringeeCall) {
        stringeeCall.hangup();
        stringeeCall = null;
        }
        cleanupStringeeCall();
}

/**
 * Cleanup Stringee Call
 * Xử lý cả StringeeCall và StringeeCall2
 */
function cleanupStringeeCall() {
    console.log('🧹 [Stringee] Cleaning up call...');
    
    // Kiểm tra xem là StringeeCall2 hay StringeeCall
    const isStringeeCall2 = stringeeCall && (
        stringeeCall.constructor.name === 'StringeeCall2' || 
        (typeof StringeeCall2 !== 'undefined' && stringeeCall instanceof StringeeCall2)
    );
    
    if (isStringeeCall2 && stringeeCall) {
        // StringeeCall2: cần detach và remove tất cả tracks
        if (stringeeCall.subscribedTracks && Array.isArray(stringeeCall.subscribedTracks)) {
            stringeeCall.subscribedTracks.forEach((track) => {
                track.detachAndRemove();
            });
        }
    }
    
    // Stop local tracks
    if (localVideoTrack) {
        if (localVideoTrack.stop) {
        localVideoTrack.stop();
        } else if (localVideoTrack.detachAndRemove) {
            localVideoTrack.detachAndRemove();
        }
        localVideoTrack = null;
    }
    if (localAudioTrack) {
        if (localAudioTrack.stop) {
        localAudioTrack.stop();
        } else if (localAudioTrack.detachAndRemove) {
            localAudioTrack.detachAndRemove();
        }
        localAudioTrack = null;
    }
    
    // Stop remote tracks
    if (remoteVideoTrack) {
        if (remoteVideoTrack.stop) {
        remoteVideoTrack.stop();
        } else if (remoteVideoTrack.detachAndRemove) {
            remoteVideoTrack.detachAndRemove();
        }
        remoteVideoTrack = null;
    }
    if (remoteAudioTrack) {
        if (remoteAudioTrack.stop) {
        remoteAudioTrack.stop();
        } else if (remoteAudioTrack.detachAndRemove) {
            remoteAudioTrack.detachAndRemove();
        }
        remoteAudioTrack = null;
    }
    
    // Clear video elements
    const localVideo = document.getElementById('localVideo');
    const remoteVideo = document.getElementById('remoteVideo');
    const remoteAudio = document.getElementById('remoteAudio');
    
    if (localVideo) {
        localVideo.srcObject = null;
    }
    if (remoteVideo) {
        remoteVideo.srcObject = null;
    }
    if (remoteAudio) {
        remoteAudio.srcObject = null;
    }
    
    // Clear call object
    stringeeCall = null;
}

/**
 * Get Stringee Token and Join Call
 */
async function getStringeeTokenAndJoin(callId, callType, isCaller = true) {
    try {
        console.log('📞 [Stringee] Getting token and joining call...');
        
        await waitForStringeeSDK();
        
        // Xác định API path
        let apiPath = '../src/controllers/stringee-controller.php?action=get_token';
        if (typeof getApiPath === 'function') {
            apiPath = getApiPath('src/controllers/stringee-controller.php?action=get_token');
        } else {
            const path = window.location.pathname;
            if (path.includes('/admin/')) {
                apiPath = '../src/controllers/stringee-controller.php?action=get_token';
            }
        }
        
        // Lấy conversation ID
        const conversationId = window.currentConversationId || currentConversationId;
        if (!conversationId) {
            throw new Error('currentConversationId không được định nghĩa');
        }
        
        // Get token from server
        const response = await $.post(apiPath, {
            call_id: callId,
            conversation_id: conversationId
        });
        
        if (!response.success) {
            throw new Error(response.error || 'Failed to get token');
        }
        
        console.log('✅ [Stringee] Token received');
        
        // Initialize client
        await initStringeeClient(response.token, response.server_addrs);
        
        // Make or answer call
        if (isCaller) {
            // QUAN TRỌNG: Lấy receiver_id và caller_id từ response (đã được thêm trong stringee-controller.php)
            const receiverId = response.receiver_id;
            const callerId = response.caller_id || response.user_id;
            
            if (!receiverId || !callerId) {
                console.error('❌ [Stringee] Missing call info:', {
                    receiver_id: receiverId,
                    caller_id: callerId,
                    user_id: response.user_id,
                    response: response
                });
                throw new Error('Thiếu thông tin caller hoặc receiver ID. Vui lòng kiểm tra call session.');
            }
            
            // Đảm bảo cả hai đều là string (Stringee yêu cầu)
            const fromUserId = String(callerId);
            const toUserId = String(receiverId);
            
            console.log('📞 [Stringee] Making call:', {
                from: fromUserId,
                to: toUserId,
                callType: callType,
                callId: callId
            });
            
            // QUAN TRỌNG: Truyền callId để Stringee callback có thể query database
            await makeStringeeCall(fromUserId, toUserId, callType, callId);
        } else {
            console.log('📞 [Stringee] Waiting for incoming call...');
        }
            
            return response;
        
    } catch (error) {
        console.error('❌ [Stringee] Error getting token:', error);
        throw error;
    }
}

/**
 * Update Stringee Call ID in database
 * Gọi API để lưu stringee_call_id vào database
 */
async function updateStringeeCallId(callId, stringeeCallId) {
    try {
        // Xác định API path
        let apiPath = '../src/controllers/stringee-controller.php?action=update_stringee_call_id';
        if (typeof getApiPath === 'function') {
            apiPath = getApiPath('src/controllers/stringee-controller.php?action=update_stringee_call_id');
        } else {
            const path = window.location.pathname;
            if (path.includes('/admin/')) {
                apiPath = '../src/controllers/stringee-controller.php?action=update_stringee_call_id';
            }
        }
        
        const response = await $.post(apiPath, {
            call_id: callId,
            stringee_call_id: stringeeCallId
        });
        
        if (response.success) {
            console.log('✅ [Stringee] Updated stringee_call_id:', stringeeCallId);
        } else {
            console.warn('⚠️ [Stringee] Failed to update stringee_call_id:', response.error);
        }
    } catch (error) {
        console.error('❌ [Stringee] Error updating stringee_call_id:', error);
        throw error;
    }
}

/**
 * Get Stringee Client Connection Status
 * Kiểm tra xem WebSocket đã kết nối chưa
 */
function getStringeeConnectionStatus() {
    if (!stringeeClient) {
        return {
            connected: false,
            status: 'not_initialized',
            message: 'Stringee client chưa được khởi tạo'
        };
    }
    
    // Stringee SDK có thể có property để check connection status
    // Nếu không có, ta có thể check qua các event đã được trigger
    try {
        // Thử check connection state nếu SDK hỗ trợ
        if (stringeeClient.connected !== undefined) {
            return {
                connected: stringeeClient.connected === true,
                status: stringeeClient.connected ? 'connected' : 'disconnected',
                message: stringeeClient.connected ? 'Đã kết nối' : 'Chưa kết nối'
            };
        }
        
        // Fallback: giả định đã connected nếu client tồn tại
        return {
            connected: true,
            status: 'connected',
            message: 'Đã kết nối (status không thể xác định chính xác)'
        };
    } catch (error) {
        console.warn('⚠️ [Stringee] Cannot check connection status:', error);
        return {
            connected: false,
            status: 'unknown',
            message: 'Không thể kiểm tra trạng thái kết nối'
        };
    }
}

// Export functions
window.StringeeHelper = {
    initClient: initStringeeClient,
    makeCall: makeStringeeCall,
    answerCall: answerStringeeCall,
    enableCameraAndMicrophone: enableCameraAndMicrophone,
    enableMicrophone: enableMicrophone,
    toggleMute: toggleMute,
    toggleCamera: toggleCamera,
    endCall: endStringeeCall,
    cleanup: cleanupStringeeCall,
    getTokenAndJoin: getStringeeTokenAndJoin,
    getConnectionStatus: getStringeeConnectionStatus,
    getClient: function() { return stringeeClient; }
};

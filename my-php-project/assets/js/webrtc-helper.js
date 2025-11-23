/**
 * WebRTC Helper - Xử lý WebRTC peer-to-peer connections
 * Sử dụng Socket.IO cho signaling
 */

let localStream = null;
let remoteStream = null;
let peerConnection = null;
let currentCallId = null;
let currentCallType = 'voice'; // 'voice' hoặc 'video'
let isCaller = false;
let socket = null;

// STUN/TURN servers (có thể dùng free STUN servers)
const rtcConfiguration = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
        { urls: 'stun:stun2.l.google.com:19302' }
    ]
};

/**
 * Initialize WebRTC Helper
 */
function initWebRTCHelper(socketInstance) {
    socket = socketInstance;
    console.log('✅ WebRTC Helper initialized');
}

/**
 * Get user media (audio/video)
 */
async function getUserMedia(video = false, audio = true) {
    try {
        const constraints = {
            audio: audio,
            video: video ? {
                width: { ideal: 1280 },
                height: { ideal: 720 },
                facingMode: 'user'
            } : false
        };
        
        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        console.log('✅ Got user media:', { video, audio, stream });
        return stream;
    } catch (error) {
        console.error('❌ Error getting user media:', error);
        throw error;
    }
}

/**
 * Stop user media tracks
 */
function stopUserMedia() {
    if (localStream) {
        localStream.getTracks().forEach(track => {
            track.stop();
            console.log('🛑 Stopped track:', track.kind);
        });
        localStream = null;
    }
}

/**
 * Create RTCPeerConnection
 */
function createPeerConnection() {
    if (peerConnection) {
        console.warn('⚠️ Peer connection already exists, closing old one');
        peerConnection.close();
    }
    
    peerConnection = new RTCPeerConnection(rtcConfiguration);
    
    // Handle ICE candidates
    peerConnection.onicecandidate = (event) => {
        if (event.candidate && socket) {
            console.log('📤 Sending ICE candidate');
            socket.emit('ice-candidate', {
                call_id: currentCallId,
                candidate: event.candidate
            });
        }
    };
    
    // Handle remote stream
    peerConnection.ontrack = (event) => {
        console.log('📥 Received remote stream');
        remoteStream = event.streams[0];
        
        // Update UI với remote stream
        if (typeof window.onRemoteStream === 'function') {
            window.onRemoteStream(remoteStream);
        }
        
        // Auto-play remote audio/video
        const remoteAudio = document.getElementById('remoteAudio');
        const remoteVideo = document.getElementById('remoteVideo');
        
        if (remoteAudio && remoteStream.getAudioTracks().length > 0) {
            remoteAudio.srcObject = remoteStream;
            remoteAudio.play().catch(err => console.error('Error playing remote audio:', err));
        }
        
        if (remoteVideo && remoteStream.getVideoTracks().length > 0) {
            remoteVideo.srcObject = remoteStream;
            remoteVideo.play().catch(err => console.error('Error playing remote video:', err));
        }
    };
    
    // Handle connection state changes
    peerConnection.onconnectionstatechange = () => {
        console.log('📞 Connection state:', peerConnection.connectionState);
        
        if (peerConnection.connectionState === 'connected') {
            if (typeof window.onCallConnected === 'function') {
                window.onCallConnected();
            }
        } else if (peerConnection.connectionState === 'disconnected' || 
                   peerConnection.connectionState === 'failed') {
            if (typeof window.onCallDisconnected === 'function') {
                window.onCallDisconnected();
            }
        }
    };
    
    // Handle ICE connection state changes
    peerConnection.oniceconnectionstatechange = () => {
        console.log('🧊 ICE connection state:', peerConnection.iceConnectionState);
        
        if (peerConnection.iceConnectionState === 'failed') {
            console.error('❌ ICE connection failed');
            if (typeof window.onCallFailed === 'function') {
                window.onCallFailed('Kết nối thất bại');
            }
        }
    };
    
    return peerConnection;
}

/**
 * Initiate call (Caller)
 */
async function initiateCall(callId, callType, receiverId) {
    try {
        currentCallId = callId;
        currentCallType = callType;
        isCaller = true;
        
        console.log('📞 Initiating call:', { callId, callType, receiverId });
        
        // Get user media
        const video = callType === 'video';
        localStream = await getUserMedia(video, true);
        
        // Update UI với local stream
        if (typeof window.onLocalStream === 'function') {
            window.onLocalStream(localStream);
        }
        
        // Show local video/audio in UI
        const localVideo = document.getElementById('localVideo');
        const localAudio = document.getElementById('localAudio');
        
        if (localVideo && video) {
            localVideo.srcObject = localStream;
            localVideo.play().catch(err => console.error('Error playing local video:', err));
        }
        
        if (localAudio && !video) {
            localAudio.srcObject = localStream;
            localAudio.play().catch(err => console.error('Error playing local audio:', err));
        }
        
        // Create peer connection
        peerConnection = createPeerConnection();
        
        // Add local tracks to peer connection
        localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track, localStream);
            console.log('➕ Added track:', track.kind);
        });
        
        // Create offer
        const offer = await peerConnection.createOffer({
            offerToReceiveAudio: true,
            offerToReceiveVideo: video
        });
        
        await peerConnection.setLocalDescription(offer);
        console.log('✅ Created offer');
        
        // Send offer via Socket.IO
        if (socket) {
            socket.emit('call-offer', {
                call_id: callId,
                receiver_id: receiverId,
                offer: offer,
                call_type: callType
            });
        }
        
        return { success: true };
        
    } catch (error) {
        console.error('❌ Error initiating call:', error);
        cleanup();
        throw error;
    }
}

/**
 * Handle incoming call offer (Receiver)
 */
async function handleIncomingOffer(data) {
    try {
        currentCallId = data.call_id;
        currentCallType = data.call_type;
        isCaller = false;
        
        console.log('📞 Received call offer:', { callId: currentCallId, callType: currentCallType });
        
        // Get user media
        const video = currentCallType === 'video';
        localStream = await getUserMedia(video, true);
        
        // Update UI với local stream
        if (typeof window.onLocalStream === 'function') {
            window.onLocalStream(localStream);
        }
        
        // Show local video/audio in UI
        const localVideo = document.getElementById('localVideo');
        const localAudio = document.getElementById('localAudio');
        
        if (localVideo && video) {
            localVideo.srcObject = localStream;
            localVideo.play().catch(err => console.error('Error playing local video:', err));
        }
        
        if (localAudio && !video) {
            localAudio.srcObject = localStream;
            localAudio.play().catch(err => console.error('Error playing local audio:', err));
        }
        
        // Create peer connection
        peerConnection = createPeerConnection();
        
        // Add local tracks to peer connection
        localStream.getTracks().forEach(track => {
            peerConnection.addTrack(track, localStream);
            console.log('➕ Added track:', track.kind);
        });
        
        // Set remote description (offer)
        await peerConnection.setRemoteDescription(new RTCSessionDescription(data.offer));
        console.log('✅ Set remote description (offer)');
        
        // Create answer
        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);
        console.log('✅ Created answer');
        
        // Send answer via Socket.IO
        if (socket) {
            socket.emit('call-answer', {
                call_id: currentCallId,
                answer: answer
            });
        }
        
        return { success: true };
        
    } catch (error) {
        console.error('❌ Error handling incoming offer:', error);
        cleanup();
        throw error;
    }
}

/**
 * Handle incoming answer (Caller)
 */
async function handleIncomingAnswer(data) {
    try {
        console.log('📞 Received call answer');
        
        if (!peerConnection) {
            console.error('❌ No peer connection');
            return;
        }
        
        // Set remote description (answer)
        await peerConnection.setRemoteDescription(new RTCSessionDescription(data.answer));
        console.log('✅ Set remote description (answer)');
        
    } catch (error) {
        console.error('❌ Error handling incoming answer:', error);
        throw error;
    }
}

/**
 * Handle ICE candidate
 */
async function handleICECandidate(data) {
    try {
        if (!peerConnection) {
            console.warn('⚠️ No peer connection, ignoring ICE candidate');
            return;
        }
        
        if (data.candidate) {
            await peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate));
            console.log('✅ Added ICE candidate');
        } else {
            console.log('✅ End of ICE candidates');
        }
        
    } catch (error) {
        console.error('❌ Error handling ICE candidate:', error);
    }
}

/**
 * End call
 */
function endCall() {
    console.log('📞 Ending call');
    cleanup();
}

/**
 * Cleanup WebRTC resources
 */
function cleanup() {
    console.log('🧹 Cleaning up WebRTC resources');
    
    // Close peer connection
    if (peerConnection) {
        peerConnection.close();
        peerConnection = null;
    }
    
    // Stop local media
    stopUserMedia();
    
    // Clear remote streams
    remoteStream = null;
    
    // Clear UI elements
    const remoteAudio = document.getElementById('remoteAudio');
    const remoteVideo = document.getElementById('remoteVideo');
    const localVideo = document.getElementById('localVideo');
    const localAudio = document.getElementById('localAudio');
    
    if (remoteAudio) {
        remoteAudio.srcObject = null;
    }
    if (remoteVideo) {
        remoteVideo.srcObject = null;
    }
    if (localVideo) {
        localVideo.srcObject = null;
    }
    if (localAudio) {
        localAudio.srcObject = null;
    }
    
    currentCallId = null;
    currentCallType = 'voice';
    isCaller = false;
    
    console.log('✅ Cleanup completed');
}

/**
 * Toggle mute audio
 */
function toggleMute() {
    if (localStream) {
        const audioTracks = localStream.getAudioTracks();
        if (audioTracks.length > 0) {
            audioTracks[0].enabled = !audioTracks[0].enabled;
            console.log('🔇 Mute toggled:', !audioTracks[0].enabled);
            return !audioTracks[0].enabled;
        }
    }
    return false;
}

/**
 * Toggle camera (video)
 */
function toggleCamera() {
    if (localStream) {
        const videoTracks = localStream.getVideoTracks();
        if (videoTracks.length > 0) {
            videoTracks[0].enabled = !videoTracks[0].enabled;
            console.log('📹 Camera toggled:', !videoTracks[0].enabled);
            return !videoTracks[0].enabled;
        }
    }
    return false;
}

// Export functions
window.WebRTCHelper = {
    init: initWebRTCHelper,
    initiateCall: initiateCall,
    handleIncomingOffer: handleIncomingOffer,
    handleIncomingAnswer: handleIncomingAnswer,
    handleICECandidate: handleICECandidate,
    endCall: endCall,
    cleanup: cleanup,
    stopUserMedia: stopUserMedia,
    toggleMute: toggleMute,
    toggleCamera: toggleCamera
};


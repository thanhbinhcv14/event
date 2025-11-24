/**
 * WebRTC Helper - Xử lý kết nối WebRTC peer-to-peer
 * Sử dụng Socket.IO cho signaling
 */

let localStream = null;
let remoteStream = null;
let peerConnection = null;
let currentCallId = null;
let currentCallType = 'voice'; // 'voice' hoặc 'video'
let isCaller = false;
// Đổi tên thành webrtcSocket để tránh conflict với biến socket trong chat.php
let webrtcSocket = null;

// Cấu hình STUN/TURN servers (có thể dùng free STUN servers)
const rtcConfiguration = {
    iceServers: [
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
        { urls: 'stun:stun2.l.google.com:19302' }
    ]
};

/**
 * Khởi tạo WebRTC Helper
 */
function initWebRTCHelper(socketInstance) {
    webrtcSocket = socketInstance;
    console.log('✅ WebRTC Helper đã được khởi tạo');
}

/**
 * Lấy user media (audio/video)
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
        
        // Xử lý lỗi permission cụ thể
        let errorMessage = 'Lỗi truy cập camera/microphone';
        
        if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
            errorMessage = 'Bạn đã từ chối quyền truy cập camera/microphone. Vui lòng cấp quyền trong cài đặt trình duyệt.';
        } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
            errorMessage = 'Không tìm thấy thiết bị camera/microphone. Vui lòng kiểm tra thiết bị của bạn.';
        } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
            errorMessage = 'Không thể truy cập camera/microphone. Thiết bị có thể đang được sử dụng bởi ứng dụng khác.';
        } else if (error.name === 'OverconstrainedError' || error.name === 'ConstraintNotSatisfiedError') {
            errorMessage = 'Thiết bị không hỗ trợ yêu cầu. Vui lòng thử lại với cài đặt khác.';
        } else if (error.message) {
            errorMessage = error.message;
        }
        
        // Tạo error object với message rõ ràng
        const customError = new Error(errorMessage);
        customError.name = error.name;
        customError.originalError = error;
        throw customError;
    }
}

/**
 * Dừng user media tracks (mic và camera)
 */
function stopUserMedia() {
    if (localStream) {
        // QUAN TRỌNG: Dừng tất cả tracks (mic và camera) để giải phóng thiết bị
        localStream.getTracks().forEach(track => {
            track.stop(); // Dừng track và giải phóng thiết bị
            console.log('🛑 Đã dừng track:', track.kind);
        });
        localStream = null;
    }
}

/**
 * Tạo RTCPeerConnection
 */
function createPeerConnection() {
    if (peerConnection) {
        console.warn('⚠️ Peer connection đã tồn tại, đóng kết nối cũ');
        peerConnection.close();
    }
    
    peerConnection = new RTCPeerConnection(rtcConfiguration);
    
    // Xử lý ICE candidates
    peerConnection.onicecandidate = (event) => {
        if (event.candidate && webrtcSocket) {
            console.log('📤 Đang gửi ICE candidate');
            webrtcSocket.emit('ice-candidate', {
                call_id: currentCallId,
                candidate: event.candidate
            });
        }
    };
    
    // Xử lý remote stream
    peerConnection.ontrack = (event) => {
        console.log('📥 Đã nhận remote stream');
        remoteStream = event.streams[0];
        
        // Cập nhật UI với remote stream
        if (typeof window.onRemoteStream === 'function') {
            window.onRemoteStream(remoteStream);
        }
        
        // Tự động phát remote audio/video
        // QUAN TRỌNG: Chỉ play ở đây, không play lại trong onRemoteStream callback
        // để tránh AbortError (play() request bị interrupt bởi request mới)
        const remoteAudio = document.getElementById('remoteAudio');
        const remoteVideo = document.getElementById('remoteVideo');
        
        if (remoteAudio && remoteStream.getAudioTracks().length > 0) {
            // Kiểm tra xem audio element đã có srcObject chưa để tránh duplicate play
            if (remoteAudio.srcObject !== remoteStream) {
                remoteAudio.srcObject = remoteStream;
                // Sử dụng Promise để đảm bảo play() hoàn thành trước khi có request mới
                remoteAudio.play().catch(err => {
                    // Chỉ log error nếu không phải AbortError (có thể do user interaction)
                    if (err.name !== 'AbortError') {
                        console.error('Lỗi phát remote audio:', err);
                    }
                });
            }
        }
        
        if (remoteVideo && remoteStream.getVideoTracks().length > 0) {
            // Kiểm tra xem video element đã có srcObject chưa để tránh duplicate play
            if (remoteVideo.srcObject !== remoteStream) {
                remoteVideo.srcObject = remoteStream;
                remoteVideo.play().catch(err => {
                    if (err.name !== 'AbortError') {
                        console.error('Lỗi phát remote video:', err);
                    }
                });
            }
        }
    };
    
    // Xử lý thay đổi trạng thái kết nối
    peerConnection.onconnectionstatechange = () => {
        console.log('📞 Trạng thái kết nối:', peerConnection.connectionState);
        
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
    
    // Xử lý thay đổi trạng thái kết nối ICE
    peerConnection.oniceconnectionstatechange = () => {
        console.log('🧊 Trạng thái kết nối ICE:', peerConnection.iceConnectionState);
        
        if (peerConnection.iceConnectionState === 'failed') {
            console.error('❌ Kết nối ICE thất bại');
            if (typeof window.onCallFailed === 'function') {
                window.onCallFailed('Kết nối thất bại');
            }
        }
    };
    
    return peerConnection;
}

/**
 * Khởi tạo cuộc gọi (Caller - Người gọi)
 */
async function initiateCall(callId, callType, receiverId) {
    // QUAN TRỌNG: Khai báo biến ở đây (ngoài try block) để đảm bảo luôn có giá trị trong catch block
    // Nếu đã có localStream và peerConnection, không cần tạo lại
    // (Trường hợp gửi lại offer sau khi receiver accept)
    let isResendingOffer = localStream !== null && peerConnection !== null;
    
    try {
        currentCallId = callId;
        currentCallType = callType;
        isCaller = true;
        
        console.log('📞 Đang khởi tạo cuộc gọi:', { callId, callType, receiverId });
        
        // Cập nhật giá trị isResendingOffer sau khi đã set các biến
        isResendingOffer = localStream !== null && peerConnection !== null;
        
        if (!isResendingOffer) {
            // QUAN TRỌNG: Reset audio/video tracks về enabled khi bắt đầu call mới
            // Đảm bảo mic và camera đều bật khi bắt đầu call mới
            if (localStream) {
                localStream.getAudioTracks().forEach(track => {
                    track.enabled = true;
                });
                localStream.getVideoTracks().forEach(track => {
                    track.enabled = true;
                });
            }
            // Lấy user media với error handling tốt hơn
            const video = callType === 'video';
            try {
                localStream = await getUserMedia(video, true);
            } catch (mediaError) {
                console.error('❌ Lỗi khi lấy user media:', mediaError);
                // Re-throw error với message rõ ràng để caller có thể xử lý
                throw mediaError;
            }
            
            // Cập nhật UI với local stream
            if (typeof window.onLocalStream === 'function') {
                window.onLocalStream(localStream);
            }
            
            // Hiển thị local video/audio trong UI
            const localVideo = document.getElementById('localVideo');
            const localAudio = document.getElementById('localAudio');
            
            // Chỉ play local video/audio ở đây, không play lại trong onLocalStream callback
            // để tránh AbortError (play() request bị interrupt bởi request mới)
            if (localVideo && video) {
                // Kiểm tra xem video element đã có srcObject chưa để tránh duplicate play
                if (localVideo.srcObject !== localStream) {
                    localVideo.srcObject = localStream;
                    // Sử dụng Promise để đảm bảo play() hoàn thành trước khi có request mới
                    localVideo.play().catch(err => {
                        // Chỉ log error nếu không phải AbortError (có thể do user interaction)
                        if (err.name !== 'AbortError') {
                            console.error('Lỗi phát local video:', err);
                        }
                    });
                }
            }
            
            if (localAudio && !video) {
                // Kiểm tra xem audio element đã có srcObject chưa để tránh duplicate play
                if (localAudio.srcObject !== localStream) {
                    localAudio.srcObject = localStream;
                    localAudio.play().catch(err => {
                        if (err.name !== 'AbortError') {
                            console.error('Lỗi phát local audio:', err);
                        }
                    });
                }
            }
            
            // Tạo peer connection
            peerConnection = createPeerConnection();
            
            // QUAN TRỌNG: Đảm bảo tất cả tracks đều enabled khi bắt đầu call mới
            localStream.getTracks().forEach(track => {
                track.enabled = true; // Reset về enabled
                peerConnection.addTrack(track, localStream);
                console.log('➕ Đã thêm track:', track.kind, 'enabled:', track.enabled);
            });
        } else {
            console.log('📞 Đang gửi lại offer (tái sử dụng stream và connection hiện có)');
        }
        
        // Tạo offer (hoặc tạo lại nếu đã có peerConnection)
        if (!peerConnection) {
            peerConnection = createPeerConnection();
            if (localStream) {
                localStream.getTracks().forEach(track => {
                    peerConnection.addTrack(track, localStream);
                });
            }
        }
        
        const video = callType === 'video';
        const offer = await peerConnection.createOffer({
            offerToReceiveAudio: true,
            offerToReceiveVideo: video
        });
        
        await peerConnection.setLocalDescription(offer);
        console.log('✅ Đã tạo offer' + (isResendingOffer ? ' (gửi lại)' : ''));
        
        // Gửi offer qua Socket.IO
        if (webrtcSocket) {
            webrtcSocket.emit('call-offer', {
                call_id: callId,
                receiver_id: receiverId,
                offer: offer,
                call_type: callType
            });
        }
        
        return { success: true };
        
    } catch (error) {
        console.error('❌ Lỗi khi khởi tạo cuộc gọi:', error);
        // Không cleanup nếu đang resend offer (để giữ stream và connection)
        if (!isResendingOffer) {
            cleanup();
        }
        throw error;
    }
}

/**
 * Xử lý incoming call offer (Receiver - Người nhận)
 */
async function handleIncomingOffer(data) {
    try {
        currentCallId = data.call_id;
        currentCallType = data.call_type;
        isCaller = false;
        
        console.log('📞 Đã nhận call offer:', { callId: currentCallId, callType: currentCallType });
        
        // Tạo peer connection TRƯỚC để có thể nhận ICE candidates
        peerConnection = createPeerConnection();
        
        // Lấy user media với error handling tốt hơn
        const video = currentCallType === 'video';
        try {
            localStream = await getUserMedia(video, true);
            
            // QUAN TRỌNG: Đảm bảo tất cả tracks đều enabled khi nhận call mới
            if (localStream) {
                localStream.getAudioTracks().forEach(track => {
                    track.enabled = true; // Reset về enabled
                });
                localStream.getVideoTracks().forEach(track => {
                    track.enabled = true; // Reset về enabled
                });
            }
        } catch (mediaError) {
            console.error('❌ Lỗi khi lấy user media:', mediaError);
            
            // Nếu là video call và permission bị từ chối, thử chỉ audio
            if (video && (mediaError.name === 'NotAllowedError' || mediaError.name === 'PermissionDeniedError')) {
                console.warn('⚠️ Quyền video bị từ chối, thử chỉ audio...');
                try {
                    localStream = await getUserMedia(false, true); // Chỉ audio
                    console.log('✅ Đã lấy audio stream');
                } catch (audioError) {
                    console.error('❌ Quyền audio cũng bị từ chối:', audioError);
                    // Cleanup peer connection nếu có
                    if (peerConnection) {
                        peerConnection.close();
                        peerConnection = null;
                    }
                    // Re-throw với message rõ ràng
                    throw new Error('Bạn đã từ chối quyền truy cập camera/microphone. Vui lòng cấp quyền trong cài đặt trình duyệt.');
                }
            } else {
                // Cleanup peer connection nếu có
                if (peerConnection) {
                    peerConnection.close();
                    peerConnection = null;
                }
                // Re-throw error với message rõ ràng
                throw mediaError;
            }
        }
        
        // Cập nhật UI với local stream
        if (typeof window.onLocalStream === 'function') {
            window.onLocalStream(localStream);
        }
        
        // Hiển thị local video/audio trong UI
        const localVideo = document.getElementById('localVideo');
        const localAudio = document.getElementById('localAudio');
        
        if (localVideo && video) {
            localVideo.srcObject = localStream;
            localVideo.play().catch(err => console.error('Lỗi phát local video:', err));
        }
        
        if (localAudio && !video) {
            localAudio.srcObject = localStream;
            localAudio.play().catch(err => console.error('Lỗi phát local audio:', err));
        }
        
        // Peer connection đã được tạo ở trên để nhận ICE candidates sớm
        // Không cần tạo lại peer connection ở đây
        
        // QUAN TRỌNG: Đảm bảo tất cả tracks đều enabled khi nhận call mới
        // Thêm local tracks vào peer connection
        localStream.getTracks().forEach(track => {
            track.enabled = true; // Reset về enabled
            peerConnection.addTrack(track, localStream);
            console.log('➕ Đã thêm track:', track.kind, 'enabled:', track.enabled);
        });
        
        // Set remote description (offer)
        await peerConnection.setRemoteDescription(new RTCSessionDescription(data.offer));
        console.log('✅ Đã set remote description (offer)');
        
        // Xử lý queued ICE candidates sau khi remote description được set
        if (peerConnection._iceCandidateQueue && peerConnection._iceCandidateQueue.length > 0) {
            console.log(`📥 Đang xử lý ${peerConnection._iceCandidateQueue.length} queued ICE candidates`);
            for (const candidate of peerConnection._iceCandidateQueue) {
                try {
                    await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                    console.log('✅ Đã thêm queued ICE candidate');
                } catch (err) {
                    console.warn('⚠️ Lỗi khi thêm queued ICE candidate:', err);
                }
            }
            peerConnection._iceCandidateQueue = [];
        }
        
        // Tạo answer
        const answer = await peerConnection.createAnswer();
        await peerConnection.setLocalDescription(answer);
        console.log('✅ Đã tạo answer');
        
        // Gửi answer qua Socket.IO
        if (webrtcSocket) {
            webrtcSocket.emit('call-answer', {
                call_id: currentCallId,
                answer: answer
            });
        }
        
        return { success: true };
        
    } catch (error) {
        console.error('❌ Lỗi khi xử lý incoming offer:', error);
        cleanup();
        throw error;
    }
}

/**
 * Xử lý incoming answer (Caller - Người gọi)
 */
async function handleIncomingAnswer(data) {
    try {
        console.log('📞 Đã nhận call answer');
        
        if (!peerConnection) {
            console.error('❌ Không có peer connection');
            return;
        }
        
        // QUAN TRỌNG: Kiểm tra signaling state trước khi set remote description
        // Chỉ set remote answer nếu:
        // 1. Chưa có remote description (remoteDescription === null)
        // 2. Hoặc signaling state là 'have-local-offer' (đang chờ answer)
        const currentState = peerConnection.signalingState;
        const hasRemoteDescription = peerConnection.remoteDescription !== null;
        
        console.log('📞 Trạng thái signaling hiện tại:', currentState);
        console.log('📞 Đã có remote description:', hasRemoteDescription);
        
        if (hasRemoteDescription) {
            console.warn('⚠️ Remote description đã được set, bỏ qua...');
            // Vẫn xử lý queued ICE candidates nếu có
            if (peerConnection._iceCandidateQueue && peerConnection._iceCandidateQueue.length > 0) {
                console.log(`📥 Đang xử lý ${peerConnection._iceCandidateQueue.length} queued ICE candidates`);
                for (const candidate of peerConnection._iceCandidateQueue) {
                    try {
                        await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                        console.log('✅ Đã thêm queued ICE candidate');
                    } catch (err) {
                        console.warn('⚠️ Lỗi khi thêm queued ICE candidate:', err);
                    }
                }
                peerConnection._iceCandidateQueue = [];
            }
            return; // Đã set rồi, không cần set lại
        }
        
        // Kiểm tra signaling state phải là 'have-local-offer' để set remote answer
        if (currentState !== 'have-local-offer') {
            console.warn(`⚠️ Signaling state là '${currentState}', mong đợi 'have-local-offer'. Answer có thể được xử lý không đúng.`);
            // Vẫn thử set, nhưng sẽ catch error nếu fail
        }
        
        // Set remote description (answer)
        await peerConnection.setRemoteDescription(new RTCSessionDescription(data.answer));
        console.log('✅ Đã set remote description (answer)');
        
        // Xử lý queued ICE candidates sau khi remote description được set
        if (peerConnection._iceCandidateQueue && peerConnection._iceCandidateQueue.length > 0) {
            console.log(`📥 Đang xử lý ${peerConnection._iceCandidateQueue.length} queued ICE candidates`);
            for (const candidate of peerConnection._iceCandidateQueue) {
                try {
                    await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                    console.log('✅ Đã thêm queued ICE candidate');
                } catch (err) {
                    console.warn('⚠️ Lỗi khi thêm queued ICE candidate:', err);
                }
            }
            peerConnection._iceCandidateQueue = [];
        }
        
    } catch (error) {
        console.error('❌ Lỗi khi xử lý incoming answer:', error);
        // Không throw error để tránh crash, chỉ log
        // Error có thể do answer đã được set rồi hoặc state không đúng
        if (error.name === 'InvalidStateError') {
            console.warn('⚠️ InvalidStateError: Remote description có thể đã được set hoặc signaling state không đúng');
        }
    }
}

/**
 * Xử lý ICE candidate
 */
async function handleICECandidate(data) {
    try {
        if (!peerConnection) {
            console.warn('⚠️ Không có peer connection, bỏ qua ICE candidate');
            return;
        }
        
        // QUAN TRỌNG: Kiểm tra remote description đã được set chưa
        // ICE candidate chỉ có thể được add sau khi remote description đã được set
        if (!peerConnection.remoteDescription) {
            console.warn('⚠️ Remote description chưa được set, đưa ICE candidate vào queue');
            // Lưu ICE candidate vào queue để add sau khi remote description được set
            if (!peerConnection._iceCandidateQueue) {
                peerConnection._iceCandidateQueue = [];
            }
            if (data.candidate) {
                peerConnection._iceCandidateQueue.push(data.candidate);
            }
            return;
        }
        
        if (data.candidate) {
            await peerConnection.addIceCandidate(new RTCIceCandidate(data.candidate));
            console.log('✅ Đã thêm ICE candidate');
        } else {
            console.log('✅ Kết thúc ICE candidates');
        }
        
    } catch (error) {
        console.error('❌ Lỗi khi xử lý ICE candidate:', error);
        // Không throw error để tránh crash, chỉ log
    }
}

/**
 * Kết thúc cuộc gọi
 */
function endCall() {
    console.log('📞 Đang kết thúc cuộc gọi');
    cleanup();
}

/**
 * Cleanup WebRTC resources
 */
function cleanup() {
    console.log('🧹 Đang dọn dẹp tài nguyên WebRTC');
    
    // Đóng peer connection
    if (peerConnection) {
        peerConnection.close();
        peerConnection = null;
    }
    
    // Dừng local media (mic và camera)
    stopUserMedia();
    
    // Xóa remote streams
    remoteStream = null;
    
    // Xóa UI elements và đảm bảo camera được tắt
    const remoteAudio = document.getElementById('remoteAudio');
    const remoteVideo = document.getElementById('remoteVideo');
    const localVideo = document.getElementById('localVideo');
    const localAudio = document.getElementById('localAudio');
    
    if (remoteAudio) {
        remoteAudio.pause();
        remoteAudio.srcObject = null;
    }
    if (remoteVideo) {
        remoteVideo.pause();
        remoteVideo.srcObject = null;
    }
    // ✅ QUAN TRỌNG: Đảm bảo local video được pause và clear để tắt camera
    if (localVideo) {
        localVideo.pause();
        localVideo.srcObject = null;
        console.log('📹 Đã tắt local video (camera)');
    }
    if (localAudio) {
        localAudio.pause();
        localAudio.srcObject = null;
    }
    
    currentCallId = null;
    currentCallType = 'voice';
    isCaller = false;
    
    console.log('✅ Dọn dẹp hoàn tất');
}

/**
 * Bật/tắt mute audio (microphone)
 */
function toggleMute() {
    if (localStream) {
        const audioTracks = localStream.getAudioTracks();
        if (audioTracks.length > 0) {
            audioTracks[0].enabled = !audioTracks[0].enabled;
            console.log('🔇 Đã bật/tắt mute:', !audioTracks[0].enabled);
            return !audioTracks[0].enabled;
        }
    }
    return false;
}

/**
 * Bật/tắt camera (video)
 */
function toggleCamera() {
    if (localStream) {
        const videoTracks = localStream.getVideoTracks();
        if (videoTracks.length > 0) {
            videoTracks[0].enabled = !videoTracks[0].enabled;
            console.log('📹 Đã bật/tắt camera:', !videoTracks[0].enabled);
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


import React, { useEffect, useRef, useState } from "react";
import { FaPhone, FaPhoneSlash, FaMicrophone, FaMicrophoneSlash, FaVideo, FaVideoSlash } from "react-icons/fa";
import { useSearchParams } from "react-router-dom";
import io from "socket.io-client";
import "./VideoCall.css";

const VideoCallPage = () => {
    const [searchParams] = useSearchParams();
    const conversationId = searchParams.get("conversationId");
    const currentUserId = searchParams.get("currentUserId");
    const currentUserRole = searchParams.get("currentRole"); // 1, 3, hoặc 5
    const receiverId = searchParams.get("receiverId");
    const receiverRole = searchParams.get("receiverRole");
    
    const [currentUserData, setCurrentUserData] = useState(null);
    const [receiverUserData, setReceiverUserData] = useState(null);
    const myVideo = useRef(null);
    const peerVideo = useRef(null);
    const localStream = useRef(null);
    const peerConnection = useRef(null);
    const socket = useRef(null);
    
    const [cameraEnabled, setCameraEnabled] = useState(true);
    const [micEnabled, setMicEnabled] = useState(true);
    const [callStarted, setCallStarted] = useState(false);
    const [callStatus, setCallStatus] = useState("connecting"); // connecting, ringing, active, ended
    
    const iceServers = {
        iceServers: [
            { urls: "stun:stun.l.google.com:19302" },
            { urls: "stun:stun1.l.google.com:19302" }
        ],
    };
    
    // Lấy thông tin người dùng
    useEffect(() => {
        const fetchUserData = async () => {
            try {
                // Fetch current user data
                const currentUserResponse = await fetch(
                    `/src/controllers/chat-controller.php?action=get_user_info&user_id=${currentUserId}`
                );
                const currentUser = await currentUserResponse.json();
                setCurrentUserData(currentUser.data);
                
                // Fetch receiver user data
                const receiverResponse = await fetch(
                    `/src/controllers/chat-controller.php?action=get_user_info&user_id=${receiverId}`
                );
                const receiverUser = await receiverResponse.json();
                setReceiverUserData(receiverUser.data);
            } catch (error) {
                console.error("Lỗi khi fetch người dùng:", error);
            }
        };
        
        if (currentUserId && receiverId) {
            fetchUserData();
        }
    }, [currentUserId, receiverId]);
    
    // Khởi tạo Socket.IO và WebRTC
    useEffect(() => {
        if (!currentUserId || !receiverId || !conversationId) {
            console.error("Thiếu thông tin cần thiết cho video call");
            return;
        }
        
        // Kết nối Socket.IO
        const socketServerURL = window.location.hostname.includes('sukien.info.vn') 
            ? 'wss://ws.sukien.info.vn' 
            : 'http://localhost:3000';
        
        socket.current = io(socketServerURL, {
            path: '/socket.io',
            transports: ['polling', 'websocket']
        });
        
        socket.current.emit("addUser", currentUserId);
        socket.current.emit("join_user_room", { userId: currentUserId });
        socket.current.emit("join_conversation", { conversation_id: conversationId });
        
        // Xử lý WebRTC offer (người nhận)
        socket.current.on("webrtc-offer", async ({ from, offer, call_id }) => {
            console.log("📞 Received WebRTC offer from:", from);
            if (!peerConnection.current) {
                peerConnection.current = createPeerConnection();
            }
            
            try {
                await peerConnection.current.setRemoteDescription(new RTCSessionDescription(offer));
                const answer = await peerConnection.current.createAnswer();
                await peerConnection.current.setLocalDescription(answer);
                
                socket.current.emit("webrtc-answer", {
                    to: from,
                    from: currentUserId,
                    answer,
                    call_id
                });
                
                setCallStarted(true);
                setCallStatus("active");
            } catch (error) {
                console.error("❌ Error handling offer:", error);
            }
        });
        
        // Xử lý WebRTC answer (người gọi)
        socket.current.on("webrtc-answer", async ({ answer, call_id }) => {
            console.log("📞 Received WebRTC answer");
            if (peerConnection.current) {
                try {
                    await peerConnection.current.setRemoteDescription(new RTCSessionDescription(answer));
                    setCallStatus("active");
                } catch (error) {
                    console.error("❌ Error handling answer:", error);
                }
            }
        });
        
        // Xử lý ICE candidate
        socket.current.on("webrtc-ice-candidate", async ({ candidate, call_id }) => {
            try {
                if (peerConnection.current && candidate) {
                    await peerConnection.current.addIceCandidate(new RTCIceCandidate(candidate));
                }
            } catch (err) {
                console.error("❌ Lỗi add ICE candidate:", err);
            }
        });
        
        // Xử lý call ended
        socket.current.on("call_ended", ({ call_id }) => {
            console.log("📞 Call ended");
            leaveCall();
        });
        
        // Cleanup khi component unmount
        return () => {
            if (socket.current) {
                socket.current.disconnect();
            }
            if (peerConnection.current) {
                peerConnection.current.close();
                peerConnection.current = null;
            }
            if (localStream.current) {
                localStream.current.getTracks().forEach(track => track.stop());
                localStream.current = null;
            }
        };
    }, [conversationId, currentUserId, receiverId]);
    
    // Lấy media stream (camera + microphone)
    useEffect(() => {
        navigator.mediaDevices
            .getUserMedia({ video: true, audio: true })
            .then((stream) => {
                localStream.current = stream;
                if (myVideo.current) {
                    myVideo.current.srcObject = stream;
                }
            })
            .catch((err) => {
                console.error("❌ Lỗi getUserMedia:", err);
                alert("Không thể truy cập camera/microphone. Vui lòng kiểm tra quyền truy cập.");
            });
    }, []);
    
    const createPeerConnection = () => {
        const pc = new RTCPeerConnection(iceServers);
        
        // Thêm local tracks vào peer connection
        if (localStream.current) {
            localStream.current.getTracks().forEach((track) => {
                pc.addTrack(track, localStream.current);
            });
        }
        
        // Xử lý remote stream
        pc.ontrack = (event) => {
            console.log("✅ Received remote stream");
            if (peerVideo.current) {
                peerVideo.current.srcObject = event.streams[0];
            }
        };
        
        // Xử lý ICE candidates
        pc.onicecandidate = (event) => {
            if (event.candidate) {
                socket.current.emit("webrtc-ice-candidate", {
                    candidate: event.candidate,
                    to: receiverId,
                    from: currentUserId,
                    conversation_id: conversationId
                });
            }
        };
        
        // Xử lý connection state changes
        pc.onconnectionstatechange = () => {
            console.log("📡 Connection state:", pc.connectionState);
            if (pc.connectionState === "failed" || pc.connectionState === "disconnected") {
                setCallStatus("ended");
            }
        };
        
        return pc;
    };
    
    const startCall = async () => {
        if (callStarted) return;
        
        try {
            // Tạo call session trên server
            const response = await fetch('/src/controllers/call-controller.php?action=initiate_call', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    conversation_id: conversationId,
                    call_type: 'video'
                })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                alert('Lỗi khởi tạo cuộc gọi: ' + (data.error || 'Unknown error'));
                return;
            }
            
            setCallStatus("ringing");
            
            // Tạo peer connection
            peerConnection.current = createPeerConnection();
            
            // Tạo offer
            const offer = await peerConnection.current.createOffer();
            await peerConnection.current.setLocalDescription(offer);
            
            // Gửi offer qua socket
            socket.current.emit("webrtc-offer", {
                offer,
                to: receiverId,
                from: currentUserId,
                call_id: data.call_id,
                conversation_id: conversationId
            });
            
            setCallStarted(true);
        } catch (error) {
            console.error("❌ Error starting call:", error);
            alert("Lỗi khởi tạo cuộc gọi: " + error.message);
        }
    };
    
    const toggleCamera = () => {
        const videoTrack = localStream.current?.getVideoTracks()[0];
        if (videoTrack) {
            videoTrack.enabled = !videoTrack.enabled;
            setCameraEnabled(videoTrack.enabled);
        }
    };
    
    const toggleMic = () => {
        const audioTrack = localStream.current?.getAudioTracks()[0];
        if (audioTrack) {
            audioTrack.enabled = !audioTrack.enabled;
            setMicEnabled(audioTrack.enabled);
        }
    };
    
    const leaveCall = async () => {
        try {
            // Gửi signal end call qua socket
            if (socket.current) {
                socket.current.emit("call_ended", {
                    conversation_id: conversationId,
                    from: currentUserId,
                    to: receiverId
                });
            }
            
            // End call trên server
            await fetch('/src/controllers/call-controller.php?action=end_call', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    call_id: conversationId // Cần lưu call_id từ startCall
                })
            });
        } catch (error) {
            console.error("Error ending call:", error);
        }
        
        // Cleanup WebRTC
        if (peerConnection.current) {
            peerConnection.current.close();
            peerConnection.current = null;
        }
        
        // Stop local stream
        if (localStream.current) {
            localStream.current.getTracks().forEach((track) => track.stop());
            localStream.current = null;
        }
        
        // Clear video elements
        if (myVideo.current) myVideo.current.srcObject = null;
        if (peerVideo.current) peerVideo.current.srcObject = null;
        
        // Disconnect socket
        if (socket.current) {
            socket.current.disconnect();
        }
        
        setCallStarted(false);
        setCallStatus("ended");
        
        // Redirect về chat page
        window.location.href = `/chat.php?conversation=${conversationId}`;
    };
    
    const getRoleName = (role) => {
        const roleMap = {
            '1': 'Quản trị viên',
            '3': 'Quản lý sự kiện',
            '5': 'Khách hàng'
        };
        return roleMap[role] || 'Người dùng';
    };
    
    const LocalControls = () => (
        <div className="video-controls">
            {!callStarted && (
                <button onClick={startCall} title="Bắt đầu gọi" className="btn blue">
                    <FaPhone size={20} />
                </button>
            )}
            {callStarted && (
                <button onClick={leaveCall} title="Rời cuộc gọi" className="btn red">
                    <FaPhoneSlash size={20} />
                </button>
            )}
            <button 
                onClick={toggleCamera} 
                title={cameraEnabled ? "Tắt camera" : "Bật camera"} 
                className={`btn ${cameraEnabled ? 'blue' : 'gray'}`}
            >
                {cameraEnabled ? <FaVideo size={20} /> : <FaVideoSlash size={20} />}
            </button>
            <button 
                onClick={toggleMic} 
                title={micEnabled ? "Tắt mic" : "Bật mic"} 
                className={`btn ${micEnabled ? 'blue' : 'gray'}`}
            >
                {micEnabled ? <FaMicrophone size={20} /> : <FaMicrophoneSlash size={20} />}
            </button>
        </div>
    );
    
    return (
        <div className="video-container">
            <div className="video-box">
                <video ref={myVideo} autoPlay playsInline muted className="video" />
                <div className="label">
                    Bạn ({getRoleName(currentUserRole)}) - {currentUserData?.HoTen || currentUserData?.Email || "Đang tải..."}
                </div>
                <LocalControls />
            </div>
            <div className="video-box">
                <video ref={peerVideo} autoPlay playsInline className="video" />
                <div className="label">
                    {getRoleName(receiverRole)} - {receiverUserData?.HoTen || receiverUserData?.Email || "Đang tải..."}
                </div>
                {callStatus === "ringing" && (
                    <div className="call-status">Đang gọi...</div>
                )}
                {callStatus === "active" && (
                    <div className="call-status active">Đang kết nối</div>
                )}
            </div>
        </div>
    );
};

export default VideoCallPage;


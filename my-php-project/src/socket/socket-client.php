<?php
/**
 * Socket.IO Client Integration for Event Management System
 * This class handles real-time notifications and communication
 */

class SocketClient {
    private $socketUrl;
    private $socketPort;
    
    public function __construct($url = 'http://localhost', $port = 3000) {
        $this->socketUrl = $url;
        $this->socketPort = $port;
    }
    
    /**
     * Send event registration notification to admins
     */
    public function notifyEventRegistration($eventId, $eventName, $userName, $userId) {
        $data = [
            'eventId' => $eventId,
            'eventName' => $eventName,
            'userName' => $userName,
            'userId' => $userId
        ];
        
        $this->sendToSocket('event_registered', $data);
    }
    
    /**
     * Send event status update notification to user
     */
    public function notifyEventStatusUpdate($eventId, $eventName, $status, $userName, $adminName, $userId) {
        $data = [
            'eventId' => $eventId,
            'eventName' => $eventName,
            'status' => $status,
            'userName' => $userName,
            'adminName' => $adminName,
            'userId' => $userId
        ];
        
        $this->sendToSocket('event_status_updated', $data);
    }
    
    /**
     * Send admin comment notification to user
     */
    public function notifyAdminComment($eventId, $eventName, $comment, $adminName, $userId) {
        $data = [
            'eventId' => $eventId,
            'eventName' => $eventName,
            'comment' => $comment,
            'adminName' => $adminName,
            'userId' => $userId
        ];
        
        $this->sendToSocket('admin_comment_added', $data);
    }
    
    /**
     * Send system notification to all users
     */
    public function sendSystemNotification($message, $type = 'info') {
        $data = [
            'message' => $message,
            'type' => $type
        ];
        
        $this->sendToSocket('system_notification', $data);
    }
    
    /**
     * Send data to Socket.IO server
     */
    private function sendToSocket($event, $data) {
        $url = $this->socketUrl . ':' . $this->socketPort . '/socket.io/';
        
        // Create cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'event' => $event,
            'data' => $data
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode([
                'event' => $event,
                'data' => $data
            ]))
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Log the result
        error_log("Socket notification sent: $event, HTTP Code: $httpCode");
        
        return $httpCode === 200;
    }
    
    /**
     * Get Socket.IO client script
     */
    public function getClientScript($userId = null, $userRole = null, $userName = null) {
        $script = "
        <script src=\"{$this->socketUrl}:{$this->socketPort}/socket.io/socket.io.js\"></script>
        <script>
            // Initialize Socket.IO connection
            const socket = io('{$this->socketUrl}:{$this->socketPort}');
            
            // Connection status
            socket.on('connect', function() {
                console.log('Connected to Socket.IO server');
                updateConnectionStatus(true);
            });
            
            socket.on('disconnect', function() {
                console.log('Disconnected from Socket.IO server');
                updateConnectionStatus(false);
            });
            
            // Authentication
            " . ($userId ? "
            socket.emit('authenticate', {
                userId: {$userId},
                userRole: " . ($userRole ?: 'null') . ",
                userName: '" . ($userName ?: 'User') . "'
            });
            " : "") . "
            
            // Event registration notifications
            socket.on('new_event_registration', function(data) {
                showNotification('success', data.message, 'Sự kiện mới');
                console.log('New event registration:', data);
            });
            
            // Event status updates
            socket.on('event_status_change', function(data) {
                showNotification('info', data.message, 'Cập nhật trạng thái');
                console.log('Event status change:', data);
            });
            
            // Admin comments
            socket.on('admin_comment', function(data) {
                showNotification('warning', data.message, 'Ghi chú admin');
                console.log('Admin comment:', data);
            });
            
            // System notifications
            socket.on('system_notification', function(data) {
                showNotification('info', data.message, 'Thông báo hệ thống');
                console.log('System notification:', data);
            });
            
            // Admin notifications
            socket.on('admin_notification', function(data) {
                showNotification('info', data.message, 'Thông báo admin');
                console.log('Admin notification:', data);
            });
            
            // Connection status indicator
            function updateConnectionStatus(connected) {
                const indicator = document.getElementById('socketStatus');
                if (indicator) {
                    indicator.className = connected ? 'text-success' : 'text-danger';
                    indicator.innerHTML = connected ? 
                        '<i class=\"fa fa-circle\"></i> Đã kết nối' : 
                        '<i class=\"fa fa-circle\"></i> Mất kết nối';
                }
            }
            
            // Notification system
            function showNotification(type, message, title) {
                // Create notification element
                const notification = document.createElement('div');
                notification.className = 'alert alert-' + type + ' alert-dismissible fade show position-fixed';
                notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                notification.innerHTML = `
                    <strong>` + title + `</strong><br>
                    ` + message + `
                    <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
                `;
                
                document.body.appendChild(notification);
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 5000);
            }
            
            // Export socket for use in other scripts
            window.socket = socket;
        </script>
        ";
        
        return $script;
    }
    
    /**
     * Get connection status indicator HTML
     */
    public function getConnectionStatusIndicator() {
        return '
        <div class="d-flex align-items-center">
            <span id="socketStatus" class="text-muted">
                <i class="fa fa-circle"></i> Đang kết nối...
            </span>
        </div>';
    }
}

// Helper function to get Socket.IO client instance
function getSocketClient() {
    return new SocketClient();
}

// Helper function to send event registration notification
function notifyEventRegistration($eventId, $eventName, $userName, $userId) {
    $socket = getSocketClient();
    return $socket->notifyEventRegistration($eventId, $eventName, $userName, $userId);
}

// Helper function to send event status update notification
function notifyEventStatusUpdate($eventId, $eventName, $status, $userName, $adminName, $userId) {
    $socket = getSocketClient();
    return $socket->notifyEventStatusUpdate($eventId, $eventName, $status, $userName, $adminName, $userId);
}

// Helper function to send admin comment notification
function notifyAdminComment($eventId, $eventName, $comment, $adminName, $userId) {
    $socket = getSocketClient();
    return $socket->notifyAdminComment($eventId, $eventName, $comment, $adminName, $userId);
}

// Helper function to send system notification
function sendSystemNotification($message, $type = 'info') {
    $socket = getSocketClient();
    return $socket->sendSystemNotification($message, $type);
}
?>

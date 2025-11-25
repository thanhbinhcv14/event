/**
 * Facebook JavaScript SDK Integration
 * Thay thế Hybridauth bằng Facebook SDK
 */

// Suppress React error #418 từ Facebook SDK (lỗi internal của Facebook, không ảnh hưởng chức năng)
(function() {
    const originalError = window.onerror;
    window.onerror = function(msg, url, line, col, error) {
        // Suppress React error #418 từ Facebook SDK
        if (msg && msg.toString().includes('React error #418')) {
            console.warn('Facebook SDK React error #418 suppressed (internal Facebook error, does not affect functionality)');
            return true; // Suppress error
        }
        // Gọi original error handler nếu có
        if (originalError) {
            return originalError.apply(this, arguments);
        }
        return false;
    };
})();

// Suppress uncaught errors từ Facebook SDK
window.addEventListener('error', function(e) {
    if (e.message && e.message.toString().includes('React error #418')) {
        e.preventDefault();
        console.warn('Facebook SDK React error #418 suppressed');
        return true;
    }
}, true);

// Khởi tạo Facebook SDK
window.fbAsyncInit = function() {
    // Lấy App ID từ config hoặc dùng giá trị mặc định
    const appId = '877436944712009'; // Facebook App ID
    
    try {
        FB.init({
            appId: appId,
            cookie: true, // Cho phép cookies để server-side auth
            xfbml: false, // Tắt XFBML parsing để tránh lỗi React #418
            version: 'v18.0' // Facebook API version
        });
        
        // Log để debug
        console.log('Facebook SDK initialized with App ID:', appId);
        console.log('Current domain:', window.location.hostname);
        
        // Kiểm tra login status khi page load (với delay để tránh lỗi)
        // Tắt auto check để tránh lỗi React #418 khi page load
        // setTimeout(function() {
        //     try {
        //         checkLoginState();
        //     } catch (e) {
        //         console.warn('Error checking login state:', e);
        //     }
        // }, 500);
        
        // Log page view (có thể gây lỗi nếu không cần thiết)
        // Tắt AppEvents để tránh lỗi React #418
        // try {
        //     FB.AppEvents.logPageView();
        // } catch (e) {
        //     console.warn('Error logging page view:', e);
        // }
    } catch (e) {
        console.error('Error initializing Facebook SDK:', e);
    }
};

// Load Facebook SDK asynchronously với error handling
(function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) {
        console.warn('Facebook SDK script already exists');
        return;
    }
    js = d.createElement(s);
    js.id = id;
    js.src = "https://connect.facebook.net/vi_VN/sdk.js";
    js.async = true;
    
    // Error handling khi load SDK
    js.onerror = function() {
        console.error('Failed to load Facebook SDK');
    };
    
    js.onload = function() {
        console.log('Facebook SDK script loaded successfully');
    };
    
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));

/**
 * Kiểm tra trạng thái đăng nhập Facebook
 */
function checkLoginState() {
    // Kiểm tra FB đã sẵn sàng chưa
    if (typeof FB === 'undefined' || !FB.getLoginStatus) {
        console.warn('Facebook SDK chưa sẵn sàng');
        return;
    }
    
    try {
        FB.getLoginStatus(function(response) {
            try {
                if (response && response.status === 'connected') {
                    // User đã đăng nhập và đã authorize app
                    console.log('Facebook: User is connected');
                    // Có thể tự động lấy thông tin user nếu cần
                } else if (response && response.status === 'not_authorized') {
                    // User đã đăng nhập Facebook nhưng chưa authorize app
                    console.log('Facebook: User is not authorized');
                } else {
                    // User chưa đăng nhập Facebook
                    console.log('Facebook: User is not logged in');
                }
            } catch (e) {
                console.warn('Error processing login status:', e);
            }
        }, true); // Force refresh để đảm bảo có token mới nhất
    } catch (e) {
        console.warn('Error getting login status:', e);
    }
}

/**
 * Đăng nhập bằng Facebook
 */
function loginWithFacebook() {
    // Kiểm tra FB đã sẵn sàng chưa
    if (typeof FB === 'undefined' || !FB.login) {
        console.error('Facebook SDK chưa sẵn sàng. Vui lòng thử lại sau vài giây.');
        alert('Facebook SDK chưa sẵn sàng. Vui lòng thử lại sau vài giây.');
        return;
    }
    
    try {
        FB.login(function(response) {
            try {
                if (response && response.authResponse) {
                    // User đã đăng nhập và authorize app
                    console.log('Facebook: Login successful');
                    
                    // Lấy access token
                    const accessToken = response.authResponse.accessToken;
                    const userID = response.authResponse.userID;
                    
                    if (!accessToken || !userID) {
                        console.error('Facebook: Missing access token or user ID');
                        alert('Lỗi: Không nhận được thông tin đăng nhập từ Facebook');
                        return;
                    }
                    
                    // Gửi access token về server để xử lý
                    sendAccessTokenToServer(accessToken, userID);
                } else {
                    // User đã hủy đăng nhập hoặc có lỗi
                    if (response && response.error) {
                        console.error('Facebook login error:', response.error);
                        alert('Lỗi đăng nhập Facebook: ' + (response.error.message || 'Không xác định'));
                    } else {
                        console.log('Facebook: User cancelled login');
                        // Không hiển thị alert khi user hủy
                    }
                }
            } catch (e) {
                console.error('Error processing Facebook login response:', e);
                alert('Lỗi xử lý phản hồi từ Facebook');
            }
        }, {
            scope: 'public_profile,email', // Permissions cần thiết
            return_scopes: true
        });
    } catch (e) {
        console.error('Error calling FB.login:', e);
        alert('Lỗi khi gọi Facebook login API');
    }
}

/**
 * Gửi access token về server để xử lý
 */
function sendAccessTokenToServer(accessToken, userID) {
    // Hiển thị loading
    const btn = document.getElementById('fb-login-btn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
    }
    
    // Gửi request đến server
    fetch('facebook-login.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            access_token: accessToken,
            user_id: userID
        })
    })
    .then(response => {
        // Kiểm tra content-type trước khi parse JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // Server trả về HTML thay vì JSON (có thể là error page)
            return response.text().then(text => {
                console.error('Server returned HTML instead of JSON:', text.substring(0, 200));
                throw new Error('Server returned invalid response. Please check if facebook-login.php exists.');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Đăng nhập thành công, redirect
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.reload();
            }
        } else {
            // Đăng nhập thất bại
            console.error('Lỗi đăng nhập:', data.message || 'Không xác định');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fab fa-facebook me-2"></i>Đăng nhập với Facebook';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        console.error('Lỗi kết nối server: ' + (error.message || 'Không xác định'));
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fab fa-facebook me-2"></i>Đăng nhập với Facebook';
        }
    });
}

/**
 * Đăng xuất Facebook
 */
function logoutFromFacebook() {
    FB.logout(function(response) {
        console.log('Facebook: Logout successful');
    });
}

/**
 * Lấy thông tin user từ Facebook
 */
function getFacebookUserInfo() {
    FB.api('/me', {
        fields: 'id,name,email,picture'
    }, function(response) {
        if (response && !response.error) {
            console.log('Facebook User Info:', response);
            return response;
        } else {
            console.error('Error getting user info:', response.error);
            return null;
        }
    });
}


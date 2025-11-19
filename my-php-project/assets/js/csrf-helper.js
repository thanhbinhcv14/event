/**
 * CSRF Protection Helper for Frontend
 * Tự động thêm CSRF token vào tất cả AJAX requests
 */

(function() {
    'use strict';
    
    let csrfToken = null;
    
    /**
     * Lấy CSRF token từ server
     */
    async function fetchCSRFToken() {
        try {
            // Xác định đường dẫn tương đối dựa trên vị trí hiện tại
            // Sử dụng base URL hoặc tính toán từ pathname
            const pathname = window.location.pathname;
            const basePath = pathname.substring(0, pathname.lastIndexOf('/'));
            
            // Xác định số cấp thư mục cần lùi lại
            let levelsUp = 0;
            
            // Đếm số cấp thư mục từ root project
            if (pathname.includes('/admin/')) {
                // Từ /admin/xxx.php cần lùi 1 cấp về root
                levelsUp = 1;
            } else if (pathname.includes('/events/')) {
                // Từ /events/xxx.php cần lùi 1 cấp về root
                levelsUp = 1;
            } else if (pathname.includes('/payment/')) {
                // Từ /payment/xxx.php cần lùi 1 cấp về root
                levelsUp = 1;
            } else if (pathname.includes('/includes/')) {
                // Từ /includes/xxx.php cần lùi 1 cấp về root
                levelsUp = 1;
            }
            // Nếu đang ở root (index.php, login.php, etc.), không cần lùi
            
            // Tạo đường dẫn với số cấp lùi lại
            let csrfUrl = '';
            for (let i = 0; i < levelsUp; i++) {
                csrfUrl += '../';
            }
            csrfUrl += 'src/controllers/event-register.php?action=get_csrf_token';
            
            // Debug: log URL để kiểm tra
            console.log('Fetching CSRF token from:', csrfUrl, 'Current pathname:', pathname);
            
            let response = await fetch(csrfUrl, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            // Kiểm tra content-type trước khi parse
            let contentType = response.headers.get('content-type');
            let isJson = contentType && contentType.includes('application/json');
            
            // Nếu response không phải JSON, thử với absolute URL
            if (response.ok && !isJson) {
                // Clone response để có thể đọc text mà không consume body
                const clonedResponse = response.clone();
                const responseText = await clonedResponse.text();
                // Nếu trả về HTML, có thể URL không đúng, thử với absolute path
                if (responseText.trim().startsWith('<!')) {
                    console.warn('Relative URL returned HTML, trying absolute URL');
                    // Tạo absolute URL từ origin và pathname
                    const origin = window.location.origin;
                    // Tìm base path của project (thường là /event/my-php-project hoặc /my-php-project)
                    const pathParts = pathname.split('/').filter(p => p);
                    // Tìm vị trí của 'my-php-project' hoặc project root
                    let projectRootIndex = -1;
                    for (let i = 0; i < pathParts.length; i++) {
                        if (pathParts[i] === 'my-php-project' || pathParts[i] === 'event') {
                            projectRootIndex = i;
                            break;
                        }
                    }
                    // Nếu không tìm thấy, giả sử toàn bộ path là project root
                    if (projectRootIndex === -1) {
                        projectRootIndex = 0;
                    }
                    // Lấy project base path
                    const projectBase = '/' + pathParts.slice(0, projectRootIndex + 1).join('/');
                    const absoluteUrl = origin + projectBase + '/src/controllers/event-register.php?action=get_csrf_token';
                    console.log('Trying absolute URL:', absoluteUrl);
                    
                    response = await fetch(absoluteUrl, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });
                    contentType = response.headers.get('content-type');
                    isJson = contentType && contentType.includes('application/json');
                }
            }
            
            if (response.ok && isJson) {
                try {
                    const data = await response.json();
                    if (data.success && data.csrf_token) {
                        csrfToken = data.csrf_token;
                        return csrfToken;
                    } else {
                        console.warn('CSRF token response invalid:', data);
                    }
                } catch (jsonError) {
                    console.error('Failed to parse CSRF token JSON:', jsonError);
                }
            } else {
                // Đọc response text để debug (chỉ đọc một lần)
                const responseText = await response.text();
                if (response.ok) {
                    console.warn('CSRF token endpoint returned non-JSON. Status:', response.status, 'Content-Type:', contentType, 'URL:', csrfUrl, 'Preview:', responseText.substring(0, 200));
                    // Thử parse lại nếu có thể là JSON nhưng content-type sai
                    try {
                        const data = JSON.parse(responseText);
                        if (data.success && data.csrf_token) {
                            csrfToken = data.csrf_token;
                            return csrfToken;
                        }
                    } catch (e) {
                        // Không phải JSON, bỏ qua
                    }
                } else {
                    console.warn('CSRF token fetch failed. Status:', response.status, 'URL:', csrfUrl, 'Response:', responseText.substring(0, 200));
                }
            }
        } catch (error) {
            console.error('Error fetching CSRF token:', error);
            // Không throw error để tránh break app, chỉ log
        }
        return null;
    }
    
    /**
     * Lấy CSRF token (từ cache hoặc fetch mới)
     * @param {boolean} forceRefresh - Force refresh token
     */
    async function getCSRFToken(forceRefresh = false) {
        if (csrfToken && !forceRefresh) {
            return csrfToken;
        }
        return await fetchCSRFToken();
    }
    
    /**
     * Lấy CSRF token đồng bộ (chỉ trả về nếu đã có trong cache)
     * @returns {string|null} Token nếu có, null nếu chưa có
     */
    function getCSRFTokenSync() {
        return csrfToken;
    }
    
    /**
     * Thêm CSRF token vào data object
     */
    async function addCSRFToken(data) {
        if (!data) {
            data = {};
        }
        
        // Nếu data là FormData, thêm token vào FormData
        if (data instanceof FormData) {
            const token = await getCSRFToken();
            if (token) {
                data.append('csrf_token', token);
            }
            return data;
        }
        
        // Nếu data là object, thêm token vào object
        if (typeof data === 'object') {
            const token = await getCSRFToken();
            if (token) {
                data.csrf_token = token;
            }
            return data;
        }
        
        return data;
    }
    
    /**
     * Override jQuery AJAX để tự động thêm CSRF token
     */
    if (typeof jQuery !== 'undefined') {
        // Intercept jQuery AJAX requests
        const originalAjax = jQuery.ajax;
        
        jQuery.ajax = function(options) {
            // Chỉ thêm CSRF token cho POST, PUT, DELETE requests
            const method = (options.method || options.type || 'GET').toUpperCase();
            
            if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
                // Kiểm tra nếu là JSON request
                const isJsonRequest = options.contentType && options.contentType.includes('application/json');
                
                // Lấy token đồng bộ (nếu đã có trong cache)
                const currentToken = getCSRFTokenSync();
                
                // Thêm token vào data ngay lập tức (nếu đã có trong cache)
                if (currentToken) {
                    if (options.data) {
                        if (options.data instanceof FormData) {
                            if (!options.data.has('csrf_token')) {
                                options.data.append('csrf_token', currentToken);
                            }
                        } else if (typeof options.data === 'object' && !Array.isArray(options.data)) {
                            if (!options.data.csrf_token) {
                                options.data.csrf_token = currentToken;
                            }
                        } else if (typeof options.data === 'string') {
                            if (isJsonRequest) {
                                try {
                                    const jsonData = JSON.parse(options.data);
                                    if (!jsonData.csrf_token) {
                                        jsonData.csrf_token = currentToken;
                                        options.data = JSON.stringify(jsonData);
                                    }
                                } catch (e) {
                                    console.warn('Failed to parse JSON data for CSRF token:', e);
                                }
                            }
                        }
                    } else {
                        if (isJsonRequest) {
                            options.data = JSON.stringify({ csrf_token: currentToken });
                        } else {
                            options.data = { csrf_token: currentToken };
                        }
                    }
                    
                    // Thêm vào headers
                    if (!options.headers) {
                        options.headers = {};
                    }
                    options.headers['X-CSRF-Token'] = currentToken;
                } else {
                    // Token chưa có, log warning và thêm vào beforeSend
                    console.warn('CSRF token not in cache, will fetch in beforeSend');
                }
                
                const originalBeforeSend = options.beforeSend;
                
                // Sử dụng beforeSend để đảm bảo token được thêm vào header và data
                options.beforeSend = function(xhr, settings) {
                    // Gọi original beforeSend nếu có
                    if (originalBeforeSend) {
                        const result = originalBeforeSend.call(this, xhr, settings);
                        if (result === false) {
                            return false;
                        }
                    }
                    
                    // Lấy token (sync nếu có, async nếu chưa có)
                    const token = getCSRFTokenSync();
                    
                    if (token) {
                        // Đảm bảo header có token
                        xhr.setRequestHeader('X-CSRF-Token', token);
                        
                        // Đảm bảo data có token (nếu chưa có)
                        if (settings.data) {
                            if (settings.data instanceof FormData) {
                                if (!settings.data.has('csrf_token')) {
                                    settings.data.append('csrf_token', token);
                                }
                            } else if (typeof settings.data === 'object' && !Array.isArray(settings.data)) {
                                if (!settings.data.csrf_token) {
                                    settings.data.csrf_token = token;
                                }
                            } else if (typeof settings.data === 'string' && isJsonRequest) {
                                try {
                                    const jsonData = JSON.parse(settings.data);
                                    if (!jsonData.csrf_token) {
                                        jsonData.csrf_token = token;
                                        settings.data = JSON.stringify(jsonData);
                                        options.data = settings.data;
                                    }
                                } catch (e) {
                                    // Ignore parse errors
                                }
                            }
                        }
                    } else {
                        // Token chưa có, cần fetch (nhưng không thể đợi async trong beforeSend)
                        // Log warning và thêm vào header nếu có sau
                        console.warn('CSRF token not available in beforeSend');
                        getCSRFToken().then(t => {
                            if (t) {
                                xhr.setRequestHeader('X-CSRF-Token', t);
                            }
                        });
                    }
                };
            }
            
            return originalAjax.call(this, options);
        };
    }
    
    /**
     * Helper function để fetch với CSRF token
     */
    window.fetchWithCSRF = async function(url, options = {}) {
        const method = (options.method || 'GET').toUpperCase();
        
        if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
            // Thêm CSRF token vào headers
            if (!options.headers) {
                options.headers = {};
            }
            
            const token = await getCSRFToken();
            if (token) {
                options.headers['X-CSRF-Token'] = token;
            }
            
            // Thêm CSRF token vào body nếu có
            if (options.body) {
                if (options.body instanceof FormData) {
                    if (token) {
                        options.body.append('csrf_token', token);
                    }
                } else if (typeof options.body === 'string') {
                    try {
                        const data = JSON.parse(options.body);
                        data.csrf_token = token;
                        options.body = JSON.stringify(data);
                    } catch (e) {
                        // Nếu không phải JSON, thêm vào query string
                        if (token) {
                            options.body += '&csrf_token=' + encodeURIComponent(token);
                        }
                    }
                } else if (typeof options.body === 'object') {
                    options.body.csrf_token = token;
                }
            } else if (token) {
                // Nếu không có body, tạo body mới
                options.body = JSON.stringify({ csrf_token: token });
                if (!options.headers['Content-Type']) {
                    options.headers['Content-Type'] = 'application/json';
                }
            }
        }
        
        return fetch(url, options);
    };
    
    /**
     * Export CSRF helper functions
     */
    window.CSRFHelper = {
        getToken: getCSRFToken,
        fetchToken: fetchCSRFToken,
        addToken: addCSRFToken,
        refreshToken: async function() {
            csrfToken = null;
            return await fetchCSRFToken();
        }
    };
    
    // Tự động lấy token khi page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fetchCSRFToken);
    } else {
        fetchCSRFToken();
    }
})();




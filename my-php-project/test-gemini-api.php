<?php
/**
 * Test file để kiểm tra API gemini-ai.php có hoạt động không
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Gemini API</title>
</head>
<body>
    <h1>Test Gemini API</h1>
    <button onclick="testAPI()">Test API</button>
    <div id="result"></div>
    
    <script>
    async function testAPI() {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = 'Đang test...';
        
        try {
            const response = await fetch('src/controllers/gemini-ai.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'chat',
                    message: 'Xin chào',
                    history: '[]'
                })
            });
            
            const text = await response.text();
            resultDiv.innerHTML = `
                <h3>Status: ${response.status}</h3>
                <h3>Response:</h3>
                <pre>${text}</pre>
            `;
        } catch (error) {
            resultDiv.innerHTML = `<h3>Error:</h3><pre>${error.message}</pre>`;
        }
    }
    </script>
</body>
</html>


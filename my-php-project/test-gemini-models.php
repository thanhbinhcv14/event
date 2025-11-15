<?php
/**
 * Script test để kiểm tra các model Gemini nào hoạt động
 */
header('Content-Type: text/html; charset=utf-8');

$apiKey = 'AIzaSyDtCMBxxPV1ryIWhWR6oRsPhA8Pchi7rZ8';

// Danh sách các model Gemini để test
$models = [
    'gemini-2.5-pro',        // Model mới nhất, mạnh nhất
    'gemini-2.5-flash',      // Model mới nhất, nhanh nhất
    'gemini-2.5-flash-lite', // Model tối ưu tốc độ và chi phí
    'gemini-1.5-pro',        // Model 1.5 mạnh
    'gemini-1.5-flash',      // Model 1.5 nhanh
    'gemini-pro',            // Model cũ
    'gemini-1.0-pro',        // Model 1.0
];

$testPrompt = "Xin chào, bạn có khỏe không?";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Gemini Models</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .model-test {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        .model-name {
            font-weight: bold;
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            margin: 5px 0;
        }
        .status.success {
            background: #4CAF50;
            color: white;
        }
        .status.error {
            background: #f44336;
            color: white;
        }
        .status.testing {
            background: #ff9800;
            color: white;
        }
        .response {
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }
        .info {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
        }
        button {
            background: #2196F3;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 0;
        }
        button:hover {
            background: #1976D2;
        }
        .summary {
            background: white;
            border: 2px solid #2196F3;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .summary h2 {
            margin-top: 0;
            color: #2196F3;
        }
    </style>
</head>
<body>
    <h1>🔍 Kiểm tra Model Gemini</h1>
    <p>Script này sẽ test từng model Gemini để xem model nào hoạt động với API key của bạn.</p>
    
    <button onclick="testAllModels()">Bắt đầu Test Tất Cả Models</button>
    <button onclick="location.reload()">Reset</button>
    
    <div id="summary" class="summary" style="display: none;">
        <h2>📊 Tóm tắt kết quả</h2>
        <div id="summary-content"></div>
    </div>
    
    <div id="results"></div>
    
    <script>
    const models = <?php echo json_encode($models); ?>;
    const apiKey = '<?php echo $apiKey; ?>';
    const testPrompt = <?php echo json_encode($testPrompt); ?>;
    
    async function testModel(modelName) {
        const resultDiv = document.getElementById(`result-${modelName}`);
        const statusDiv = document.getElementById(`status-${modelName}`);
        const responseDiv = document.getElementById(`response-${modelName}`);
        const timeDiv = document.getElementById(`time-${modelName}`);
        
        statusDiv.innerHTML = '<span class="status testing">Đang test...</span>';
        responseDiv.textContent = '';
        timeDiv.textContent = '';
        
        const startTime = Date.now();
        const url = `https://generativelanguage.googleapis.com/v1beta/models/${modelName}:generateContent?key=${apiKey}`;
        
        const data = {
            contents: [{
                parts: [{ text: testPrompt }]
            }],
            generationConfig: {
                temperature: 0.7,
                maxOutputTokens: 100
            }
        };
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const endTime = Date.now();
            const duration = ((endTime - startTime) / 1000).toFixed(2);
            timeDiv.textContent = `Thời gian: ${duration}s`;
            
            const responseText = await response.text();
            
            if (response.ok) {
                const result = JSON.parse(responseText);
                if (result.candidates && result.candidates[0] && result.candidates[0].content) {
                    const message = result.candidates[0].content.parts[0].text;
                    statusDiv.innerHTML = '<span class="status success">✅ HOẠT ĐỘNG</span>';
                    responseDiv.textContent = `Phản hồi: ${message.substring(0, 200)}...`;
                    return { success: true, model: modelName, duration: duration };
                } else {
                    statusDiv.innerHTML = '<span class="status error">❌ Lỗi: Cấu trúc response không đúng</span>';
                    responseDiv.textContent = responseText.substring(0, 500);
                    return { success: false, model: modelName, error: 'Invalid response structure' };
                }
            } else {
                statusDiv.innerHTML = `<span class="status error">❌ HTTP ${response.status}</span>`;
                responseDiv.textContent = responseText.substring(0, 500);
                return { success: false, model: modelName, error: `HTTP ${response.status}` };
            }
        } catch (error) {
            const endTime = Date.now();
            const duration = ((endTime - startTime) / 1000).toFixed(2);
            timeDiv.textContent = `Thời gian: ${duration}s`;
            statusDiv.innerHTML = `<span class="status error">❌ Lỗi: ${error.message}</span>`;
            responseDiv.textContent = error.toString();
            return { success: false, model: modelName, error: error.message };
        }
    }
    
    async function testAllModels() {
        const resultsDiv = document.getElementById('results');
        resultsDiv.innerHTML = '';
        
        const summaryDiv = document.getElementById('summary');
        summaryDiv.style.display = 'none';
        
        // Tạo HTML cho từng model
        models.forEach(model => {
            const modelDiv = document.createElement('div');
            modelDiv.className = 'model-test';
            modelDiv.id = `test-${model}`;
            modelDiv.innerHTML = `
                <div class="model-name">🤖 ${model}</div>
                <div id="status-${model}"></div>
                <div id="time-${model}" class="info"></div>
                <div id="response-${model}" class="response"></div>
            `;
            resultsDiv.appendChild(modelDiv);
        });
        
        // Test từng model
        const results = [];
        for (const model of models) {
            const result = await testModel(model);
            results.push(result);
            // Đợi 1 giây giữa các request để tránh rate limit
            await new Promise(resolve => setTimeout(resolve, 1000));
        }
        
        // Hiển thị tóm tắt
        const workingModels = results.filter(r => r.success);
        const failedModels = results.filter(r => !r.success);
        
        const summaryContent = document.getElementById('summary-content');
        summaryContent.innerHTML = `
            <p><strong>✅ Models hoạt động (${workingModels.length}):</strong></p>
            <ul>
                ${workingModels.map(m => `<li><strong>${m.model}</strong> - Thời gian: ${m.duration}s</li>`).join('')}
            </ul>
            ${failedModels.length > 0 ? `
                <p><strong>❌ Models không hoạt động (${failedModels.length}):</strong></p>
                <ul>
                    ${failedModels.map(m => `<li><strong>${m.model}</strong> - ${m.error}</li>`).join('')}
                </ul>
            ` : ''}
            <p><strong>💡 Khuyến nghị:</strong> Sử dụng model <strong>${workingModels.length > 0 ? workingModels[0].model : 'N/A'}</strong> (nhanh nhất và hoạt động tốt)</p>
        `;
        summaryDiv.style.display = 'block';
    }
    
    // Tự động test khi load trang (nếu có query parameter)
    if (window.location.search.includes('auto=true')) {
        testAllModels();
    }
    </script>
</body>
</html>


<?php
/**
 * Simple Debug - Kiểm tra elements trong event-registration.php
 */

echo "<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Simple Debug - Event Registration</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        pre { background: #eee; padding: 10px; border-radius: 3px; overflow-x: auto; }
        iframe { width: 100%; height: 600px; border: 1px solid #ddd; }
    </style>
</head>
<body>";

echo "<h1>🔍 Simple Debug - Event Registration</h1>";

echo "<div class='debug-section'>";
echo "<h2>1. Load Event Registration Page</h2>";
echo "<p>Loading event-registration.php in iframe to check elements:</p>";
echo "<iframe src='event-registration.php' id='eventFrame'></iframe>";
echo "</div>";

echo "<div class='debug-section'>";
echo "<h2>2. Element Check Results</h2>";
echo "<div id='element-results'></div>";
echo "</div>";

echo "<div class='debug-section'>";
echo "<h2>3. Manual Check</h2>";
echo "<button onclick='checkElements()' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Check Elements</button>";
echo "<button onclick='checkFunctions()' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;'>Check Functions</button>";
echo "<button onclick='testSubmit()' style='background: #ffc107; color: black; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;'>Test Submit</button>";
echo "</div>";

echo "<script>
function logResult(message, type = 'info') {
    const resultsDiv = document.getElementById('element-results');
    const color = type === 'error' ? 'red' : type === 'success' ? 'green' : 'blue';
    resultsDiv.innerHTML += '<div style=\"color: ' + color + '; margin: 5px 0;\">' + message + '</div>';
}

function checkElements() {
    logResult('=== CHECKING ELEMENTS ===');
    
    const elements = [
        'eventRegistrationForm',
        'submitBtn',
        'nextBtn', 
        'prevBtn',
        'step1', 'step2', 'step3', 'step4', 'step5',
        'eventName', 'eventType', 'eventDescription',
        'startDate', 'endDate', 'expectedGuests', 'budget',
        'customerSearch', 'locationList', 'equipmentList',
        'adminNotes'
    ];
    
    let foundCount = 0;
    elements.forEach(elementId => {
        try {
            const iframe = document.getElementById('eventFrame');
            const element = iframe.contentDocument.getElementById(elementId);
            if (element) {
                foundCount++;
                logResult(elementId + ': Found', 'success');
            } else {
                logResult(elementId + ': Not found', 'error');
            }
        } catch (e) {
            logResult(elementId + ': Error checking - ' + e.message, 'error');
        }
    });
    
    logResult('Found ' + foundCount + ' out of ' + elements.length + ' elements', foundCount === elements.length ? 'success' : 'error');
}

function checkFunctions() {
    logResult('=== CHECKING FUNCTIONS ===');
    
    const functions = [
        'handleSubmit',
        'setupEventListeners', 
        'validateCurrentStep',
        'updateStepDisplay',
        'changeStep'
    ];
    
    let foundCount = 0;
    functions.forEach(funcName => {
        try {
            const iframe = document.getElementById('eventFrame');
            const func = iframe.contentWindow[funcName];
            if (typeof func === 'function') {
                foundCount++;
                logResult(funcName + ': Found', 'success');
            } else {
                logResult(funcName + ': Not found', 'error');
            }
        } catch (e) {
            logResult(funcName + ': Error checking - ' + e.message, 'error');
        }
    });
    
    logResult('Found ' + foundCount + ' out of ' + functions.length + ' functions', foundCount === functions.length ? 'success' : 'error');
}

function testSubmit() {
    logResult('=== TESTING SUBMIT ===');
    
    try {
        const iframe = document.getElementById('eventFrame');
        const submitBtn = iframe.contentDocument.getElementById('submitBtn');
        
        if (submitBtn) {
            logResult('Submit button found', 'success');
            logResult('Display: ' + submitBtn.style.display, 'info');
            logResult('Disabled: ' + submitBtn.disabled, 'info');
            logResult('HTML: ' + submitBtn.innerHTML, 'info');
            
            // Check if form exists
            const form = iframe.contentDocument.getElementById('eventRegistrationForm');
            if (form) {
                logResult('Form found', 'success');
            } else {
                logResult('Form not found!', 'error');
            }
            
            // Check current step
            if (iframe.contentWindow.currentStep !== undefined) {
                logResult('Current step: ' + iframe.contentWindow.currentStep, 'info');
                logResult('Total steps: ' + iframe.contentWindow.totalSteps, 'info');
                logResult('Should show submit: ' + (iframe.contentWindow.currentStep === iframe.contentWindow.totalSteps), 'info');
            } else {
                logResult('currentStep variable not defined!', 'error');
            }
            
        } else {
            logResult('Submit button not found!', 'error');
        }
    } catch (e) {
        logResult('Error testing submit: ' + e.message, 'error');
    }
}

// Auto-check when iframe loads
document.getElementById('eventFrame').onload = function() {
    setTimeout(() => {
        logResult('Iframe loaded, checking elements...', 'info');
        checkElements();
    }, 2000);
};
</script>";

echo "</body></html>";
?>

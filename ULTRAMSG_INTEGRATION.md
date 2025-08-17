# UltraMsg WhatsApp API Integration

This document describes the UltraMsg WhatsApp API integration that has been implemented in the LaundryPro system.

## Overview

The UltraMsg integration provides a complete WhatsApp messaging solution using the UltraMsg API. It includes:

- Text message sending
- Media file sending (images, videos, audio)
- Document sending (PDFs, text files, etc.)
- Instance information retrieval
- Chat history retrieval
- Test message functionality

## Files Created

1. **`app/Http/Controllers/Api/UltraMsgController.php`** - API controller handling HTTP requests
2. **`app/Services/UltraMsgService.php`** - Business logic service for UltraMsg API interactions
3. **`test_ultramsg.php`** - Test script to verify API functionality
4. **`ULTRAMSG_INTEGRATION.md`** - This documentation file

## API Endpoints

All endpoints are protected by authentication and require a valid API token.

### Base URL
```
/api/ultramsg
```

### Endpoints

#### 1. Send Text Message
```
POST /api/ultramsg/send-message
```

**Request Body:**
```json
{
    "to": "96878622990",
    "body": "Hello from UltraMsg API!"
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Message sent successfully",
    "data": {
        "sent": true,
        "id": "message_id_here"
    }
}
```

#### 2. Send Media
```
POST /api/ultramsg/send-media
```

**Request Body:**
```json
{
    "to": "96878622990",
    "media": "base64_encoded_media_content",
    "filename": "image.jpg",
    "caption": "Optional caption for the media"
}
```

#### 3. Send Document
```
POST /api/ultramsg/send-document
```

**Request Body:**
```json
{
    "to": "96878622990",
    "document": "base64_encoded_document_content",
    "filename": "document.pdf",
    "caption": "Optional caption for the document"
}
```

#### 4. Get Instance Information
```
GET /api/ultramsg/instance-info
```

**Response:**
```json
{
    "status": "success",
    "data": {
        "instance": {
            "id": "instance_id",
            "name": "Instance Name",
            "status": "connected"
        }
    }
}
```

#### 5. Get Chat History
```
GET /api/ultramsg/chat-history?to=96878622990&limit=50&page=1
```

**Query Parameters:**
- `to` (required): Phone number
- `limit` (optional): Number of messages to retrieve (default: 50, max: 100)
- `page` (optional): Page number (default: 1)

#### 6. Send Test Message
```
POST /api/ultramsg/send-test
```

**Request Body:**
```json
{
    "to": "96878622990"
}
```

## Configuration

The UltraMsg integration requires the following settings to be configured in your Laravel application:

### Required Settings

Add these settings to your database settings table:

```sql
INSERT INTO settings (key, value, group_name) VALUES
('ultramsg_enabled', 'true', 'whatsapp'),
('ultramsg_token', 'your_ultramsg_token_here', 'whatsapp'),
('ultramsg_instance_id', 'your_instance_id_here', 'whatsapp');
```

### Settings Description

- **`ultramsg_enabled`**: Enable/disable UltraMsg integration (true/false)
- **`ultramsg_token`**: Your UltraMsg API token
- **`ultramsg_instance_id`**: Your UltraMsg instance ID

## Usage Examples

### PHP Example (Using cURL)

```php
<?php
// Send a text message
$data = [
    'to' => '96878622990',
    'body' => 'Hello from LaundryPro!'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://your-domain.com/api/ultramsg/send-message');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer your_laravel_token'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

echo $response;
?>
```

### JavaScript Example (Using Fetch)

```javascript
// Send a text message
async function sendWhatsAppMessage(phoneNumber, message) {
    const response = await fetch('/api/ultramsg/send-message', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer your_laravel_token'
        },
        body: JSON.stringify({
            to: phoneNumber,
            body: message
        })
    });
    
    return await response.json();
}

// Usage
sendWhatsAppMessage('96878622990', 'Hello from LaundryPro!')
    .then(result => console.log(result))
    .catch(error => console.error(error));
```

### Sending Media Files

```javascript
// Convert file to base64 and send
async function sendMediaFile(phoneNumber, file, caption = '') {
    const base64 = await fileToBase64(file);
    
    const response = await fetch('/api/ultramsg/send-media', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer your_laravel_token'
        },
        body: JSON.stringify({
            to: phoneNumber,
            media: base64,
            filename: file.name,
            caption: caption
        })
    });
    
    return await response.json();
}

function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => {
            const base64 = reader.result.split(',')[1];
            resolve(base64);
        };
        reader.onerror = error => reject(error);
    });
}
```

## Testing

Use the provided test script to verify your UltraMsg integration:

1. Update the configuration in `test_ultramsg.php`:
   ```php
   $baseUrl = 'http://your-domain.com/api';
   $testPhoneNumber = 'your_test_phone_number';
   ```

2. Run the test script:
   ```bash
   php test_ultramsg.php
   ```

The script will test all endpoints and display the responses.

## Error Handling

The API returns consistent error responses:

```json
{
    "status": "error",
    "message": "Error description",
    "data": null
}
```

Common error scenarios:
- Invalid phone number format
- Missing required parameters
- UltraMsg API not configured
- Network connectivity issues
- Invalid API credentials

## Phone Number Formatting

The system automatically formats phone numbers to the required format for UltraMsg API:

- Removes all non-digit characters
- Adds country code if not present (default: 968 for Oman)
- Validates minimum length requirements

## Logging

All UltraMsg API interactions are logged with detailed information:

- Request parameters (excluding sensitive data)
- Response status and data
- Error messages and stack traces
- Phone number formatting details

Logs can be found in your Laravel log files.

## Security Considerations

1. **Authentication**: All endpoints require valid Laravel authentication
2. **Input Validation**: All inputs are validated and sanitized
3. **Rate Limiting**: Consider implementing rate limiting for production use
4. **Token Security**: Store UltraMsg tokens securely in environment variables or encrypted database fields
5. **Phone Number Privacy**: Logs are sanitized to protect user privacy

## Troubleshooting

### Common Issues

1. **"UltraMsg API is not configured"**
   - Check that all required settings are configured
   - Verify token and instance ID are correct

2. **"Phone number is empty"**
   - Ensure phone number is provided in the request
   - Check phone number format

3. **"Request failed"**
   - Check network connectivity
   - Verify UltraMsg API credentials
   - Check UltraMsg service status

4. **"Message not sent"**
   - Verify UltraMsg instance is connected
   - Check if the recipient number is valid
   - Ensure message content meets UltraMsg requirements

### Debug Mode

Enable debug logging by setting the log level to debug in your Laravel configuration.

## Migration from WA Client

If you're migrating from the existing WA Client integration:

1. Update your settings to include UltraMsg configuration
2. Update your frontend code to use the new UltraMsg endpoints
3. Test thoroughly before switching over
4. Consider running both systems in parallel during transition

## Support

For issues related to:
- **UltraMsg API**: Contact UltraMsg support
- **Laravel Integration**: Check this documentation and Laravel logs
- **General System**: Contact your system administrator

## Changelog

### Version 1.0.0
- Initial UltraMsg integration
- Text message sending
- Media file sending
- Document sending
- Instance information retrieval
- Chat history retrieval
- Test message functionality
- Comprehensive error handling and logging

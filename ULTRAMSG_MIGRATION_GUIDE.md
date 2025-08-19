# UltraMsg WhatsApp API Integration

This guide explains the UltraMsg WhatsApp API integration in your RestaurantPro system.

## Overview

The system now uses UltraMsg API exclusively for WhatsApp messaging. This provides better reliability, advanced features, and improved performance compared to other WhatsApp APIs.

## What's Changed

### Backend Changes

1. **UltraMsg-Only Configuration:**
   - `ultramsg_token`: Your UltraMsg API token
   - `ultramsg_instance_id`: Your UltraMsg instance ID
   - Removed all WA Client settings

2. **Updated WhatsAppService:**
   - Uses UltraMsg API exclusively
   - Simplified configuration and error handling
   - Better performance and reliability

3. **UltraMsg Controller:**
   - Dedicated UltraMsg API endpoints
   - Advanced features like media sending, document sending
   - Instance information and chat history

### Frontend Changes

1. **Simplified Settings Interface:**
   - UltraMsg configuration only
   - Clean, focused user experience
   - Clear visual indicators

2. **Enhanced WhatsApp Settings Component:**
   - Direct UltraMsg configuration
   - Better validation and error handling
   - Informative UI with UltraMsg branding

## Configuration

### Step 1: Configure UltraMsg Settings

1. Go to **Settings > WhatsApp Settings** in your admin panel
2. Enable **"Enable WhatsApp"** toggle
3. Enter your UltraMsg credentials:
   - **UltraMsg Token**: Your UltraMsg API token
   - **UltraMsg Instance ID**: Your UltraMsg instance ID
4. Configure notification settings:
   - **Notification Number**: Your business phone number
   - **Country Code**: Your country code (default: 968)
5. Save the settings

### Step 2: Test the Configuration

1. Use the **"Send Test Message"** feature in the WhatsApp settings
2. Enter a test phone number
3. Verify that the message is sent successfully

### Step 3: Verify All Features Work

Test the following features to ensure they work with UltraMsg:
- Order notifications
- Customer communications
- System alerts
- Any custom WhatsApp integrations

## Configuration Example

```json
{
  "whatsapp_enabled": true,
  "ultramsg_token": "b6ght2y2ff7rbha6",
  "ultramsg_instance_id": "instance139458",
  "whatsapp_notification_number": "78622990",
  "whatsapp_country_code": "968"
}
```

## API Endpoints

### UltraMsg Endpoints

- `POST /api/ultramsg/send-message` - Send text messages
- `POST /api/ultramsg/send-media` - Send media files
- `POST /api/ultramsg/send-document` - Send documents
- `GET /api/ultramsg/instance-info` - Get instance status
- `GET /api/ultramsg/chat-history` - Get chat history
- `POST /api/ultramsg/send-test` - Send test message

### Legacy Endpoints (Still Available)

- `POST /api/settings/whatsapp/send-test` - Send test message (uses UltraMsg)

## Benefits of UltraMsg

1. **Better Reliability**: More stable API with better uptime
2. **Advanced Features**: Support for media, documents, and chat history
3. **Better Documentation**: Comprehensive API documentation
4. **Cost Effective**: Often more affordable than other WhatsApp APIs
5. **Better Support**: More responsive customer support
6. **Simplified Setup**: No complex configuration required

## Troubleshooting

### Common Issues

1. **"UltraMsg API is not configured"**
   - Check that `whatsapp_enabled` is set to `true`
   - Verify your token and instance ID are correct
   - Ensure the settings are saved

2. **"Message not sent"**
   - Check your UltraMsg instance is connected
   - Verify the phone number format (should include country code)
   - Check UltraMsg dashboard for any errors

3. **"Configuration error"**
   - Ensure both token and instance ID are provided
   - Check that the instance ID format is correct
   - Verify the token is valid and active

### Debug Mode

Enable debug logging to see detailed API interactions:

```php
// In your .env file
LOG_LEVEL=debug
```

### Testing

Use the provided test script to verify your UltraMsg configuration:

```bash
php test_ultramsg.php
```

## Support

For issues related to:
- **UltraMsg API**: Contact UltraMsg support
- **System Integration**: Check this documentation
- **Configuration**: Verify your settings match the examples above

## Changelog

### Version 2.0.0
- Switched to UltraMsg API exclusively
- Removed WA Client integration
- Simplified configuration interface
- Enhanced error handling and logging
- Added comprehensive testing tools

### Version 1.x.x
- Original WA Client integration
- Basic WhatsApp messaging functionality

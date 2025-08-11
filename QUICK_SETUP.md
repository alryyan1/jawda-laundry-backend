# 🚀 Quick Setup: Fix WhatsApp Media Sending

## 🎯 The Problem
Your WhatsApp media (PDFs, images) are failing because the local URLs (`http://192.168.137.1/...`) are not accessible from WhatsApp servers on the internet.

## ✅ The Solution
Use **ImgBB** - a free image hosting service that provides publicly accessible URLs.

## 🚀 3-Step Setup

### Step 1: Get ImgBB API Key
1. Go to [https://api.imgbb.com/](https://api.imgbb.com/)
2. Click **"Get API Key"**
3. Copy your API key (looks like: `abc123def456ghi789`)

### Step 2: Add to .env File
Add these lines to your `.env` file:
```env
CLOUD_STORAGE_TYPE=imgbb
CLOUD_STORAGE_API_KEY=your-api-key-here
```

### Step 3: Test
Run this command to test:
```bash
php test_cloud_storage.php
```

## 🔍 What Happens Now

1. **Before**: Files saved locally → URLs like `http://192.168.137.1/...` → ❌ WhatsApp can't access
2. **After**: Files uploaded to ImgBB → URLs like `https://i.ibb.co/...` → ✅ WhatsApp can access

## 📱 Test WhatsApp Sending

Once configured, test sending a PDF:
```bash
# This will now use ImgBB instead of local storage
php test_cloud_storage.php
```

## 🆘 If You Need Help

### Option A: Use Hostinger File Manager
1. Login to Hostinger control panel
2. Go to File Manager
3. Navigate to `public_html/laundry-backend/public/storage/whatsapp/`
4. Upload files manually
5. Use URLs like: `https://yourdomain.com/laundry-backend/public/storage/whatsapp/filename.pdf`

### Option B: Use Cloudinary (25GB free)
1. Sign up at [cloudinary.com](https://cloudinary.com/)
2. Get API key and secret
3. Add to .env:
```env
CLOUD_STORAGE_TYPE=cloudinary
CLOUD_STORAGE_API_KEY=your-api-key
CLOUD_STORAGE_API_SECRET=your-api-secret
CLOUD_STORAGE_BUCKET=your-cloud-name
```

## 🎉 Success!
After setup, your WhatsApp service will automatically:
1. Upload media to ImgBB
2. Get public URLs
3. Send media successfully via WhatsApp
4. Fall back to local storage if needed

---

**Need help?** The service automatically falls back to local storage, so your existing functionality won't break.

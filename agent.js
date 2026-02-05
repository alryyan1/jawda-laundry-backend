require('dotenv').config();
const fs = require('fs');
const os = require('os');
const path = require('path');
const axios = require('axios');
const Pusher = require('pusher-js');
const { print } = require('pdf-to-printer');

// Support both PUSHER_KEY and PUSHER_APP_KEY (for Laravel .env compatibility)
const PUSHER_KEY = process.env.PUSHER_KEY || process.env.PUSHER_APP_KEY;
const PUSHER_CLUSTER = process.env.PUSHER_CLUSTER || process.env.PUSHER_APP_CLUSTER;
const API_BASE = process.env.API_BASE || 'http://127.0.0.1/jawda-laundry-backend/public/api';
const SANCTUM_TOKEN = process.env.SANCTUM_TOKEN || '';

if (!PUSHER_KEY || !PUSHER_CLUSTER) {
  console.error('Missing PUSHER_KEY/PUSHER_APP_KEY or PUSHER_CLUSTER/PUSHER_APP_CLUSTER in .env');
  process.exit(1);
}

console.log('[Agent] Starting...');
const pusher = new Pusher(PUSHER_KEY, { cluster: PUSHER_CLUSTER, forceTLS: false });
const channel = pusher.subscribe('print-jobs');

channel.bind('PrintJobCreated', async (event) => {
  try {
    // Laravel broadcasts the data directly, not wrapped in printJob
    const payload = event || {};
    const orderId = payload.order_id || payload.orderId;
    
    if (!orderId) {
      console.error('[Agent] No order_id found in event payload:', JSON.stringify(event));
      return;
    }

    const pdfUrl = `${API_BASE}/orders/${orderId}/pos-invoice-pdf`;
    console.log(`[Agent] Received print job for order ${orderId} - ${pdfUrl}`);

    const tmpFile = path.join(os.tmpdir(), `order-${orderId}-${Date.now()}.pdf`);
    
    // Download PDF
    const response = await axios.get(pdfUrl, {
      responseType: 'arraybuffer',
      headers: SANCTUM_TOKEN ? { Authorization: `Bearer ${SANCTUM_TOKEN}` } : {},
    });
    
    fs.writeFileSync(tmpFile, response.data);
    console.log(`[Agent] PDF downloaded to ${tmpFile}`);

    // Print PDF
    await print(tmpFile, { printer: undefined });
    console.log(`[Agent] Printed order ${orderId}`);

    // Clean up temporary file
    try {
      fs.unlinkSync(tmpFile);
      console.log(`[Agent] Cleaned up temporary file: ${tmpFile}`);
    } catch (cleanupError) {
      console.warn(`[Agent] Failed to cleanup temp file: ${cleanupError.message}`);
    }
  } catch (err) {
    console.error('[Agent] Error handling print job:', err?.message || err);
    if (err.response) {
      console.error('[Agent] Response status:', err.response.status);
      console.error('[Agent] Response data:', err.response.data);
    }
  }
});

console.log('[Agent] Listening for print jobs on channel: print-jobs');

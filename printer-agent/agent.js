require('dotenv').config();
const fs = require('fs');
const os = require('os');
const path = require('path');
const axios = require('axios');
const Pusher = require('pusher-js');
const { print } = require('pdf-to-printer');

const PUSHER_KEY = process.env.PUSHER_KEY;
const PUSHER_CLUSTER = process.env.PUSHER_CLUSTER;
const API_BASE = process.env.API_BASE || 'http://localhost:8000/api';
const SANCTUM_TOKEN = process.env.SANCTUM_TOKEN || '';

if (!PUSHER_KEY || !PUSHER_CLUSTER) {
  console.error('Missing PUSHER_KEY or PUSHER_CLUSTER in .env');
  process.exit(1);
}

console.log('[Agent] Starting...');
const pusher = new Pusher(PUSHER_KEY, { cluster: PUSHER_CLUSTER, forceTLS: false });
const channel = pusher.subscribe('print-jobs');

channel.bind('PrintJobCreated', async (event) => {
  try {
    const payload = event && event.printJob ? event.printJob : event;
    const jobId = payload.job_id || payload.id;
    const orderId = payload.order_id || payload.orderId || payload.order_id;
    const pdfUrl = `${API_BASE}/orders/${orderId}/pos-invoice-pdf`;
    console.log(`[Agent] Received job ${jobId} for order ${orderId} ${pdfUrl}`);

    const tmpFile = path.join(os.tmpdir(), `order-${orderId}-${Date.now()}.pdf`);
    const response = await axios.get(pdfUrl, {
      responseType: 'arraybuffer',
      headers: SANCTUM_TOKEN ? { Authorization: `Bearer ${SANCTUM_TOKEN}` } : {},
    });
    fs.writeFileSync(tmpFile, response.data);

    if (SANCTUM_TOKEN && jobId) {
      try {
        await axios.patch(`${API_BASE}/print-jobs/${jobId}`, { status: 'processing' }, {
          headers: { Authorization: `Bearer ${SANCTUM_TOKEN}` },
        });
      } catch (e) {}
    }

    await print(tmpFile, { printer: undefined });

    if (SANCTUM_TOKEN && jobId) {
      try {
        await axios.patch(`${API_BASE}/print-jobs/${jobId}`, { status: 'printed' }, {
          headers: { Authorization: `Bearer ${SANCTUM_TOKEN}` },
        });
      } catch (e) {
        console.log(e)
      }
    }

    console.log(`[Agent] Printed job ${jobId}`);
  } catch (err) {
    console.error('[Agent] Error handling print job:', err?.message || err);
  }
});

console.log('[Agent] Listening for print jobs on channel: print-jobs');



# Print Agent Setup Guide

## Overview
The print agent is a Node.js service that listens to Pusher events and automatically prints POS invoices when orders are created and received.

## Prerequisites
- Node.js (v14 or higher)
- npm or yarn
- A printer connected to the system

## Installation

1. **Install Dependencies**
   ```bash
   npm install
   ```

2. **Configure Environment Variables**
   Create a `.env` file in the backend root directory with the following variables:
   ```env
   PUSHER_KEY=9c05659f4270f3e99edf
   PUSHER_CLUSTER=ap2
   API_BASE=http://localhost:8000/api
   SANCTUM_TOKEN=your_sanctum_token_here
   ```

   **Note:** 
   - `PUSHER_KEY` and `PUSHER_CLUSTER` are required
   - `API_BASE` defaults to `http://localhost:8000/api` if not provided
   - `SANCTUM_TOKEN` is optional but recommended for authenticated API requests

3. **Configure Backend .env**
   Ensure your Laravel `.env` file has the following Pusher configuration:
   ```env
   BROADCAST_DRIVER=pusher
   PUSHER_APP_ID=2109634
   PUSHER_APP_KEY=9c05659f4270f3e99edf
   PUSHER_APP_SECRET=563fb29b44a1c35598bf
   PUSHER_APP_CLUSTER=ap2
   ```

## Running the Agent

### Development
```bash
node agent.js
```

### Production (using PM2)
```bash
pm2 start agent.js --name print-agent
pm2 save
pm2 startup
```

## How It Works

1. When an order is created and marked as received in the POS system, a `PrintJobCreated` event is broadcast via Pusher
2. The agent listens to the `print-jobs` channel for `PrintJobCreated` events
3. When an event is received, the agent:
   - Downloads the PDF invoice from the API endpoint
   - Saves it to a temporary file
   - Sends it to the default printer
   - Cleans up the temporary file

## Troubleshooting

### Agent not receiving events
- Verify Pusher credentials in both backend `.env` and agent `.env`
- Check that `BROADCAST_DRIVER=pusher` in backend `.env`
- Ensure the agent is connected: you should see `[Agent] Listening for print jobs on channel: print-jobs`

### PDF download fails
- Check `API_BASE` URL is correct
- Verify `SANCTUM_TOKEN` if API requires authentication
- Check network connectivity to the API server

### Printing fails
- Verify a printer is installed and set as default
- Check printer is online and has paper
- On Windows, ensure the default printer is accessible
- On Linux/Mac, ensure CUPS is configured properly

### Logs
The agent logs all activities to the console. Monitor the output for:
- `[Agent] Received print job for order X` - Event received
- `[Agent] PDF downloaded to...` - PDF downloaded successfully
- `[Agent] Printed order X` - Print job completed
- `[Agent] Error...` - Any errors encountered

## Testing

To test the agent:
1. Start the agent: `node agent.js`
2. Create and receive an order in the POS system
3. The agent should automatically print the invoice

## Notes

- The agent runs as a separate process from the Laravel application
- PDFs are temporarily stored in the OS temp directory and automatically cleaned up
- The agent will continue running and listening for new print jobs
- Errors are logged but won't crash the agent, allowing it to continue processing future jobs

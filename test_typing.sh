#!/bin/bash
source .env
curl -s -X POST "https://graph.facebook.com/v18.0/${WHATSAPP_PHONE_NUMBER_ID}/messages" \
-H "Authorization: Bearer ${WHATSAPP_ACCESS_TOKEN}" \
-H "Content-Type: application/json" \
-d '{
  "messaging_product": "whatsapp",
  "status": "read",
  "message_id": "wamid.HBgMMjQzODI4NDI0MDA5FQIAEhgUM0I2NjZFQ0JDOUFEODNDMTZGNkQA",
  "typing_indicator": {
    "type": "text"
  }
}'

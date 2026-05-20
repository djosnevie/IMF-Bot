#!/bin/bash
source .env
curl -s -X POST "https://graph.facebook.com/v18.0/${WHATSAPP_PHONE_NUMBER_ID}/messages" \
-H "Authorization: Bearer ${WHATSAPP_ACCESS_TOKEN}" \
-H "Content-Type: application/json" \
-d '{
  "messaging_product": "whatsapp",
  "recipient_type": "individual",
  "to": "243828424009",
  "type": "typing_indicator",
  "typing_indicator": {
    "action": "typing_on"
  }
}'

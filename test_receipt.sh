#!/bin/bash
curl -X POST \
  -d "branch_id=1" \
  -d "items[0][description]=Test Product" \
  -d "items[0][quantity]=1" \
  -d "items[0][unit_price_chf]=100.00" \
  http://localhost/receipts \
  -i
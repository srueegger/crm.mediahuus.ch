#!/bin/bash

# First, login to get session cookie
echo "Logging in..."
COOKIE_JAR=$(mktemp)
curl -c "$COOKIE_JAR" -X POST \
  -d "email=admin@mediahuus.ch" \
  -d "password=admin123" \
  http://localhost/login \
  -i

echo -e "\n\nCreating receipt..."
# Then create receipt with session cookie
curl -b "$COOKIE_JAR" -X POST \
  -d "branch_id=1" \
  -d "items[0][description]=Test Product" \
  -d "items[0][quantity]=1" \
  -d "items[0][unit_price_chf]=100.00" \
  http://localhost/receipts \
  -i

# Cleanup
rm "$COOKIE_JAR"
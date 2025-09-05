#!/bin/bash

# First, login to get session cookie
echo "Logging in..."
COOKIE_JAR=$(mktemp)
curl -c "$COOKIE_JAR" -X POST \
  -d "email=admin@mediahuus.ch" \
  -d "password=admin123" \
  http://localhost/login \
  -s > /dev/null

echo "Viewing receipt..."
# View the created receipt (document ID 10)
curl -b "$COOKIE_JAR" http://localhost/receipts/10 -i

echo -e "\n\nTesting PDF generation..."
# Test PDF generation
curl -b "$COOKIE_JAR" http://localhost/receipts/10/pdf -i

# Cleanup
rm "$COOKIE_JAR"
#!/bin/bash
# SSL Setup Script for aibotchat.xyz
# Run this script on your EC2 server as root or with sudo

set -e

DOMAIN="aibotchat.xyz"
EMAIL="your-email@example.com"  # Change this to your email

echo "=== Installing Nginx and Certbot ==="
# For Amazon Linux 2023 / AL2
if command -v dnf &> /dev/null; then
    sudo dnf install -y nginx certbot python3-certbot-nginx
# For Ubuntu/Debian
elif command -v apt &> /dev/null; then
    sudo apt update
    sudo apt install -y nginx certbot python3-certbot-nginx
fi

echo "=== Creating certbot webroot directory ==="
sudo mkdir -p /var/www/certbot

echo "=== Starting Nginx temporarily for certificate validation ==="
# Create a temporary nginx config for certbot validation
sudo tee /etc/nginx/conf.d/certbot-temp.conf > /dev/null <<'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name aibotchat.xyz api.aibotchat.xyz;

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    location / {
        return 200 'Certbot validation server';
        add_header Content-Type text/plain;
    }
}
EOF

sudo systemctl start nginx || sudo systemctl restart nginx

echo "=== Obtaining SSL Certificate ==="
# Get certificate for both domains
sudo certbot certonly --webroot \
    -w /var/www/certbot \
    -d $DOMAIN \
    -d api.$DOMAIN \
    --email $EMAIL \
    --agree-tos \
    --non-interactive

echo "=== Removing temporary config ==="
sudo rm /etc/nginx/conf.d/certbot-temp.conf

echo "=== Installing production nginx config ==="
# Copy the production nginx config
sudo tee /etc/nginx/conf.d/aibotchat.conf > /dev/null <<'NGINX_CONF'
# Redirect HTTP to HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name aibotchat.xyz api.aibotchat.xyz;

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    location / {
        return 301 https://$host$request_uri;
    }
}

# Admin Panel - aibotchat.xyz
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name aibotchat.xyz;

    ssl_certificate /etc/letsencrypt/live/aibotchat.xyz/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/aibotchat.xyz/privkey.pem;
    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL:50m;
    ssl_session_tickets off;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    add_header Strict-Transport-Security "max-age=63072000" always;

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
}

# API - api.aibotchat.xyz
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name api.aibotchat.xyz;

    ssl_certificate /etc/letsencrypt/live/aibotchat.xyz/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/aibotchat.xyz/privkey.pem;
    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL:50m;
    ssl_session_tickets off;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    add_header Strict-Transport-Security "max-age=63072000" always;

    # CORS headers for API
    add_header Access-Control-Allow-Origin "https://aibotchat.xyz" always;
    add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" always;
    add_header Access-Control-Allow-Headers "Authorization, Content-Type, Accept, X-Requested-With" always;
    add_header Access-Control-Allow-Credentials "true" always;

    # Handle preflight requests
    if ($request_method = 'OPTIONS') {
        add_header Access-Control-Allow-Origin "https://aibotchat.xyz" always;
        add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" always;
        add_header Access-Control-Allow-Headers "Authorization, Content-Type, Accept, X-Requested-With" always;
        add_header Access-Control-Allow-Credentials "true" always;
        add_header Content-Length 0;
        add_header Content-Type text/plain;
        return 204;
    }

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 300;
        proxy_connect_timeout 300;
        client_max_body_size 64M;
    }
}
NGINX_CONF

echo "=== Testing nginx configuration ==="
sudo nginx -t

echo "=== Restarting nginx ==="
sudo systemctl restart nginx
sudo systemctl enable nginx

echo "=== Setting up automatic certificate renewal ==="
# Add cron job for auto-renewal
(crontab -l 2>/dev/null; echo "0 3 * * * certbot renew --quiet --post-hook 'systemctl reload nginx'") | crontab -

echo ""
echo "=== Setup Complete! ==="
echo ""
echo "Your domains are now configured:"
echo "  - https://aibotchat.xyz (Admin Panel)"
echo "  - https://api.aibotchat.xyz (API)"
echo ""
echo "Make sure your DNS records are configured:"
echo "  - A record: aibotchat.xyz -> your-server-ip"
echo "  - A record: api.aibotchat.xyz -> your-server-ip"
echo ""
echo "Now start your Docker containers:"
echo "  docker compose -f docker-compose.prod.yml up -d"

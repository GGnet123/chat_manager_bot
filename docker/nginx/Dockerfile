FROM nginx:alpine

ARG UID=1000
ARG GID=1000

# Remove default nginx config
RUN rm /etc/nginx/conf.d/default.conf

# Copy nginx configuration
COPY server.conf /etc/nginx/conf.d/default.conf

# Create system user and set permissions
RUN addgroup -g ${GID} appgroup 2>/dev/null || true \
    && adduser -u ${UID} -G appgroup -s /bin/sh -D appuser 2>/dev/null || true

# Create required directories
RUN mkdir -p /var/www/public \
    && chown -R appuser:appgroup /var/www \
    && chown -R appuser:appgroup /var/cache/nginx \
    && chown -R appuser:appgroup /var/log/nginx \
    && touch /var/run/nginx.pid \
    && chown appuser:appgroup /var/run/nginx.pid

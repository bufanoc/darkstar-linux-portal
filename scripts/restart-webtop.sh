#!/bin/bash

# Check if cron is paused
PAUSE_FLAG="/var/lib/darkstar/cron-paused"

if [ -f "$PAUSE_FLAG" ]; then
    # Cron is paused, skip restart
    echo "$(date): Auto-restart paused by admin" >> /var/log/darkstar-restart.log
    exit 0
fi

# Proceed with restart
cd /root/darkstar-linux-portal
docker compose restart webtop

#!/bin/bash
###############################################################################
# Dark Star Portal - Security & Health Monitoring Script
# Created: 2025-10-31
# Purpose: Monitor system health, security status, and log important events
###############################################################################

LOG_FILE="/var/log/darkstar-monitor.log"
ALERT_FILE="/var/log/darkstar-alerts.log"
MAX_LOG_SIZE=10485760  # 10MB

# Colors for terminal output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to log messages
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Function to log alerts
log_alert() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] ALERT: $1" >> "$ALERT_FILE"
    echo -e "${RED}[ALERT]${NC} $1"
}

# Function to check if log files are too large
rotate_logs() {
    if [ -f "$LOG_FILE" ] && [ $(stat -f%z "$LOG_FILE" 2>/dev/null || stat -c%s "$LOG_FILE") -gt $MAX_LOG_SIZE ]; then
        mv "$LOG_FILE" "$LOG_FILE.old"
        log_message "Log file rotated"
    fi
}

# Check UFW firewall status
check_firewall() {
    echo -e "\n${GREEN}=== Firewall Status ===${NC}"
    if ! ufw status | grep -q "Status: active"; then
        log_alert "UFW Firewall is NOT active!"
        return 1
    else
        echo "✓ UFW Firewall: ACTIVE"
        log_message "Firewall check: OK"
        return 0
    fi
}

# Check Fail2Ban status
check_fail2ban() {
    echo -e "\n${GREEN}=== Fail2Ban Status ===${NC}"
    if ! systemctl is-active --quiet fail2ban; then
        log_alert "Fail2Ban service is NOT running!"
        return 1
    else
        echo "✓ Fail2Ban: RUNNING"

        # Show current bans
        BANNED_COUNT=$(fail2ban-client status sshd | grep "Currently banned" | awk '{print $NF}')
        if [ "$BANNED_COUNT" -gt 0 ]; then
            echo "  └─ Currently banned IPs: $BANNED_COUNT"
            fail2ban-client status sshd | grep "Banned IP list" | sed 's/.*:/  └─/'
        fi

        log_message "Fail2Ban check: OK - $BANNED_COUNT IPs banned"
        return 0
    fi
}

# Check Apache status
check_apache() {
    echo -e "\n${GREEN}=== Apache Status ===${NC}"
    if ! systemctl is-active --quiet apache2; then
        log_alert "Apache service is NOT running!"
        return 1
    else
        echo "✓ Apache: RUNNING"

        # Check recent 5xx errors
        ERROR_COUNT=$(grep -c "\" 5[0-9][0-9] " /var/log/apache2/terminal-portal-error.log 2>/dev/null | tail -1 || echo 0)
        if [ "$ERROR_COUNT" -gt 10 ]; then
            log_alert "High number of Apache 5xx errors: $ERROR_COUNT"
        fi

        log_message "Apache check: OK"
        return 0
    fi
}

# Check Docker containers
check_containers() {
    echo -e "\n${GREEN}=== Docker Containers ===${NC}"

    # Check if landing-terminal is running
    if ! docker ps --format '{{.Names}}' | grep -q "landing-terminal"; then
        log_alert "landing-terminal container is NOT running!"
        echo "✗ landing-terminal: STOPPED"
        return 1
    else
        echo "✓ landing-terminal: RUNNING"
    fi

    # Check if webtop is running (optional)
    if docker ps --format '{{.Names}}' | grep -q "darkstar-webtop"; then
        echo "✓ darkstar-webtop: RUNNING"

        # Check if webtop has internet access (should not)
        NETWORK_CHECK=$(docker inspect darkstar-webtop --format '{{json .NetworkSettings.Networks}}' | grep -c "internet")
        if [ "$NETWORK_CHECK" -gt 0 ]; then
            log_message "Webtop is connected to internet network"
        fi
    else
        echo "  darkstar-webtop: STOPPED (expected when not in use)"
    fi

    log_message "Container check: OK"
    return 0
}

# Check recent SSH attempts
check_ssh_attacks() {
    echo -e "\n${GREEN}=== SSH Security ===${NC}"

    # Count failed attempts in last hour
    FAILED_ATTEMPTS=$(grep "Failed password\|Invalid user" /var/log/auth.log | grep "$(date '+%b %_d %H')" | wc -l)

    if [ "$FAILED_ATTEMPTS" -gt 50 ]; then
        log_alert "High number of SSH failed attempts: $FAILED_ATTEMPTS in last hour"
        echo -e "${YELLOW}⚠${NC} Failed SSH attempts (last hour): $FAILED_ATTEMPTS"
    elif [ "$FAILED_ATTEMPTS" -gt 10 ]; then
        echo -e "${YELLOW}⚠${NC} Failed SSH attempts (last hour): $FAILED_ATTEMPTS"
    else
        echo "✓ Failed SSH attempts (last hour): $FAILED_ATTEMPTS (normal)"
    fi

    # Show top attacking IPs from today
    echo "  Recent attack sources:"
    grep "$(date '+%b %_d')" /var/log/auth.log | grep "Invalid user\|Failed password" | \
        grep -oE '([0-9]{1,3}\.){3}[0-9]{1,3}' | sort | uniq -c | sort -rn | head -3 | \
        awk '{print "    " $2 " (" $1 " attempts)"}'

    log_message "SSH attack check: $FAILED_ATTEMPTS failed attempts in last hour"
}

# Check system resources
check_resources() {
    echo -e "\n${GREEN}=== System Resources ===${NC}"

    # Disk space
    DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
    echo "  Disk Usage: ${DISK_USAGE}%"
    if [ "$DISK_USAGE" -gt 90 ]; then
        log_alert "Disk usage critical: ${DISK_USAGE}%"
    elif [ "$DISK_USAGE" -gt 80 ]; then
        log_alert "Disk usage high: ${DISK_USAGE}%"
    fi

    # Memory
    MEM_USAGE=$(free | grep Mem | awk '{printf "%.0f", $3/$2 * 100}')
    echo "  Memory Usage: ${MEM_USAGE}%"
    if [ "$MEM_USAGE" -gt 90 ]; then
        log_alert "Memory usage critical: ${MEM_USAGE}%"
    fi

    # Load average
    LOAD=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $1}' | sed 's/,//')
    echo "  Load Average: $LOAD"

    log_message "Resource check: Disk=${DISK_USAGE}% Memory=${MEM_USAGE}% Load=$LOAD"
}

# Check isolated network security
check_network_isolation() {
    echo -e "\n${GREEN}=== Network Isolation ===${NC}"

    # Check if iptables rule exists for isolated network
    if iptables -L DOCKER-USER -n -v | grep -q "isolated0"; then
        echo "✓ Network isolation rule: ACTIVE"
        log_message "Network isolation check: OK"
    else
        log_alert "Network isolation rule MISSING! Containers may have internet access!"
        return 1
    fi
}

# Main execution
main() {
    echo "========================================"
    echo "  Dark Star Portal - System Monitor"
    echo "  $(date '+%Y-%m-%d %H:%M:%S')"
    echo "========================================"

    rotate_logs
    log_message "=== Monitoring check started ==="

    check_firewall
    check_fail2ban
    check_apache
    check_containers
    check_ssh_attacks
    check_resources
    check_network_isolation

    echo -e "\n${GREEN}=== Monitoring Complete ===${NC}"
    echo "Logs: $LOG_FILE"
    echo "Alerts: $ALERT_FILE"

    log_message "=== Monitoring check completed ==="
}

# Run if executed directly
if [ "${BASH_SOURCE[0]}" == "${0}" ]; then
    main "$@"
fi

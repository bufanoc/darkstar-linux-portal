#!/bin/bash
################################################################################
# Dark Star Portal - Container Security Audit Script
# Tests for container escape vulnerabilities and security misconfigurations
################################################################################

set -e -o pipefail
# Note: Individual test commands are wrapped to not trigger exit on failure

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PASSED=0
FAILED=0
WARNINGS=0

# Test result tracking
declare -a FAILED_TESTS=()
declare -a WARNING_TESTS=()

echo -e "${BLUE}"
echo "╔════════════════════════════════════════════════════╗"
echo "║   Dark Star Portal - Security Audit                ║"
echo "║   Container Escape & Misconfiguration Testing      ║"
echo "╚════════════════════════════════════════════════════╝"
echo -e "${NC}"
echo ""

test_pass() {
    echo -e "${GREEN}[PASS]${NC} $1"
    PASSED=$((PASSED + 1))
}

test_fail() {
    echo -e "${RED}[FAIL]${NC} $1"
    FAILED_TESTS+=("$1")
    FAILED=$((FAILED + 1))
}

test_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
    WARNING_TESTS+=("$1")
    WARNINGS=$((WARNINGS + 1))
}

test_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

################################################################################
# Test 1: Docker Socket Exposure
################################################################################
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test 1: Checking for Docker socket exposure..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check webtop container
if docker exec darkstar-webtop test -e /var/run/docker.sock 2>/dev/null; then
    test_fail "Docker socket exposed to webtop container (CRITICAL)"
else
    test_pass "Docker socket NOT exposed to webtop container"
fi

# Check terminal container
if docker exec landing-terminal test -e /var/run/docker.sock 2>/dev/null; then
    test_fail "Docker socket exposed to terminal container (CRITICAL)"
else
    test_pass "Docker socket NOT exposed to terminal container"
fi

echo ""

################################################################################
# Test 2: Privileged Container Check
################################################################################
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test 2: Checking for privileged containers..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Webtop
if docker inspect darkstar-webtop --format='{{.HostConfig.Privileged}}' | grep -q "true"; then
    test_fail "Webtop container is running in privileged mode (CRITICAL)"
else
    test_pass "Webtop container is NOT privileged"
fi

# Terminal
if docker inspect landing-terminal --format='{{.HostConfig.Privileged}}' | grep -q "true"; then
    test_fail "Terminal container is running in privileged mode (CRITICAL)"
else
    test_pass "Terminal container is NOT privileged"
fi

echo ""

################################################################################
# Test 3: Host Namespace Sharing
################################################################################
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test 3: Checking for host namespace sharing..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# PID namespace
for container in darkstar-webtop landing-terminal; do
    if docker inspect "$container" --format='{{.HostConfig.PidMode}}' | grep -q "host"; then
        test_fail "$container shares host PID namespace (CRITICAL)"
    else
        test_pass "$container has isolated PID namespace"
    fi
done

# Network namespace (should NOT be host mode)
for container in darkstar-webtop landing-terminal; do
    if docker inspect "$container" --format='{{.HostConfig.NetworkMode}}' | grep -q "host"; then
        test_fail "$container shares host network namespace (CRITICAL)"
    else
        test_pass "$container has isolated network namespace"
    fi
done

echo ""

################################################################################
# Test 4: Capability Analysis
################################################################################
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test 4: Analyzing container capabilities..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Dangerous capabilities to check for
DANGEROUS_CAPS=("CAP_SYS_ADMIN" "CAP_SYS_MODULE" "CAP_SYS_RAWIO" "CAP_SYS_PTRACE" "CAP_DAC_READ_SEARCH" "CAP_DAC_OVERRIDE")

for container in darkstar-webtop landing-terminal; do
    test_info "Checking capabilities for $container..."
    CAPS=$(docker inspect "$container" --format='{{.HostConfig.CapAdd}}')

    if [ "$CAPS" = "[]" ] || [ "$CAPS" = "<no value>" ]; then
        test_pass "$container: No additional dangerous capabilities added"
    else
        for cap in "${DANGEROUS_CAPS[@]}"; do
            if echo "$CAPS" | grep -q "$cap"; then
                test_warn "$container: Has dangerous capability $cap"
            fi
        done
    fi
done

echo ""

################################################################################
# Test 5: File System Mount Analysis
################################################################################
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test 5: Analyzing file system mounts..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check for sensitive host directory mounts
SENSITIVE_PATHS=("/etc" "/root" "/var/run" "/proc" "/sys" "/boot")

for container in darkstar-webtop landing-terminal; do
    test_info "Checking mounts for $container..."
    MOUNTS=$(docker inspect "$container" --format='{{range .Mounts}}{{.Source}}:{{.Destination}} {{end}}')

    FOUND_SENSITIVE=false
    for path in "${SENSITIVE_PATHS[@]}"; do
        if echo "$MOUNTS" | grep -q "$path:"; then
            test_fail "$container: Mounts sensitive host path $path"
            FOUND_SENSITIVE=true
        fi
    done

    if [ "$FOUND_SENSITIVE" = false ]; then
        test_pass "$container: No sensitive host paths mounted"
    fi
done

echo ""

################################################################################
# Test 6: Host Device Access
################################################################################
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test 6: Checking for host device access..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

for container in darkstar-webtop landing-terminal; do
    DEVICES=$(docker inspect "$container" --format='{{.HostConfig.Devices}}')

    if [ "$DEVICES" = "[]" ] || [ "$DEVICES" = "<no value>" ]; then
        test_pass "$container: No host devices exposed"
    else
        test_warn "$container: Has access to host devices: $DEVICES"
    fi
done

echo ""

################################################################################
# Test 7: Security Options
################################################################################
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test 7: Verifying security options..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check for no-new-privileges
for container in darkstar-webtop landing-terminal; do
    SEC_OPTS=$(docker inspect "$container" --format='{{.HostConfig.SecurityOpt}}')

    if echo "$SEC_OPTS" | grep -q "no-new-privileges:true"; then
        test_pass "$container: Has no-new-privileges enabled"
    else
        test_warn "$container: Missing no-new-privileges flag"
    fi
done

# Check for seccomp
for container in darkstar-webtop landing-terminal; do
    SEC_OPTS=$(docker inspect "$container" --format='{{.HostConfig.SecurityOpt}}')

    if echo "$SEC_OPTS" | grep -q "seccomp:unconfined"; then
        test_warn "$container: Running with seccomp:unconfined (reduced security)"
    else
        test_pass "$container: Using seccomp profile"
    fi
done

echo ""

################################################################################
# Test 8: Network Isolation Verification
################################################################################
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test 8: Testing network isolation..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Test if webtop has internet access (should fail if on isolated network)
test_info "Testing webtop internet connectivity (should be blocked by default)..."
if docker exec darkstar-webtop timeout 5 wget -q --spider http://google.com 2>/dev/null; then
    test_warn "Webtop has internet access (may be enabled via API)"
else
    test_pass "Webtop internet access blocked (isolated network working)"
fi

# Check network connections
WEBTOP_NETWORKS=$(docker inspect darkstar-webtop --format='{{range $k,$v := .NetworkSettings.Networks}}{{$k}} {{end}}')
test_info "Webtop connected to networks: $WEBTOP_NETWORKS"

if echo "$WEBTOP_NETWORKS" | grep -q "internet"; then
    test_warn "Webtop is connected to internet network (may have been enabled)"
else
    test_pass "Webtop not connected to internet network"
fi

echo ""

################################################################################
# Test 9: Container Process Inspection
################################################################################
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test 9: Inspecting container processes..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check if container can see host processes
test_info "Checking if containers can see host processes..."
WEBTOP_PROCS=$(docker exec darkstar-webtop ps aux 2>/dev/null | wc -l)
HOST_PROCS=$(ps aux | wc -l)

if [ "$WEBTOP_PROCS" -ge "$((HOST_PROCS - 10))" ]; then
    test_fail "Webtop can see host processes (namespace escape risk)"
else
    test_pass "Webtop process namespace is isolated (saw $WEBTOP_PROCS vs host $HOST_PROCS)"
fi

echo ""

################################################################################
# Test 10: Container Breakout Attempts
################################################################################
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test 10: Attempting common container breakout techniques..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Test 10a: Try to access host root filesystem
test_info "Attempting to access host root filesystem..."
if docker exec darkstar-webtop test -d /host 2>/dev/null; then
    test_fail "Container has /host directory mounted (potential breakout)"
else
    test_pass "No /host directory found in container"
fi

# Test 10b: Try to modify cgroups
test_info "Attempting to modify cgroups..."
if docker exec darkstar-webtop sh -c "echo test > /sys/fs/cgroup/memory/memory.limit_in_bytes" 2>/dev/null; then
    test_fail "Container can modify cgroup limits (privilege escalation risk)"
else
    test_pass "Container cannot modify cgroup limits"
fi

# Test 10c: Check for writable /proc/sys
test_info "Checking /proc/sys write access..."
if docker exec darkstar-webtop sh -c "echo 1 > /proc/sys/kernel/core_pattern" 2>/dev/null; then
    test_fail "Container can write to /proc/sys (escape risk)"
else
    test_pass "Container cannot write to /proc/sys"
fi

# Test 10d: Try to load kernel modules
test_info "Attempting to load kernel module..."
if docker exec darkstar-webtop modprobe dummy 2>/dev/null; then
    test_fail "Container can load kernel modules (CRITICAL)"
else
    test_pass "Container cannot load kernel modules"
fi

echo ""

################################################################################
# Test 11: Port Binding Security
################################################################################
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test 11: Checking port binding security..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check if ports are bound to localhost only
PUBLIC_PORTS=$(docker ps --format '{{.Ports}}' | grep -o '0\.0\.0\.0:[0-9]*' 2>/dev/null | wc -l || echo 0)

if [ "$PUBLIC_PORTS" -gt 0 ]; then
    test_fail "Container ports exposed to 0.0.0.0 (public access)"
    docker ps --format '{{.Names}}: {{.Ports}}' | grep "0.0.0.0"
else
    test_pass "All container ports bound to localhost only"
fi

echo ""

################################################################################
# Test 12: Resource Limits
################################################################################
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test 12: Verifying resource limits..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

for container in darkstar-webtop landing-terminal; do
    MEM_LIMIT=$(docker inspect "$container" --format='{{.HostConfig.Memory}}')
    CPU_LIMIT=$(docker inspect "$container" --format='{{.HostConfig.NanoCpus}}')

    if [ "$MEM_LIMIT" = "0" ]; then
        test_warn "$container: No memory limit set (DoS risk)"
    else
        LIMIT_MB=$((MEM_LIMIT / 1024 / 1024))
        test_pass "$container: Memory limit set to ${LIMIT_MB}MB"
    fi

    if [ "$CPU_LIMIT" = "0" ]; then
        test_warn "$container: No CPU limit set (DoS risk)"
    else
        test_pass "$container: CPU limit configured"
    fi
done

echo ""

################################################################################
# Test 13: User and Permission Checks
################################################################################
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Test 13: Checking user permissions in containers..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check if container processes run as root
for container in darkstar-webtop landing-terminal; do
    RUNNING_USER=$(docker exec "$container" whoami 2>/dev/null || echo "unknown")

    if [ "$RUNNING_USER" = "root" ]; then
        test_warn "$container: Running processes as root user"
    else
        test_pass "$container: Running as non-root user ($RUNNING_USER)"
    fi
done

echo ""

################################################################################
# Summary
################################################################################
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${BLUE}Security Audit Summary${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo -e "${GREEN}Passed Tests: $PASSED${NC}"
echo -e "${YELLOW}Warnings: $WARNINGS${NC}"
echo -e "${RED}Failed Tests: $FAILED${NC}"
echo ""

if [ $FAILED -gt 0 ]; then
    echo -e "${RED}━━━ FAILED TESTS ━━━${NC}"
    for test in "${FAILED_TESTS[@]}"; do
        echo -e "${RED}  ✗${NC} $test"
    done
    echo ""
fi

if [ $WARNINGS -gt 0 ]; then
    echo -e "${YELLOW}━━━ WARNINGS ━━━${NC}"
    for test in "${WARNING_TESTS[@]}"; do
        echo -e "${YELLOW}  ⚠${NC} $test"
    done
    echo ""
fi

################################################################################
# Overall Assessment
################################################################################
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${BLUE}Overall Security Assessment${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if [ $FAILED -eq 0 ] && [ $WARNINGS -le 3 ]; then
    echo -e "${GREEN}✓ EXCELLENT${NC} - Container security is well configured"
    echo "  Your containers have strong isolation and security controls."
    EXIT_CODE=0
elif [ $FAILED -eq 0 ] && [ $WARNINGS -le 6 ]; then
    echo -e "${YELLOW}⚠ GOOD${NC} - Container security is acceptable with minor concerns"
    echo "  Consider addressing warnings to improve security posture."
    EXIT_CODE=0
elif [ $FAILED -le 2 ]; then
    echo -e "${YELLOW}⚠ MODERATE${NC} - Some security issues detected"
    echo "  Address failed tests before production deployment."
    EXIT_CODE=1
else
    echo -e "${RED}✗ CRITICAL${NC} - Significant security vulnerabilities detected"
    echo "  DO NOT use in production until all critical issues are resolved."
    EXIT_CODE=2
fi

echo ""
echo "For more information on container security best practices, see:"
echo "  - https://docs.docker.com/engine/security/"
echo "  - https://cheatsheetseries.owasp.org/cheatsheets/Docker_Security_Cheat_Sheet.html"
echo ""

exit $EXIT_CODE

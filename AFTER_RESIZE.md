# After Droplet Resize - Quick Start

**You just resized your Digital Ocean droplet. Here's what to do next:**

---

## Step 1: Verify New Resources

```bash
# Check RAM (should show 4GB+)
free -h

# Check CPU (should show 2+)
nproc
```

✅ If RAM shows 4GB+ and CPUs show 2+, proceed to next step.

---

## Step 2: Pull Latest Changes

```bash
cd /root/darkstar-linux-portal
git pull origin alpha
```

---

## Step 3: Clean Restart

```bash
# Stop all containers
docker compose down

# Remove old webtop configuration (fresh start)
rm -rf webtop-config

# Start containers with new resource limits
docker compose up -d
```

---

## Step 4: Verify Everything Works

```bash
# Wait 30 seconds for containers to fully start
sleep 30

# Check containers are running
docker ps

# Check webtop resource usage (should be under 70%)
docker stats darkstar-webtop --no-stream

# Check webtop logs (should show no X server crashes)
docker logs darkstar-webtop --tail 30
```

---

## Step 5: Test Desktop Access

Open your browser and visit:
- **Portal**: https://oops.skyfort.group/
- **Desktop**: https://oops.skyfort.group/webtop/
- **Terminal**: https://oops.skyfort.group/terminal/

The desktop should now load properly without the "Play Stream" loop!

---

## Expected Results

**Good Signs**:
- Docker stats shows webtop using ~50-70% of available memory
- Desktop loads within 10-15 seconds
- No "Play Stream" button appears
- KDE desktop displays properly
- No X server crashes in logs

**If Still Having Issues**:
```bash
# Check detailed logs
docker logs darkstar-webtop --tail 100

# Check memory usage
docker stats --no-stream

# Restart containers
docker compose restart
```

---

## Network Control

To enable internet access in the desktop:
1. Go to: https://oops.skyfort.group/
2. Find the "Network Control" panel
3. Enter your password (from `config/deployment.env`)
4. Click "Enable Internet"

---

## Need Help?

Check the full deployment guide: `DEPLOY.md`
Or see session continuity: `/root/session_continuity.md`

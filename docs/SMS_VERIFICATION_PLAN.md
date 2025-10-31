# SMS Verification System - Implementation Plan
**Dark Star Portal - Responsible Access Provider**

## Executive Summary

Transform the simple password-based internet access control into a comprehensive SMS verification system that promotes responsible usage while maintaining security and user experience.

## Current System vs. Proposed System

### Current (Password-Based)
```
User clicks "Enable Internet"
  ↓
Enters password
  ↓
Internet enabled
```

### Proposed (SMS Verification)
```
User clicks "Enable Internet"
  ↓
Wizard: "Verification Required for Members"
  ↓
Form: Name, Email, Phone Number
  ↓
SMS sent with 6-digit code
  ↓
User enters verification code
  ↓
Code validated → Success message
  ↓
Magic link sent to email
  ↓
User clicks magic link
  ↓
Internet enabled for 24 hours
```

---

## Technology Stack

### SMS Provider: Twilio
- **Trial:** $15 free credit (enough for 1,900+ SMS)
- **Cost:** $0.0079 per SMS in US
- **Phone Number:** $1/month for Twilio number
- **Reliability:** Industry standard, 99.95% uptime
- **Documentation:** Excellent PHP SDK

### Database: MySQL/MariaDB
- **Already Available:** Included in Ubuntu 24.04
- **Purpose:** Store verification records
- **Schema:** Single table for tracking verifications

### Email: PHP mail() or SMTP
- **Option 1:** Server's native mail (simple, may hit spam)
- **Option 2:** SendGrid/Mailgun SMTP (more reliable)
- **Recommendation:** Start with native, upgrade if needed

### Session Management: PHP Sessions
- **Secure:** HTTPOnly, Secure cookies
- **Token Storage:** Cryptographically secure random tokens

---

## Database Schema

```sql
CREATE DATABASE darkstar_access;

USE darkstar_access;

CREATE TABLE network_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    sms_code VARCHAR(6) NOT NULL,
    magic_token VARCHAR(64) NOT NULL UNIQUE,
    ip_address VARCHAR(45),
    user_agent TEXT,

    -- Status tracking
    sms_verified BOOLEAN DEFAULT FALSE,
    link_clicked BOOLEAN DEFAULT FALSE,
    access_enabled BOOLEAN DEFAULT FALSE,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sms_verified_at TIMESTAMP NULL,
    link_clicked_at TIMESTAMP NULL,
    access_enabled_at TIMESTAMP NULL,
    access_expires_at TIMESTAMP NULL,

    -- Indexes for performance
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_token (magic_token),
    INDEX idx_expires (access_expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate limiting table
CREATE TABLE verification_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_type ENUM('sms', 'verification', 'link') NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_phone_time (phone, attempted_at),
    INDEX idx_email_time (email, attempted_at),
    INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Implementation Steps

### Phase 1: Database Setup (15 minutes)
1. Install MySQL/MariaDB if not present
2. Create database and tables
3. Create dedicated database user
4. Test connection from PHP

### Phase 2: Twilio Integration (30 minutes)
1. Sign up for Twilio account
2. Get phone number
3. Install Twilio PHP SDK via Composer
4. Create SMS sending function
5. Test SMS delivery

### Phase 3: Backend API Endpoints (1 hour)

**Endpoint 1:** `/api/request-verification.php`
- Accepts: name, email, phone
- Validates input
- Checks rate limits
- Generates 6-digit code
- Sends SMS via Twilio
- Returns: success/error

**Endpoint 2:** `/api/verify-code.php`
- Accepts: phone, code
- Validates code
- Marks SMS as verified
- Generates magic token
- Sends email with magic link
- Returns: success/error

**Endpoint 3:** `/api/enable-access.php`
- Accepts: token (from URL)
- Validates token
- Enables Docker network
- Sets 24-hour expiration
- Returns: success page

**Endpoint 4:** `/api/check-access-status.php`
- Checks current access status
- Returns: enabled/disabled, time remaining

### Phase 4: Frontend Wizard UI (1 hour)
1. Multi-step modal wizard
2. Step 1: Information collection
3. Step 2: SMS code entry
4. Step 3: Success message
5. Loading states and error handling
6. Responsive design matching site theme

### Phase 5: Email Templates (30 minutes)
1. Magic link email with branding
2. HTML + plain text versions
3. Instructions and support info

### Phase 6: Testing & Security (30 minutes)
1. Rate limiting enforcement
2. SQL injection prevention
3. XSS protection
4. CSRF tokens
5. Input validation
6. Error handling

---

## Security Features

### Rate Limiting
- **SMS requests:** Max 3 per phone per hour
- **Code attempts:** Max 5 per phone per hour
- **IP-based:** Max 10 requests per IP per hour
- **Cooldown:** 60 seconds between requests

### Token Security
- **Magic tokens:** 64 characters, cryptographically secure
- **Single-use:** Token invalidated after use
- **Expiration:** Magic links expire after 1 hour
- **Access duration:** 24 hours after link click

### Data Protection
- **Prepared statements:** All database queries
- **Input sanitization:** Email, phone number validation
- **HTTPS only:** All API endpoints require HTTPS
- **HTTPOnly cookies:** Session cookies protected

### Privacy
- **Data retention:** Auto-delete records after 30 days
- **Minimal logging:** Only essential information
- **No sharing:** User data never shared with third parties

---

## User Flow Detailed

### Step 1: Initial Request
```
User clicks "Enable Internet" button
  ↓
Modal opens: "Internet Access Verification"
  ↓
Message: "For safety and security, internet access is only
         available to verified members. This ensures
         responsible usage of our shared resources."
  ↓
Form appears:
  - Name: [text input]
  - Email: [email input]
  - Phone: [phone input with country code]
  - [Continue] button
```

### Step 2: SMS Verification
```
User submits form
  ↓
Loading: "Sending verification code..."
  ↓
SMS sent to user's phone
  ↓
New screen: "Check Your Phone"
  ↓
Message: "We've sent a 6-digit code to [phone number]"
  ↓
Input: [_ _ _ _ _ _] (6 digit code entry)
  ↓
Auto-submit when 6 digits entered
```

### Step 3: Email Magic Link
```
Code verified successfully
  ↓
Screen: "Almost There!"
  ↓
Message: "Check your email at [email]
         We've sent you a magic link to complete verification."
  ↓
Email sent with subject: "Dark Star Portal - Complete Your Verification"
  ↓
User clicks link in email
```

### Step 4: Access Granted
```
Link opens → /api/enable-access.php?token=...
  ↓
Token validated
  ↓
Docker network connected
  ↓
Success page: "Welcome to Dark Star Portal"
  ↓
Message: "Internet access is now enabled for 24 hours.
         You can close this window and return to your desktop."
  ↓
Access automatically expires after 24 hours
```

---

## Cost Analysis

### Setup Costs
- Twilio phone number: $1/month
- Initial trial credit: $15 (free)

### Operating Costs (Monthly)

**Scenario 1: Light Usage (5 users/day)**
- 150 SMS/month × $0.0079 = $1.19
- Phone number = $1.00
- **Total: $2.19/month**

**Scenario 2: Moderate Usage (20 users/day)**
- 600 SMS/month × $0.0079 = $4.74
- Phone number = $1.00
- **Total: $5.74/month**

**Scenario 3: Heavy Usage (50 users/day)**
- 1,500 SMS/month × $0.0079 = $11.85
- Phone number = $1.00
- **Total: $12.85/month**

### Break-Even Analysis
- Twilio trial ($15) = ~1,900 SMS
- At 5 users/day = 127 days of free usage
- At 20 users/day = 32 days of free usage

---

## Configuration Files

### Twilio Configuration
```php
// config/twilio.php
<?php
return [
    'account_sid' => 'AC...', // From Twilio dashboard
    'auth_token' => '...', // From Twilio dashboard
    'phone_number' => '+1234567890', // Your Twilio number
];
```

### Database Configuration
```php
// config/database.php
<?php
return [
    'host' => 'localhost',
    'database' => 'darkstar_access',
    'username' => 'darkstar_user',
    'password' => 'secure_password_here',
    'charset' => 'utf8mb4'
];
```

### Access Configuration
```php
// config/access.php
<?php
return [
    'access_duration_hours' => 24,
    'magic_link_expiry_minutes' => 60,
    'rate_limit_sms_per_hour' => 3,
    'rate_limit_attempts_per_hour' => 5,
    'rate_limit_ip_per_hour' => 10,
    'data_retention_days' => 30
];
```

---

## Error Handling

### User-Friendly Messages

**SMS Failed:**
> "Unable to send verification code. Please check your phone number and try again."

**Code Invalid:**
> "The code you entered is incorrect. Please try again. (3 attempts remaining)"

**Too Many Attempts:**
> "Too many verification attempts. Please wait 1 hour before trying again."

**Link Expired:**
> "This verification link has expired. Please start the verification process again."

**Already Verified:**
> "You've already verified! Internet access is active for [X hours] remaining."

---

## Monitoring & Logging

### Log Events
- Verification requests (phone, email, timestamp)
- SMS sent successfully
- SMS delivery failures
- Code verification attempts (success/failure)
- Magic link clicks
- Access enabled/disabled events
- Rate limit triggers
- Error conditions

### Metrics to Track
- Daily verification requests
- SMS success rate
- Average time from request to access
- Failed verification reasons
- Popular usage times
- Access duration patterns

---

## Maintenance Tasks

### Daily
- Monitor SMS delivery success rate
- Check for unusual patterns (fraud attempts)
- Review error logs

### Weekly
- Clean up expired verification records (30+ days old)
- Review Twilio usage and costs
- Check rate limit effectiveness

### Monthly
- Analyze usage patterns
- Optimize SMS message content
- Review and update documentation
- Cost analysis and optimization

---

## Fallback & Recovery

### SMS Delivery Failure
1. Retry once after 30 seconds
2. If still failing, offer email-only verification option
3. Log issue for investigation

### Database Failure
1. Display maintenance message
2. Alert administrator
3. Fall back to password-based system temporarily

### Twilio Service Outage
1. Check Twilio status page
2. Queue verification requests
3. Process when service restored
4. Offer password backup method

---

## Migration Path

### Phase 1: Parallel Running
- Keep password system active
- Add SMS system as optional
- Users can choose either method

### Phase 2: Gradual Transition
- Encourage SMS verification
- Keep password as backup
- Monitor adoption rate

### Phase 3: Full Switch
- SMS verification becomes primary
- Password kept for admin/emergency only
- Update documentation

---

## Testing Checklist

### Unit Tests
- [ ] Phone number validation
- [ ] Email validation
- [ ] Code generation (6 digits, unique)
- [ ] Token generation (64 chars, secure)
- [ ] Rate limiting logic
- [ ] Expiration calculations

### Integration Tests
- [ ] SMS sending via Twilio
- [ ] Email delivery
- [ ] Database operations
- [ ] Docker network control
- [ ] Full user flow end-to-end

### Security Tests
- [ ] SQL injection attempts
- [ ] XSS attempts
- [ ] CSRF protection
- [ ] Rate limit bypass attempts
- [ ] Token manipulation attempts

### User Experience Tests
- [ ] Mobile device compatibility
- [ ] Form validation messages
- [ ] Loading states
- [ ] Error recovery
- [ ] Accessibility (screen readers)

---

## Next Steps

1. **User Decision:** Approve plan and budget
2. **Twilio Setup:** Create account, get credentials
3. **Database Setup:** Install MySQL, create schema
4. **Development:** Implement according to phases
5. **Testing:** Comprehensive testing before deployment
6. **Deployment:** Gradual rollout with monitoring
7. **Documentation:** User guide and admin docs

---

## Support & Documentation

### User Documentation
- How to verify your phone number
- What to do if SMS doesn't arrive
- How long does access last
- How to re-enable after expiration
- Privacy and data handling

### Admin Documentation
- Twilio account management
- Database maintenance
- Monitoring and logging
- Troubleshooting common issues
- Cost optimization strategies

---

## Conclusion

This SMS verification system provides:
✅ Enhanced security through multi-factor verification
✅ Responsible access control
✅ Professional user experience
✅ Scalable architecture
✅ Cost-effective solution
✅ Privacy-focused design

**Estimated Total Implementation Time:** 4-5 hours
**Estimated Monthly Cost:** $2-13 depending on usage
**ROI:** Improved security, better user tracking, professional appearance

**Status:** Ready for implementation upon Twilio account creation

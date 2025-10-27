# Security Policy

## 🔒 Reporting a Vulnerability

We take the security of Finova seriously. If you discover a security vulnerability, please follow these steps:

### Reporting Process

1. **DO NOT** open a public GitHub issue for security vulnerabilities
2. Email us directly at: **hi@mikpa.com**
3. Include detailed information about the vulnerability:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

### What to Expect

- **Response Time:** We aim to respond within 24 hours
- **Updates:** We'll keep you informed about the progress
- **Credit:** We'll acknowledge your contribution (if desired) once the issue is resolved

## 🛡️ Security Best Practices

### For Deployment

1. **Environment Variables**
   - Never commit `.env` files
   - Use strong, unique passwords

2. **Database Security**
   - Use strong database passwords
   - Enable SSL/TLS connections

3. **API Keys**
   - Keep OpenAI/Gemini API keys secure
   - Use environment variables

4. **Server Configuration**
   - Keep PHP and Laravel updated
   - Use HTTPS only

### For Development

1. **Dependencies**
   - Keep dependencies updated
   - Review package permissions

2. **Code Quality**
   - Follow security best practices
   - Sanitize user inputs
   - Use parameterized queries

## 📋 Security Checklist for Self-Hosting

- [ ] Change default admin credentials immediately
- [ ] Configure proper file permissions (755 for directories, 644 for files)
- [ ] Disable debug mode in production (`APP_DEBUG=false`)
- [ ] Set up regular backups
- [ ] Enable HTTPS with valid SSL certificate
- [ ] Configure proper CORS settings
- [ ] Review and configure `.htaccess` or nginx rules

**Thank you for helping keep Finova secure!** 🙏


# Security Policy

## Supported Versions

We actively support and release security updates for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 0.4.x   | :white_check_mark: |
| 0.3.x   | :white_check_mark: |
| < 0.3   | :x:                |

## Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security vulnerability, please follow these steps:

### 1. **Do NOT** create a public GitHub issue

Please do not report security vulnerabilities through public GitHub issues. This helps protect users while we work on a fix.

### 2. Report the vulnerability

Send an email to **git@fishda.net** with the following information:

- **Description**: A clear description of the vulnerability
- **Impact**: The potential impact of the vulnerability
- **Steps to reproduce**: Detailed steps to reproduce the issue
- **Affected versions**: Which versions of the package are affected
- **Suggested fix**: If you have a suggested fix, please include it (optional but appreciated)

### 3. What to expect

- **Acknowledgment**: You will receive an acknowledgment within 48 hours
- **Initial assessment**: We will provide an initial assessment within 7 days
- **Updates**: We will keep you informed of our progress
- **Resolution**: We will work to resolve the issue as quickly as possible

### 4. Disclosure policy

- We will coordinate with you on the disclosure timeline
- We will credit you in the security advisory (unless you prefer to remain anonymous)
- We will not disclose the vulnerability publicly until a fix is available

## Security Best Practices

When using this package, please follow these security best practices:

1. **Keep dependencies updated**: Regularly update this package and its dependencies
2. **Review permissions**: Regularly audit and review permissions assigned to users and roles
3. **Use least privilege**: Grant only the minimum permissions necessary
4. **Validate input**: Always validate and sanitize user input before processing
5. **Monitor access**: Implement logging and monitoring for permission-related operations

## Security Updates

Security updates will be released as:
- **Patch versions** (e.g., 0.4.1) for critical security fixes
- **Minor versions** (e.g., 0.5.0) for security improvements and non-critical fixes

All security updates will be documented in:
- The [CHANGELOG.md](CHANGELOG.md)
- GitHub Security Advisories
- Release notes

## Thank You

We appreciate your help in keeping this package secure. Security researchers who responsibly disclose vulnerabilities will be credited in our security advisories.


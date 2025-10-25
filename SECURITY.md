# Security Policy

## Supported Versions

We take security seriously and are committed to addressing security vulnerabilities promptly. The following versions of ElasticBridge are currently being supported with security updates:

| Version | Supported          |
| ------- | ------------------ |
| 2.x     | :white_check_mark: |
| 1.x     | :x:                |

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues.**

If you discover a security vulnerability, please report it privately to help us address it before it can be exploited.

### Private Reporting (Preferred Method)

1. Go to the [Security Advisories page](https://github.com/Lacasera/elastic-bridge/security/advisories)
2. Click "Report a vulnerability"
3. Provide detailed information about the vulnerability
4. Submit the report

GitHub will privately notify the maintainers, and we can collaborate on a fix before any public disclosure.

### Alternative Reporting Methods

If you're unable to use GitHub's private reporting feature, you can:

- Send an email to the maintainer (check package.json or composer.json for contact details)
- Open a draft security advisory in the repository

### What to Include in Your Report

Please include as much of the following information as possible:

- **Type of vulnerability** (e.g., SQL injection, XSS, authentication bypass, etc.)
- **Full paths of affected source files**
- **Location of the affected code** (tag/branch/commit or direct URL)
- **Step-by-step instructions to reproduce** the vulnerability
- **Proof-of-concept or exploit code** (if possible)
- **Impact of the vulnerability** - what an attacker could do
- **Possible remediation** (if you have suggestions)

### What to Expect

After you submit a vulnerability report:

1. **Acknowledgment**: We'll acknowledge receipt of your report within 48 hours
2. **Assessment**: We'll investigate and assess the severity within 5 business days
3. **Updates**: We'll keep you informed about our progress toward a fix
4. **Fix & Release**: We'll work on a fix and prepare a security release
5. **Disclosure**: Once the fix is released, we'll publicly disclose the vulnerability (with credit to you, if desired)
6. **Credit**: Security researchers who responsibly disclose vulnerabilities will be credited in the release notes (unless they prefer to remain anonymous)

## Security Best Practices for Users

When using ElasticBridge in your applications:

1. **Keep Dependencies Updated**: Regularly update ElasticBridge and all dependencies
2. **Use Environment Variables**: Never hardcode Elasticsearch credentials in your code
3. **Validate Input**: Always validate and sanitize user input before using it in queries
4. **Principle of Least Privilege**: Use Elasticsearch credentials with minimal required permissions
5. **Secure Your Elasticsearch Instance**: 
   - Enable authentication
   - Use TLS/SSL for connections
   - Implement proper network security
   - Keep Elasticsearch updated
6. **Review Query Logic**: Be cautious with dynamic queries that incorporate user input
7. **Monitor Access**: Log and monitor Elasticsearch access patterns

## Known Security Considerations

### Query Injection

When building dynamic queries with user input, always validate and sanitize:

```php
// ❌ BAD - Direct user input in query
$field = $_GET['field']; // Dangerous!
$results = HotelRoom::asBoolean()->mustMatch($field, $value)->get();

// ✅ GOOD - Whitelist allowed fields
$allowedFields = ['name', 'description', 'location'];
$field = in_array($_GET['field'], $allowedFields) ? $_GET['field'] : 'name';
$results = HotelRoom::asBoolean()->mustMatch($field, $value)->get();
```

### Data Exposure

Be mindful of what data you expose through your Elasticsearch indices:

```php
// ❌ BAD - Exposing sensitive fields
$user = User::find($id); // Returns all fields including passwords, tokens, etc.

// ✅ GOOD - Select only necessary fields
$user = User::find($id)->get(['id', 'name', 'email']);
```

## Security Updates

Security updates will be released as patch versions (e.g., 2.0.1) and announced through:

- GitHub Security Advisories
- Release notes
- Package manager notifications (Composer)

Subscribe to repository notifications to stay informed about security updates.

## Bug Bounty Program

We currently do not offer a bug bounty program, but we deeply appreciate responsible security research and will acknowledge contributors in our security advisories.

## Questions?

If you have questions about this security policy, please:

1. Check our [documentation](https://elasticbridge.dev)
2. Open a [GitHub Discussion](https://github.com/Lacasera/elastic-bridge/discussions)
3. For sensitive security questions, use the private vulnerability reporting process

Thank you for helping keep ElasticBridge and its users safe!

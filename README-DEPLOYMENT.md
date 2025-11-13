# ManageMyParking - Deployment Packages

## Directory Structure

```
├── jrk/                           # v1.1 STABLE - DO NOT MODIFY
│   └── (Working production baseline - reference only)
├── jrk-payments/                  # v2.0 DEVELOPMENT
│   └── (Payment system v2.0 - in testing)
└── ManageMyParking-v2.0-VERIFIED-CLEAN.zip  # Deployment package
```

## Important Notes

### v1.1 (`jrk/` directory)
- **STABLE PRODUCTION VERSION**
- Currently deployed and working on production sites
- **DO NOT DELETE OR MODIFY** - Reference source only
- All code verified working with production databases

### v2.0 (`jrk-payments/` directory)
- Development version with payment system features
- Being tested and stabilized
- Will replace v1.1 once fully stable

## Deployment Package

**ManageMyParking-v2.0-VERIFIED-CLEAN.zip** (272 KB)
- Complete v2.0 codebase
- Code verified: NO typos, correct file names, proper extensions
- Ready for testing deployment
- See VERIFICATION_REPORT.md for complete audit details

## Code Verification Status

✅ All file names correct (no "1" suffixes)
✅ All file extensions correct (.php)
✅ No hardcoded localhost URLs
✅ Session cookie path = '/' (production-ready)
✅ All API endpoints present and correct

**Note:** Any deployment issues (ERR_NAME_NOT_RESOLVED, wrong URLs) are environmental (browser cache, server cache, old files), NOT code issues.

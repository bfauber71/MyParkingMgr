/**
 * Dynamic Configuration Loader
 * Loads application configuration dynamically
 */

(function() {
    // Function to get the base path dynamically
    function detectBasePath() {
        // For root installation, return empty string
        // For subdirectory installation, return the subdirectory path
        const pathname = window.location.pathname;
        
        // If we're at root or index.html, base path is empty
        if (pathname === '/' || pathname === '/index.html' || pathname === '') {
            return '';
        }
        
        // Try to detect from script tags
        const scriptTags = document.getElementsByTagName('script');
        for (let i = 0; i < scriptTags.length; i++) {
            const src = scriptTags[i].src;
            if (src && src.includes('/assets/config.js')) {
                const match = src.match(/^https?:\/\/[^\/]+(.*)\/assets\/config\.js/);
                if (match && match[1]) {
                    return match[1];
                }
            }
        }
        
        // If pathname contains index.html, extract base path
        if (pathname.includes('/index.html')) {
            return pathname.replace('/index.html', '');
        }
        
        // Default: assume root installation
        return '';
    }
    
    // Set default configuration
    window.MPM_CONFIG = window.MPM_CONFIG || {
        basePath: detectBasePath(),
        apiBase: detectBasePath() + '/api',
        appName: 'MyParkingManager'
    };
    
    // Set API_BASE for backward compatibility
    window.API_BASE = window.MPM_CONFIG.apiBase;
    
    // Try to load configuration from server
    const configScript = document.createElement('script');
    configScript.src = window.MPM_CONFIG.basePath + '/api/app-config';
    configScript.onerror = function() {
        console.log('Using default configuration');
    };
    
    // Insert before other scripts
    const firstScript = document.getElementsByTagName('script')[0];
    if (firstScript && firstScript.parentNode) {
        firstScript.parentNode.insertBefore(configScript, firstScript);
    } else {
        document.head.appendChild(configScript);
    }
})();
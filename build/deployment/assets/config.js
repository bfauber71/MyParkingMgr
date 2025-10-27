/**
 * Dynamic Configuration Loader
 * Loads application configuration dynamically
 */

(function() {
    // Function to get the base path dynamically
    function detectBasePath() {
        const pathname = window.location.pathname;
        const scriptTags = document.getElementsByTagName('script');
        
        // Try to detect from script tags
        for (let i = 0; i < scriptTags.length; i++) {
            const src = scriptTags[i].src;
            if (src && src.includes('/assets/')) {
                const match = src.match(/^https?:\/\/[^\/]+(.*)\/assets\//);
                if (match) {
                    return match[1] || '';
                }
            }
        }
        
        // Fallback: detect from pathname
        // Remove the filename and get the directory path
        const pathParts = pathname.split('/');
        pathParts.pop(); // Remove filename
        
        // If we're in /public or a known endpoint, go up one level
        if (pathParts[pathParts.length - 1] === 'public') {
            pathParts.pop();
        }
        
        return pathParts.join('/') || '';
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
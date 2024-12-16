const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
const terser = require('terser');
const postcss = require('postcss');
const cssnano = require('cssnano');
const autoprefixer = require('autoprefixer');

// Configuration
const config = {
  pluginName: 'affiliate-content-generator',
  pluginVersion: '1.0.0',
  srcDir: './src',
  buildDir: './build',
  distDir: './dist',
  phpFiles: [
    'main-plugin.php',
    'includes/content-generator.php',
    'includes/quality-handler.php',
    'includes/quality-handler-enhanced.php',
    'includes/research-tools.php',
    'includes/template-handler.php',
    'includes/api-handler.php',
    'includes/seo-handler.php',
    'includes/cache-handler.php',
    'templates/settings-template.php',
    'templates/admin-page.php',
  ],
  assets: {
    js: [
      'assets/js/admin.js',
      'assets/js/settings.js'
    ],
    css: [
      'assets/css/admin.css'
    ]
  },
  phpDependencies: [
    'wp-includes/rest-api',
    'wp-includes/formatting',
    'wp-includes/post'
  ],
  // Production environment configuration
  prodConfig: {
    minify: true,
    removeComments: true,
    generateSourceMaps: false,
  },
  // Development environment configuration
  devConfig: {
    watchEnabled: true,
    sourceMaps: true,
    liveReload: true,
    debugMode: true
  }
};

// Create necessary directories
function createDirectories() {
  const dirs = [
    config.buildDir,
    config.distDir,
    `${config.buildDir}/includes`,
    `${config.buildDir}/templates`,
    `${config.buildDir}/assets/js`,
    `${config.buildDir}/assets/css`
  ];

  dirs.forEach(dir => {
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir, { recursive: true });
    }
  });
}

// Clean build and dist directories
function cleanDirectories() {
  [config.buildDir, config.distDir].forEach(dir => {
    if (fs.existsSync(dir)) {
      fs.rmSync(dir, { recursive: true, force: true });
    }
  });
}

// Process PHP files
async function processPhpFiles() {
  const isProd = process.env.NODE_ENV === 'production';

  for (const file of config.phpFiles) {
    const srcPath = path.join(config.srcDir, file);
    const buildPath = path.join(config.buildDir, file);
    
    try {
      const destDir = path.dirname(buildPath);
      if (!fs.existsSync(destDir)) {
        fs.mkdirSync(destDir, { recursive: true });
      }

      let content = fs.readFileSync(srcPath, 'utf8');
      
      if (isProd) {
        content = optimizePhpForProduction(content);
      } else {
        content = addDebugHelpers(content);
      }

      content = processPhpDependencies(content);
      fs.writeFileSync(buildPath, content);
      validatePhpFile(buildPath);
    } catch (error) {
      console.error(`Error processing ${file}:`, error);
      throw error;
    }
  }
}

function optimizePhpForProduction(content) {
  content = content.replace(/define\('WP_DEBUG', true\)/, "define('WP_DEBUG', false)");
  content = content.replace(/error_reporting\(E_ALL\)/, "error_reporting(0)");
  content = content.replace(/\/\*\*[\s\S]*?\*\/\n/g, '');
  content = content.replace(/\/\/[\s\S]*?\n/g, '\n');
  content = content.replace(/\s+/g, ' ');
  content = content.replace(/\$this->load_dependencies\(\);/g, 
    "if (!defined('DOING_AJAX')) { $this->load_dependencies(); }");
  
  return content;
}

function addDebugHelpers(content) {
  // Check if debug_log function already exists
  if (content.includes('debug_log')) {
    return content; // Return unchanged if debug_log already exists
  }
  
  // Add debug_log function only if it doesn't exist
  content = content.replace(/class\s+(\w+)\s*{/, (match, className) => `
    class ${className} {
      private function debug_log($message, $data = null) {
        if (!$this->debug_mode) return;
        
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}][{$className}] {$message}";
        
        if ($data !== null) {
            $log_entry .= "\nData: " . print_r($data, true);
        }
        
        $log_entry .= "\n" . str_repeat('-', 80) . "\n";
        
        error_log($log_entry, 3, ACG_DEBUG_LOG);
      }
  `);
  
  return content;
}

function processPhpDependencies(content) {
  const dependencyCheck = `
    function check_dependencies() {
      $required = ['${config.phpDependencies.join("', '")}'];
      foreach ($required as $dep) {
        if (!file_exists(ABSPATH . $dep)) {
          throw new Exception("Missing required dependency: $dep");
        }
      }
    }
  `;
  
  return content.replace(/class\s+(\w+)\s*{/, `$&\n${dependencyCheck}`);
}

function validatePhpFile(filePath) {
  try {
    execSync(`C:\\xampp\\php\\php.exe -l ${filePath}`, { stdio: 'pipe' });
  } catch (error) {
    throw new Error(`PHP syntax error in ${path.basename(filePath)}: ${error.message}`);
  }
}

// Process JavaScript files
async function processJavaScript() {
  const isProd = process.env.NODE_ENV === 'production';

  for (const file of config.assets.js) {
    const srcPath = path.join(config.srcDir, file);
    const buildPath = path.join(config.buildDir, file);
    const fileName = path.basename(file);
    
    try {
      const destDir = path.dirname(buildPath);
      if (!fs.existsSync(destDir)) {
        fs.mkdirSync(destDir, { recursive: true });
      }

      let code = fs.readFileSync(srcPath, 'utf8');
      
      if (fileName === 'admin.js' || fileName === 'settings.js') {
        code = processAdminJS(code);
      }

      const minified = await terser.minify(code, {
        compress: {
          drop_console: isProd,
          drop_debugger: isProd,
          dead_code: true
        },
        mangle: isProd,
        sourceMap: !isProd && config.devConfig.sourceMaps
      });

      fs.writeFileSync(buildPath, minified.code);
      if (minified.map) {
        fs.writeFileSync(`${buildPath}.map`, minified.map);
      }
    } catch (error) {
      console.error(`Error processing ${file}:`, error);
      throw error;
    }
  }
}

// Process CSS files
async function processCSS() {
  const isProd = process.env.NODE_ENV === 'production';
  
  const processor = postcss([
    autoprefixer(),
    cssnano({
      preset: ['default', {
        discardComments: {
          removeAll: isProd
        }
      }]
    })
  ]);

  for (const file of config.assets.css) {
    try {
      const srcPath = path.join(config.srcDir, file);
      const buildPath = path.join(config.buildDir, file);
      
      const destDir = path.dirname(buildPath);
      if (!fs.existsSync(destDir)) {
        fs.mkdirSync(destDir, { recursive: true });
      }

      const css = fs.readFileSync(srcPath, 'utf8');
      const result = await processor.process(css, { 
        from: srcPath, 
        to: buildPath,
        map: !isProd && config.devConfig.sourceMaps ? { inline: false } : false
      });
      
      fs.writeFileSync(buildPath, result.css);
      if (result.map) {
        fs.writeFileSync(`${buildPath}.map`, result.map.toString());
      }
    } catch (error) {
      console.error(`Error processing ${file}:`, error);
      throw error;
    }
  }
}

function processAdminJS(code) {
  code = `try {
    ${code}
  } catch (error) {
    console.error('Admin JS Error:', error);
    if (window.wp && wp.data && wp.data.dispatch) {
      wp.data.dispatch('core/notices').createErrorNotice(
        'An error occurred in the admin interface. Please refresh the page.'
      );
    }
  }`;
  
  return code;
}

// Create distribution package
function createDistPackage() {
    try {
        const zipFileName = `${config.pluginName}-${config.pluginVersion}.zip`;
        const zipPath = path.join(config.distDir, zipFileName);

        if (!fs.existsSync(config.distDir)) {
            fs.mkdirSync(config.distDir);
        }

        const metadata = generatePluginMetadata();
        fs.writeFileSync(path.join(config.buildDir, 'readme.txt'), metadata);

        const sevenZipCommand = `7z a -tzip "${path.resolve(zipPath)}" "${path.resolve(config.buildDir)}\\*"`;
        execSync(sevenZipCommand, { stdio: 'inherit' });

        const checksum = execSync(`certutil -hashfile "${zipPath}" SHA256`).toString().split('\n')[1].trim();
        fs.writeFileSync(
            path.join(config.distDir, `${zipFileName}.sha256`),
            checksum
        );
    } catch (error) {
        console.error('Error creating distribution package:', error);
        throw error;
    }
}

function generatePluginMetadata() {
  return `=== ${config.pluginName} ===
Version: ${config.pluginVersion}
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
License: GPL v2 or later

Automated affiliate content generation using AI.

== Description ==
A powerful WordPress plugin for generating affiliate content using Claude AI.

== Installation ==
1. Upload the plugin files to /wp-content/plugins/
2. Activate the plugin
3. Configure your API key in the settings

== Changelog ==
= ${config.pluginVersion} =
* Initial release
`;
}

// Watch for file changes
function watchFiles() {
  if (!config.devConfig.watchEnabled) return;

  console.log('👀 Watching for file changes...');

  fs.watch(path.join(config.srcDir), { recursive: true }, (eventType, filename) => {
    if (filename && filename.endsWith('.php')) {
      console.log(`🔄 PHP file changed: ${filename}`);
      processPhpFiles().catch(console.error);
    }
  });

  fs.watch(path.join(config.srcDir, 'assets/js'), (eventType, filename) => {
    if (filename && filename.endsWith('.js')) {
      console.log(`🔄 JS file changed: ${filename}`);
      processJavaScript().catch(console.error);
    }
  });

  fs.watch(path.join(config.srcDir, 'assets/css'), (eventType, filename) => {
    if (filename && filename.endsWith('.css')) {
      console.log(`🔄 CSS file changed: ${filename}`);
      processCSS().catch(console.error);
    }
  });
}

// Main build process
async function build() {
  const startTime = Date.now();
  
  try {
    console.log('🚀 Starting build process...');
    
    console.log('🧹 Cleaning directories...');
    cleanDirectories();
    
    console.log('📁 Creating directories...');
    createDirectories();
    
    console.log('📝 Processing PHP files...');
    await processPhpFiles();
    
    console.log('🔧 Processing JavaScript...');
    await processJavaScript();
    
    console.log('🎨 Processing CSS...');
    await processCSS();
    
    console.log('📦 Creating distribution package...');
    await createDistPackage();
    
    if (config.devConfig.watchEnabled) {
      watchFiles();
    }
    
    const buildTime = ((Date.now() - startTime) / 1000).toFixed(2);
    console.log(`✅ Build completed successfully in ${buildTime}s`);
  } catch (error) {
    console.error('❌ Build failed:', error);
    process.exit(1);
  }
}

// Run build process
build();

module.exports = {
  build,
  config,
  processPhpFiles,
  processJavaScript,
  processCSS,
  createDistPackage,
  watchFiles
};
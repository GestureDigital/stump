import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite';
import { wordpressPlugin, wordpressThemeJson } from '@roots/vite-plugin';
import fs from 'fs'
import path from 'path'

/**
 * Writes theme/public/hot with the dev server origin (like Laravel),
 * and removes it when the server stops.
 */
function wpHotFile() {
   return {
	 name: 'wp-hot-file',
	 apply: 'serve',
	 configureServer(server) {
	   const proto   = server.config.server.https ? 'https' : 'http'
	   const host    = server.config.server.host || 'localhost'
	   const port    = server.config.server.port
	   const origin  = `${proto}://${host}:${port}`
	   const hotPath = path.resolve(__dirname, 'public/build/hot')
 
	   fs.writeFileSync(hotPath, origin)
	   server.httpServer?.once('close', () => { try { fs.unlinkSync(hotPath) } catch {} })
	 },
   }
 }
 
export default defineConfig(({ mode, command }) => {
   const isDev = mode === 'development' || command === 'serve'
 
   return {
	 /** 
	 In dev, Vite serves assets itself; in prod, they live in /public/
	 Replace with your theme name
	 **/
	 base: isDev ? '/' : '/wp-content/themes/stump/public/',
 
	 publicDir: false,
  build: {
	  outDir: 'public/build',
	  assetsDir: 'assets', 
	  manifest: 'manifest.json',
	  emptyOutDir: true,
	  rollupOptions: {
		input: [
		  'resources/css/app.css',
		  'resources/css/editor.css',
		  'resources/js/app.js',
		  'resources/js/editor.js'
		],
	  },
	},
  server: {
	  host: 'stump.local', // your Local domain
	  cors: true,
	  strictPort: true,
	  port: 5173,
	  origin: 'http://localhost:5173', // set to your dev origin if different
	  hmr: { protocol: 'ws', host: 'localhost', port: 5173 },
	},
  plugins: [
	tailwindcss(),
	wordpressPlugin(),
	wpHotFile(),
	// Generate the theme.json file in the public/build/assets directory
	// based on the Tailwind config and the theme.json file from base theme folder
	wordpressThemeJson({
	  disableTailwindColors: false,
	  disableTailwindFonts: false,
	  disableTailwindFontSizes: false,
	}),
  ],
  resolve: {
	alias: {
	  '@scripts': '/resources/js',
	  '@styles': '/resources/css',
	  '@fonts': '/resources/fonts',
	  '@images': '/resources/images',
	},
  },
  }
})
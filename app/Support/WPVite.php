<?php
// app/inc/WPVite.php

namespace App\Support;

class WPVite
{
	protected string $buildDirectory = 'public';
	protected string $manifestFilename = 'manifest.json';

	/** Cache per-manifest path */
	protected static array $manifestCache = [];

	public function __construct(array $args = [])
	{
		if (isset($args['buildDirectory'])) {
			$this->buildDirectory = trim($args['buildDirectory'], '/');
		}
		if (isset($args['manifestFilename'])) {
			$this->manifestFilename = $args['manifestFilename'];
		}
	}

	/** Prefer child theme; change to get_template_directory* if you build in parent theme */
	protected function themePath(string $sub = ''): string
	{
		return get_stylesheet_directory() . ($sub ? '/' . ltrim($sub, '/') : '');
	}

	protected function themeUrl(string $sub = ''): string
	{
		return get_stylesheet_directory_uri() . ($sub ? '/' . ltrim($sub, '/') : '');
	}

	protected function hotFilePath(): string
	{
		return $this->themePath($this->buildDirectory . '/hot');
	}

	protected function isHot(): bool
	{
		return is_file($this->hotFilePath());
	}

	protected function devOrigin(): string
	{
		return trim((string) file_get_contents($this->hotFilePath()));
	}

	protected function manifestPath(): string
	{
		return $this->themePath($this->buildDirectory . '/' . $this->manifestFilename);
	}

	protected function manifest(): array
	{
		$path = $this->manifestPath();

		if (!isset(self::$manifestCache[$path])) {
			if (!is_file($path)) {
				throw new \RuntimeException("Vite manifest not found at: {$path}");
			}
			self::$manifestCache[$path] = json_decode((string) file_get_contents($path), true) ?: [];
		}

		return self::$manifestCache[$path];
	}

	protected function chunk(array $manifest, string $key): array
	{
		if (!isset($manifest[$key])) {
			throw new \RuntimeException("Vite entry not found in manifest: {$key}");
		}
		return $manifest[$key];
	}

	/** Public: URL for a single asset key (e.g. 'resources/js/app.js') */
	public function assetUrl(string $entry): string
	{
		if ($this->isHot()) {
			return rtrim($this->devOrigin(), '/') . '/' . ltrim($entry, '/');
		}
		$chunk = $this->chunk($this->manifest(), $entry);
		return $this->themeUrl($this->buildDirectory . '/' . $chunk['file']);
	}

	/** Public: HTML tags for one or more entrypoints */
	public function tags($entries): string
	{
		$entries = is_array($entries) ? $entries : [$entries];
		$html = '';
	
		if ($this->isHot()) {
			$origin = rtrim($this->devOrigin(), '/');
			$html .= '<script type="module" src="' . esc_url($origin . '/@vite/client') . '"></script>';
	
			foreach ($entries as $e) {
				$url = $origin . '/' . ltrim($e, '/');
				// ⬇️ Dev: use a link tag for CSS entries, script tag otherwise
				if ($this->isCss($url)) {
					$html .= $this->stylesheetTag($url);
				} else {
					$html .= $this->scriptTag($url);
				}
			}
			return $html;
		}
	
		$manifest = $this->manifest();
		$preloads = [];
		$tags = [];
	
		foreach ($entries as $e) {
			$chunk = $this->chunk($manifest, $e);
			$entryUrl = $this->themeUrl($this->buildDirectory . '/' . $chunk['file']);
	
			// Preload the entry script or style
			$preloads[] = [$entryUrl, $this->isCss($entryUrl) ? 'style' : 'script'];
	
			// Preload + link any imported chunks and their CSS
			foreach ($chunk['imports'] ?? [] as $importKey) {
				$importChunk = $manifest[$importKey] ?? null;
				if ($importChunk) {
					$importUrl = $this->themeUrl($this->buildDirectory . '/' . $importChunk['file']);
					$preloads[] = [$importUrl, 'script'];
	
					foreach ($importChunk['css'] ?? [] as $cssFile) {
						$cssUrl = $this->themeUrl($this->buildDirectory . '/' . $cssFile);
						$preloads[] = [$cssUrl, 'style'];
						$tags[]     = $this->stylesheetTag($cssUrl);
					}
				}
			}
	
			// ⬇️ Prod: if the entry itself is CSS, output a stylesheet tag; else script tag
			if ($this->isCss($entryUrl)) {
				$tags[] = $this->stylesheetTag($entryUrl);
			} else {
				$tags[] = $this->scriptTag($entryUrl);
			}
	
			// Any CSS emitted by the entry chunk
			foreach ($chunk['css'] ?? [] as $cssFile) {
				$cssUrl = $this->themeUrl($this->buildDirectory . '/' . $cssFile);
				$preloads[] = [$cssUrl, 'style'];
				$tags[]     = $this->stylesheetTag($cssUrl);
			}
		}
	
		// Deduplicate and print minimal preloads
		$tags = array_values(array_unique($tags));
		foreach ($this->uniquePreloads($preloads) as [$url, $as]) {
			$html .= $this->preloadTag($url, $as);
		}
	
		$html .= implode('', $tags);
		return $html;
	}

	protected function uniquePreloads(array $items): array
	{
		$seen = [];
		$out  = [];
		foreach ($items as [$url, $as]) {
			if (isset($seen[$url])) continue;
			$seen[$url] = true;
			$out[] = [$url, $as];
		}
		return $out;
	}

	protected function isCss(string $path): bool
	{
		return (bool) preg_match('/\.(css)(\?|$)/', $path);
	}

	protected function scriptTag(string $url): string
	{
		return '<script type="module" src="' . esc_url($url) . '"></script>';
	}

	protected function stylesheetTag(string $url): string
	{
		return '<link rel="stylesheet" href="' . esc_url($url) . '" />';
	}

	protected function preloadTag(string $url, string $as): string
	{
		if ($as === 'style') {
			return '<link rel="preload" as="style" href="' . esc_url($url) . '" />';
		}
		// JS modules
		return '<link rel="modulepreload" href="' . esc_url($url) . '" />';
	}
	
	// Inlining SVG Support
	public function inlineSvg(string $asset, array $attrs = []): string
	{
		try {
			// Resolve a filesystem path to the SVG (dev → source; prod → built file if present)
			$path = null;
	
			if ($this->isHot()) {
				$path = $this->themePath($asset); // e.g. resources/images/logo.svg
			} else {
				// Try built asset first
				$manifest = $this->manifest();
				if (isset($manifest[$asset])) {
					$built = $this->themePath($this->buildDirectory . '/' . $manifest[$asset]['file']);
					if (is_file($built)) {
						$path = $built;
					}
				}
				// Fallback to source
				if (!$path) {
					$path = $this->themePath($asset);
				}
			}
	
			if (!is_file($path)) {
				// final fallback: <img src="…">
				return '<img alt="" src="' . esc_url($this->assetUrl($asset)) . '">';
			}
	
			$svg = file_get_contents($path);
			if ($svg === false) {
				return '<img alt="" src="' . esc_url($this->assetUrl($asset)) . '">';
			}
	
			$svg = $this->sanitizeSvg($svg);
			if ($attrs) {
				$svg = $this->injectSvgAttributes($svg, $attrs);
			}
	
			return $svg; // already markup
		} catch (\Throwable $e) {
			return '<img alt="" src="' . esc_url($this->assetUrl($asset)) . '">';
		}
	}
	
	protected function sanitizeSvg(string $svg): string
	{
		// Remove XML declaration
		$svg = preg_replace('/<\?xml.*?\?>/i', '', $svg);
		// Strip potentially dangerous content
		$svg = preg_replace('#<script[^>]*>.*?</script>#is', '', $svg);
		$svg = preg_replace('#<foreignObject[^>]*>.*?</foreignObject>#is', '', $svg);
		// Remove inline event handlers
		$svg = preg_replace('/\son\w+="[^"]*"/i', '', $svg);
		$svg = preg_replace("/\son\w+='[^']*'/i", '', $svg);
		// Disallow javascript: URLs
		$svg = preg_replace('/\s(xlink:href|href)\s*=\s*["\']\s*javascript:[^"\']*["\']/i', '', $svg);
	
		return trim($svg);
	}
	
	protected function injectSvgAttributes(string $svg, array $attrs): string
	{
		if (!preg_match('/<svg\b[^>]*>/i', $svg, $m, PREG_OFFSET_CAPTURE)) {
			return $svg;
		}
	
		$start = $m[0][1];
		$len   = strlen($m[0][0]);
		$head  = substr($svg, 0, $start);
		$tag   = $m[0][0];
		$tail  = substr($svg, $start + $len);
	
		// Merge/override a few common attributes if provided
		foreach (['class','width','height','fill','stroke'] as $merge) {
			if (isset($attrs[$merge])) {
				$tag = preg_replace('/\s'.$merge.'="[^"]*"/i', '', $tag);
				$tag = preg_replace("/\s{$merge}='[^']*'/i", '', $tag);
			}
		}
	
		$attrStr = '';
		foreach ($attrs as $k => $v) {
			if ($v === null || $v === false) continue;
			$attrStr .= ' ' . $k . '="' . esc_attr($v) . '"';
		}
	
		// Rebuild the opening <svg …>
		$tag = rtrim(substr($tag, 0, -1)) . $attrStr . '>';
	
		return $head . $tag . $tail;
	}
}
<?php
/**
 * Shared helpers: escaping, settings, CSRF, flash, uploads, logging, pagination.
 */
$acceptsGzip = preg_match('/(?:^|,)\s*gzip\s*(?:;\s*q=([0-9.]+))?/i', (string) ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''), $gzipMatch) === 1
    && (!isset($gzipMatch[1]) || (float) $gzipMatch[1] > 0);
if (!headers_sent()) {
    header('Vary: Accept-Encoding', false);
}
if (!headers_sent() && $acceptsGzip && function_exists('gzencode') && !ini_get('zlib.output_compression')) {
    ob_start(static function (string $buffer): string {
        $compressed = gzencode($buffer, 6);
        if ($compressed === false) {
            return $buffer;
        }
        if (!headers_sent()) {
            header('Content-Encoding: gzip');
        }
        return $compressed;
    });
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

date_default_timezone_set(app_config('app.timezone') ?: 'Africa/Cairo');

/** Current application-local time for comparisons with DATETIME values. */
function app_now(): string
{
    return date('Y-m-d H:i:s');
}

/** Parse a stored datetime using its source zone and normalize it to UTC. */
function datetime_utc(?string $value, string $sourceTimezone = 'Africa/Cairo'): ?DateTimeImmutable
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    try {
        return (new DateTimeImmutable($value, new DateTimeZone($sourceTimezone)))
            ->setTimezone(new DateTimeZone('UTC'));
    } catch (Throwable $ex) {
        return null;
    }
}

function utc_datetime_iso(?string $value, string $sourceTimezone = 'Africa/Cairo'): string
{
    return datetime_utc($value, $sourceTimezone)?->format('Y-m-d\\TH:i:s\\Z') ?? '';
}

function utc_timestamp(?string $value, string $sourceTimezone = 'Africa/Cairo'): int
{
    return datetime_utc($value, $sourceTimezone)?->getTimestamp() ?? 0;
}

/** Render a UTC instant for client-side formatting in the visitor's time zone. */
function localized_datetime_html(?string $value, string $format = 'datetime', string $sourceTimezone = 'Africa/Cairo'): string
{
    $instant = datetime_utc($value, $sourceTimezone);
    if (!$instant) {
        return '—';
    }
    $format = in_array($format, ['date', 'time'], true) ? $format : 'datetime';
    $local = $instant->setTimezone(new DateTimeZone($sourceTimezone));
    $fallback = match ($format) {
        'date' => $local->format('Y-m-d') . ' (' . $sourceTimezone . ')',
        'time' => $local->format('H:i') . ' (' . $sourceTimezone . ')',
        default => $local->format('Y-m-d H:i') . ' (' . $sourceTimezone . ')',
    };
    return '<time datetime="' . e($instant->format('Y-m-d\\TH:i:s\\Z')) . '" data-local-datetime data-local-format="' . e($format) . '">' . e($fallback) . '</time>';
}

if (app_config('app.debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

/** Escape output. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Allow only safe URL schemes for links managed through the CMS. */
function safe_href(string $value, array $allowedSchemes = ['http', 'https', 'mailto', 'tel']): string
{
    $value = trim($value);
    if ($value === '' || preg_match('/[\x00-\x20\x7f]/', $value) || str_contains($value, '\\') || str_starts_with($value, '//')) {
        return '';
    }

    $parts = parse_url($value);
    if ($parts === false) {
        return '';
    }
    if (isset($parts['scheme']) && !in_array(strtolower($parts['scheme']), $allowedSchemes, true)) {
        return '';
    }
    return $value;
}

/** Normalize a phone number for a tel: link; reject everything except digits and common formatting. */
function safe_tel(string $value): string
{
    $value = trim($value);
    if ($value === '' || !preg_match('/^[+0-9().\s-]+$/u', $value)) {
        return '';
    }
    $normalized = preg_replace('/[().\s-]+/u', '', $value) ?? '';
    return preg_match('/^\+?[0-9]{7,15}$/D', $normalized) ? $normalized : '';
}

/** Build a WhatsApp click-to-chat link from the configured company phone. */
function whatsapp_href(string $message = ''): string
{
    $phone = safe_tel((string) setting('phone_intl', '+201505768013'))
        ?: safe_tel((string) setting('phone', '01505768013'));
    $number = preg_replace('/\D+/', '', $phone) ?? '';
    if (str_starts_with($number, '0')) {
        $number = '2' . $number;
    }
    if (!preg_match('/^[1-9][0-9]{7,14}$/D', $number)) {
        return '';
    }
    return 'https://wa.me/' . $number . ($message !== '' ? '?text=' . rawurlencode($message) : '');
}

/** Permit only absolute HTTP(S) URLs with a host. */
function safe_http_url(string $value): string
{
    $value = safe_href($value, ['http', 'https']);
    if ($value === '') {
        return '';
    }
    $parts = parse_url($value);
    return is_array($parts) && !empty($parts['host']) && empty($parts['user']) && empty($parts['pass']) ? $value : '';
}

/** Permit Google Maps URLs intended for iframe embeds. */
function safe_map_embed(string $value): string
{
    $value = safe_http_url($value);
    if ($value === '') {
        return '';
    }
    $parts = parse_url($value);
    $host = strtolower($parts['host'] ?? '');
    $path = strtolower($parts['path'] ?? '');
    $allowedHosts = ['google.com', 'www.google.com', 'maps.google.com'];
    return in_array($host, $allowedHosts, true) && str_starts_with($path, '/maps') ? $value : '';
}

/** Sanitize CMS rich text to a small, safe set of formatting tags. */
function sanitize_rich_html(string $html): string
{
    if (!class_exists('DOMDocument')) {
        return e($html);
    }

    $previousErrors = libxml_use_internal_errors(true);
    $document = new DOMDocument('1.0', 'UTF-8');
    $source = '<!doctype html><html><head><meta charset="UTF-8"></head><body><div id="bluedesk-rich-root">' . $html . '</div></body></html>';
    $loaded = $document->loadHTML($source, LIBXML_NONET | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previousErrors);
    if (!$loaded) {
        return e($html);
    }

    $xpath = new DOMXPath($document);
    $root = $xpath->query('//*[@id="bluedesk-rich-root"]')->item(0);
    if (!$root) {
        return e($html);
    }

    $allowedTags = ['a', 'b', 'blockquote', 'br', 'caption', 'code', 'del', 'div', 'em', 'figcaption', 'figure', 'font', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'img', 'li', 'mark', 'ol', 'p', 'pre', 's', 'small', 'span', 'strike', 'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'u', 'ul'];
    $dropTags = ['applet', 'embed', 'form', 'iframe', 'math', 'object', 'script', 'style', 'svg', 'template'];
    $safeColor = static function (string $value): ?string {
        $value = strtolower(trim($value));
        $named = ['black', 'blue', 'brown', 'gray', 'green', 'grey', 'maroon', 'navy', 'olive', 'orange', 'purple', 'red', 'silver', 'teal', 'white', 'yellow', 'transparent'];
        if (in_array($value, $named, true) || preg_match('/^#[0-9a-f]{3,8}$/D', $value)) {
            return $value;
        }
        return preg_match('/^rgba?\(\s*[0-9.%,\s]+\)$/D', $value) ? $value : null;
    };
    $safeStyle = static function (string $style) use ($safeColor): string {
        $clean = [];
        foreach (explode(';', $style) as $declaration) {
            $parts = explode(':', $declaration, 2);
            if (count($parts) !== 2) continue;
            $property = strtolower(trim($parts[0]));
            $value = trim($parts[1]);
            if ($value === '' || preg_match('/[<>]|url\s*\(|expression\s*\(|javascript\s*:/i', $value)) continue;
            $safe = null;
            if (in_array($property, ['color', 'background-color'], true)) {
                $safe = $safeColor($value);
            } elseif ($property === 'text-align' && preg_match('/^(left|right|center|justify|start|end)$/iD', $value)) {
                $safe = strtolower($value);
            } elseif ($property === 'direction' && preg_match('/^(ltr|rtl)$/iD', $value)) {
                $safe = strtolower($value);
            } elseif ($property === 'font-weight' && preg_match('/^(normal|bold|[1-9]00)$/iD', $value)) {
                $safe = strtolower($value);
            } elseif ($property === 'font-style' && preg_match('/^(normal|italic|oblique)$/iD', $value)) {
                $safe = strtolower($value);
            } elseif ($property === 'text-decoration' && preg_match('/^(none|underline|line-through|overline)(\s+(underline|line-through|overline))*$/iD', $value)) {
                $safe = strtolower($value);
            } elseif ($property === 'vertical-align' && preg_match('/^(baseline|sub|super|middle|top|bottom)$/iD', $value)) {
                $safe = strtolower($value);
            } elseif ($property === 'font-family') {
                $families = ['arial' => 'Arial', 'aptos' => 'Aptos', 'calibri' => 'Calibri', 'cambria' => 'Cambria', 'cairo' => 'Cairo', 'candara' => 'Candara', 'consolas' => 'Consolas', 'courier new' => 'Courier New', 'garamond' => 'Garamond', 'georgia' => 'Georgia', 'helvetica' => 'Helvetica', 'lato' => 'Lato', 'montserrat' => 'Montserrat', 'open sans' => 'Open Sans', 'poppins' => 'Poppins', 'roboto' => 'Roboto', 'sans-serif' => 'sans-serif', 'serif' => 'serif', 'monospace' => 'monospace', 'system-ui' => 'system-ui', 'segoe ui' => 'Segoe UI', 'tahoma' => 'Tahoma', 'times new roman' => 'Times New Roman', 'trebuchet ms' => 'Trebuchet MS', 'verdana' => 'Verdana'];
                $familyList = [];
                foreach (explode(',', $value) as $family) {
                    $family = trim(trim($family), "\"'");
                    $canonicalFamily = $families[strtolower($family)] ?? null;
                    if ($canonicalFamily === null) {
                        $familyList = [];
                        break;
                    }
                    $familyList[] = $canonicalFamily;
                }
                if ($familyList !== []) $safe = implode(', ', $familyList);
            } elseif ($property === 'font-size' && preg_match('/^([0-9]+(?:\.[0-9]+)?)(px|pt|em|rem|%)$/iD', $value, $size)) {
                $number = (float) $size[1];
                $unit = strtolower($size[2]);
                $maximum = $unit === 'pt' ? 36 : ($unit === '%' ? 300 : 48);
                if ($number >= 8 && $number <= $maximum) $safe = $size[1] . $unit;
            } elseif (in_array($property, ['line-height'], true) && preg_match('/^(normal|[1-3](?:\.[0-9]+)?|[8-9][0-9](?:\.[0-9]+)?%)$/iD', $value)) {
                $safe = strtolower($value);
            } elseif (preg_match('/^(margin|margin-(top|bottom|left|right)|padding|padding-(top|bottom|left|right))$/D', $property)
                && preg_match('/^(0|[0-9]{1,2}(?:\.[0-9]+)?(?:px|pt|em|rem)|auto)(\s+(0|[0-9]{1,2}(?:\.[0-9]+)?(?:px|pt|em|rem)|auto)){0,3}$/iD', $value)) {
                $safe = strtolower($value);
            } elseif (in_array($property, ['width', 'max-width'], true) && preg_match('/^(100|[1-9]?[0-9])%$|^(auto|[0-9]{1,3}(?:px|pt))$/iD', $value)) {
                $safe = strtolower($value);
            } elseif ($property === 'white-space' && preg_match('/^(normal|pre|pre-wrap|nowrap)$/iD', $value)) {
                $safe = strtolower($value);
            } elseif ($property === 'list-style-type' && preg_match('/^(disc|circle|square|decimal|lower-alpha|upper-alpha|lower-roman|upper-roman|none)$/iD', $value)) {
                $safe = strtolower($value);
            } elseif ($property === 'border-collapse' && preg_match('/^(collapse|separate)$/iD', $value)) {
                $safe = strtolower($value);
            } elseif ($property === 'border' && preg_match('/^(0|[1-3]px\s+(solid|dashed|dotted)\s+#[0-9a-f]{3,8})$/iD', $value)) {
                $safe = strtolower($value);
            }
            if ($safe !== null) $clean[] = $property . ':' . $safe;
        }
        return implode(';', $clean);
    };
    $clean = function (DOMNode $parent) use (&$clean, $allowedTags, $dropTags, $safeStyle): void {
        $children = [];
        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }
        foreach ($children as $child) {
            if ($child instanceof DOMComment) {
                $parent->removeChild($child);
                continue;
            }
            if (!($child instanceof DOMElement)) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, $dropTags, true)) {
                $parent->removeChild($child);
                continue;
            }
            if (!in_array($tag, $allowedTags, true)) {
                $clean($child);
                while ($child->firstChild) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                $parent->removeChild($child);
                continue;
            }

            $allowedAttributes = ['title', 'style', 'dir', 'align'];
            if ($tag === 'a') $allowedAttributes = array_merge($allowedAttributes, ['href']);
            if ($tag === 'img') $allowedAttributes = array_merge($allowedAttributes, ['src', 'alt', 'width', 'height']);
            if (in_array($tag, ['td', 'th'], true)) $allowedAttributes = array_merge($allowedAttributes, ['colspan', 'rowspan', 'scope']);
            if ($tag === 'ol') $allowedAttributes = array_merge($allowedAttributes, ['start', 'type']);
            if ($tag === 'li') $allowedAttributes[] = 'value';
            if ($tag === 'font') $allowedAttributes = array_merge($allowedAttributes, ['color', 'face', 'size']);
            $attributes = [];
            foreach ($child->attributes as $attribute) {
                $attributes[] = $attribute->name;
            }
            foreach ($attributes as $attribute) {
                $value = $child->getAttribute($attribute);
                $attribute = strtolower($attribute);
                if (!in_array($attribute, $allowedAttributes, true)) {
                    $child->removeAttribute($attribute);
                    continue;
                }
                if ($attribute === 'style') {
                    $cleanedStyle = $safeStyle($value);
                    if ($cleanedStyle === '') $child->removeAttribute($attribute);
                    else $child->setAttribute($attribute, $cleanedStyle);
                } elseif ($attribute === 'align') {
                    if (preg_match('/^(left|right|center|justify)$/iD', $value)) {
                        $style = $safeStyle($child->getAttribute('style') . ';text-align:' . strtolower($value));
                        $child->setAttribute('style', $style);
                    }
                    $child->removeAttribute($attribute);
                } elseif ($attribute === 'dir') {
                    if (!preg_match('/^(ltr|rtl|auto)$/iD', $value)) $child->removeAttribute($attribute);
                } elseif ($attribute === 'href' || $attribute === 'src') {
                    $safe = safe_href($value, $attribute === 'href' ? ['http', 'https', 'mailto', 'tel'] : ['http', 'https']);
                    if ($safe === '') {
                        $child->removeAttribute($attribute);
                    } else {
                        if ($tag === 'img' && $attribute === 'src') {
                            $safe = normalize_legacy_upload_path($safe);
                        }
                        $child->setAttribute($attribute, $safe);
                    }
                } elseif (($attribute === 'width' || $attribute === 'height') && !preg_match('/^[1-9][0-9]{0,3}$/', $value)) {
                    $child->removeAttribute($attribute);
                } elseif (in_array($attribute, ['colspan', 'rowspan', 'start', 'value'], true) && !preg_match('/^[1-9][0-9]{0,2}$/D', $value)) {
                    $child->removeAttribute($attribute);
                } elseif ($attribute === 'type' && $tag === 'ol' && !preg_match('/^(1|a|A|i|I)$/D', $value)) {
                    $child->removeAttribute($attribute);
                } elseif ($attribute === 'scope' && !in_array(strtolower($value), ['row', 'col', 'rowgroup', 'colgroup'], true)) {
                    $child->removeAttribute($attribute);
                } elseif ($tag === 'font' && $attribute === 'color') {
                    $color = $safeColor($value);
                    if ($color === null) $child->removeAttribute($attribute);
                    else $child->setAttribute($attribute, $color);
                } elseif ($tag === 'font' && $attribute === 'size' && !preg_match('/^[1-7]$/D', $value)) {
                    $child->removeAttribute($attribute);
                } elseif ($tag === 'font' && $attribute === 'face') {
                    $fontStyle = $safeStyle('font-family:' . $value);
                    if ($fontStyle === '') $child->removeAttribute($attribute);
                    else $child->setAttribute('style', $safeStyle($child->getAttribute('style') . ';' . $fontStyle));
                }
            }
            if ($tag === 'img' && !$child->hasAttribute('src')) {
                $parent->removeChild($child);
                continue;
            }
            $clean($child);
        }
    };
    $clean($root);

    $result = '';
    foreach ($root->childNodes as $child) {
        $result .= $document->saveHTML($child);
    }
    return $result;
}

/** Absolute site URL from a relative path. */
function url(string $path = ''): string
{
    return rtrim(app_config('app.base_url'), '/') . '/' . ltrim($path, '/');
}

/** Resolve CMS-managed relative links from the site root, including clean blog routes. */
function site_href(string $value): string
{
    $href = safe_href($value);
    if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, '/')) {
        return $href;
    }
    if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $href)) {
        return $href;
    }
    $path = parse_url($href, PHP_URL_PATH);
    if (is_string($path) && preg_match('#(?:^|/)\.\.?(/|$)#', $path)) {
        return '';
    }
    return url($href);
}

/** Canonical public blog URL for the selected server-rendered language. */
function blog_url(string $path = 'blog', string $language = 'ar'): string
{
    $target = url($path);
    return $language === 'en' ? $target . (str_contains($target, '?') ? '&' : '?') . 'lang=en' : $target;
}

/** Add or remove the crawlable language query parameter on a public URL. */
function url_with_language(string $href, string $language): string
{
    $href = safe_http_url($href);
    $parts = $href !== '' ? parse_url($href) : false;
    if (!is_array($parts) || empty($parts['host']) || !in_array($language, ['ar', 'en'], true)) {
        return '';
    }

    $query = [];
    parse_str((string) ($parts['query'] ?? ''), $query);
    unset($query['lang']);
    if ($language === 'en') {
        $query['lang'] = 'en';
    }
    $origin = strtolower((string) ($parts['scheme'] ?? 'https')) . '://' . $parts['host'];
    if (isset($parts['port'])) {
        $origin .= ':' . (int) $parts['port'];
    }
    $result = $origin . ($parts['path'] ?? '/');
    if ($query) {
        $result .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
    if (!empty($parts['fragment'])) {
        $result .= '#' . $parts['fragment'];
    }
    return $result;
}

/** Move legacy local /bluedesk/uploads URLs to the configured production upload path. */
function normalize_legacy_upload_path(string $path): string
{
    $parts = parse_url($path);
    if (!is_array($parts)) {
        return $path;
    }

    $legacyPrefix = '/bluedesk/uploads/';
    $pathPart = (string) ($parts['path'] ?? '');
    if (!str_starts_with($pathPart, $legacyPrefix)) {
        return $path;
    }

    $host = strtolower((string) ($parts['host'] ?? ''));
    if ($host !== '') {
        $siteHost = strtolower((string) (parse_url((string) app_config('app.base_url'), PHP_URL_HOST) ?: ''));
        if (!in_array($host, ['localhost', '127.0.0.1', '::1', $siteHost], true)) {
            return $path;
        }
    }

    $uploadPrefix = trim((string) app_config('app.upload_url'), '/');
    if ($uploadPrefix === '') {
        return $path;
    }

    $suffix = substr($pathPart, strlen($legacyPrefix));
    if ($suffix === '') {
        return $path;
    }
    $newPath = '/' . $uploadPrefix . '/' . ltrim($suffix, '/');
    $normalized = $host !== '' ? url($newPath) : $newPath;
    if (isset($parts['query'])) {
        $normalized .= '?' . $parts['query'];
    }
    if (isset($parts['fragment'])) {
        $normalized .= '#' . $parts['fragment'];
    }
    return $normalized;
}

/** Resolve an image/asset setting: absolute URLs and root paths pass through. */
function asset(string $path): string
{
    $path = trim($path);
    if ($path === '' || preg_match('/[\x00-\x20\x7f]/', $path) || str_contains($path, '\\')) {
        return '';
    }
    $path = normalize_legacy_upload_path($path);
    if (preg_match('#^https?://#i', $path)) {
        return safe_http_url($path);
    }
    if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $path)) {
        return '';
    }

    // Prefer the generated WebP copy for uploaded JPEG/PNG/GIF images while
    // keeping the source file available in the media library.
    $uploadPrefix = trim((string) app_config('app.upload_url'), '/');
    $relativePath = ltrim($path, '/');
    $uploadDir = rtrim((string) app_config('app.upload_dir'), '/\\');
    if ($uploadPrefix !== '' && str_starts_with($relativePath, $uploadPrefix . '/')) {
        $filename = basename($relativePath);
        if (preg_match('/^[a-z0-9][a-z0-9._-]*\.(?:jpe?g|png|gif)$/i', $filename)) {
            $webpName = preg_replace('/\.(?:jpe?g|png|gif)$/i', '.webp', $filename);
            if ($webpName && $uploadDir !== '' && is_file($uploadDir . DIRECTORY_SEPARATOR . $webpName)) {
                $path = preg_replace('/\.(?:jpe?g|png|gif)$/i', '.webp', $path) ?? $path;
            }
        }
        $assetFile = $uploadDir !== '' ? $uploadDir . DIRECTORY_SEPARATOR . basename($path) : '';
        if ($assetFile !== '' && is_file($assetFile)) {
            $version = (string) (filemtime($assetFile) ?: 1);
            $path .= str_contains($path, '?') ? '&v=' . rawurlencode($version) : '?v=' . rawurlencode($version);
        }
    }
    if (str_starts_with($path, '/') && !str_starts_with($path, '//')) {
        return $path;
    }
    return url($path);
}

/** Responsive WebP sources for an uploaded image, or an empty string if unavailable. */
function asset_srcset(string $path): string
{
    $path = normalize_legacy_upload_path(trim($path));
    $uploadPrefix = trim((string) app_config('app.upload_url'), '/');
    $relativePath = ltrim($path, '/');
    if ($uploadPrefix === '' || !str_starts_with($relativePath, $uploadPrefix . '/')) {
        return '';
    }

    $filename = basename($relativePath);
    if (!preg_match('/^([a-z0-9][a-z0-9._-]*)\.(?:jpe?g|png|gif|webp)$/i', $filename, $matches)) {
        return '';
    }
    $stem = preg_replace('/\.(?:jpe?g|png|gif|webp)$/i', '', $filename);
    $uploadDir = rtrim((string) app_config('app.upload_dir'), '/\\');
    if (!$stem || $uploadDir === '') {
        return '';
    }

    $candidates = [];
    foreach ([128, 160, 256, 320, 480, 768, 1280, 1920] as $width) {
        $variant = $stem . '-' . $width . 'w.webp';
        $variantPath = $uploadDir . DIRECTORY_SEPARATOR . $variant;
        if (is_file($variantPath)) {
            $version = (string) (filemtime($variantPath) ?: 1);
            $candidates[$width] = upload_url($variant) . '?v=' . rawurlencode($version) . ' ' . $width . 'w';
        }
    }

    $fullName = $stem . '.webp';
    $fullPath = $uploadDir . DIRECTORY_SEPARATOR . $fullName;
    if (is_file($fullPath)) {
        $dimensions = @getimagesize($fullPath);
        $fullWidth = (int) ($dimensions[0] ?? 0);
        if ($fullWidth > 0) {
            $version = (string) (filemtime($fullPath) ?: 1);
            $candidates[$fullWidth] = upload_url($fullName) . '?v=' . rawurlencode($version) . ' ' . $fullWidth . 'w';
        }
    }

    if (!$candidates) {
        return '';
    }
    ksort($candidates, SORT_NUMERIC);
    return implode(', ', array_values($candidates));
}

/** HTML attributes for responsive uploaded images. */
function asset_srcset_attributes(string $path, string $sizes): string
{
    $srcset = asset_srcset($path);
    return $srcset === '' ? '' : ' srcset="' . e($srcset) . '" sizes="' . e($sizes) . '"';
}

/** Intrinsic dimensions for a locally uploaded image, to reserve its layout space. */
function asset_dimensions_attributes(string $path): string
{
    $path = normalize_legacy_upload_path(trim($path));
    $parts = parse_url($path);
    if (!is_array($parts)) {
        return '';
    }
    $host = strtolower((string) ($parts['host'] ?? ''));
    $siteHost = strtolower((string) (parse_url((string) app_config('app.base_url'), PHP_URL_HOST) ?: ''));
    if ($host !== '' && $host !== $siteHost) {
        return '';
    }

    $uploadPrefix = trim((string) app_config('app.upload_url'), '/');
    $relativePath = ltrim((string) ($parts['path'] ?? ''), '/');
    $uploadDir = rtrim((string) app_config('app.upload_dir'), '/\\');
    if ($uploadPrefix === '' || $uploadDir === '' || !str_starts_with($relativePath, $uploadPrefix . '/')) {
        return '';
    }

    $filename = basename($relativePath);
    if (!preg_match('/^[a-z0-9][a-z0-9._-]*\.(?:jpe?g|png|gif|webp)$/iD', $filename)) {
        return '';
    }
    $filePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
    if (!is_file($filePath) && preg_match('/\.(?:jpe?g|png|gif)$/iD', $filename)) {
        $webpFilename = preg_replace('/\.(?:jpe?g|png|gif)$/iD', '.webp', $filename);
        if (is_string($webpFilename) && is_file($uploadDir . DIRECTORY_SEPARATOR . $webpFilename)) {
            $filePath = $uploadDir . DIRECTORY_SEPARATOR . $webpFilename;
        }
    }
    if (!is_file($filePath)) {
        return '';
    }

    $dimensions = @getimagesize($filePath);
    $width = (int) ($dimensions[0] ?? 0);
    $height = (int) ($dimensions[1] ?? 0);
    return $width > 0 && $height > 0
        ? ' width="' . $width . '" height="' . $height . '" style="aspect-ratio:' . $width . ' / ' . $height . '"'
        : '';
}

/** Return true when a GIF contains more than one image frame. */
function upload_gif_is_animated(string $path): bool
{
    $data = @file_get_contents($path);
    if (!is_string($data) || strlen($data) < 13 || !in_array(substr($data, 0, 6), ['GIF87a', 'GIF89a'], true)) {
        return false;
    }

    $length = strlen($data);
    $packed = ord($data[10]);
    $offset = 13 + (($packed & 0x80) ? 3 * (1 << (($packed & 0x07) + 1)) : 0);
    $frames = 0;
    while ($offset < $length) {
        $marker = ord($data[$offset++]);
        if ($marker === 0x3B) { // GIF trailer
            break;
        }
        if ($marker === 0x21) { // Extension blocks
            if ($offset >= $length) break;
            $offset++; // extension label
            while ($offset < $length) {
                $blockSize = ord($data[$offset++]);
                if ($blockSize === 0) break;
                $offset += $blockSize;
            }
            continue;
        }
        if ($marker !== 0x2C || $offset + 9 > $length) { // Image descriptor
            break;
        }

        if (++$frames > 1) {
            return true;
        }
        $descriptorPacked = ord($data[$offset + 8]);
        $offset += 9;
        if ($descriptorPacked & 0x80) {
            $offset += 3 * (1 << (($descriptorPacked & 0x07) + 1));
        }
        if ($offset >= $length) break;
        $offset++; // LZW minimum code size
        while ($offset < $length) {
            $blockSize = ord($data[$offset++]);
            if ($blockSize === 0) break;
            $offset += $blockSize;
        }
    }
    return false;
}

/** Create WebP and responsive variants without touching the uploaded original. */
function generate_upload_webp_variants(string $filename, string $sourcePath, string $mime): bool
{
    if (!function_exists('imagewebp') || !in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
        return false;
    }
    if ($mime === 'image/gif' && upload_gif_is_animated($sourcePath)) {
        return false; // Keep animation intact; GD only encodes the first frame.
    }
    $info = @getimagesize($sourcePath);
    $width = (int) ($info[0] ?? 0);
    $height = (int) ($info[1] ?? 0);
    if ($width < 1 || $height < 1 || $width * $height > 40000000) {
        return false;
    }
    $contents = @file_get_contents($sourcePath);
    try {
        $decoded = $contents === false ? false : @imagecreatefromstring($contents);
    } catch (Throwable $exception) {
        $decoded = false;
    }
    unset($contents);
    if (!$decoded) {
        return false;
    }

    // Normalize palette images to true color; GD's WebP encoder cannot encode palette surfaces.
    $image = $decoded;
    if (!imageistruecolor($decoded)) {
        $image = imagecreatetruecolor($width, $height);
        if ($mime !== 'image/jpeg') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagefill($image, 0, 0, $transparent);
        }
        imagecopy($image, $decoded, 0, 0, 0, 0, $width, $height);
        imagedestroy($decoded);
    }

    $saveWebp = static function ($resource, string $destination, int $quality): bool {
        try {
            return @imagewebp($resource, $destination, $quality);
        } catch (Throwable $exception) {
            return false;
        }
    };

    $dir = dirname($sourcePath);
    $stem = pathinfo($filename, PATHINFO_FILENAME);
    $quality = $mime === 'image/jpeg' ? 82 : 90;
    $isWebpSource = strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'webp';
    if (!$isWebpSource) {
        $fullPath = $dir . DIRECTORY_SEPARATOR . $stem . '.webp';
        if (!$saveWebp($image, $fullPath, $quality) || !is_file($fullPath)) {
            @unlink($fullPath);
            imagedestroy($image);
            return false;
        }
    }

    foreach ([128, 160, 256, 320, 480, 768, 1280, 1920] as $targetWidth) {
        if ($width <= $targetWidth) {
            continue;
        }
        $targetHeight = max(1, (int) round($height * ($targetWidth / $width)));
        $variant = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($mime !== 'image/jpeg') {
            imagealphablending($variant, false);
            imagesavealpha($variant, true);
            $transparent = imagecolorallocatealpha($variant, 0, 0, 0, 127);
            imagefill($variant, 0, 0, $transparent);
        }
        if (imagecopyresampled($variant, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height)) {
            $variantPath = $dir . DIRECTORY_SEPARATOR . $stem . '-' . $targetWidth . 'w.webp';
            if (!$saveWebp($variant, $variantPath, $quality)) {
                @unlink($variantPath);
            }
        }
        imagedestroy($variant);
    }
    imagedestroy($image);
    return true;
}

/** Accept only six-digit hex colors before placing settings in CSS. */
function safe_css_color(string $value, string $fallback): string
{
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $fallback;
}

/** Upload base URL (web path). */
function upload_url(string $filename = ''): string
{
    $base = rtrim(app_config('app.upload_url'), '/');
    return $filename === '' ? $base : $base . '/' . ltrim($filename, '/');
}

/** All settings as key => value (cached). */
function settings(): array
{
    static $settings = null;
    if ($settings === null) {
        $settings = [];
        try {
            foreach (db_fetch_all('SELECT setting_key, setting_value FROM settings') as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Throwable $ex) {
            error_log('Settings load failed: ' . $ex->getMessage());
        }
    }
    return $settings;
}

function setting(string $key, string $default = ''): string
{
    $all = settings();
    return array_key_exists($key, $all) && $all[$key] !== null ? $all[$key] : $default;
}

/** Save a single setting. */
function save_setting(string $key, string $value): void
{
    db_query(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
        [$key, $value]
    );
}

/** CSRF token for the current session. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['_token'] ?? '';
    return is_string($token) && $token !== '' && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf(): void
{
    if (!verify_csrf()) {
        http_response_code(403);
        exit('انتهت صلاحية الجلسة. من فضلك أعد المحاولة. (CSRF)');
    }
}

/** Flash message helpers. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function client_ip(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

/** Remove all generated files and the private source copy for one random upload stem. */
function cleanup_upload_files(string $dir, string $stem): void
{
    if (!preg_match('/^\d{8}-\d{6}-[a-f0-9]{12}$/D', $stem)) {
        return;
    }
    $dir = rtrim($dir, '/\\');
    $paths = array_merge(
        glob($dir . DIRECTORY_SEPARATOR . $stem . '.*') ?: [],
        glob($dir . DIRECTORY_SEPARATOR . $stem . '-*-w.webp') ?: [],
        glob($dir . DIRECTORY_SEPARATOR . '.originals' . DIRECTORY_SEPARATOR . $stem . '.*') ?: []
    );
    foreach (array_unique($paths) as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

/** Log an admin activity. */
function log_activity(string $action, string $details = ''): void
{
    try {
        db_query(
            'INSERT INTO activity_log (user_id, action, details, ip) VALUES (?, ?, ?, ?)',
            [$_SESSION['uid'] ?? null, mb_substr($action, 0, 120), mb_substr($details, 0, 2000), client_ip()]
        );
    } catch (Throwable $ex) {
        error_log('Activity log failed: ' . $ex->getMessage());
    }
}

/**
 * Validate and store an uploaded image. Returns the public URL or null.
 * @param array  $file  $_FILES[...] entry
 * @param string $error Filled with an error message when upload fails
 */
function upload_image(array $file, ?string &$error = null): ?string
{
    $error = null;
    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($uploadError !== UPLOAD_ERR_OK) {
        $error = 'فشل رفع الملف (كود ' . $uploadError . ').';
        return null;
    }

    $tmpNameRaw = $file['tmp_name'] ?? '';
    if (!is_string($tmpNameRaw)) {
        $error = 'تعذر التحقق من الملف المرفوع.';
        return null;
    }
    $tmpName = $tmpNameRaw;
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        $error = 'تعذر التحقق من الملف المرفوع.';
        return null;
    }
    $max = (int) app_config('app.max_upload');
    $actualSize = filesize($tmpName);
    if ($actualSize === false || $actualSize < 1 || $actualSize > $max) {
        $error = 'حجم الملف أكبر من الحد المسموح (' . round($max / 1048576, 1) . ' ميجابايت).';
        return null;
    }
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($tmpName) ?: '';
    if (!isset($allowed[$mime])) {
        $error = 'نوع الملف غير مسموح. المسموح: JPG, PNG, WEBP, GIF.';
        return null;
    }
    $imageInfo = @getimagesize($tmpName);
    if (!$imageInfo || ($imageInfo['mime'] ?? '') !== $mime) {
        $error = 'الملف لا يحتوي على صورة صالحة.';
        return null;
    }
    $width = (int) ($imageInfo[0] ?? 0);
    $height = (int) ($imageInfo[1] ?? 0);
    if ($width < 1 || $height < 1 || $width > 12000 || $height > 12000 || $width * $height > 40000000) {
        $error = 'أبعاد الصورة أكبر من الحد المسموح.';
        return null;
    }

    $animatedGif = $mime === 'image/gif' && upload_gif_is_animated($tmpName);
    if ($mime !== 'image/webp' && !$animatedGif && !function_exists('imagewebp')) {
        $error = 'تحويل الصور إلى WebP غير متاح على الخادم. فعّل دعم WebP في إضافة GD ثم أعد المحاولة.';
        return null;
    }

    $ext = $allowed[$mime];
    $dir = app_config('app.upload_dir');
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        $error = 'تعذر إنشاء مجلد الرفع.';
        return null;
    }
    $filename = date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
        $error = 'تعذر حفظ الملف على الخادم.';
        return null;
    }
    $imagePath = $dir . DIRECTORY_SEPARATOR . $filename;
    $converted = $mime === 'image/webp'
        ? generate_upload_webp_variants($filename, $imagePath, $mime)
        : (!$animatedGif && generate_upload_webp_variants($filename, $imagePath, $mime));
    if (!$converted && !$animatedGif && $mime !== 'image/webp') {
        $stem = pathinfo($filename, PATHINFO_FILENAME);
        cleanup_upload_files($dir, $stem);
        $error = 'تعذر تحويل الصورة إلى WebP. جرّب صورة أصغر أو تحقق من دعم WebP على الخادم.';
        return null;
    }

    // Store the optimized file as the media record and content URL; retain the uploaded original on disk.
    $storedFilename = $converted && $mime !== 'image/webp'
        ? pathinfo($filename, PATHINFO_FILENAME) . '.webp'
        : $filename;
    $storedPath = $dir . DIRECTORY_SEPARATOR . $storedFilename;
    $storedMime = $storedFilename !== $filename ? 'image/webp' : $mime;
    $storedSize = is_file($storedPath) ? (int) filesize($storedPath) : $actualSize;
    if ($converted && $mime !== 'image/webp') {
        $privateOriginalDir = $dir . DIRECTORY_SEPARATOR . '.originals';
        if (!is_dir($privateOriginalDir) && !@mkdir($privateOriginalDir, 0700, true) && !is_dir($privateOriginalDir)) {
            cleanup_upload_files($dir, pathinfo($filename, PATHINFO_FILENAME));
            $error = 'تعذر حفظ النسخة الأصلية في التخزين الخاص.';
            return null;
        }
        @chmod($privateOriginalDir, 0700);
        if (!@rename($imagePath, $privateOriginalDir . DIRECTORY_SEPARATOR . $filename)) {
            cleanup_upload_files($dir, pathinfo($filename, PATHINFO_FILENAME));
            $error = 'تعذر نقل النسخة الأصلية إلى التخزين الخاص.';
            return null;
        }
    }
    try {
        db_query(
            'INSERT INTO media (filename, original_name, mime, size, uploaded_by) VALUES (?, ?, ?, ?, ?)',
            [$storedFilename, mb_substr(is_string($file['name'] ?? null) ? $file['name'] : 'image', 0, 255), $storedMime, $storedSize, $_SESSION['uid'] ?? null]
        );
    } catch (Throwable $ex) {
        error_log('Media insert failed: ' . $ex->getMessage());
        cleanup_upload_files($dir, pathinfo($filename, PATHINFO_FILENAME));
        $error = 'تعذر تسجيل الصورة في مكتبة الوسائط.';
        return null;
    }
    return upload_url($storedFilename);
}

/** Maximum upload size for testimonial videos, capped by PHP's request limits. */
function testimonial_video_max_size(): int
{
    $configured = (int) (app_config('app.max_video_upload') ?: 35 * 1024 * 1024);
    $max = max(1, $configured);
    foreach (['upload_max_filesize', 'post_max_size'] as $settingName) {
        $raw = trim((string) ini_get($settingName));
        if (!preg_match('/^(\d+(?:\.\d+)?)\s*([KMG])?$/i', $raw, $matches)) {
            continue;
        }
        $unit = strtoupper($matches[2] ?? '');
        $bytes = (int) ((float) $matches[1] * match ($unit) {
            'G' => 1024 ** 3,
            'M' => 1024 ** 2,
            'K' => 1024,
            default => 1,
        });
        if ($bytes > 0) {
            $max = min($max, $bytes);
        }
    }
    return $max;
}

/** Validate and store a browser-playable MP4 or WebM testimonial video. */
function upload_testimonial_video(array $file, ?string &$error = null): ?string
{
    $error = null;
    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($uploadError !== UPLOAD_ERR_OK) {
        $error = in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'حجم الفيديو يتجاوز الحد المسموح على الخادم (' . round(testimonial_video_max_size() / 1048576) . ' ميجابايت).'
            : 'فشل رفع الفيديو (كود ' . $uploadError . ').';
        return null;
    }
    $tmpName = $file['tmp_name'] ?? '';
    if (!is_string($tmpName) || $tmpName === '' || !is_uploaded_file($tmpName)) {
        $error = 'تعذر التحقق من ملف الفيديو المرفوع.';
        return null;
    }
    $actualSize = filesize($tmpName);
    $max = testimonial_video_max_size();
    if ($actualSize === false || $actualSize < 1 || $actualSize > $max) {
        $error = 'حجم الفيديو يتجاوز الحد المسموح (' . round($max / 1048576) . ' ميجابايت).';
        return null;
    }
    $allowed = ['video/mp4' => 'mp4', 'video/webm' => 'webm'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName) ?: '';
    if (!isset($allowed[$mime])) {
        $error = 'صيغة الفيديو غير مدعومة. ارفع MP4 أو WebM.';
        return null;
    }
    $dir = app_config('app.upload_dir');
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        $error = 'تعذر إنشاء مجلد رفع الفيديو.';
        return null;
    }
    $filename = date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
    $destination = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($tmpName, $destination)) {
        $error = 'تعذر حفظ الفيديو على الخادم.';
        return null;
    }
    return upload_url($filename);
}

/** Return only a locally uploaded video URL; never embed arbitrary video URLs. */
function testimonial_video_asset(?string $path): string
{
    $path = normalize_legacy_upload_path(trim((string) $path));
    $base = rtrim((string) app_config('app.upload_url'), '/') . '/';
    if (!str_starts_with($path, $base)) {
        return '';
    }
    $filename = substr($path, strlen($base));
    if (!preg_match('/^\d{8}-\d{6}-[a-f0-9]{12}\.(mp4|webm)$/D', $filename)) {
        return '';
    }
    return asset($path);
}

/** Delete a previously uploaded file (only inside uploads dir). */
function delete_upload(?string $webPath): void
{
    if (!$webPath) {
        return;
    }
    $webPath = normalize_legacy_upload_path($webPath);
    $base = rtrim(app_config('app.upload_url'), '/') . '/';
    if (strpos($webPath, $base) !== 0) {
        return; // external URL or not an upload
    }
    $filename = basename((string) (parse_url($webPath, PHP_URL_PATH) ?: $webPath));
    if (!preg_match('/^\d{8}-\d{6}-[a-f0-9]{12}\.(?:jpe?g|png|gif|webp|mp4|webm)$/i', $filename)) {
        return;
    }
    $dir = rtrim((string) app_config('app.upload_dir'), '/\\');
    $stem = pathinfo($filename, PATHINFO_FILENAME);
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        cleanup_upload_files($dir, $stem);
    } else {
        $fullPath = $dir . DIRECTORY_SEPARATOR . $filename;
        if (is_file($fullPath)) @unlink($fullPath);
    }
    try {
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            db_query('DELETE FROM media WHERE filename LIKE ?', [$stem . '.%']);
        } else {
            db_query('DELETE FROM media WHERE filename = ?', [$filename]);
        }
    } catch (Throwable $ex) {
        error_log('Media delete failed: ' . $ex->getMessage());
    }
}

/** Pagination helper. */
function paginate(int $total, int $per = 10): array
{
    $pages = max(1, (int) ceil($total / $per));
    $page  = max(1, (int) ($_GET['p'] ?? 1));
    $page  = min($page, $pages);
    return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $per, 'per' => $per, 'total' => $total];
}

function pagination_links(array $p, array $query = []): string
{
    if ($p['pages'] <= 1) {
        return '';
    }
    $html = '<nav class="pagination" aria-label="ترقيم الصفحات">';
    for ($i = 1; $i <= $p['pages']; $i++) {
        $query['p'] = $i;
        $href = '?' . http_build_query($query);
        $cls  = $i === $p['page'] ? 'active' : '';
        $html .= '<a class="' . $cls . '" href="' . e($href) . '">' . $i . '</a>';
    }
    return $html . '</nav>';
}

/** Slug generator (ASCII-friendly; Arabic becomes page-{time}). */
function make_slug(string $text): string
{
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');
    if ($slug === '') {
        $slug = 'page-' . time();
    }
    return substr($slug, 0, 120);
}

/** Split newline separated feature list. */
function split_lines(?string $text): array
{
    if (!$text) {
        return [];
    }
    $lines = preg_split('/\r\n|\r|\n/', $text);
    return array_values(array_filter(array_map('trim', $lines), fn ($l) => $l !== ''));
}

/** Page row by slug (cached). */
function page_by_slug(string $slug): ?array
{
    static $cache = [];
    if (!array_key_exists($slug, $cache)) {
        $cache[$slug] = db_fetch('SELECT * FROM pages WHERE slug = ?', [$slug]);
    }
    return $cache[$slug];
}

/** Meta data for the public head. */
function page_meta(string $slug): array
{
    $page = page_by_slug($slug);
    return [
        'title_ar' => $page['meta_title_ar'] ?? setting('seo_title_ar'),
        'title_en' => $page['meta_title_en'] ?? setting('seo_title_en'),
        'desc_ar'  => $page['meta_desc_ar'] ?? setting('seo_desc_ar'),
        'desc_en'  => $page['meta_desc_en'] ?? setting('seo_desc_en'),
        'canonical'=> $page['canonical'] ?? '',
    ];
}

/** Does the given nav URL match the current script? */
function nav_active(string $url): bool
{
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $path = trim((string) (parse_url($url, PHP_URL_PATH) ?: ''), '/');
    if ($current === 'project.php' && in_array(basename($path), ['portfolio.php', 'portfolio.html', 'portfolio'], true)) {
        return true;
    }
    if (in_array($current, ['blog.php', 'blog-post.php'], true)) {
        return $path === 'blog' || $path === 'blog.php' || str_starts_with($path, 'blog/');
    }
    return basename($path) === $current;
}

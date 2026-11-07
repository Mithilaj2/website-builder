<?php
declare(strict_types=1);

namespace App\Core;

/** Rich-text sanitising with HTML Purifier (allow-list of formatting tags only). */
final class Html
{
    private static ?\HTMLPurifier $purifier = null;

    public static function purify(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }
        if (self::$purifier === null) {
            $config = \HTMLPurifier_Config::createDefault();
            $cache = STORAGE_PATH . '/temporary/htmlpurifier';
            if (!is_dir($cache)) {
                @mkdir($cache, 0755, true);
            }
            $config->set('Cache.SerializerPath', is_writable($cache) ? $cache : null);
            $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,s,a[href|title|target|rel],ul,ol,li,h2,h3,h4,blockquote,hr,table,thead,tbody,tr,th,td,span');
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
            $config->set('Attr.AllowedFrameTargets', ['_blank']);
            $config->set('HTML.TargetNoopener', true);
            $config->set('AutoFormat.RemoveEmpty', true);
            self::$purifier = new \HTMLPurifier($config);
        }
        return self::$purifier->purify($html);
    }
}

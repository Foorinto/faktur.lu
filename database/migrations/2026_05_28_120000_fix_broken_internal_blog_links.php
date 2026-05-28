<?php

use App\Models\BlogPost;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

/**
 * Fix broken internal blog links across all locales.
 *
 * Two root causes detected:
 *  - FR: "Articles connexes" links used accented slugs
 *    (e.g. /fr/blog/note-de-crédit-...) while real slugs are unaccented.
 *  - PT (and potentially others): related-article links pointed at the FR
 *    slug (e.g. /pt/blog/note-de-credit-...) instead of the PT slug.
 *
 * Resolution strategy per broken link (slug not present in the post's locale):
 *  1. ASCII-fold the slug; if it matches a slug in the same locale, use it.
 *  2. Otherwise treat the (folded) slug as a translation_key and resolve the
 *     correct slug for the post's locale.
 *
 * Idempotent: valid links are left untouched, so re-running is a no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        $all = BlogPost::all(['locale', 'slug', 'translation_key']);

        $slugSet = [];          // "locale|slug" => true
        $slugByKeyLocale = [];   // "locale|translation_key" => slug
        $validKeys = [];         // translation_key => true

        foreach ($all as $a) {
            $slugSet[$a->locale . '|' . $a->slug] = true;
            if ($a->translation_key) {
                $slugByKeyLocale[$a->locale . '|' . $a->translation_key] = $a->slug;
                $validKeys[$a->translation_key] = true;
            }
        }

        foreach (BlogPost::all() as $p) {
            $loc = $p->locale;
            $changed = false;

            $content = preg_replace_callback(
                '~(href="(?:https?://[^"]+)?(?:/[a-z]{2})?/blog/)([^"]+)(")~u',
                function ($m) use ($loc, $slugSet, $slugByKeyLocale, $validKeys, &$changed) {
                    $slug = $m[2];

                    // Already valid in this locale → leave untouched.
                    if (isset($slugSet[$loc . '|' . $slug])) {
                        return $m[0];
                    }

                    $ascii = Str::ascii($slug);

                    // Case 1: ASCII-folded slug exists in this locale (FR accents).
                    if (isset($slugSet[$loc . '|' . $ascii])) {
                        $changed = true;

                        return $m[1] . $ascii . $m[3];
                    }

                    // Case 2: slug (or its ASCII form) is a translation_key →
                    // resolve to this locale's slug (PT pointing at FR slug).
                    $key = isset($validKeys[$slug]) ? $slug : (isset($validKeys[$ascii]) ? $ascii : null);
                    if ($key !== null && isset($slugByKeyLocale[$loc . '|' . $key])) {
                        $changed = true;

                        return $m[1] . $slugByKeyLocale[$loc . '|' . $key] . $m[3];
                    }

                    // Unresolved → leave as-is.
                    return $m[0];
                },
                $p->content
            );

            if ($changed && $content !== $p->content) {
                $p->update(['content' => $content]);
            }
        }
    }

    public function down(): void
    {
        // No-op: reverting would re-break the links.
    }
};
